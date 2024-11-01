<?php

/*
  Plugin Name: Stamps.com XML File Export Import (BASIC)
  Plugin URI: hhttps://wordpress.org/plugins/xml-file-export-import-for-stampscom-and-woocommerce/
  Description: Import and Export Order detail including line items, From and To your WooCommerce Store as Stamps.com XML.
  Author: WebToffee
  Author URI: http://www.webtoffee.com/
  Version: 1.2.7
  WC tested up to: 5.6
  Text Domain: xml-file-export-import-for-stampscom-and-woocommerce
  License: GPLv3
  License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH') || !is_admin()) {
    return;
}

define("WF_ORDER_IMP_EXP_STAMPS_XML_ID", "wf_order_imp_exp_stamps_xml");
define("WF_WOOCOMMERCE_ORDER_IM_EX_STAMPS_XML", "wf_woocommerce_order_im_ex_stamps_xml");
define( 'WF_ORDER_IMP_EXP_STAMPS_XML', '1.2.7' );

/**
 * Check if WooCommerce is active
 */
if ( ! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) && !array_key_exists( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_site_option( 'active_sitewide_plugins', array() ) ) ) ) { // deactive if woocommerce in not active
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    deactivate_plugins( plugin_basename(__FILE__) );
}
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    if (!class_exists('WF_Order_Import_Export_Stamps_XML')) :

        /**
         * Main XML Import class
         */
        class WF_Order_Import_Export_Stamps_XML {

            /**
             * Constructor
             */
            public function __construct() {
                define('WF_OrderImpExpStampsXML_FILE', __FILE__);

                add_filter('woocommerce_screen_ids', array($this, 'woocommerce_screen_ids'));
                add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'wf_plugin_action_links'));
                add_action('init', array($this, 'load_plugin_textdomain'));
                add_action('init', array($this, 'catch_export_request'), 20);
                add_action('admin_init', array($this, 'register_importers'));
                
                add_filter('admin_footer_text', array($this, 'WT_admin_footer_text'), 100);
                add_action('wp_ajax_oxies_wt_review_plugin', array($this, "review_plugin"));
                
                
                if (!get_option('OSXEIPF_Webtoffee_storefrog_admin_notices_dismissed')) {
                    add_action('admin_notices', array($this,'webtoffee_storefrog_admin_notices'));
                    add_action('wp_ajax_OSXEIPF_webtoffee_storefrog_notice_dismiss', array($this,'webtoffee_storefrog_notice_dismiss'));
                }

                include_once( 'includes/class-wf-orderimpexpstampsxml-system-status-tools.php' );
                include_once( 'includes/class-wf-orderimpexpstampsxml-admin-screen.php' );
                include_once( 'includes/importer/class-wf-orderimpexpstampsxml-importer.php' );

                if (defined('DOING_AJAX')) {
                    include_once( 'includes/class-wf-orderimpexpstampsxml-ajax-handler.php' );
                }
            }

            public function wf_plugin_action_links($links) {
                $plugin_links = array(
                    '<a href="' . admin_url('admin.php?page=wf_woocommerce_order_im_ex_stamps_xml') . '">' . __('Import Export', 'xml-file-export-import-for-stampscom-and-woocommerce') . '</a>',
                    '<a href="https://www.webtoffee.com/product/order-import-export-plugin-for-woocommerce/?utm_source=free_plugin_listing&utm_medium=stamp_order_xml_imp_exp_basic&utm_campaign=Order_Import_Export&utm_content='.WF_ORDER_IMP_EXP_STAMPS_XML.'" target="_blank" style="color:#3db634;">' . __( 'Premium Upgrade', 'xml-file-export-import-for-stampscom-and-woocommerce' ) . '</a>',
                    '<a target="_blank" href="https://wordpress.org/support/plugin/xml-file-export-import-for-stampscom-and-woocommerce/">' . __('Support', 'xml-file-export-import-for-stampscom-and-woocommerce') . '</a>',
                    '<a target="_blank" href="https://wordpress.org/support/plugin/xml-file-export-import-for-stampscom-and-woocommerce/reviews/">' . __('Review', 'xml-file-export-import-for-stampscom-and-woocommerce') . '</a>',
                );
                return array_merge($plugin_links, $links);
            }

            /**
             * Add screen ID
             */
            public function woocommerce_screen_ids($ids) {
                $ids[] = 'admin'; // For import screen
                return $ids;
            }

            /**
             * Handle localisation
             */
            public function load_plugin_textdomain() {
                load_plugin_textdomain('xml-file-export-import-for-stampscom-and-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/lang/');
            }

            /**
             * Catches an export request and exports the data. This class is only loaded in admin.
             */
            public function catch_export_request() {
                if (!empty($_GET['action']) && !empty($_GET['page']) && $_GET['page'] == 'wf_woocommerce_order_im_ex_stamps_xml') {
                    switch ($_GET['action']) {
                        case "export" :
                            $user_ok = self::hf_user_permission();
                            if ($user_ok) {
                            include_once( 'includes/exporter/class-wf-orderimpexpstampsxmlbase-exporter.php' );
                            WF_OrderImpExpStampsXMLBase_Exporter::do_export('shop_order');
                            } else {
                                wp_redirect(wp_login_url());
                            }
                            break;
                    }
                }
            }

            /**
             * Register importers for use
             */
            public function register_importers() {
                register_importer('woocommerce_wf_order_stamps_xml', 'WooCommerce Order XML', __('Import <strong>Orders</strong> to your store via a xml file.', 'xml-file-export-import-for-stampscom-and-woocommerce'), 'WF_OrderImpExpStampsXML_Importer::order_importer');
            }
            
            public static function hf_user_permission() {
                // Check if user has rights to export
                $current_user = wp_get_current_user();
                $current_user->roles = apply_filters('hf_add_user_roles', $current_user->roles);
                $current_user->roles = array_unique($current_user->roles);
                $user_ok = false;
                $wf_roles = apply_filters('hf_user_permission_roles', array('administrator', 'shop_manager'));
                if ($current_user instanceof WP_User) {
                    $can_users = array_intersect($wf_roles, $current_user->roles);
                    if (!empty($can_users) || is_super_admin($current_user->ID)) {
                        $user_ok = true;
                    }
                }
                return $user_ok;
            }
            
            function webtoffee_storefrog_admin_notices() {

                if (apply_filters('webtoffee_storefrog_suppress_admin_notices', false) || !self::hf_user_permission()) {
                    return;
                }
                $screen = get_current_screen();

                $allowed_screen_ids = array('woocommerce_page_wf_woocommerce_order_im_ex_stamps_xml');
                if (in_array($screen->id, $allowed_screen_ids) || (isset($_GET['import']) && $_GET['import'] == 'woocommerce_wf_order_stamps_xml')) {

                    $notice = __('<h3>Save Time, Money & Hassle on Your WooCommerce Data Migration?</h3>', 'xml-file-export-import-for-stampscom-and-woocommerce');
                    $notice .= __('<h3>Use StoreFrog Migration Services.</h3>', 'xml-file-export-import-for-stampscom-and-woocommerce');

                    $content = '<style>.webtoffee-storefrog-nav-tab.updated {z-index:2;display: flex;align-items: center;margin: 18px 20px 10px 0;padding:23px;border-left-color: #2c85d7!important}.webtoffee-storefrog-nav-tab ul {margin: 0;}.webtoffee-storefrog-nav-tab h3 {margin-top: 0;margin-bottom: 9px;font-weight: 500;font-size: 16px;color: #2880d3;}.webtoffee-storefrog-nav-tab h3:last-child {margin-bottom: 0;}.webtoffee-storefrog-banner {flex-basis: 20%;padding: 0 15px;margin-left: auto;} .webtoffee-storefrog-banner a:focus{box-shadow: none;}</style>';
                    $content .= '<div class="updated woocommerce-message webtoffee-storefrog-nav-tab notice is-dismissible"><ul>' . $notice . '</ul><div class="webtoffee-storefrog-banner"><a href="http://www.storefrog.com/" target="_blank"> <img src="' . plugins_url(basename(plugin_dir_path(WF_OrderImpExpStampsXML_FILE))) . '/images/storefrog.png"/></a></div><div style="position: absolute;top: 0;right: 1px;z-index: 10000;" ><button type="button" id="webtoffee-storefrog-notice-dismiss" class="notice-dismiss"><span class="screen-reader-text">' . __('Dismiss this notice.', 'xml-file-export-import-for-stampscom-and-woocommerce') . '</span></button></div></div>';
                    echo $content;


                    wc_enqueue_js("jQuery( '#webtoffee-storefrog-notice-dismiss' ).click( function() {
                                        jQuery.post( '" . admin_url("admin-ajax.php") . "', { action: 'OSXEIPF_webtoffee_storefrog_notice_dismiss' } );
                                        jQuery('.webtoffee-storefrog-nav-tab').fadeOut();
                                    });
                                ");
                }
            }

            function webtoffee_storefrog_notice_dismiss() {

                if (!self::hf_user_permission()) {
                    wp_die(-1);
                }
                update_option('OSXEIPF_Webtoffee_storefrog_admin_notices_dismissed', 1);
                wp_die();
            }  
            
            
            public function WT_admin_footer_text($footer_text) {
                 if (!self::hf_user_permission()) {
                    return $footer_text;
                }
                $screen = get_current_screen();
                $allowed_screen_ids = array('woocommerce_page_wf_woocommerce_order_im_ex_stamps_xml');
                if (in_array($screen->id, $allowed_screen_ids) || (isset($_GET['import']) && $_GET['import'] == 'woocommerce_wf_order_stamps_xml')) {
                    if (!get_option('oxies_wt_plugin_reviewed')) {
                        $footer_text = sprintf( 
                                __('If you like the plugin please leave us a %1$s review.', 'xml-file-export-import-for-stampscom-and-woocommerce'), '<a href="https://wordpress.org/support/plugin/xml-file-export-import-for-stampscom-and-woocommerce/reviews?rate=5#new-post" target="_blank" class="wt-review-link" data-rated="' . esc_attr__('Thanks :)', 'xml-file-export-import-for-stampscom-and-woocommerce') . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
                        );
                        wc_enqueue_js(
                                "jQuery( 'a.wt-review-link' ).click( function() {
                                                   jQuery.post( '" . WC()->ajax_url() . "', { action: 'oxies_wt_review_plugin' } );
                                                   jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
                                           });"
                        );
                    } else {
                        $footer_text = __('Thank you for your review.', 'xml-file-export-import-for-stampscom-and-woocommerce');
                    }
                }

                return '<i>'.$footer_text.'</i>';
            }
            
            
            public function review_plugin(){
                if (!self::hf_user_permission()) {
                    wp_die(-1);
                }
                update_option('oxies_wt_plugin_reviewed', 1);
                wp_die();
                
            }                       


        }

        endif;

    new WF_Order_Import_Export_Stamps_XML();
    
}

