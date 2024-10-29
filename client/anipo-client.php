<?php

defined('ABSPATH') || exit;

final class ANIPO_CLIENT
{
    private static $_instance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        add_action('woocommerce_order_details_after_customer_details', [$this, 'display_order_barcode_link']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_client_style']);
    }

    public function display_order_barcode_link($order)
    {
        $barcode = $order->get_meta('anipo_barcode');
        if (!empty($barcode)) {
            // Display the barcode link
            echo '<div class="anipo-order-barcode">';
            echo '<h3>' . esc_html__('Barcode', 'anipo') . '</h3>';
            echo '<p><a href="' . esc_url(ANIPO_POST_TRACKING . '?id=' . $barcode) . '" target="_blank">' . esc_html($barcode) . '</a></p>';
            echo '</div>';
        }
    }

    public function enqueue_client_style()
    {
        wp_enqueue_style('barcode-section-styles', ANIPO_URL . 'client/css/client.css', [], '1.0.0');
    }
}

ANIPO_CLIENT::instance();
