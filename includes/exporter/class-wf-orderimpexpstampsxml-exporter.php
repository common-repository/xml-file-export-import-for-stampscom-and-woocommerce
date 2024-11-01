<?php

if (!defined('ABSPATH')) {
    exit;
}

class WF_OrderImpExpStampsXML_Exporter extends XMLWriter {

    private $ids;

    public function __construct($ids) {

        $this->ids = $ids;
        $this->openMemory();
        $this->setIndent(TRUE);
        $xml_version = '1.0';
        $xml_encoding = 'UTF-8';
        $xml_standalone = 'no';
        $this->startDocument($xml_version, $xml_encoding, $xml_standalone);
    }

    public function do_xml_export($filename, $xml) {

        global $wpdb;
        
            $charset = get_option('blog_charset');
            header(apply_filters('hf_order_import_export_xml_content_type', "Content-Type: application/xml; charset={$charset}"));
            header(sprintf('Content-Disposition: attachment; filename="%s"', $filename. ".xml"));
            header('Pragma: no-cache');
            header('Expires: 0');
            if (version_compare(PHP_VERSION, '5.6', '<')) {
                iconv_set_encoding('output_encoding', $charset);
            } else {
                ini_set('default_charset', 'UTF-8');
            }

            echo $xml;
            exit;
        
    }

    public function get_order_details_xml($data_array, $xmlns = NULL) {
        $xmlnsurl = $xmlns;
        $keys = array_keys($data_array);
        $root_tag = reset($keys);

        WF_OrderImpExpStampsXML_Exporter::array_to_xml($this, $root_tag, $data_array[$root_tag], $xmlnsurl);
        return $this->output_xml();
    }

    public static function array_to_xml($xml_writer, $element_key, $element_value = array(), $xmlnsurl = NULL) {

        if (!empty($xmlnsurl)) {
            $my_root_tag = $element_key;
            $xml_writer->startElementNS(null, $element_key, $xmlnsurl);
        } else {
            $my_root_tag = '';
        }

        if (is_array($element_value)) {
            //handle attributes
            if ('@attributes' === $element_key) {
                foreach ($element_value as $attribute_key => $attribute_value) {

                    $xml_writer->startAttribute($attribute_key);
                    $xml_writer->text($attribute_value);
                    $xml_writer->endAttribute();
                }
                return;
            }

            //handle order elements
            if (is_numeric(key($element_value))) {

                foreach ($element_value as $child_element_key => $child_element_value) {

                    if ($element_key !== $my_root_tag)
                        $xml_writer->startElement($element_key);


                    foreach ($child_element_value as $sibling_element_key => $sibling_element_value) {
                        self::array_to_xml($xml_writer, $sibling_element_key, $sibling_element_value);
                    }

                    $xml_writer->endElement();
                }
            } else {

                if ($element_key !== $my_root_tag)
                    $xml_writer->startElement($element_key);

                foreach ($element_value as $child_element_key => $child_element_value) {
                    self::array_to_xml($xml_writer, $child_element_key, $child_element_value);
                }

                $xml_writer->endElement();
            }
        } else {

            //handle single elements
            if ('@value' == $element_key) {

                $xml_writer->text($element_value);
            } else {

                //wrap element in CDATA tag if it contain illegal characters
                if (false !== strpos($element_value, '<') || false !== strpos($element_value, '>')) {

                    $xml_writer->startElement($element_key);
                    $xml_writer->writeCdata($element_value);
                    $xml_writer->endElement();
                } else {

                    $xml_writer->writeElement($element_key, $element_value);
                }
            }

            return;
        }
    }

    private function output_xml() {
        $this->endDocument();
        return $this->outputMemory();
    }

