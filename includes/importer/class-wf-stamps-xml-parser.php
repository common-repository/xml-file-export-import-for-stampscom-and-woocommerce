<?php
/**
 * WooCommerce XML Importer class for managing parsing of XML files.
 */
class WF_XML_Parser {

    var $row;
    var $post_type;
    var $posts = array();
    var $processed_posts = array();
    var $file_url_import_enabled = true;
    var $log;
    var $merged = 0;
    var $skipped = 0;
    var $imported = 0;
    var $errored = 0;
    var $id;
    var $file_url;
    var $delimiter;

    /**
     * Constructor
     */
    public function __construct($post_type = 'shop_order') {
        $this->post_type = $post_type;
        
    }

    /**
     * Parse the data
     * @param  string  $file      [description]
     * @return array
     */
    public function parse_data($file) {
        // Set locale
        $enc = mb_detect_encoding($file, 'UTF-8, ISO-8859-1', true);
        if ($enc)
            setlocale(LC_ALL, 'en_US.' . $enc);
        @ini_set('auto_detect_line_endings', true);

        $xml = simplexml_load_file($file);
        $root_tag = $xml->getName();
        $xml_array = array();
        $xml_array[$root_tag] = $xml;

        return array($xml_array);
    }

    /**
     * Parse orders
     * @param  array  $item
     * @param  integer $merge_empty_cells
     * @return array
     */
    public function parse_orders($item) {

        global $WF_Stamps_XML_Order_Import, $wpdb;
        $postmeta = $default_stamps_order_meta = $order = $order_meta_data = array();
        
        $default_stamps_order_meta['TrackingNumber'] = (string)$item->TrackingNumber;
        $default_stamps_order_meta['ActualMailingDate'] = (string)$item->ActualMailingDate;
        $default_stamps_order_meta['DesiredMailingDate'] = (string)$item->DesiredMailingDate;
        $default_stamps_order_meta['HidePostageAmount'] = (string)$item->HidePostageAmount;
        $default_stamps_order_meta['MailClass'] = (string)$item->MailClass;
        $default_stamps_order_meta['Mailpiece'] = (string)$item->Mailpiece;
        $default_stamps_order_meta['ShipMethod'] = (string)$item->ShipMethod;
        $default_stamps_order_meta['PostageCost MailClass'] = (string)$item->PostageCost->MailClass[0];
        $default_stamps_order_meta['PostageCost Total'] = (string)$item->PostageCost->Total[0];
        $default_stamps_order_meta['TrackingService'] = (string)$item->Services->TrackingService[0];
        $default_stamps_order_meta['TrackingNumber'] = (string)$item->TrackingNumber;
        $default_stamps_order_meta['WeightOz'] = (string)$item->WeightOz;
        
        
        //apply filter if any alteration for meta names and values or filed to be done
        $default_stamps_order_meta = apply_filters('hf_alter_stamps_order_meta',$default_stamps_order_meta , $item );
        //echo '<pre>';print_r($default_stamps_order_meta);echo '</pre>';exit;
        $order['order_number'] = (string)$item->OrderID;
        if ($order['order_number']) {
           $order['postmeta'] =  $default_stamps_order_meta;
           $results[] = $order;
        }
        // Result
        return array( $this->post_type => $results );
    }

}
