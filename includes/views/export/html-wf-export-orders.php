<div class="tool-box bg-white-stamps p-20p-stamps">
    <?php
    $order_statuses = wc_get_order_statuses();
    ?>
    <h3 class="title"><?php _e('Export Orders in Stamps.com XML Format:', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></h3>
    <p><?php _e('Export and download your orders in Stamps.com XML format. This file can be used to import orders into your Stamps.com Application.', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></p>
    <form action="<?php echo admin_url('admin.php?page=wf_woocommerce_order_im_ex_stamps_xml&action=export'); ?>" method="post">
        <p class="submit" style="padding-left: 10px;"><input type="submit" class="button button-primary" value="<?php _e('Export Orders', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?>" /></p>
    </form>
</div>
</div>
        <?php include(dirname(__FILE__) . '/../market.php'); ?>
        <div class="clearfix"></div>
</div>