<?php
/**
 * WordPress Importer class for managing the import process of a XML file
 *
 * @package WordPress
 * @subpackage Importer
 */
if (!class_exists('WP_Importer'))
    return;

class WF_OrderImpExpStampsXML_Order_Import extends WP_Importer {

    var $id;
    var $file_url;
    var $profile;
    var $merge_empty_cells;
    var $processed_posts = array();
    var $merged = 0;
    var $skipped = 0;
    var $imported = 0;
    var $errored = 0;
    // Results
    var $import_results = array();

    /**
     * Constructor
     */
    public function __construct() {

        if (WC()->version < '2.7.0') {
            $this->log = new WC_Logger();
        } else {
            $this->log = wc_get_logger();
        }
        $this->import_page = 'woocommerce_wf_order_stamps_xml';
        $this->file_url_import_enabled = apply_filters('woocommerce_xml_order_file_url_import_enabled', true);
    }

    /**
     * Registered callback function for the WordPress Importer
     *
     * Manages the three separate stages of the CSV import process
     */
    public function dispatch() {
        global $woocommerce, $wpdb;

        if (!empty($_POST['delimiter'])) {
            $this->delimiter = stripslashes(trim($_POST['delimiter']));
        }

        if (!empty($_POST['profile'])) {
            $this->profile = stripslashes(trim($_POST['profile']));
        } else if (!empty($_GET['profile'])) {
            $this->profile = stripslashes(trim($_GET['profile']));
        }
        if (!$this->profile)
            $this->profile = '';

        if (!empty($_POST['merge_empty_cells']) || !empty($_GET['merge_empty_cells'])) {
            $this->merge_empty_cells = 1;
        } else {
            $this->merge_empty_cells = 0;
        }

        $step = empty($_GET['step']) ? 0 : absint($_GET['step']);

        switch ($step) {
            case 0 :
                $this->header();
                $this->greet();
                break;
            case 1 :
                $this->header();

                check_admin_referer('import-upload');

                if (!empty($_GET['file_url']))
                    $this->file_url = esc_url_raw($_GET['file_url']);
                if (!empty($_GET['file_id']))
                    $this->id = absint($_GET['file_id']);

                if (!empty($_GET['clearmapping']) || $this->handle_upload())
                    $this->import_options();
                else
                    _e('Error with handle_upload!', 'xml-file-export-import-for-stampscom-and-woocommerce');
                break;
            case 2 :
                $this->header();

                check_admin_referer('import-woocommerce');

                $this->id = absint($_POST['import_id']);

                if ($this->file_url_import_enabled)
                    $this->file_url = esc_url_raw($_POST['import_url']);
                if ($this->id)
                    $file = get_attached_file($this->id);
                else if ($this->file_url_import_enabled)
                    $file = ABSPATH . $this->file_url;

                $file = str_replace("\\", "/", $file);

                if ($file) {

                    $xml = simplexml_load_file($file);
                    $root_tag = $xml->getName();
                    $xml_array = array();
                    $xml_array[$root_tag] = $xml;
                    //echo '<pre>';print_r($xml_array);exit;
                    ?>
                    <table id="import-progress" class="widefat_importer widefat">
                        <thead>
                            <tr>
                                <th class="status">&nbsp;</th>
                                <th class="row"><?php _e('Row', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></th>
                                <th><?php _e('OrderID', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></th>
                                <th><?php _e('Processed', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></th>
                                <th class="reason"><?php _e('Status Msg', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="importer-loading">
                                <td colspan="5"></td>
                            </tr>
                        </tfoot>
                        <tbody></tbody>
                    </table>
                    <script type="text/javascript">
                    jQuery(document).ready(function($) {

                        if (! window.console) { window.console = function(){}; }

                        var processed_posts = [];
                        var i = 1;
                        var done_count = 0;
                        function import_rows() {

                            var data = {
                                action:     'woocommerce_stamps_xml_order_import_request',
                                file:       '<?php echo addslashes($file); ?>',
                                wt_nonce : '<?php echo wp_create_nonce( WF_ORDER_IMP_EXP_STAMPS_XML_ID )?>',
                            };
                            return $.ajax({
                                url:        '<?php echo add_query_arg(array('import_page' => $this->import_page, 'step' => '3', 'merge' => !empty($_GET['merge']) ? '1' : '0'), admin_url('admin-ajax.php')); ?>',
                                data:       data,
                                type:       'POST',
                                success:    function(response) {
                                    if (response) {

                                        try {
                                            // Get the valid JSON only from the returned string
                                            if (response.indexOf("<!--WC_START-->") >= 0)
                                                    response = response.split("<!--WC_START-->")[1]; // Strip off before after WC_START

                                            if (response.indexOf("<!--WC_END-->") >= 0)
                                                    response = response.split("<!--WC_END-->")[0]; // Strip off anything after WC_END

                                            // Parse
                                            var results = $.parseJSON(response);
                                            if (results.error) {

                                                $('#import-progress tbody').append('<tr id="row-' + i + '" class="error"><td class="status" colspan="5">' + results.error + '</td></tr>');
                                                i++;
                                            } else if (results.import_results && $(results.import_results).size() > 0) {
                                           
                                                $.each(results.processed_posts, function(index, value) {
                                                    processed_posts.push(value);
                                                });
                                                $(results.import_results).each(function(index, row) {
                                                    $('#import-progress tbody').append('<tr id="row-' + i + '" class="' + row['status'] + '"><td><mark class="result" title="' + row['status'] + '">' + row['status'] + '</mark></td><td class="row">' + i + '</td><td>' + row['order_number'] + '</td><td>' + row['post_id'] + ' - ' + row['post_title'] + '</td><td class="reason">' + row['reason'] + '</td></tr>');
                                                    i++;
                                                });
                                            }

                                        } catch (err) {}

                                    } else {
                                        $('#import-progress tbody').append('<tr class="error"><td class="status" colspan="5">' +  '<?php _e('AJAX Error', 'xml-file-export-import-for-stampscom-and-woocommerce'); ?>' + '</td></tr>');
                                    }

                                    var w = $(window);
                                    var row = $("#row-" + (i - 1));
                                    if (row.length) {
                                        w.scrollTop(row.offset().top - (w.height() / 2));
                                    }

                                    done_count++;
                                    $('body').trigger('woocommerce_stamps_xml_order_import_request_complete');
                                }
                            });
                        }

                        var rows = [];
                        <?php
                        $limit = apply_filters('woocommerce_xml_import_limit_per_request', 10);
                        $enc = mb_detect_encoding($file, 'UTF-8, ISO-8859-1', true);
                        if ($enc)
                            setlocale(LC_ALL, 'en_US.' . $enc);
                        @ini_set('auto_detect_line_endings', true);

                        $count = 0;

                        $import_count = 0;
                        ?>

                        var data = rows.shift();
                        var regen_count = 0;
                        import_rows();
                        $('body').on('woocommerce_stamps_xml_order_import_request_complete', function() {
                            import_done();

                        } );

                        function import_done() {
                            var data = {
                                action: 'woocommerce_stamps_xml_order_import_request',
                                file: '<?php echo $file; ?>',
                                processed_posts: processed_posts,
                                wt_nonce : '<?php echo wp_create_nonce( WF_ORDER_IMP_EXP_STAMPS_XML_ID )?>',
                            };

                            $.ajax({
                                url: '<?php echo add_query_arg(array('import_page' => $this->import_page, 'step' => '4', 'merge' => !empty($_GET['merge']) ? 1 : 0), admin_url('admin-ajax.php')); ?>',
                                data:       data,
                                type:       'POST',
                                success:    function( response ) {
                                    console.log( response );
                                    $('#import-progress tbody').append( '<tr class="complete"><td colspan="5">' + response + '</td></tr>' );
                                    $('.importer-loading').hide();
                                }
                            });
                        }
                    });
                    </script>
                    <?php
                } else {
                    echo '<p class="error">' . __('Error finding uploaded file!', 'xml-file-export-import-for-stampscom-and-woocommerce') . '</p>';
                }
                break;
            case 3 :
                if (!wp_verify_nonce($_POST['wt_nonce'], WF_ORDER_IMP_EXP_STAMPS_XML_ID) || !WF_Order_Import_Export_Stamps_XML::hf_user_permission()) {
                    wp_die(__('Access Denied', 'xml-file-export-import-for-stampscom-and-woocommerce'));
                }
                $file = stripslashes($_POST['file']);
                if (filter_var($file, FILTER_VALIDATE_URL)){ // Validating given path is valid path, not a URL
                    die();
                }

                add_filter('http_request_timeout_stamps_xml', array($this, 'bump_request_timeout_stamps_xml'));

                if (function_exists('gc_enable'))
                    gc_enable();

                @set_time_limit(0);
                @ob_flush();
                @flush();
                $wpdb->hide_errors();
                
                $this->parsed_data = $this->import_start($file);
                $this->import();
                $this->import_end();

                $results = array();
                $results['import_results'] = $this->import_results;
                $results['processed_posts'] = $this->processed_posts;

                echo "<!--WC_START-->";
                echo json_encode($results);
                echo "<!--WC_END-->";
                exit;
                break;
            case 4 :
                if (!wp_verify_nonce($_POST['wt_nonce'], WF_ORDER_IMP_EXP_STAMPS_XML_ID) || !WF_Order_Import_Export_Stamps_XML::hf_user_permission()) {
                    wp_die(__('Access Denied', 'xml-file-export-import-for-stampscom-and-woocommerce'));
                }
                add_filter('http_request_timeout_stamps_xml', array($this, 'bump_request_timeout_stamps_xml'));

                if (function_exists('gc_enable'))
                    gc_enable();

                @set_time_limit(0);
                @ob_flush();
                @flush();
                $wpdb->hide_errors();

                $this->processed_posts = isset($_POST['processed_posts']) ? array_map('intval',$_POST['processed_posts']) : array();

                _e('Step 1...', 'xml-file-export-import-for-stampscom-and-woocommerce') . ' ';

                wp_defer_term_counting(true);
                wp_defer_comment_counting(true);

                _e('Step 2...', 'xml-file-export-import-for-stampscom-and-woocommerce') . ' ';

                echo 'Step 3...' . ' '; // Easter egg

                _e('Finalizing...', 'xml-file-export-import-for-stampscom-and-woocommerce') . ' ';

                // SUCCESS
                _e('Finished. Import complete.', 'xml-file-export-import-for-stampscom-and-woocommerce');

                $file = isset($_POST['file']) ? stripslashes($_POST['file']) : '';                                 
                if(in_array(pathinfo($file, PATHINFO_EXTENSION),array('txt','csv'))){
                    unlink($file);
                }
                exit;
                break;
        }

        $this->footer();
    }

    /**
     * format_data_from_csv
     */
    public function format_data_from_csv($data, $enc) {
        return ( $enc == 'UTF-8' ) ? $data : utf8_encode($data);
    }

    /**
     * Display pre-import options
     */
    public function import_options() {
        $j = 0;

        if ($this->id)
            $file = get_attached_file($this->id);
        else if ($this->file_url_import_enabled)
            $file = ABSPATH . $this->file_url;
        else
            return;
        $merge = (!empty($_GET['merge']) && $_GET['merge']) ? 1 : 0;

        include( 'views/html-wf-import-options.php' );
    }

    /**
     * The main controller for the actual import stage.
     */
    public function import() {
        global $woocommerce, $wpdb;

        wp_suspend_cache_invalidation(true);
        $this->hf_order_log_data_change('stamps-import', '---');
        $this->hf_order_log_data_change('stamps-import', __('Processing orders.', 'xml-file-export-import-for-stampscom-and-woocommerce'));
        $merging = 1;
        $record_offset = 0;
        foreach ($this->parsed_data[0]['Print']->Item as $key => $item) {
            $order = $this->parser->parse_orders($item);
            //echo '<pre>';print_r($order['shop_order'][0]);echo '</pre>';
            if (!is_wp_error($order))
                $this->process_orders($order['shop_order'][0]);
            else
                $this->add_import_result('failed', $order->get_error_message(), 'Not parsed', json_encode($item), '-');

            unset($item, $order);
     //       $i++;
        }
        $this->hf_order_log_data_change('stamps-import', __('Finished processing Orders.', 'xml-file-export-import-for-stampscom-and-woocommerce'));
        wp_suspend_cache_invalidation(false);
    }

    /**
     * Parses the CSV file and prepares us for the task of processing parsed data
     *
     * @param string $file Path to the CSV file for importing
     */
    public function import_start($file) {

        $memory = size_format(wc_let_to_num(ini_get('memory_limit')));
        $wp_memory = size_format(wc_let_to_num(WP_MEMORY_LIMIT));

        $this->hf_order_log_data_change('stamps-import', '---[ New Import ] PHP Memory: ' . $memory . ', WP Memory: ' . $wp_memory);
        $this->hf_order_log_data_change('stamps-import', __('Parsing order XML.', 'xml-file-export-import-for-stampscom-and-woocommerce'));

        $this->parser = new WF_XML_Parser('shop_order');

        $this->parsed_data = $this->parser->parse_data($file);

        $this->hf_order_log_data_change('stamps-import', __('Finished parsing order XML.', 'xml-file-export-import-for-stampscom-and-woocommerce'));

        unset($import_data);

        wp_defer_term_counting(true);
        wp_defer_comment_counting(true);

        return $this->parsed_data;
    }

    /**
     * Performs post-import cleanup of files and the cache
     */
    public function import_end() {

          do_action('import_end');
    }

    /**
     * Handles the CSV upload and initial parsing of the file to prepare for
     * displaying author import options
     *
     * @return bool False if error uploading or invalid file, true otherwise
     */
    public function handle_upload() {

        if (empty($_POST['file_url'])) {

            $file = wp_import_handle_upload();

            if (isset($file['error'])) {
                echo '<p><strong>' . __('Sorry, there has been an error.', 'xml-file-export-import-for-stampscom-and-woocommerce') . '</strong><br />';
                echo esc_html($file['error']) . '</p>';
                return false;
            }

            $this->id = absint($file['id']);
            return true;
        } else {

            if (file_exists(ABSPATH . $_POST['file_url'])) {

                $this->file_url =  esc_url_raw($_POST['file_url']);
                return true;
            } else {

                echo '<p><strong>' . __('Sorry, there has been an error.', 'xml-file-export-import-for-stampscom-and-woocommerce') . '</strong></p>';
                return false;
            }
        }

        return false;
    }

    public function order_exists($orderID) {
        global $wpdb;
        $query = "SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order' AND post_status IN ( 'wc-pending', 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-failed' , 'wc-refunded', 'wc-cancelled')";
        //$args = array();
        $posts_are_exist = $wpdb->get_col($query);

        if ($posts_are_exist) {
            foreach ($posts_are_exist as $exist_id) {
                $found = false;
                if ($exist_id == $orderID) {
                    $found = TRUE;
                }
                if ($found)
                    return TRUE;
            }
        } else {
            return FALSE;
        }
    }

    /**
     * Create new posts based on import information
     */
    private function process_orders($post) {

        global $wpdb;
        $is_order_exist = $this->order_exists($post['order_number']);
        if ($is_order_exist) {
            foreach ($post['postmeta'] as $key => $meta) {
                update_post_meta($post['order_number'], $key, $meta);
            }
            $out_updated_msg = 'Order Successfully updated.';
            $view_status = 'imported';
            $this->imported++;
        } else {
            $out_updated_msg = 'Order doesnot exist.';
            $view_status = 'skipped';
            $this->skipped++;
        }
        $this->processed_posts[$post['order_number']] = $post['order_number'];

        $this->add_import_result($view_status, __($out_updated_msg, 'xml-file-export-import-for-stampscom-and-woocommerce'), $post['order_number'], $post['order_number'], $post['order_number']);
        $this->hf_order_log_data_change('stamps-import', sprintf(__('> &#8220;%s&#8221;' . $out_updated_msg, 'xml-file-export-import-for-stampscom-and-woocommerce'), $post['order_number']), true);
        $this->hf_order_log_data_change('stamps-import', sprintf(__('> Finished importing order %s', 'xml-file-export-import-for-stampscom-and-woocommerce'), $post['order_number']));


        $this->hf_order_log_data_change('stamps-import', __('Finished processing orders.', 'xml-file-export-import-for-stampscom-and-woocommerce'));

        unset($post); //end here
    }

    /**
     * Log a row's import status
     */
    protected function add_import_result($status, $reason, $post_id = '', $post_title = '', $order_number = '') {
        $this->import_results[] = array(
            'post_title' => $post_title,
            'post_id' => $post_id,
            'order_number' => $order_number,
            'status' => $status,
            'reason' => $reason
        );
    }

    /**
     * Decide what the maximum file size for downloaded attachments is.
     * Default is 0 (unlimited), can be filtered via import_attachment_size_limit
     *
     * @return int Maximum attachment file size to import
     */
    public function max_attachment_size() {
        return apply_filters('import_attachment_size_limit', 0);
    }

    // Display import page title
    public function header() {
        echo '<div class="woocommerce">';
        echo '<div><div class="icon32" id="icon-woocommerce-importer"><br></div>';
        $tab = 'import';
        include_once(plugin_dir_path(WF_OrderImpExpStampsXML_FILE).'includes/views/html-wf-common-header.php');
    }

    // Close div.wrap
    public function footer() {
        echo '</div>';
    }

    /**
     * Display introductory text and file upload form
     */
    public function greet() {
        $action = 'admin.php?import=woocommerce_wf_order_stamps_xml&amp;step=1&amp;merge=' . (!empty($_GET['merge']) ? 1 : 0 );
        $bytes = apply_filters('import_upload_size_limit', wp_max_upload_size());
        $size = size_format($bytes);
        $upload_dir = wp_upload_dir();
        include( 'views/html-wf-import-greeting.php' );
    }

    /**
     * Added to http_request_timeout filter to force timeout at 60 seconds during import
     * @return int 60
     */
    public function bump_request_timeout_stamps_xml($val) {
        return 60;
    }

    public function xml_to_array($xml_tree, $root = false) {
        print_r($xml_tree);
        exit;
        $array_name = $xml_tree['tag'];
        foreach ($xml_tree['children'] as $children) {
            $child_id = $children['attributes']['id'];
            $child_name = $children['tag'];
            $child_name.=($child_id) ? "__" . $child_id : '';
            if (is_array($children['children'])) {
                $child_array = xml_to_array($children);
                $temp_array[$child_name] = $child_array;
            } else {
                $temp_array[$child_name] = $children['value'];
            }
        }

        if (!$root)
            $xml_array = $temp_array;
        else
            $xml_array[$array_name] = $temp_array;

        return $xml_array;
    }
    
    public function hf_order_log_data_change($content = 'order-stampsxml-import', $data = '') {
        if (WC()->version < '2.7.0') {
            $this->log->add($content, $data);
        } else {
            $context = array('source' => $content);
            $this->log->log("debug", $data, $context);
        }
    }

}
