<?php

/**
 * Plugin Name: Anipo
 * Plugin URI: https://anipo.ir/
 * Description: A plugin for Anipo shops
 * Version: 1.0.2
 * Author: Middle East Varkan Programmers Company
 * Author URI: https://varkan.ir/
 * Text Domain: anipo
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Anipo
 */

defined('ABSPATH') || exit;

final class ANIPO
{
    private static $_instance = null;
    private ANIPO_API $api;
    public ANIPO_DATE_TIME $date_time;
    private string $keyword_option_name = 'anipo_key';
    private string $shop_call_number_option_name = 'anipo_shop_call_number';
    private string $print_factor_option_name = 'anipo_print_factor';

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        $this->define_constants();
        register_activation_hook(__FILE__, [$this, 'activate']);
        include_once ANIPO_PATH . 'vendor/autoload.php';
        $this->api = $this->add_api();
        $this->date_time = $this->add_date_time();
        add_action('plugins_loaded', [$this, 'load_text_domain']);
        add_action('admin_init', [$this, 'activate']);
//        add_filter('plugin_action_links', [$this, 'prevent_woocommerce_deactivation'], 10, 4); // Maybe it will be used in the future
        $this->dashboard();
        $this->client();
    }

    private function define_constants()
    {
        define('ANIPO_MIN_PHP_VERSION', '7.4');
        define('ANIPO_URL', trailingslashit(plugins_url('/', __FILE__)));
        define('ANIPO_PATH', trailingslashit(plugin_dir_path(__FILE__)));
        define('ANIPO_API_URL', 'https://panel.anipo.ir/backend/api/');
        define('ANIPO_POST_TRACKING', 'https://tracking.post.ir/');
        define('ANIPO_WEIGHT_UNIT', get_option('woocommerce_weight_unit'));
    }

    public function activate()
    {
        $this->check_php_version();
        $this->check_woocommerce_dependency(); // Maybe it will be used in the future
    }

    private function check_php_version()
    {
        // Compare the current PHP version with the required version
        if (version_compare(PHP_VERSION, ANIPO_MIN_PHP_VERSION, '<')) {
            // Deactivate the plugin
            deactivate_plugins(plugin_basename(__FILE__));
            // Display an admin notice with an error message
            $error_message = sprintf(
                'Anipo plugin requires PHP version %s or higher. Your current PHP version is %s. Anipo has been deactivated.',
                ANIPO_MIN_PHP_VERSION,
                PHP_VERSION
            );
            add_action('admin_notices', function () use (&$error_message) {
                echo '<div class="error"><p><strong>Warning:</strong> ' . esc_html($error_message) . '</p></div>';
            });
        }
    }

    private function check_woocommerce_dependency()
    {
        // Check if WooCommerce is not active
        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            // Deactivate this plugin
            deactivate_plugins(plugin_basename(__FILE__));
            // Display an admin notice
            add_action('admin_notices', function () {
                echo '<div class="error"><p><strong>' . esc_html__('Warning', 'anipo') . ':</strong> ' . esc_html__('Anipo plugin requires WooCommerce to be active. Please activate WooCommerce to use this plugin.', 'anipo') . '</p></div>';
            });
        }
    }

    public function prevent_woocommerce_deactivation($actions, $plugin_file, $plugin_data, $context)
    {
        // Check if WooCommerce is being targeted for deactivation
        if ($plugin_file == 'woocommerce/woocommerce.php' && is_plugin_active(plugin_basename(__FILE__))) {
            // Display an admin notice
            add_action('admin_notices', function () {
                echo '<div class="error"><p><strong>WooCommerce cannot be deactivated:</strong> It is required by your active plugin.</p></div>';
            });
            // Remove the deactivate link
            unset($actions['deactivate']);
        }
        return $actions;
    }

    private function dashboard()
    {
        include_once ANIPO_PATH . 'admin/anipo-admin-menu.php';
    }

    private function client()
    {
        include_once ANIPO_PATH . 'client/anipo-client.php';
    }

    public function load_text_domain()
    {
        load_plugin_textdomain('anipo', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    private function add_api()
    {
        return include_once ANIPO_PATH . 'api/anipo-api.php';
    }

    private function add_date_time()
    {
        return include_once ANIPO_PATH . 'date-time/date-time.php';
    }

    public function get_keyword()
    {
        return get_option($this->keyword_option_name, '');
    }

    public function update_keyword($keyword)
    {
        update_option($this->keyword_option_name, $keyword);
    }

    public function get_print_factor_setting()
    {
        return get_option($this->print_factor_option_name, 0);
    }

    public function update_print_factor_setting($value)
    {
        update_option($this->print_factor_option_name, $value);
    }

    public function get_shop_call_number()
    {
        return get_option($this->shop_call_number_option_name, '');
    }

    public function update_shop_call_number($call_number)
    {
        update_option($this->shop_call_number_option_name, $call_number);
    }

    public function first_connection_api($api_key)
    {
        return $this->api->first_connection_api($api_key);
    }

    public function calculate_order_weight(WC_Order $order)
    {
        return $this->api->calculate_order_weight($order);
    }

    public function get_order_date_and_time(WC_Order $order)
    {
        $date_created = $order->get_date_created();
        if (!is_null($date_created)) {
            $order_date_time = $this->date_time->date_and_time($date_created->getTimestamp());
            return ['date' => $order_date_time['date'], 'time' => $order_date_time['time']];
        }
        return ['date' => '', 'time' => ''];
    }

    public function allowed_woocommerce_statuses()
    {
        return ['completed', 'processing'];
    }

    public function get_shop_logo()
    {
        // Get the custom logo ID set in the customizer
        $custom_logo_id = get_theme_mod('custom_logo');
        // If a custom logo exists, retrieve the URL
        if ($custom_logo_id) {
            return wp_get_attachment_image_url($custom_logo_id, 'full');
        } else {
            return ''; // Return empty if no logo is set
        }
    }
}

$anipo = ANIPO::instance();
