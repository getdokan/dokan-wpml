=== Dokan WPML ===
Contributors: wedevs, dokaninc
Tags: WPML, i18n, l10n, Translation, Dokan
Donate link: https://tareq.co/donate
Requires at least: 6.5
Tested up to: 6.7.2
WC requires at least: 8.5.0
WC tested up to: 9.7.0
Requires PHP: 7.4
Stable tag: 1.1.10
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

v1.1.9 -> Feb 28, 2025
---------------------------
- **fix:** Product status count fix for vendor dashboard.
- **update:** Added support for Dokan single store custom endpoint translation.
- **update:** Shipping method title translate support added.
- **update:** Vendor store url additional endpoint translation added.

v1.1.8 -> Feb 11, 2025
---------------------------
- **fix:** Added translation support for vendor verification and biography messages
- **update:** Added translation for vendor store URL additional endpoint

v1.1.7 -> Nov 11, 2024
---------------------------
- **fix:** Widget and verification page help text translate issue fixed
- **update:** Add Dokan URL translation pause & resume functionalities.
- **update:** Add Printful url translation with settings query vars map.

v1.1.6 -> Aug 28, 2024
---------------------------
- **update:** Vendor Verification Method Translation Support added.

v1.1.5 -> Jul 10, 2024
---------------------------
- **new:**  Added string translation configuration for dokan dashboard menu manager.

v1.1.3 -> Jun 13, 2024
---------------------------
- **update:** Dokan Product meta-key set to copy in WPML
- **fix:** Store category counts sync for multiple language

v1.1.2 -> Apr 01, 2024
---------------------------
- **update:** Vendor Subscription remaining product count WPML Support

v1.1.1 -> Mar 13, 2024
---------------------------
- **update:** Vendor Dashboard Settings submenu translation Support added
- **update:** Allowed category in vendor subscription translation support added

v1.1.0 -> Mar 03, 2024
---------------------------

- **update:** single string translation support added for Shipping Status, RMA Reason, Abuse Report Reason #44
- **update:** Vendor Subscription products various custom field value sync with Translation
- **update:** Added Dokan Vendor Subscription WPML Support
- **update:** Preserve query params on WPML Language switch

v1.0.10 -> Jan 23, 2024
---------------------------
- **fix:** Translation-Related Issues with Specific Menu Items in Vendor Dashboard with WPML
- **fix:** Woocommerce error notice when Dokan WPML Integration plugin is active
- **fix:** PHP warning creation of dynamic property

v1.0.9 -> Dec 12, 2023
---------------------------
- **new:** added a new filter named `dokan_get_translated_page_id` support added. With it, we will be able to get translated Page id from any Page ID.
- **update:** WordPress Version 6.4.2 compatibility added.
- **update:** WooCommerce Version 8.2.2 compatibility added.

v1.0.8 -> Jun 08, 2023
---------------------------
- **fix:** When the admin disables vendors using one language the vendor product still shows on the shop page after switching to another language.
- **fix:** Store category filter does not work for the secondary language.
- **fix:** The secondary language products are not being deleted after deleting a product from the primary language.
- **fix:** Fixed a issue when we are using dokan_get_navigation_url() function to get any dashboard link, it gives translated page URL instead of the default language dashboard page URL.

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
