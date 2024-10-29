<?php

defined('ABSPATH') || exit;

final class ANIPO_API
{
    private static $_instance = null;
    private string $base_api = 'cyn-anipo/v1';

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        $this->register_routes();
    }

    private function register_routes()
    {
        add_action('rest_api_init', function () {
            register_rest_route($this->base_api, '/orders/(?P<day>\d+)', [
                'methods' => 'POST',
                'callback' => [$this, 'show_orders'],
                'permission_callback' => '__return_true'
            ]);
        });
        add_action('rest_api_init', function () {
            register_rest_route($this->base_api, '/issued-order', [
                'methods' => 'POST',
                'callback' => [$this, 'issued_order'],
                'permission_callback' => '__return_true'
            ]);
        });
        add_action('wp_ajax_anipo_get_order_barcode', [$this, 'get_barcode']);
        add_action('wp_ajax_anipo_print_barcode', [$this, 'print_barcode']);
    }

    public function first_connection_api($api_key)
    {
        $api_url = ANIPO_API_URL . 'initialConnect/';
        // Data to send
        $body = [
            'keyword' => $api_key,
            'apiurl' => site_url(),
        ];
        // API request arguments
        $args = $this->api_args($body);
        // Send the POST request to the API
        return wp_remote_post($api_url, $args);
    }

    public function get_barcode()
    {
        try {
            // Security check
            check_ajax_referer('anipo_get_order_barcode_nonce', 'nonce');
            if (!isset($_POST['order_id']) || !isset($_POST['order_weight'])) {
                wp_send_json_success(['status' => false, 'message' => 'Invalid input']);
            }
            $order_id = intval($_POST['order_id']);
            $order_weight = intval($_POST['order_weight']);
            $order = new WC_Order($order_id);
            if (!$order) {
                wp_send_json_success(['status' => false, 'message' => 'Invalid order ID']);
            }
            $api_response = $this->get_barcode_api($order, $order_weight);
            if (!is_wp_error($api_response)) {
                $body = wp_remote_retrieve_body($api_response);
                $body = json_decode($body);
                if ($body->status) {
                    $order->update_meta_data('anipo_is_issued_barcode', true);
                    $order->update_meta_data('anipo_barcode', $body->data->barcode);
                    $order->update_meta_data('anipo_post_cost', $body->data->postprice);
                    $order->update_meta_data('anipo_tax', $body->data->tax);
                    $order->update_meta_data('anipo_box_size_id', $body->data->boxSizeId);
                    $order->update_meta_data('anipo_barcode_timestamp', $body->data->timestamp);
                    $order->update_meta_data('anipo_barcode_submitted_weight', $body->data->weight);
                    $order->save();
                    wp_send_json_success(['status' => true, 'message' => 'صدور بارکد با موفقیت انجام شد', 'data' => $this->barcode_print_data($order)]);
                } else {
                    wp_send_json_success(['status' => false, 'message' => esc_html($body->message)]);
                }
            } else {
                wp_send_json_success(['status' => false, 'message' => __('Api Failed', 'anipo')]);
            }
        } catch (Exception $e) {
            wp_send_json_success(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    private function get_barcode_api(WC_Order $order, $order_weight)
    {
        global $anipo;
        $customer_country = $order->get_billing_country();
        $customer_state = $order->get_billing_state();
        // Get the full list of states for the country
        $states = WC()->countries->get_states($customer_country);
        // Retrieve the state name from the state code
        $customer_state_name = $states[$customer_state] ?? '';
        $order_date_time = $anipo->get_order_date_and_time($order);
        $orderData = [
            'orderId' => $order->get_id(),
            'date' => $order_date_time['date'],
            'time' => $order_date_time['time'],
            'province_code' => $customer_state,
            'province_name' => $customer_state_name,
            'city_name' => $order->get_billing_city(),
            'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'postcode' => $order->get_billing_postcode(),
            'national_code' => $order->get_meta('_billing_national_code'),
            'call_number' => $order->get_billing_phone(),
            'address' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
            'weight' => $order_weight,
            'sum' => $order->get_total(),
        ];
        $body = [
            'keyword' => $anipo->get_keyword(),
            'ordersData' => $orderData,
        ];
        $api_url = ANIPO_API_URL . 'getBarcode/';
        $args = $this->api_args($body);
        return wp_remote_post($api_url, $args);
    }

    public function print_barcode()
    {
        try {
            // Security check
            check_ajax_referer('anipo_get_order_barcode_nonce', 'nonce');
            global $anipo;
            if (!isset($_POST['order_id'])) {
                wp_send_json_success(['status' => false, 'message' => 'Invalid input']);
            }
            $order_id = intval($_POST['order_id']);
            $order = new WC_Order($order_id);
            if (!$order) {
                wp_send_json_success(['status' => false, 'message' => __('Invalid order ID', 'anipo')]);
            }
            $body = [
                'keyword' => $anipo->get_keyword(),
                'orderId' => $order_id,
            ];
            $api_url = ANIPO_API_URL . 'orderPluginData/';
            $args = $this->api_args($body);
            $api_response = wp_remote_post($api_url, $args);
            if (!is_wp_error($api_response)) {
                $body = wp_remote_retrieve_body($api_response);
                $body = json_decode($body);
                if ($body->status) {
                    $order->update_meta_data('anipo_is_issued_barcode', true);
                    $order->update_meta_data('anipo_barcode', $body->data->barcode);
                    $order->update_meta_data('anipo_post_cost', $body->data->post_cost);
                    $order->update_meta_data('anipo_tax', $body->data->tax);
                    $order->update_meta_data('anipo_box_size_id', $body->data->box_size_id);
                    $order->update_meta_data('anipo_barcode_timestamp', $body->data->barcode_timestamp);
                    $order->update_meta_data('anipo_barcode_submitted_weight', $body->data->submitted_weight);
                    $order->save();
                } else {
                    wp_send_json_success(['status' => false, 'message' => esc_html($body->message)]);
                }
            } else {
                wp_send_json_success(['status' => false, 'message' => __('Api Failed', 'anipo')]);
            }
            wp_send_json_success(['status' => true, 'message' => 'دریافت اطلاعات با موفقیت انجام شد', 'data' => $this->barcode_print_data($order)]);
        } catch (Exception $e) {
            wp_send_json_success(['status' => false, 'message' => $e->getMessage()]);
        }
    }

    public function calculate_order_weight(WC_Order $order)
    {
        $total_weight = 0;
        // Loop through each item in the order
        foreach ($order->get_items() as $item_id => $item) {
            // Get the product associated with the line item
            $product = $item->get_product();
            // Check if product exists and has a weight
            if ($product && $product->has_weight() && $product->get_weight() > 0) {
                // Get product weight
                $product_weight = $product->get_weight();
                // Multiply the weight by the quantity of the product in the order
                $total_weight += $product_weight * $item->get_quantity();
            } else {
                $total_weight = 0;
                break;
            }
        }
        $total_weight = $total_weight * 1.2;
        if (ANIPO_WEIGHT_UNIT === 'kg') {
            $total_weight = round($total_weight * 1000);
        } elseif (ANIPO_WEIGHT_UNIT === 'lbs') {
            $total_weight = round($total_weight * 453.592);
        } elseif (ANIPO_WEIGHT_UNIT === 'oz') {
            $total_weight = round($total_weight * 28.3495);
        }
        return $total_weight;
    }

    private function api_args($body)
    {
        return [
            'body' => $body,
            'timeout' => '15',
            'redirection' => '5',
            'blocking' => true,
            'headers' => [],
        ];
    }

    private function authorization($keyword)
    {
        global $anipo;
        return $keyword === $anipo->get_keyword();
    }

    /**
     * @throws Exception
     */
    public function show_orders(WP_REST_Request $request)
    {
        global $anipo;
        if (!$this->authorization($request->get_header('keyword'))) {
            return new WP_REST_Response(['status' => false, 'error' => 'keyword is wrong'], 403);
        }
        //Get Orders that completed in last {$day} days
        $day = $request->get_param('day');
        $order_query = new WC_Order_Query([
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids',
            'status' => $anipo->allowed_woocommerce_statuses(),
            'date_created' => '>' . (time() - $day * DAY_IN_SECONDS),
        ]);
        $order_ids = $order_query->get_orders();
        //make orders array for api
        $orders = [];
        foreach ($order_ids as $order_id) {
            $order = new WC_Order($order_id);
            $order_date_time = $anipo->get_order_date_and_time($order);
            $customer_country = $order->get_billing_country();
            $customer_state = $order->get_billing_state();
            // Get the full list of states for the country
            $states = WC()->countries->get_states($customer_country);
            // Retrieve the state name from the state code
            $customer_state_name = $states[$customer_state] ?? '';
            $orderData = [
                'api_order_id' => $order_id,
                'order_details' => [
                    'call_number' => $order->get_billing_phone(),
                    'weight' => $this->calculate_order_weight($order),
                    'sum' => $order->get_total(),
                    'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    'national_code' => $order->get_meta('_billing_national_code'),
                    'province_code' => $customer_state,
                    'province_name' => $customer_state_name,
                    'city_name' => $order->get_billing_city(),
                    'postcode' => $order->get_billing_postcode(),
                    'address' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
                    'date' => $order_date_time['date'],
                    'time' => $order_date_time['time'],
                ],
                'order_products' => [],
            ];
            //Get Products from order and make array for api
            $order_products = $order->get_items();
            foreach ($order_products as $order_product) {
                $order_data = $order_product->get_data();
                if ($order_data['variation_id'] === 0) {
                    $product = new WC_Product($order_data['product_id']);
                } else {
                    $product = new WC_Product_Variation($order_data['variation_id']);
                }
                $order_product_details = [
                    'name' => $product->get_name(),
                    'weight' => $product->get_weight(),
                    'number' => $order_product->get_quantity(),
                    'unitPrice' => intval($order_product->get_subtotal()) / $order_product->get_quantity(),
                    'discount' => (intval($order_product->get_subtotal()) - intval($order_product->get_total())) / $order_product->get_quantity(),
                ];
                //add meta_data for variation products {color, size, etc.}
                $meta_data = $order_data['meta_data'];
                foreach ($meta_data as $data) {
                    $order_product_details[$data->get_data()['key']] = str_replace("-", " ", urldecode($data->get_data()['value']));
                }
                $orderData['order_products'][] = $order_product_details;
            }
            $orders[] = $orderData;
        }
        return new WP_REST_Response(['status' => true, 'orders' => $orders], 200);
    }

    public function issued_order(WP_REST_Request $request)
    {
        if (!$this->authorization($request->get_header('keyword'))) {
            return new WP_REST_Response(['status' => false, 'error' => 'keyword is wrong'], 403);
        }

        $query_params = $request->get_query_params();

        //Get Params
        $order_id = $query_params['order_id'] ?? null;
        $barcode = $query_params['barcode'] ?? null;
        $post_cost = $query_params['post_cost'] ?? null;
        $tax = $query_params['tax'] ?? null;
        $boxSizeId = $query_params['box_size_id'] ?? null;
        $barcode_timestamp = $query_params['barcode_timestamp'] ?? null;
        $barcode_submitted_weight = $query_params['submitted_weight'] ?? null;

        //Check Params
        if (!$order_id) {
            return new WP_REST_Response(['status' => false, 'error' => 'order id is required'], 403);
        }

        if (!$barcode) {
            return new WP_REST_Response(['status' => false, 'error' => 'barcode is required'], 403);
        }

        if (!$post_cost) {
            return new WP_REST_Response(['status' => false, 'error' => 'post cost is required'], 403);
        }


        $wc_order = wc_get_order($order_id);

        //if order is not exist
        if (!$wc_order) {
            return new WP_REST_Response(['status' => false, 'error' => 'order id is not found'], 200);
        }

//        $is_issued_barcode = get_post_meta( $order_id, 'anipo_is_issued_barcode' ) ?? null;
//
//        //if order not issued before
//        if (!$is_issued_barcode) {
        $wc_order->update_meta_data('anipo_is_issued_barcode', true);
        $wc_order->update_meta_data('anipo_barcode', $barcode);
        $wc_order->update_meta_data('anipo_post_cost', $post_cost);
        $wc_order->update_meta_data('anipo_tax', $tax);
        $wc_order->update_meta_data('anipo_box_size_id', $boxSizeId);
        if (is_null($barcode_timestamp)) {
            $saved_timestamp = $wc_order->get_meta('anipo_barcode_timestamp');
            if (empty($saved_timestamp)) {
                $wc_order->update_meta_data('anipo_barcode_timestamp', time());
            }
        } else {
            $wc_order->update_meta_data('anipo_barcode_timestamp', $barcode_timestamp);
        }
        if (!is_null($barcode_submitted_weight)) {
            $wc_order->update_meta_data('anipo_barcode_submitted_weight', $barcode_submitted_weight);
        }
        $wc_order->save();
        return new WP_REST_Response(['status' => true, 'anipo_is_issued_barcode' => true, 'is_updated' => true], 200);
//        }
//        return new WP_REST_Response( [ 'status' => true, 'anipo_is_issued_barcode' => true, 'is_updated' => false ], 200 );
    }

    /**
     * @throws Exception
     */
    public function barcode_print_data(WC_Order $order)
    {
        global $anipo;
        $barcode_timestamp = $order->get_meta('anipo_barcode_timestamp');
        if (!empty($barcode_timestamp)) {
            $barcode_date_time = $anipo->date_time->date_and_time($barcode_timestamp);
            $barcode_date = $barcode_date_time['date'];
            $barcode_time = $barcode_date_time['time'];
        } else {
            $order_date_time = $anipo->get_order_date_and_time($order);
            $barcode_date = $order_date_time['date'];
            $barcode_time = $order_date_time['time'];
        }
        $weight = $order->get_meta('anipo_barcode_submitted_weight');
        if (empty($weight)) {
            $weight = $this->calculate_order_weight($order);
        }

        $shop_address = get_option('woocommerce_store_address'); // Street address
        $shop_address_2 = get_option('woocommerce_store_address_2'); // Optional second address line
        $shop_city = get_option('woocommerce_store_city'); // City
        $shop_postcode = get_option('woocommerce_store_postcode'); // Postcode

        // Get the country and state separated
        $shop_raw_country = get_option('woocommerce_default_country'); // Country and state in 'COUNTRY:STATE' format
        $split_country = explode(":", $shop_raw_country);
        $shop_country = $split_country[0]; // Country code (e.g., 'US')
        $shop_state = $split_country[1] ?? ''; // State code (e.g., 'NY')

        // Get the full list of states for the country
        $states = WC()->countries->get_states($shop_country);
        // Retrieve the state name from the state code
        $shop_state_name = $states[$shop_state] ?? '';

        // Get the shop name from WordPress general settings
        $shop_name = get_option('blogname');

        // Get the shop phone number from WooCommerce settings
        $shop_phone = $anipo->get_shop_call_number();
        if (!$shop_phone) {
            $shop_phone = '';
        }

        $site_url = home_url();
        $domain = wp_parse_url($site_url, PHP_URL_HOST);

        $customer_country = $order->get_billing_country();
        $customer_state = $order->get_billing_state();
        // Get the full list of states for the country
        $states = WC()->countries->get_states($customer_country);
        // Retrieve the state name from the state code
        $customer_state_name = $states[$customer_state] ?? '';
        $customer_city = $order->get_billing_city();
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $customer_postcode = $order->get_billing_postcode();
        $customer_call_number = $order->get_billing_phone();
        $customer_address = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();

        $products = [];
        if (intval($anipo->get_print_factor_setting()) === 1) {
            // Loop through each item in the order
            foreach ($order->get_items() as $item_id => $item) {
                // Get the product name
                $product_name = $item->get_name();
                // Get the quantity of the product
                $product_quantity = $item->get_quantity();
                $products[] = [
                    'name' => $product_name,
                    'quantity' => $product_quantity,
                    'price' => intval($item->get_subtotal()) / $product_quantity,
                    'discount' => (intval($item->get_subtotal()) - intval($item->get_total())) / $product_quantity,
                ];
            }
        }


        return [
            'order_id' => $order->get_id(),
            'barcode' => $order->get_meta('anipo_barcode'),
            'weight' => $weight,
            'box_size_id' => $order->get_meta('anipo_box_size_id'),
            'post_price' => $order->get_meta('anipo_post_cost'),
            'post_tax' => $order->get_meta('anipo_tax'),
            'domain' => $domain,
            'barcode_date' => $barcode_date,
            'barcode_time' => strlen($barcode_time) > 5 ? substr($barcode_time, 0, 5) : $barcode_time,
            'shop_address' => $shop_address,
            'shop_name' => $shop_name,
            'shop_city' => $shop_city,
            'shop_postcode' => $shop_postcode,
            'shop_state' => $shop_state_name,
            'shop_call_number' => $shop_phone,
            'shop_logo' => $anipo->get_shop_logo(),
            'customer_state' => $customer_state_name,
            'customer_city' => $customer_city,
            'customer_name' => $customer_name,
            'customer_postcode' => $customer_postcode,
            'customer_call_number' => $customer_call_number,
            'customer_address' => $customer_address,
            'products' => $products,
        ];
    }
}

return ANIPO_API::instance();
