<div class="orderimpexp-main-box-stamps">
    <div class="orderimpexp-view-stamps" style="width: 68%">
        <div class="tool-box bg-white-stamps p-20p-stamps">
            <p><?php _e('You can import orders (XML format exported from Stamps.com) in to the shop using below method.', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></p>
            <?php if (!empty($upload_dir['error'])) : ?>
                <div class="error"><p><?php _e('Before you can upload your import file, you will need to fix the following error:', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></p>
                    <p><strong><?php echo $upload_dir['error']; ?></strong></p></div>
            <?php else : ?>
                <form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr(wp_nonce_url($action, 'import-upload')); ?>">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th>
                                    <label for="upload"><?php _e('Select a file from your computer', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></label>
                                </th>
                                <td>
                                    <input type="file" id="upload" name="import" size="25" />
                                    <input type="hidden" name="action" value="save" />
                                    <input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
                                    <small><?php printf(__('Maximum size: %s'), $size); ?></small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Upload file and import'); ?>" />
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php include(plugin_dir_path(WF_OrderImpExpStampsXML_FILE) . 'includes/views/market.php'); ?>
       <div class="clearfix"></div>
</div>