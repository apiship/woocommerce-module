=== WP ApiShip for WooCommerce ===
Contributors: apiship
Tags: shipping, delivery, woocommerce, shipping calculator, pickup point
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.8.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Official ApiShip integration for WooCommerce: real-time shipping rates, pickup points and order delivery management for 40+ delivery services.

== Description ==

**ApiShip** is a delivery integration platform for e-commerce. This plugin connects your WooCommerce store to the ApiShip platform, so your customers get a wide choice of delivery methods while you manage the whole delivery workflow from the WordPress admin panel.

* Real-time shipping cost calculation for 40+ delivery services (couriers and pickup points), taking dimensions, weight and personal rates into account
* Pickup point selection on the checkout page with a map (Yandex.Maps)
* Automatic order submission to the delivery service
* Delivery status tracking synchronized with WooCommerce order statuses
* Shipping labels and acceptance certificates printing from the admin panel
* High-Performance Order Storage (HPOS) support

The full list of supported delivery services: https://apiship.ru/couriers

An ApiShip account is required. The checkout page must use the classic checkout shortcode `[woocommerce_checkout]` (Checkout Blocks are not supported).

= Русское описание =

Официальный модуль платформы ApiShip для WooCommerce: расчёт стоимости доставки в реальном времени, выбор пункта выдачи на карте, передача заказов в службы доставки, отслеживание статусов и печать наклеек — для 40+ служб доставки по прямым договорам.

== External services ==

This plugin relies on the following third-party services. Data is transmitted only in the circumstances described below.

**ApiShip API (api.apiship.ru)**

The plugin is an integration with the ApiShip delivery platform and sends requests to `https://api.apiship.ru/` whenever it calculates shipping rates, loads pickup point lists, creates/cancels delivery orders, requests shipping labels or synchronizes delivery statuses. The data sent includes order contents (dimensions, weight, cost), delivery addresses and recipient contact details — this is required to arrange the delivery. When the test mode is enabled in the plugin settings, the same requests go to the ApiShip development server `https://api.dev.apiship.ru/`. Delivery service icons are loaded from `https://storage.apiship.ru/`.

* Service: https://apiship.ru/
* API documentation: https://docs.apiship.ru/ and https://api.apiship.ru/doc/
* Terms of service (public offer): https://storage.apiship.ru/docs/offer.pdf

**Yandex.Maps JavaScript API (api-maps.yandex.ru)**

The pickup point selection map on the checkout page and on the admin order page is rendered with the Yandex.Maps JavaScript API loaded from `https://api-maps.yandex.ru/`. The visitor's browser requests map tiles and geodata directly from Yandex when the map is opened.

* Service: https://yandex.ru/maps-api/
* Terms of use: https://yandex.ru/legal/maps_api/
* Privacy policy: https://yandex.ru/legal/confidential/

== Source code ==

All JavaScript and CSS files are shipped in this plugin in both minified (`*.min.js`, `*.min.css`) and human-readable non-minified form. The non-compressed sources are located next to the minified files in the `assets/js/` and `assets/css/` directories of the plugin.

The complete source code of the plugin is also publicly available and maintained at:

https://github.com/apiship/woocommerce-module

No build tools are required: the minified assets are produced from the adjacent source files by standard JS/CSS minification.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory or install it via the WordPress admin panel.
2. Activate the plugin (WooCommerce must be installed and active).
3. Get an API token in your ApiShip account: https://a.apiship.ru/
4. Enter the token on the WooCommerce → Settings → ApiShip tab and configure providers and tariffs.
5. Add the "ApiShip integrator" shipping method to your shipping zones.

== Frequently Asked Questions ==

= Do I need an ApiShip account? =

Yes. The plugin transmits orders through the ApiShip platform, so a contract with ApiShip (or direct contracts with delivery services connected through ApiShip) is required. Sign up at https://a.apiship.ru/.

= Does the plugin support the block-based checkout? =

No. The pickup point selection works with the classic checkout shortcode `[woocommerce_checkout]` only.

== Changelog ==

= 1.8.0 =
* WordPress.org plugin review fixes.
* Text domain changed to `apiship` to match the plugin slug.
* PHP identifiers renamed: `WP_ApiShip*` → `ApiShip*` (namespace, classes, constants).
* All output is escaped (esc_html/esc_attr/esc_url/wp_kses); tariff JSON is no longer printed unescaped.
* Input sanitization: targeted reading of `$_POST` fields instead of processing the whole request.
* Unlimited `set_time_limit(0)` calls removed or bounded inside the specific long-running functions.
* Test API endpoint switched to HTTPS.
* `ABSPATH` guard added to all template files.
* readme.txt added: external services and non-minified sources are documented.

= 1.7.1 =
* HPOS compatibility restored (order meta box, scripts, orders list column, bulk actions).
* Fatal activation error fixed; initialization moved to `plugins_loaded`.
* Security hardening: SQL injection fix in the status handler, XSS fixes, AJAX capability checks, secure cookie flags.
* Compatibility with WordPress 7.0 / WooCommerce 10.9 / PHP 8.4.

= 1.7.0 =
* WooCommerce HPOS (High-Performance Order Storage) support with data migration.

Full changelog: https://github.com/apiship/woocommerce-module/blob/main/CHANGELOG.md
