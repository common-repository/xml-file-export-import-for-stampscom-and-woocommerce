<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="market-box-stamps table-box-main">
    <div class="orderimpexp-review-widget-stamps">
        <?php
        echo sprintf(__('<div class=""><p><i>If you like the plugin please leave us a %1$s review!</i><p></div>', 'xml-file-export-import-for-stampscom-and-woocommerce'), '<a href="https://wordpress.org/support/plugin/xml-file-export-import-for-stampscom-and-woocommerce/reviews/?rate=5#new-post" target="_blank" class="xa-orderimpexp-rating-link" data-reviewed="' . esc_attr__('Thanks for the review.', 'xml-file-export-import-for-stampscom-and-woocommerce') . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>');
        ?>
    </div>
    <div class="orderimpexp-premium-features-stamps">        
        <ul style="font-weight: bold; color:#666; list-style: none; background:#f8f8f8; padding:20px; margin:20px 15px; font-size: 15px; line-height: 26px;">
            <li><?php echo __('30 Day Money Back Guarantee','xml-file-export-import-for-stampscom-and-woocommerce'); ?></li>
            <li><?php echo __('Fast and Superior Support','xml-file-export-import-for-stampscom-and-woocommerce'); ?></li>
            <li>
                <a href="https://www.webtoffee.com/product/order-import-export-plugin-for-woocommerce/?utm_source=free_plugin_sidebar&utm_medium=stamp_order_xml_imp_exp_basic&utm_campaign=Order_Import_Export&utm_content=<?php echo WF_ORDER_IMP_EXP_STAMPS_XML; ?>" target="_blank" class="button button-primary button-go-pro"><?php _e('Upgrade to Premium', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></a>
            </li>
        </ul>        
        <span>
            <ul class="ticked-list">
                <li><?php _e('Import and Export Subscriptions.', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></li>
                <li><?php _e('Filter options for Export using Order Status, Date, Coupon Type etc.', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></li>
                <li><?php _e('Manipulate/evaluate data prior to import.', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></li>
                <li><?php _e('Map and transform custom columns to WC during import.', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?> </li>
                <li><?php _e('Choice to update or skip existing orders upon import.', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></li>
                <li><?php _e('Import and Export via FTP.', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></li>
                <li><?php _e('Schedule automatic import and export.', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></li>
                <li><?php _e('XML Export/Import supports Stamps.com desktop application, UPS WorldShip, Endicia and FedEx.', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></li>
                <li><?php _e('Third party plugin customization support.', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></li>
            </ul>
        </span>
        <div style="padding-bottom: 20px">            
            <center> 
                <a href="https://www.webtoffee.com/setting-up-order-import-export-plugin-for-woocommerce/" target="_blank" class="button button-doc-demo"><?php _e('Documentation', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></a>
            </center>
            <center style="margin-top: 10px">
                <a href="<?php echo plugins_url('Sample_Order.csv', WF_OrderImpExpStampsXML_FILE); ?>" class=""><?php _e('Sample Order CSV', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></a> &MediumSpace;/ &MediumSpace;
                <a href="<?php echo plugins_url('Sample_Coupon.csv', WF_OrderImpExpStampsXML_FILE); ?>" class=""><?php _e('Sample Coupon CSV', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></a>
            </center>
        </div>
        
    </div>
    
</div>