    public function get_orders($order_ids) {


        $order_data = array();
        $wc_countries = new WC_Countries();
        $base_country = $wc_countries->get_base_country();
        foreach ($order_ids as $order_id) {

            $order = wc_get_order($order_id);

            $shipping_methods = $shipping_methods_ids = array();

            foreach ($order->get_shipping_methods() as $method) {

                $shipping_methods[] = $method['name'];
                $shipping_methods_ids[] = $method['method_id'];
            }

            $fee_total = 0;
            foreach ($order->get_fees() as $fee_id => $fee) {
                $fee_total += $fee['line_total'];
            }
            if (version_compare(WC_VERSION, '2.7', '<')) {
                $order_data[] = apply_filters('hf_order_stamps_xml_export_export_list_format', array(
                    'OrderId' => $order->id,
                    'OrderNumber' => $order->get_order_number(),
                    'OrderDate' => $order->order_date,
                    'OrderStatus' => $order->get_status(),
                    'BillingFirstName' => $order->billing_first_name,
                    'BillingLastName' => $order->billing_last_name,
                    'BillingFullName' => $order->billing_first_name . ' ' . $order->billing_last_name,
                    'BillingCompany' => $order->billing_company,
                    'BillingAddress1' => $order->billing_address_1,
                    'BillingAddress2' => $order->billing_address_2,
                    'BillingCity' => $order->billing_city,
                    'BillingState' => $order->billing_state,
                    'BillingPostCode' => $order->billing_postcode,
                    'BillingCountry' => $order->billing_country,
                    'BillingPhone' => $order->billing_phone,
                    'BillingEmail' => $order->billing_email,
                    'ShippingFirstName' => $order->shipping_first_name,
                    'ShippingLastName' => $order->shipping_last_name,
                    'ShippingFullName' => $order->shipping_first_name . ' ' . $order->shipping_last_name,
                    'ShippingCompany' => $order->shipping_company,
                    'ShippingAddress1' => $order->shipping_address_1,
                    'ShippingAddress2' => $order->shipping_address_2,
                    'ShippingCity' => $order->shipping_city,
                    'ShippingState' => $order->shipping_state,
                    'ShippingPostCode' => $order->shipping_postcode,
                    'ShippingCountry' => $order->shipping_country,
                    'ShippingMethodId' => implode(',', $shipping_methods_ids),
                    'ShippingMethod' => implode(', ', $shipping_methods),
                    'PaymentMethodId' => $order->payment_method,
                    'PaymentMethod' => $order->payment_method_title,
                    'OrderDiscountTotal' => (defined('WC_VERSION') && (WC_VERSION >= 2.3)) ? $order->get_total_discount() : $order->get_order_discount(),
                    'CartDiscountTotal' => (defined('WC_VERSION') && (WC_VERSION >= 2.3)) ? $order->get_total_discount() : $order->get_cart_discount(),
                    'DiscountTotal' => $order->get_total_discount(),
                    'ShippingTotal' => $order->get_total_shipping(),
                    'ShippingTaxTotal' => $order->get_shipping_tax(),
                    'OrderTotal' => $order->get_total(),
                    'FeeTotal' => $fee_total,
                    'TaxTotal' => $order->get_total_tax(),
                    'CompletedDate' => $order->completed_date,
                    'CustomerNote' => $order->customer_note,
                    'CustomerId' => $order->get_user_id(),
                    'OrderLineItems' => $this->get_line_items($order),
                    'StoreCountry' => $base_country
                        ), $order);
            } else {
                $order_data[] = apply_filters('hf_order_stamps_xml_export_export_list_format', array(
                    'OrderId' => $order->get_id(),
                    'OrderNumber' => $order->get_order_number(),
                    'OrderDate' => date('Y-m-d H:i:s', strtotime(get_post($order->get_id())->post_date)),
                    'OrderStatus' => $order->get_status(),
                    'BillingFirstName' => $order->get_billing_first_name(),
                    'BillingLastName' => $order->get_billing_last_name(),
                    'BillingFullName' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    'BillingCompany' => $order->get_billing_company(),
                    'BillingAddress1' => $order->get_billing_address_1(),
                    'BillingAddress2' => $order->get_billing_address_2(),
                    'BillingCity' => $order->get_billing_city(),
                    'BillingState' => $order->get_billing_state(),
                    'BillingPostCode' => $order->get_billing_postcode(),
                    'BillingCountry' => $order->get_billing_country(),
                    'BillingPhone' => $order->get_billing_phone(),
                    'BillingEmail' => $order->get_billing_email(),
                    'ShippingFirstName' => $order->get_shipping_first_name(),
                    'ShippingLastName' => $order->get_shipping_last_name(),
                    'ShippingFullName' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                    'ShippingCompany' => $order->get_shipping_company(),
                    'ShippingAddress1' => $order->get_shipping_address_1(),
                    'ShippingAddress2' => $order->get_shipping_address_2(),
                    'ShippingCity' => $order->get_shipping_city(),
                    'ShippingState' => $order->get_shipping_state(),
                    'ShippingPostCode' => $order->get_shipping_postcode(),
                    'ShippingCountry' => $order->get_shipping_country(),
                    'ShippingMethodId' => implode(',', $shipping_methods_ids),
                    'ShippingMethod' => implode(', ', $shipping_methods),
                    'PaymentMethodId' => $order->get_payment_method(),
                    'PaymentMethod' => $order->get_payment_method_title(),
                    'OrderDiscountTotal' => (defined('WC_VERSION') && (WC_VERSION >= 2.3)) ? $order->get_total_discount() : $order->get_order_discount(),
                    'CartDiscountTotal' => (defined('WC_VERSION') && (WC_VERSION >= 2.3)) ? $order->get_total_discount() : $order->get_cart_discount(),
                    'DiscountTotal' => $order->get_total_discount(),
                    'ShippingTotal' => $order->get_total_shipping(),
                    'ShippingTaxTotal' => $order->get_shipping_tax(),
                    'OrderTotal' => $order->get_total(),
                    'FeeTotal' => $fee_total,
                    'TaxTotal' => $order->get_total_tax(),
                    'CompletedDate' => $order->get_date_completed(),
                    'CustomerNote' => $order->get_customer_note(),
                    'CustomerId' => $order->get_user_id(),
                    'OrderLineItems' => $this->get_line_items($order),
                    'StoreCountry' => $base_country
                        ), $order);
            }
        }

        return $order_data;
    }

