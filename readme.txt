=== ABN Field for WooCommerce ===
Contributors: businessapi
Tags: abn, australia, checkout, billing, woocommerce
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds an Australian Business Number (ABN) field to the WooCommerce billing address and checks it with the official ABN checksum.

== Description ==

Customers with an Australian billing address can enter their ABN at checkout. The field is hidden for other countries.

* Works with the classic checkout and the Checkout block.
* Accepts spaces, dashes and dots, then checks the 11 digits against the official ABN checksum.
* Saved in your chosen format on the order and the customer.
* Customers can change it in My Account > Addresses > Billing address.
* Shown under the billing address on the admin order screen (where you can edit it), in order emails, on the thank-you page and in My Account.

= Settings =

WooCommerce > Settings > General > ABN field:

* Required: No, Yes, or Only when a company name is entered (default).
* Format saved: Digits only (51824753556) or Spaced (51 824 753 556, default).
* Label: defaults to "ABN".

= Privacy =

The ABN is stored only in your WooCommerce store, as order and customer meta. The plugin makes no external requests and collects no data.

Selling registrations to your clients? See [BAPI Forms](https://wordpress.org/plugins/bapi-forms/).

== Frequently Asked Questions ==

= Does it work with PDF invoice and export plugins? =

The ABN is saved in the standard billing meta keys `_billing_abn` (order) and `billing_abn` (customer), and is part of the formatted billing address. Most invoice and export plugins pick up one or the other. With the Checkout block the value is copied to `_billing_abn` as well.

= Does it check the ABN with the Australian Business Register? =

No. It runs the official checksum offline. That catches typos, but not ABNs that are cancelled or belong to someone else.

= Which formats are accepted? =

11 digits, with or without spaces, dashes or dots, such as 51 824 753 556, 51-824-753-556 or 51824753556. It is saved as digits only or spaced, depending on the setting.

= Why does the ABN also appear in the shipping address on the Checkout block? =

WooCommerce adds address fields to both address forms. With "Use same address for billing" ticked only the shipping form is shown, so the ABN has to be there. The billing value is the one that is saved and displayed.

= My Checkout block is not on the WooCommerce checkout page =

The plugin uses the Checkout block field when the checkout page holds the block. To force it, add `add_filter( 'abnf_use_block_field', '__return_true' );`.

= What is removed when I delete the plugin? =

Only its settings. ABNs saved on orders and customers are kept.

== Screenshots ==

1. ABN field in the billing address at checkout.
2. Settings in WooCommerce > Settings > General.
3. ABN under the billing address on the order screen.

== Changelog ==

= 1.0.0 =
* First release.
