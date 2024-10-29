<?php

defined('ABSPATH') || exit;

final class ANIPO_ADMIN_MENU
{
    private static $_instance = null;

    private array $woocommerce_orders_table = ['edit-shop_order', 'woocommerce_page_wc-orders'];

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_filter('manage_edit-shop_order_columns', [$this, 'add_column_to_orders_table'], 20);
        add_action('manage_shop_order_posts_custom_column', [$this, 'show_columns_value_in_orders_column'], 20, 2);
        add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'add_column_to_orders_table']);
        add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'show_columns_value_in_orders_column_HPOS'], 10, 2);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'add_barcode_to_order_details']);
        add_filter('is_protected_meta', [$this, 'hide_order_barcode_data'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_script']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_style']);
        add_action('admin_footer', [$this, 'load_barcode_modal']);
    }

    public function admin_menu()
    {
        add_menu_page(
            'Anipo',         // Page title
            __('Anipo', 'anipo'),               // Menu title in the dashboard
            'manage_options',            // Capability required
            'anipo-admin-menu',       // Menu slug
            [$this, 'register_page'], // Function to display the page content
            'dashicons-location',   // Dashicon (https://developer.wordpress.org/resource/dashicons/)
            56                            // Position
        );

        add_submenu_page(
            'anipo-admin-menu',         // Parent slug (same as the menu slug)
            __('Register', 'anipo'),             // Page title
            __('Register', 'anipo'),                   // Submenu title
            'manage_options',              // Capability required
            'anipo-admin-menu-register',              // Submenu slug
            [$this, 'register_page']      // Function to display submenu content
        );

        add_submenu_page(
            'anipo-admin-menu',         // Parent slug (same as the menu slug)
            __('Settings', 'anipo'),             // Page title
            __('Settings', 'anipo'),                   // Submenu title
            'manage_options',              // Capability required
            'anipo-admin-menu-settings',              // Submenu slug
            [$this, 'settings_page']      // Function to display submenu content
        );

        // Remove the duplicate submenu with the same slug as the main menu
        remove_submenu_page('anipo-admin-menu', 'anipo-admin-menu');
    }

    public function register_page()
    {
        include_once ANIPO_PATH . 'admin/view/anipo-admin-register-page.php';
    }

    public function settings_page()
    {
        include_once ANIPO_PATH . 'admin/view/anipo-admin-settings-page.php';
    }

    public function enqueue_admin_script()
    {
        wp_enqueue_script('anipo-admin-dashboard-ajax', ANIPO_URL . 'admin/js/ajax.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('anipo-admin-dashboard-ajax-2', ANIPO_URL . 'admin/js/ajax-2.js', array('jquery'), '1.0.0', true);
        // Pass AJAX URL and nonce to JavaScript
        wp_localize_script('anipo-admin-dashboard-ajax', 'barcodeAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('anipo_get_order_barcode_nonce'),
        ));
        wp_localize_script('anipo-admin-dashboard-ajax-2', 'barcodeAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'admin_url' => ANIPO_URL . 'admin/',
            'nonce' => wp_create_nonce('anipo_get_order_barcode_nonce'),
        ));
        wp_localize_script('anipo-admin-dashboard-ajax', 'modalAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
        ));
    }

    public function add_column_to_orders_table($columns)
    {
        $columns['anipo_get_barcode'] = __('Get Barcode', 'anipo');
        $columns['anipo_barcode'] = __('Barcode', 'anipo');
        return $columns;
    }

    public function show_columns_value_in_orders_column($column, $order_id)
    {
        global $the_order;
        $this->show_columns_value($column, $the_order);
    }

    public function show_columns_value_in_orders_column_HPOS($column, $order)
    {
        $this->show_columns_value($column, $order);
    }

    private function show_columns_value($column, $order)
    {
        global $anipo;
        if ($column === 'anipo_get_barcode') {
            $barcode = $order->get_meta('anipo_barcode');
            if (in_array($order->get_status(), $anipo->allowed_woocommerce_statuses()) && empty($barcode)) {
                $order_weight = $anipo->calculate_order_weight($order);
                echo '<button class="anipo-button anipo-show-barcode-modal-button" data-order-id="' . esc_attr($order->get_id()) . '" data-order-weight="' . esc_attr($order_weight) . '">' . esc_html(__('Get Barcode', 'anipo')) . '</button>';
            } elseif (!empty($barcode)) {
                echo '<button class="anipo-button anipo-print-barcode-button" data-order-id="' . esc_attr($order->get_id()) . '">' . esc_html(__('Print Barcode', 'anipo')) . '</button>';
            }
        } elseif ($column === 'anipo_barcode') {
            $barcode = $order->get_meta('anipo_barcode');
            if (!empty($barcode)) {
                echo '<a href="' . esc_url(ANIPO_POST_TRACKING . '?id=' . $barcode) . '" target="_blank" rel="noopener noreferrer" class="anipo-order-barcode-link">' . esc_html($barcode) . '</a>';
            }
        }
    }

    public function add_barcode_to_order_details($order)
    {
        // Get the barcode (assuming it's stored as post meta with the key '_order_barcode')
        $barcode = $order->get_meta('anipo_barcode');
        if (!empty($barcode)) {
            // Create a link for the barcode that opens in a new tab
            echo '<p><strong>' . esc_html(__('Barcode', 'anipo')) . ':' . '</strong><br>';
            echo '<a href="' . esc_url(ANIPO_POST_TRACKING . '?id=' . $barcode) . '" target="_blank" rel="noopener noreferrer" class="anipo-order-barcode-link">' . esc_html($barcode) . '</a>';
            echo '</p>';
        }
    }

    public function hide_order_barcode_data($protected, $meta_key)
    {
        // Array of meta keys you want to hide from the "Custom Fields" metabox
        $meta_keys_to_hide = [
            'anipo_is_issued_barcode',
            'anipo_barcode',
            'anipo_post_cost',
            'anipo_tax',
            'anipo_box_size_id',
            'anipo_barcode_timestamp',
            'anipo_barcode_submitted_weight',
        ];
        // If the meta key is in the array, mark it as protected
        if (in_array($meta_key, $meta_keys_to_hide)) {
            return true; // Mark the meta key as protected
        }
        return $protected;
    }

    public function load_barcode_modal()
    {
        include_once ANIPO_PATH . 'admin/view/anipo-admin-barcode-modal.php';
        include_once ANIPO_PATH . 'admin/view/anipo-admin-ajax-loader.php';
    }

    public function enqueue_admin_style()
    {
        wp_enqueue_style('anipo-dashboard-orders-table', ANIPO_URL . 'admin/css/orders-table.css', [], '1.0.0');
    }
}

ANIPO_ADMIN_MENU::instance();
