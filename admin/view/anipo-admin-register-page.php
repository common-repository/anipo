<?php

defined('ABSPATH') || exit;

global $anipo;

$current_keyword = $anipo->get_keyword();

// Send the data to the API
$api_response = $anipo->first_connection_api($current_keyword);
$has_connection = false;
// Show the API response
if (!is_wp_error($api_response)) {
    $body = wp_remote_retrieve_body($api_response);
    $body = json_decode($body);
    if ($body->status) {
        $has_connection = true;
    }
}

if (trim($current_keyword) === '' && isset($_POST['api_key']) && isset($_POST['anipo_register_nonce']) && check_admin_referer('anipo_register', 'anipo_register_nonce')) {
    $current_keyword = sanitize_text_field(wp_unslash($_POST['api_key']));
}

?>

<h1><?php esc_html_e('Register Form', 'anipo') ?></h1>
<form id="register-form" method="POST" action="">
    <label for="api_key"><?php esc_html_e('Enter the keyword', 'anipo'); ?> : </label>
    <input type="text" name="api_key" id="api_key" value="<?php echo esc_attr($current_keyword); ?>" required/>
    <?php
    wp_nonce_field('anipo_register', 'anipo_register_nonce');
    ?>
    <input type="submit" name="submit" class="anipo-button" value="<?php esc_attr_e('Connect', 'anipo'); ?>"/>
</form>

<?php

if (!isset($_POST['submit']) || !isset($_POST['anipo_register_nonce']) || !check_admin_referer('anipo_register', 'anipo_register_nonce')) {
    if ($has_connection) {
        echo '<div>' . esc_html_e('Connected', 'anipo') . '</div>';
    } else {
        echo '<div>' . esc_html_e('No Connection', 'anipo') . '</div>';
    }
}

// Check if the form is submitted
if (isset($_POST['submit']) && isset($_POST['anipo_register_nonce']) && check_admin_referer('anipo_register', 'anipo_register_nonce')) {
    // Get the input value
    $api_key = sanitize_text_field(wp_unslash($_POST['api_key']));

    // Send the data to the API
    $api_response = $anipo->first_connection_api($api_key);

    // Show the API response
    if (!is_wp_error($api_response)) {
        $body = wp_remote_retrieve_body($api_response);
        $body = json_decode($body);
        if ($body->status) {
            $anipo->update_keyword($api_key);
            $anipo->update_shop_call_number($body->data->mobile);
        }
        echo '<div>' . esc_html($body->message) . '</div>';
    } else {
        echo '<div>' . 'Failed To Connect' . '</div>';
    }
}
?>