    private function get_line_items($order) {

        $items = array();

        $weight = 0; //for third part XML
        $qty = 0; //for third part XML
        $weight_unit = get_option('woocommerce_weight_unit'); //for third part XML

        foreach ($order->get_items() as $item_id => $item) {

            @$item['id'] = $item_id;

            //discard taxes and fees
            if (isset($item['type']) && 'line_item' !== $item['type']) {
                continue;
            }
            //$product = $order->get_product_from_item($item);
            $product = (WC()->version < '4.4.0') ? $order->get_product_from_item($item) : $item->get_product();   //  get_product_from_item() deprecated since version 4.4.0 
/*
            $item_meta = new WC_Order_Item_Meta((defined('WC_VERSION') && (WC_VERSION >= 2.4)) ? $item : $item['item_meta'] );
            $item_meta = $item_meta->display(true, true);

            //remove html
            $item_meta = preg_replace('/<[^>]*>/', ' ', $item_meta);
            //remove control chars
            $item_meta = str_replace(array("\r", "\n", "\t"), '', $item_meta);
            $item_meta = strip_tags($item_meta);
*/

            if (!empty($product) && !$product->is_virtual()) {
                $weight += ($product->get_weight()!=''?$product->get_weight():0) * $item['qty'];
            }

            $qty+=$item['qty'];

            $item_format = array();
            //$item_format['SKU'] = $product ? $product->get_sku() : '';
            $item_format['ExternalID'] = $product ? ((WC()->version < '2.7.0') ? $product->id : $product->get_id()) : '';
            $item_format['Name'] = html_entity_decode($product ? $product->get_title() : $item['name'], ENT_NOQUOTES, 'UTF-8');
            $item_format['Price'] = $order->get_item_total($item);
            $item_format['Quantity'] = $item['qty'];
            $item_format['Total'] = $item['line_total'];

            if ('yes' === get_option('woocommerce_calc_taxes') && 'yes' === get_option('woocommerce_prices_include_tax')) {
                $item_format['PriceInclTax'] = $order->get_item_total($item, true);
                $item_format['LineTotalInclTax'] = $item['line_total'] + $item['line_tax'];
            }

//            $item_format['Meta'] = $item_meta;

            $items[] = apply_filters('hf_order_stamps_xml_export_line_item_format', $item_format, $order, $item);
        }
        $items['total_weight'] = $weight;
        $items['total_qty'] = $qty;
        $items['weight_unit'] = $weight_unit;
        return $items;
    }

}
