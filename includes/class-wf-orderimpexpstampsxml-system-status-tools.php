<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WF_OrderImpExpStampsXML_System_Status_Tools {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_debug_tools', array( $this, 'tools' ) );
	}

	/**
	 * Tools we add to WC
	 * @param  array $tools
	 * @return array
	 */
	public function tools( $tools ) {
            if(!isset($tools['delete_trashed_orders'])){
		$tools['delete_trashed_orders'] = array(
			'name'		=> __( 'Delete Trashed Orders','xml-file-export-import-for-stampscom-and-woocommerce'),
			'button'	=> __( 'Delete  Trashed Orders','xml-file-export-import-for-stampscom-and-woocommerce' ),
			'desc'		=> __( 'This tool will delete all  Trashed Orders.', 'xml-file-export-import-for-stampscom-and-woocommerce' ),
			'callback'  => array( $this, 'delete_trashed_orders' )
		);
            }
            if(!isset($tools['delete_all_orders'])){
		$tools['delete_all_orders'] = array(
			'name'		=> __( 'Delete Orders','xml-file-export-import-for-stampscom-and-woocommerce'),
			'button'	=> __( 'Delete ALL Orders','xml-file-export-import-for-stampscom-and-woocommerce' ),
			'desc'		=> __( 'This tool will delete all orders allowing you to start fresh.', 'xml-file-export-import-for-stampscom-and-woocommerce' ),
			'callback'  => array( $this, 'delete_all_orders' )
		);
            }
		return $tools;
	}

	/**
	 * Delete Trashed Orders
	 */
	public function delete_trashed_orders() {
		global $wpdb;
		// Delete Trashed Orders
		$result  = absint( $wpdb->delete( $wpdb->posts, array( 'post_type' => 'shop_order' , 'post_status' => 'trash') ) );
                
                // Delete meta and term relationships with no post
		$wpdb->query( "DELETE pm
			FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id
			WHERE wp.ID IS NULL" );
                // Delete order items with no post
                $wpdb->query( "DELETE oi
                        FROM {$wpdb->prefix}woocommerce_order_items oi
                        LEFT JOIN {$wpdb->posts} wp ON wp.ID = oi.order_id
                        WHERE wp.ID IS NULL" );
                        
                // Delete order item meta with no post
                $wpdb->query( "DELETE om
                        FROM {$wpdb->prefix}woocommerce_order_itemmeta om
                        LEFT JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = om.order_item_id
                        WHERE oi.order_item_id IS NULL" );
                
		echo '<div class="updated"><p>' . sprintf( __( '%d Orders Deleted', 'xml-file-export-import-for-stampscom-and-woocommerce' ), ( $result ) ) . '</p></div>';
	}

	/**
	 * Delete all orders
	 */
	public function delete_all_orders() {
		global $wpdb;

		// Delete Orders
		$result = absint( $wpdb->delete( $wpdb->posts, array( 'post_type' => 'shop_order' ) ) );

		// Delete meta and term relationships with no post
		$wpdb->query( "DELETE pm
			FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id
			WHERE wp.ID IS NULL" );
                // Delete order items with no post
                $wpdb->query( "DELETE oi
                        FROM {$wpdb->prefix}woocommerce_order_items oi
                        LEFT JOIN {$wpdb->posts} wp ON wp.ID = oi.order_id
                        WHERE wp.ID IS NULL" );
                        
                // Delete order item meta with no post
                $wpdb->query( "DELETE om
                        FROM {$wpdb->prefix}woocommerce_order_itemmeta om
                        LEFT JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_item_id = om.order_item_id
                        WHERE oi.order_item_id IS NULL" );
		echo '<div class="updated"><p>' . sprintf( __( '%d Orders Deleted', 'xml-file-export-import-for-stampscom-and-woocommerce' ), $result ) . '</p></div>';
	}	
}

new WF_OrderImpExpStampsXML_System_Status_Tools();