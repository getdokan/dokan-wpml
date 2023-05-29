=== Dokan WPML ===
Contributors: wedevs, sabbir1991, rafsuntaskin, alamgircsebd
Tags: WPML, i18n, l10n, Translation, Dokan
Donate link: https://tareq.co/donate
Requires at least: 5.4.0
Tested up to: 6.2.2
WC requires at least: 5.5.0
WC tested up to: 7.7.0
Requires PHP: 7.2
Stable tag: 1.0.7
License: GPL v2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WPML integration for Dokan Multivendor Plugin

== Description ==
Dokan Multivendor is based on the famous eCommerce solution WooCommerce. To enable multiple language feature WPML is the most reliable solution. These 5 extensions below is required to translate a WooCommerce store-

1. [Sitepress Multilingual](https://wpml.org/?aid=106335&affiliate_key=EbXH25fvBE9b)
2. WooCommerce Multilingual
3. WPML Translation Management
4. WPML Media
5. WPML String Translation

You can purchase and download all these plugin from the same site. Some of these are free!

After purchasing all these extensions and installing, you need to install this extension. It will not work unless you have activated all the plugins mentioned above.

Please remember to set the URL structure to `site.com/lang`.

Dokan does not support URL parameters. So you can NOT set the URL structure to be `site.com?lang=nl`

This extension does not have any settings. Everything is controlled from WPML settings page.

== Installation ==
1. Install and activate Sitepress Multilingual, WooCommerce Multilingual, WPML Translation Management, WPML Media, WPML String Translation plugin.
2. Configure WPML and WooCommerce Multilingual. [You can read this documentation for details](https://wpml.org/documentation/related-projects/woocommerce-multilingual/).
3. Install and activate Dokan WPML Integration plugin.
4. Navigate to – WP Dashboard → WPML → Languages → Language  URL Format. Set the translation link structure to sub-directories. That means the translation link for french would be `site.com/fr`.
5. Create translation of your pages and products and you are done!

== Frequently Asked Questions ==
1. Will this plugin translate everything automatically?
Ans: No. All these plugins enables the system to work across all the languages you want to use. You have to create each post and page manually and translate each sentence and word manually.

2. Is there an easy way to translate all my content?
Ans: Yes, you can contact the WPML team. They offer translation service via third parties.

== Screenshots ==

nothing here

== Changelog ==

v1.0.7 -> May 29, 2023
---------------------------
- [update] Updated compatibility with WordPress 6.2.2
- [update] Updated compatibility with WooCommerce 7.7.0
- [update] Updated compatibility with Dokan 3.7.19
- [new] Added two new methods (remove_url_translation and restore_url_translation) to reset home url translation
- [fix] Fixed endpoints with second argument wasn't working for translated languages, eg: mysite.com/support-tickets/10

v1.0.6 -> October 7, 2021
---------------------------
- [new] Add vendor capability for WooCommerce WPML configuration

v1.0.5 -> October 4, 2021
---------------------------
- [new] Re-build the URL with the translated endpoint
- [new] Filter the dashboard settings key to properly display the settings

v1.0.4 -> May 5, 2020
---------------------------
 - [new] Load store page language switcher filter support

v1.0.3 -> December 03, 2020
---------------------------
 - [fix] Vendor dashboard page all links coming 404 error fixed
 - [fix] Vendor dashboard all url slug missing on url issue fixed
 - [fix] Fatal error when dokan plugin deactivated
 - [fix] Improve coding structure

v1.0.2 -> November 23, 2019
---------------------------
 - [Fix]   Color customizer and order page is not loading
 - [Fix]   Undefined function calling error
 - [Fix]   wpml is not working when string translation is enabled
 - [Tweak] Add get_raw_option functions for getting raw value from database

v1.0.1 -> June 20, 2019
------------------------
 - [Fix]   My account page redirecting issue
 - [Fix]   Dokan term and condition page url fixed
 - [Fix]   Fix js issue when wpml activate in dokan core
 - [Tweak] Show error notice if WPML is not activated

v1.0 -> Jan 27, 2017
-----------------------
- Initial Release