<div class="orderimpexp-main-box-stamps">
    <div class="orderimpexp-view-stamps" style="width:68%;">
        <div class="tool-box bg-white-stamps p-20p-stamps" style="margin-bottom: 20px;">
            <h3 class="title"><?php _e('Import Orders in XML Format:', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></h3>
            <p><?php _e('Import Orders in XML format from your computer', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></p>
            <p class="submit" style="padding-left: 10px;">
                <?php
                $import_url = admin_url('admin.php?import=woocommerce_wf_order_stamps_xml');
                ?>
                <a class="button button-primary" id="mylink" href="<?php echo $import_url; ?>"><?php _e('Update Orders', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></a>
                &nbsp;
                <br>
            </p>
        </div>
