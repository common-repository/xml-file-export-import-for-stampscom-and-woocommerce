<?php

if (!defined('ABSPATH')) {
    exit;
}

class WF_OrderImpExpStampsXMLBase_Exporter {

    /**
     * Order Exporter Tool
     */
    public static function do_export($post_type = 'shop_order') {
        global $wpdb;

        $export_limit = 999999999;
        $export_offset =  0;
        $export_format = 'stamps_xml';
        $export_order_statuses = 'any';

        // Headers

            $query_args = array(
                'fields' => 'ids',
                'post_type' => 'shop_order',
                'post_status' => $export_order_statuses,
                'posts_per_page' => $export_limit,
                'offset' => $export_offset,
            );

            $query = new WP_Query($query_args);
            $order_ids = $query->posts;

        include_once( 'class-wf-orderimpexpstampsxml-exporter.php' );
        $filename = 'order_';
        $export = new WF_OrderImpExpStampsXML_Exporter($order_ids);
        $order_details = $export->get_orders($order_ids);
        $data_array = array('Orders' => array('Order' => $order_details));
        $xmlns = '';

        switch ($export_format) {
            case 'stamps_xml' :
                $data_array = apply_filters('hf_order_stamps_xml_export_format', $data_array, $order_details);
                $filename.='stamps_xml';
                $xmlns = 'http://stamps.com/xml/namespace/2009/8/Client/BatchProcessingV1';
                break;
        }
        $export->do_xml_export($filename, $export->get_order_details_xml($data_array, $xmlns));
        die();
    }

}
