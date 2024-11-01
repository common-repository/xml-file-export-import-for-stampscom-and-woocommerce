<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WF_OrderImpExpStampsXML_AJAX_Handler {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_woocommerce_stamps_xml_order_import_request', array( $this, 'hf_stamps_xml_order_import_request' ) );
	}
	
	/**
	 * Ajax event for importing a CSV
	 */
	public function hf_stamps_xml_order_import_request() {            
		define( 'WP_LOAD_IMPORTERS', true );
                WF_OrderImpExpStampsXML_Importer::order_importer();
	}
}

new WF_OrderImpExpStampsXML_AJAX_Handler();