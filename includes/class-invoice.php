<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Invoice
 * @subpackage Invoice/includes
 * @author     mbj-webdevelopment <mbjwebdevelopment@gmail.com>
 */
class Invoice {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Invoice_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {

        add_action('parse_request', array($this, 'handle_cron_requests'), 0);
        add_action('paypal_invoice_set_cron_data', array($this, 'paypal_invoice_set_cron_data'));
        add_filter('cron_schedules', array($this, 'invoice_new_interval_cron_time'));

        $this->plugin_name = 'invoice';
        $this->version = '1.0.5';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Invoice_Loader. Orchestrates the hooks of the plugin.
     * - Invoice_i18n. Defines internationalization functionality.
     * - Invoice_Admin. Defines all hooks for the admin area.
     * - Invoice_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-invoice-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-invoice-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-invoice-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/PayPal/paypal.class.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/PayPal/php-invoice-manager.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/class-paypal-invoice-credentials-for-wordpress-html-output.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/class-paypal-invoice-credentialsl-for-wordpress-setting.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-invoice-logger.php';

        $this->loader = new Invoice_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Invoice_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Invoice_i18n();
        $plugin_i18n->set_domain($this->get_plugin_name());

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new Invoice_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_my_setting');
        $this->loader->add_action('init', $plugin_admin, 'paypal_invoice_post_init');
        $this->loader->add_action('init', $plugin_admin, 'invoice_cron_set');
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_meta_boxes_item_detail', 10);
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_client_meta_boxes_detail', 10);
        $this->loader->add_action('init', $plugin_admin, 'paypal_invoice_client_create_init');
        $this->loader->add_action('admin_menu', $plugin_admin, 'payment_invoice');
        $this->loader->add_action('save_post', $plugin_admin, 'save_post_item_details', 10, 3);
        $this->loader->add_action('save_post', $plugin_admin, 'save_post_client_details', 10, 3);
        $this->loader->add_action('admin_notices', $plugin_admin, 'show_admin_notice');
        $this->loader->add_filter('manage_edit-invoice_columns', $plugin_admin, 'set_custom_edit_invoice_columns');
        $this->loader->add_action('manage_invoice_posts_custom_column', $plugin_admin, 'custom_invoice_columns', 10, 2);
        $this->loader->add_filter('manage_edit-invoice_sortable_columns', $plugin_admin, 'bs_invoice_table_sorting');
        $this->loader->add_filter('manage_edit-clients_columns', $plugin_admin, 'set_custom_edit_clients_columns');
        $this->loader->add_action('manage_clients_posts_custom_column', $plugin_admin, 'custom_clients_columns', 10, 2);
        $this->loader->add_filter('manage_edit-clients_sortable_columns', $plugin_admin, 'bs_clients_table_sorting');
        $this->loader->add_action('paypal_invoice_cron_event_set', $plugin_admin, 'paypal_invoice_cron_event_set');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Invoice_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    public function handle_cron_requests() {
        global $wp;
        if (isset($_GET['action']) && $_GET['action'] == 'cron_data') {
            $wp->query_vars['Invoice'] = $_GET['action'];
        }
        if (!empty($wp->query_vars['Invoice'])) {
            ob_start();
            $api = strtolower(esc_attr($wp->query_vars['Invoice']));
            do_action('paypal_invoice_set_cron_data');
            ob_end_clean();
            die('1');
        }
    }

    public function paypal_invoice_set_cron_data() {

        global $wp_query;

        $paypal_invoice_manager_obj = new PayPal_Invoice_Manager();
        $args = array(
            'post_type' => 'invoice',
            'meta_key' => 'create_invoice_opration',
            'orderby' => 'meta_value_num',
            'order' => 'ASC',
            'meta_query' => array(
                array(
                    'key' => 'create_invoice_opration',
                    'value' => 5,
                    'compare' => 'NOT IN',
                ),
            ),
        );
        $query = new WP_Query($args);

        while ($query->have_posts()) : $query->the_post();

            $post_invoice_id = get_post_meta(get_the_ID(), 'InvoiceID', TRUE);
            $paypal_responce_status = $paypal_invoice_manager_obj->App_GetInvoicesDetails($post_invoice_id);

            if ('Success' == $paypal_responce_status['Ack']) {
                $update_invoice_id = '';
                foreach ($paypal_responce_status as $key => $value) {
                    if ('Status' == $key) {

                        if ('Draft' == $value) {
                            $update_invoice_id = '1';
                        } elseif ('Sent' == $value) {
                            $update_invoice_id = '6';
                        } elseif ('MarkedAsPaid' == $value) {
                            $update_invoice_id = '4';
                        } elseif ('Paid' == $value) {
                            $update_invoice_id = '5';
                        } elseif ('Canceled' == $value) {
                            $update_invoice_id = '3';
                        } elseif ('Deleted' == $value) {
                            $update_invoice_id = '8';
                        } elseif ('Refunded' == $value) {
                            $update_invoice_id = '9';
                        }

                        if (isset($update_invoice_id) && !empty($update_invoice_id)) {
                            update_post_meta(get_the_ID(), 'create_invoice_opration', $update_invoice_id);
                        }
                    }
                }
            }
        endwhile;
    }

    public function invoice_new_interval_cron_time($interval) {
        $interval['min_30'] = array('interval' => 1200, 'display' => 'Once 30 minutes');
        return $interval;
    }

}