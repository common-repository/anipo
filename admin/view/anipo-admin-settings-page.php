<?php

defined('ABSPATH') || exit;

global $anipo;

if (isset($_POST['submit']) && isset($_POST['anipo_settings_nonce']) && check_admin_referer('anipo_settings', 'anipo_settings_nonce')) {
    if (isset($_POST['print-factor-checkbox'])) {
        $is_checked = sanitize_text_field(wp_unslash($_POST['print-factor-checkbox'])) ?? 0;
    } else {
        $is_checked = 0;
    }
    $anipo->update_print_factor_setting($is_checked);
}

$is_checked = $anipo->get_print_factor_setting();

?>

<h1><?php esc_html_e('Settings Form', 'anipo') ?></h1>
<form id="settings-form" method="POST" action="">
    <label for="print-factor-checkbox"><?php esc_html_e('Show Factor In Print', 'anipo') ?> :</label>
    <input type="checkbox" id="print-factor-checkbox" name="print-factor-checkbox"
           onchange="anipoUpdateCheckboxValue(this)" <?php if (intval($is_checked) === 1) {
        echo 'checked';
    } ?> value="<?php echo esc_attr($is_checked) ?>">
    <?php
    wp_nonce_field('anipo_settings', 'anipo_settings_nonce');
    ?>
    <br><input type="submit" name="submit" class="anipo-button" value="<?php esc_attr_e('Submit', 'anipo'); ?>"/>
</form>
