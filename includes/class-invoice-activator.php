<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Invoice
 * @subpackage Invoice/includes
 * @author     mbj-webdevelopment <mbjwebdevelopment@gmail.com>
 */
class Invoice_Activator {

    /**
     * @since    1.0.0
     */
    public static function activate() {
        // wp_schedule_event(time(), 'hourly', 'paypal_invoice_cron_event');
        self::create_files();
    }

    private function create_files() {
        $upload_dir = wp_upload_dir();
        $files = array(
            array(
                'base' => INV_FOR_WORDPRESS_LOG_DIR,
                'file' => '.htaccess',
                'content' => 'deny from all'
            ),
            array(
                'base' => INV_FOR_WORDPRESS_LOG_DIR,
                'file' => 'index.html',
                'content' => ''
            )
        );
        foreach ($files as $file) {
            if (wp_mkdir_p($file['base']) && !file_exists(trailingslashit($file['base']) . $file['file'])) {
                if ($file_handle = @fopen(trailingslashit($file['base']) . $file['file'], 'w')) {
                    fwrite($file_handle, $file['content']);
                    fclose($file_handle);
                }
            }
        }
    }

}