=== Anipo ===
Contributors: anipo1403
Tags: anipo, woocommerce, post, barcode, orders
Requires at least: 6.6
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Anipo adds two columns to WooCommerce orders table, one for getting and printing order barcode and the other for tracking barcode.

== Description ==
Anipo is a plugin designed to add barcode tracking functionality to your shop. It enhances the WooCommerce orders table by adding two new columns: "Get Barcode" and "Barcode." Clicking on the "Get Barcode" button opens a modal where you can enter or edit the order weight before generating the barcode tracking link. The generated barcode is a clickable link that opens in a new tab for tracking purposes.

**Key Features:**
- Adds "Get Barcode" and "Barcode" columns to WooCommerce orders page.
- Clicking the "Get Barcode" button opens a modal to fetch or edit the barcode and order weight.
- Allows admins to modify the order weight before generating the barcode.
- Displays a clickable barcode link that opens in a new tab for tracking.
- Adds barcode links to both the admin and customer order details pages.

This plugin is perfect for shop owners who need to handle barcode tracking and also adjust order weights before generating barcodes. Admins can easily generate and edit order details within the WooCommerce orders page.

**Requirements:**
- WordPress 6.6 or higher
- PHP 7.4 or higher
- WooCommerce plugin installed and activated

== Installation ==
1. Upload the `anipo` folder to the `/wp-content/plugins/` directory, or install it through the WordPress plugin screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Ensure WooCommerce is installed and activated, as Anipo requires WooCommerce to function.
4. Go to the WooCommerce Orders section to see the new "Get Barcode" and "Barcode" columns.
5. Click on "Get Barcode" to open the modal for editing the order weight if needed and getting barcode.

== Frequently Asked Questions ==

= Does Anipo work without WooCommerce? =
No, Anipo requires WooCommerce to be installed and activated for the barcode tracking and order weight functionality to work.

= What happens when I click on "Get Barcode"? =
When you click the "Get Barcode" button in the WooCommerce orders table, a modal opens where you can enter or edit the order weight before generating the barcode. The generated barcode is then displayed as a clickable tracking link in the "Barcode" column.

= Can I edit the order weight before getting the barcode? =
Yes, you can modify the order weight in the modal before generating the barcode tracking link. This allows you to update order details as needed.

= Can both admins and customers see the barcode link? =
Yes, the barcode tracking link is available on both the WooCommerce order details page for admins and the customer order details page for easy tracking.

= Does this plugin send data to an external service? =
Yes, this plugin interacts with the Anipo API (https://panel.anipo.ir/backend/api/) to send orders data to Anipo panel, generate barcodes, and fetch plugin-related data. When you perform certain actions like generating a barcode or sending order information, the plugin sends data such as order details to the Anipo API.

= Where can I find the privacy policy and terms of service for Anipo? =
- Anipo Privacy Policy: [https://anipo.ir/rules/]
- Anipo Terms of Service: [https://anipo.ir/rules/]

== Screenshots ==
1. **WooCommerce Orders Page with Barcode Columns** – Displays the "Get Barcode" and "Barcode" columns added by Anipo to the WooCommerce orders screen.
2. **Modal for Getting Barcode and Editing Order Weight** – Shows the modal where admins can fetch the barcode and update order weight before generating the barcode link.
3. **Admin Order Details Page with Barcode Link** – Displays the clickable barcode link on the order details page for tracking in a new tab.
4. **Customer Order Details Page with Barcode Link** – Shows the clickable barcode link on the customer's order details page for easy tracking.

== Changelog ==

= 1.0.0 =
* Initial release of Anipo.
* Added functionality to generate barcode tracking links in WooCommerce.
* Added "Get Barcode" and "Barcode" columns to the WooCommerce orders page.
* Included modal for getting barcode and editing order weight before generating barcode link.
= 1.0.1 =
* morilog/jalali package bug fixed
= 1.0.2 =
* vendor/symfony bug fixed

== Upgrade Notice ==
= 1.0.0 =
First release of Anipo. No special upgrade instructions are required for this version.
= 1.0.1 =
This update includes bug fix
= 1.0.2 =
This update includes bug fix

== License ==
This plugin is licensed under the GPLv2 or later.
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== External Libraries ==
This plugin relies on the following external libraries:

1. [morilog/jalali](https://github.com/morilog/jalali) - This library is used to handle Persian (Jalali) date conversions.
   - License: [MIT License](https://github.com/morilog/jalali?tab=MIT-1-ov-file)

The `morilog/jalali` package is bundled with this plugin to allow for seamless conversion of dates into the Persian (Jalali) calendar system. No data is sent to third-party servers, as this package only runs locally within your WordPress environment.
