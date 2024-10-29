<?php

defined('ABSPATH') || exit;

?>

<div class="anipo-modal anipo-modal-overlay" id="anipo-barcode-modal" style="display: none;">
    <div class="anipo-modal-content">
        <h3><?php esc_html_e('Barcode Form', 'anipo'); ?></h3>
        <form id="custom-form" class="anipo-modal-form">
            <label for="order-weight"><?php esc_html_e('Order Weight (grams)', 'anipo'); ?></label>
            <input type="text" id="order-weight" name="order-weight"/>
            <input type="hidden" name="order-id" id="order-id"/>
            <button type="submit"
                    class="anipo-button anipo-get-barcode-button"><?php esc_html_e('Get Barcode', 'anipo'); ?></button>
            <button class="anipo-button anipo-close-modal"><?php esc_html_e('Close', 'anipo'); ?></button>
        </form>
    </div>
</div>