add_filter('hf_order_stamps_xml_export_format', 'hf_order_xml_export_stamps_format', 10, 4);

function hf_order_xml_export_stamps_format($formated_orders, $raw_orders) {

    $order_details = array();
    foreach ($raw_orders as $order) {

        $order_data = array(
            'OrderDate' => $order['OrderDate'],
            'OrderID' => $order['OrderId'],
            'ShipMethod' => $order['ShippingMethod'],
            'MailClass' => 'first class',
            'Mailpiece' => 'package',
            'DeclaredValue' => $order['OrderTotal'],
            'Recipient' => array(
                'AddressFields' => array(
                    'FirstName' => $order['ShippingFirstName'],
                    'LastName' => $order['ShippingLastName'],
                    'Address1' => $order['ShippingAddress1'],
                    'Address2' => $order['ShippingAddress2'],
                    'Company' => $order['ShippingCompany'],
                    'City' => $order['ShippingCompany'],
                    'State' => $order['ShippingState'],
                    'ZIP' => $order['ShippingPostCode'],
                    'Country' => $order['ShippingCountry'],
                    'OrderedPhoneNumbers' => array(
                        'Number' => $order['BillingPhone']
                    ),
                    'OrderedEmailAddresses' => array(
                        'Address' => $order['BillingEmail']
                    )
                ),
            ),
            'WeightOz' => $order['OrderLineItems']['total_weight'],
            'RecipientEmailOptions' => array(
                'ShipmentNotification' => 'false'
            )
        );


        if ($order['StoreCountry'] !== $order['ShippingCountry']) {

            $order_data['CustomsInfo'] = array(
                'Contents' => array(
                    'Item' => array(
                        'Description' => 'HF' . $order['OrderId'],
                        'Quantity' => $order['OrderLineItems']['total_qty'],
                        'Value' => $order['OrderTotal'],
                        'WeightOz' => $order['OrderLineItems']['total_weight']
                    )
                ),
                'ContentsType' => 'other',
                'DeclaredValue' => $order['OrderTotal'],
                'UserAcknowledged' => TRUE
            );
        }

        if (sizeof($order['OrderLineItems']) >= 4) {
            unset($order['OrderLineItems']['total_weight']);
            unset($order['OrderLineItems']['total_qty']);
            unset($order['OrderLineItems']['weight_unit']);
            foreach ($order['OrderLineItems'] as $lineItems) {
                $order_data['OrderContents']['Item'][] = $lineItems;
            }
        }

        $order_details[] = $order_data;
    }
    $formated_orders = array('Print' => array('Item' => $order_details));
    return $formated_orders;
}

/*
 *  Displays update information for a plugin. 
 */
function wt_xml_file_export_import_for_stampscom_and_woocommerce_update_message( $data, $response )
{
    if(isset( $data['upgrade_notice']))
    {
        printf(
        '<div class="update-message wt-update-message">%s</div>',
           $data['upgrade_notice']
        );
    }
}
add_action( 'in_plugin_update_message-xml-file-export-import-for-stampscom-and-woocommerce/order-import-export-stamps-xml.php', 'wt_xml_file_export_import_for_stampscom_and_woocommerce_update_message', 10, 2 );