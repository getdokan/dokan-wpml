<?php
/**
 * Plugin Name: Dokan - WPML Integration
 * Plugin URI: https://wedevs.com/
 * Description: WPML and Dokan compatible package
 * Version: 1.1.10
 * Author: weDevs
 * Author URI: https://wedevs.com/
 * Text Domain: dokan-wpml
 * WC requires at least: 8.5.0
 * WC tested up to: 9.7.0
 * Domain Path: /languages/
 * License: GPL2
 */

/**
 * Copyright (c) YEAR weDevs (email: info@wedevs.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

use WeDevs\DokanPro\Modules\VendorVerification\Models\VerificationMethod;

defined( 'ABSPATH' ) || exit;

/**
 * Dokan_WPML class
 *
 * @class Dokan_WPML The class that holds the entire Dokan_WPML plugin
 */
class Dokan_WPML {

    /*
     * WordPress Endpoints text domain
     *
     * @var string
     */
    public  $wp_endpoints = 'WP Endpoints';

    /*
     * Appsero client
     *
     * @var string
     */
    protected $insights;

    /**
     * Constructor for the Dokan_WPML class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses register_activation_hook()
     * @uses register_deactivation_hook()
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {
        register_activation_hook( __FILE__, [ $this, 'dependency_missing_notice' ] );

        // Localize our plugin
        add_action( 'init', [ $this, 'localization_setup' ] );

		// load all actions and filter under plugins loaded hooks
	    add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
    }

    /**
     * Initializes the Dokan_WPML() class
     *
     * Checks for an existing Dokan_WPML() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new Dokan_WPML();
        }

        return $instance;
    }

	/**
	 * Execute on plugis loaded hooks
	 *
	 * @since 1.0.7 moved from constructor to plugins_loaded hook
	 *
	 * @return void
	 */
	public function plugins_loaded() {
		if ( true !== $this->check_dependency() ) {
			return;
		}

		// load appsero tracker
		$this->appsero_init_tracker();
        add_action( 'before_woocommerce_init', [ $this, 'declare_woocommerce_feature_compatibility' ] );

		// Load all actions hook
		add_filter( 'dokan_forced_load_scripts', [ $this, 'load_scripts_and_style' ] );
		add_filter( 'dokan_force_load_extra_args', [ $this, 'load_scripts_and_style' ] );
		add_filter( 'dokan_seller_setup_wizard_url', [ $this, 'render_wmpl_home_url' ], 70 );
		add_filter( 'dokan_get_page_url', [ $this, 'reflect_page_url' ], 10, 4 );
		add_filter( 'dokan_get_terms_condition_url', [ $this, 'get_terms_condition_url' ], 10, 2 );
		add_filter( 'dokan_redirect_login', [ $this, 'redirect_if_not_login' ], 90 );
		add_filter( 'dokan_force_page_redirect', [ $this, 'force_redirect_page' ], 90, 2 );
        add_action( 'init', [ $this, 'handle_flushing_rewrite_rules' ] );

		// Load all filters hook
        add_filter('sanitize_user_meta_product_package_id', [ $this, 'set_subscription_pack_id_in_base_language' ], 10, 3 );
        add_filter('dokan_vendor_subscription_package_title', [ $this, 'vendor_subscription_pack_title_translation' ], 10, 2 );
        add_filter('dokan_vendor_subscription_package_id', [ $this, 'get_product_id_in_base_language' ] );
		add_filter( 'dokan_get_navigation_url', [ $this, 'load_translated_url' ], 10, 2 );
		add_filter( 'body_class', [ $this, 'add_dashboard_template_class_if_wpml' ], 99 );
		add_filter( 'dokan_get_current_page_id', [ $this, 'dokan_set_current_page_id' ] );
		add_filter( 'dokan_get_translated_page_id', [ $this, 'dokan_get_translated_page_id' ] );
		add_action( 'wp_head', [ $this, 'dokan_wpml_remove_fix_fallback_links' ] );
        add_filter( 'dokan_get_store_url', [ $this, 'handle_store_url_translation' ], 5, 4 );

		add_action( 'dokan_store_page_query_filter', [ $this, 'load_store_page_language_switcher_filter' ], 10, 2 );
		add_filter( 'dokan_dashboard_nav_settings_key', [ $this, 'filter_dashboard_settings_key' ] );
		add_filter( 'dokan_dashboard_nav_menu_key', [ $this, 'filter_dashboard_settings_key' ] );
		add_filter( 'dokan_dashboard_nav_submenu_key', [ $this, 'filter_dashboard_settings_key' ] );
		add_filter( 'wcml_vendor_addon_configuration', [ $this, 'add_vendor_capability' ] );
        add_filter('icl_lang_sel_copy_parameters', [ $this, 'set_language_switcher_copy_param' ] );
        add_filter( 'dokan_vendor_subscription_product_count_query', [ $this, 'set_vendor_subscription_product_count_query' ],10 ,3 );
        add_action( 'dokan_rewrite_rules_loaded', [ $this, 'register_custom_endpoint'] );

		add_action( 'init', [ $this, 'fix_store_category_query_arg' ], 10 );
		add_action( 'init', [ $this, 'load_wpml_admin_post_actions' ], 10 );
		add_action( 'dokan_product_change_status_after_save', [ $this, 'change_product_status' ], 10, 2 );
		add_action( 'dokan_product_status_revert_after_save', [ $this, 'change_product_status' ], 10, 2 );

        add_filter( 'dokan_count_posts_args', [ $this, 'add_language_query_args' ] );
        add_filter( 'dokan_all_products_query', [ $this, 'add_language_query_args' ] );
        add_filter( 'dokan_count_posts', [ $this, 'add_language_filter_for_dokan_count_posts' ], 10, 4 );
        add_filter( 'dokan_count_stock_posts_cache_key', [ $this, 'add_language_query_args_to_post_stock_count' ], 10, 4 );
        add_filter( 'dokan_count_posts_instock', [ $this, 'add_language_query_args_dokan_count_posts_stock_status' ], 10, 5 );
        add_filter( 'dokan_count_posts_outofstock', [ $this, 'add_language_query_args_dokan_count_posts_stock_status' ], 10, 5 );

        // Single string translation.
        add_action( 'dokan_pro_register_shipping_status', [ $this, 'register_shipping_status_single_string' ] );
        add_action( 'dokan_pro_register_abuse_report_reason', [ $this, 'register_abuse_report_single_string' ] );
        add_action( 'dokan_pro_register_rms_reason', [ $this, 'register_rma_single_string' ] );
        add_filter( 'dokan_pro_shipping_status', [ $this, 'get_translated_shipping_status' ] );
        add_filter( 'dokan_pro_abuse_report_reason', [ $this, 'get_translated_abuse_report_reason' ] );
        add_filter( 'dokan_pro_subscription_allowed_categories', [ $this, 'get_translated_allowed_categories' ] );
        add_filter( 'dokan_pro_rma_reason', [ $this, 'get_translated_rma_reason' ] );
        add_action( 'dokan_pro_vendor_verification_method_created', [ $this, 'register_vendor_verification_method' ] );
        add_action( 'dokan_pro_vendor_verification_method_updated', [ $this, 'register_vendor_verification_method' ] );
        add_filter( 'dokan_pro_vendor_verification_method_title', [ $this, 'get_translated_verification_method_title' ] );
        add_filter( 'dokan_pro_vendor_verification_method_help_text', [ $this, 'get_translated_verification_method_help_text' ] );

        // Hooks for manage URL translations with Dokan and WPML.
        add_action( 'dokan_disable_url_translation', [ $this, 'disable_url_translation' ] );
        add_action( 'dokan_enable_url_translation', [ $this, 'enable_url_translation' ] );

        add_action( 'wp', [ $this, 'set_translated_query_var_to_default_query_var' ], 11 );
        add_action( 'wp', [ $this, 'set_custom_store_query_var' ], 11 );

        add_filter( 'dokan_set_store_categories', [ $this, 'set_translated_category' ] );
        add_filter( 'dokan_get_store_categories_in_vendor', [ $this, 'get_translated_category' ] );

        // shipping methods
        add_action( 'dokan_shipping_method_title_update', [ $this, 'register_shipping_method_title' ], 10, 3 );

        add_filter( 'dokan_shipping_method_translatable_title', [ $this, 'get_translated_shipping_method_title' ], 10, 2 );

        add_action( 'dokan_vendor_vacation_message_updated', [ $this, 'dokan_vendor_vacation_message_updated' ], 10, 3 );
        add_action( 'dokan_vendor_vacation_message_schedule_updated', [ $this, 'dokan_vendor_vacation_message_updated' ], 10, 3 );
        add_filter( 'dokan_get_vendor_vacation_message', [ $this, 'get_translated_dokan_vendor_vacation_message' ], 10, 2 );

        add_action( 'dokan_vendor_biography_after_update', [ $this, 'dokan_vendor_biography_updated' ], 10, 3 );
        add_filter( 'dokan_get_vendor_biography_text', [ $this, 'get_translated_dokan_vendor_biography_text' ], 10, 2 );

        // Add URL translation support for language switcher.
        add_filter( 'wpml_ls_language_url', [ $this, 'filter_language_switcher_url' ], 99, 2 );

        // Single store endpoint translation support.
        add_action( 'dokan_after_saving_settings', [ $this, 'register_single_store_custom_endpoint' ], 10, 3 );
        add_action( 'dokan_rewrite_rules_loaded', [ $this, 'handle_translated_single_store_endpoint_rewrite' ] );
        add_action( 'wpml_st_add_string_translation', [ $this, 'handle_single_store_endpoint_translation_update' ] );

		add_action( 'dokan_product_delete', [ $this, 'before_product_delete' ] );
		add_action( 'dokan_product_bulk_delete', [ $this, 'before_product_delete' ] );
	}

	/**
	 * Initialize the plugin tracker
	 *
	 * @since 1.0.7
	 *
	 * @return void
	 */
	public function appsero_init_tracker() {
		$client = new \Appsero\Client( 'f7973783-e0d0-4d56-bbba-229e5581b0cd', 'Dokan - WPML Integration', __FILE__ );

		$this->insights = $client->insights();

		$this->insights->init_plugin();
	}

	/**
     * Print error notice if dependency not active
     *
     * @since 1.0.1
     *
     * @return void
     */
    public function dependency_missing_notice() {
        deactivate_plugins( plugin_basename( __FILE__ ) );

		$missing_dependency = $this->check_dependency();
        if ( is_wp_error( $missing_dependency ) ) {
            $message = '<div class="error"><p>' . $missing_dependency->get_error_message() . '</p></div>';
            wp_die( $message );
        }
    }

	/**
	 * Check if dependency is active
	 *
	 * @since 1.0.7
	 *
	 * @return WP_Error|bool
	 */
	public function check_dependency() {
		if ( ! class_exists( 'WeDevs_Dokan' ) ) {
            // translators: %1$s: opening anchor tag, %2$s: closing anchor tag
			$error = sprintf( __( '<b>Dokan - WPML Integration</b> requires %1$s Dokan plugin %2$s to be installed & activated!', 'dokan-wpml' ), '<a target="_blank" href="https://wedevs.com/products/plugins/dokan/">', '</a>' );
			return new WP_Error( 'doakn_wpml_dependency_missing', $error );
		}

		if ( ! class_exists( 'SitePress' ) ) {
            // translators: %1$s: opening anchor tag, %2$s: closing anchor tag
			$error = sprintf( __( '<b>Dokan - WPML Integration</b> requires %1$s WPML Multilingual CMS %2$s to be installed & activated!', 'dokan-wpml' ), '<a target="_blank" href="https://wpml.org/">', '</a>' );
			return new WP_Error( 'doakn_wpml_dependency_missing', $error );
		}

		return true;
	}

	/**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'dokan-wpml', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Redirect seller setup wizerd into translated url
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function render_wmpl_home_url( $url ) {
        $translated_url = apply_filters( 'wpml_home_url', $url );
        return add_query_arg( array( 'page' => 'dokan-seller-setup' ), $translated_url );
    }

    /**
     * Load custom wpml translated page url
     *
     * @since 1.0.0
     *
     * @param  string $url
     * @param  string $name
     *
     * @return string
     */
    public function load_translated_url( $url, $name ) {
        $current_lang = apply_filters( 'wpml_current_language', null );

        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $url;
        }

        if ( ! empty( $name ) ) {
            if ( $current_lang ) {
                $name_arr = explode( '/', $name );

                if ( isset( $name_arr[1] ) ) {
                    $name_arr = array_map( function ( $part ) use ( $current_lang ) {
                        $part = $this->get_default_query_var( $part );
                        return apply_filters( 'wpml_translate_single_string', $part, $this->wp_endpoints, $part, $current_lang );
                    }, $name_arr );
                    $name = implode( '/', $name_arr );
                } else {
                    $get_name = ( ! empty( $name_arr[0] ) ) ? $name_arr[0] : $name;
                    $name     = apply_filters( 'wpml_translate_single_string', $get_name, $this->wp_endpoints, $get_name, $current_lang );
                }
            }

            $url = $this->get_dokan_url_for_language( ICL_LANGUAGE_CODE, $name . '/' );

        } else {
            $url = $this->get_dokan_url_for_language( ICL_LANGUAGE_CODE );
        }

        return $url;
    }

	/**
	 * @param string $endpoint
	 *
	 * @return string
	 */
    private function translate_endpoint( $endpoint, $language = null ) {
    	return apply_filters( 'wpml_translate_single_string', $endpoint, $this->wp_endpoints, $endpoint, $language );
    }

    /**
     * Reflect page url
     *
     * @since 1.0.1
     *
     * @return string
     */
    public function reflect_page_url( $url, $page_id, $context, $subpage ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $url;
        }

        $page_id = wpml_object_id_filter( $page_id, 'page', true, ICL_LANGUAGE_CODE );

        $url = get_permalink( $page_id );

        if ( $subpage ) {
            $subpages = explode( '/', $subpage );
            $subpages = array_map(
                function ( $item ) {
                    return $this->translate_endpoint( $this->get_default_query_var( $item ), ICL_LANGUAGE_CODE );
                },
                $subpages
            );

            $subpage = implode( '/', $subpages );
            $url     = function_exists( 'dokan_add_subpage_to_url' ) ? dokan_add_subpage_to_url( $url, $subpage ) : $url;
        }

        return $url;
    }

    /**
     * Get terms and condition page url
     *
     * @since 1.0.1
     *
     * @return string
     */
    public function get_terms_condition_url( $url, $page_id ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $url;
        }

        $page_id = wpml_object_id_filter( $page_id, 'page', true, ICL_LANGUAGE_CODE );

        return get_permalink( $page_id );
    }

    /**
     * Redirect if not login
     *
     * @since 1.0.1
     *
     * @return string
     */
    public function redirect_if_not_login( $url ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $url;
        }

        $page_id      = wc_get_page_id( 'myaccount' );
        $lang_post_id = wpml_object_id_filter( $page_id, 'page', true, ICL_LANGUAGE_CODE );

        return get_permalink( $lang_post_id );
    }

    /**
     * Add language support to product query args so that cache can be managed.
     *
     * @param array $args product query args.
     *
     * @return array
     */
    public function add_language_query_args( $args ) {
        $args['lang'] = ICL_LANGUAGE_CODE;

        return $args;
    }

    /**
     * Vendor Product Count query override.
     *
     * @param mixed $result Previous result.
     * @param string $post_type post type, (product)
     * @param int $vendor_id Vendor ID.
     * @param array $exclude_product_types Product types that need to be excluded.
     *
     * @return array|mixed|object|stdClass[]|null
     */
    public function add_language_filter_for_dokan_count_posts( $result, $post_type, $vendor_id, $exclude_product_types ) {
        global $wpdb;

        if ( $result ) {
            return $result;
        }

        $exclude_product_types_text = "'" . implode( "', '", esc_sql( $exclude_product_types ) ) . "'";

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_status, COUNT( DISTINCT wpml_translations.trid ) AS num_posts FROM {$wpdb->posts} as posts
                            JOIN {$wpdb->prefix}icl_translations wpml_translations ON posts.ID = wpml_translations.element_id AND wpml_translations.element_type = CONCAT('post_', posts.post_type)
                            INNER JOIN {$wpdb->term_relationships} AS term_relationships ON posts.ID = term_relationships.object_id
                            INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy ON term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id
                            INNER JOIN {$wpdb->terms} AS terms ON term_taxonomy.term_id = terms.term_id
                            WHERE
                                term_taxonomy.taxonomy = 'product_type'
                            AND terms.slug NOT IN ({$exclude_product_types_text})
                            AND posts.post_type = %s
                            AND posts.post_author = %d
                                GROUP BY posts.post_status",
                $post_type,
                $vendor_id
            ),
            ARRAY_A
        );
    }

    /**
     * post_stock_count cache key language change support.
     *
     * @param string $cache_key Previous cache key.
     * @param string $post_type Post type.
     * @param int $user_id Vendor ID.
     * @param string $stock_type Stock Status (instock, outofstock)
     *
     * @return string
     */
    public function add_language_query_args_to_post_stock_count( $cache_key, $post_type, $user_id, $stock_type ) {
        return "count_stock_posts_{$user_id}_{$post_type}_{$stock_type}_lang_" . ICL_LANGUAGE_CODE;
    }

    /**
     * Language support added for dokan_count_posts_{stock_status}
     *
     * @param mixed $results Previous status.
     * @param string $post_type Post Type.
     * @param int $user_id Vendor ID.
     * @param string $stock_type Stock Type.
     * @param array $exclude_product_types Excluded product types.
     *
     * @return array|mixed|object|stdClass[]|null
     */
    public function add_language_query_args_dokan_count_posts_stock_status( $results, $post_type, $user_id, $stock_type, $exclude_product_types = [ 'booking', 'auction' ] ) {
        global $wpdb;
        $exclude_product_types_text = "'" . implode( "', '", esc_sql( $exclude_product_types ) ) . "'";

        if ( $results ) {
            return $results;
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.post_status, COUNT( DISTINCT wpml_translations.trid ) AS num_posts
                FROM {$wpdb->prefix}posts as p
                    INNER JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
                    JOIN {$wpdb->prefix}icl_translations wpml_translations ON p.ID = wpml_translations.element_id AND wpml_translations.element_type = CONCAT('post_', p.post_type)

                WHERE p.post_type = %s
                AND p.post_author = %d
                AND pm.meta_key   = '_stock_status'
                AND pm.meta_value = %s
                AND p.ID IN (
                    SELECT tr.object_id FROM {$wpdb->prefix}terms AS t
                    LEFT JOIN {$wpdb->prefix}term_taxonomy AS tt ON t.term_id = tt.term_taxonomy_id
                    LEFT JOIN {$wpdb->prefix}term_relationships AS tr ON t.term_id = tr.term_taxonomy_id
                    WHERE tt.taxonomy = 'product_type' AND t.slug NOT IN ({$exclude_product_types_text})
                )
                GROUP BY p.post_status",
                $post_type,
                $user_id,
                $stock_type
            ),
            ARRAY_A
        );
    }

    /**
     * Undocumented function
     *
     * @since 1.0.1
     *
     * @return bool
     */
    public function force_redirect_page( $flag, $page_id ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return false;
        }

        $lang_post_id = wpml_object_id_filter( $page_id, 'page', true, ICL_LANGUAGE_CODE );

        if ( is_page( $lang_post_id ) ) {
            return true;
        }

        return false;
    }

    /**
     * Handle Flushing Rewrite Rules.
     *
     * @since 1.1.9
     *
     * @return void
     */
    public function handle_flushing_rewrite_rules() {
        if ( ! get_transient( 'dokan_wpml_flush_rewrite_rules_needed' ) ) {
            return;
        }

        flush_rewrite_rules();
        delete_transient( 'dokan_wpml_flush_rewrite_rules_needed' );
    }

    /**
     * Filter dokan navigation url for specific language
     *
     * @since 1.0.0
     *
     * @param  string $language
     * @param  string $name
     *
     * @return string [$url]
     */
    public function get_dokan_url_for_language( $language, $name = '' ) {
        $post_id      = $this->get_raw_option( 'dashboard', 'dokan_pages' );
        $lang_post_id = '';

        if ( function_exists( 'wpml_object_id_filter' ) ) {
            $lang_post_id = wpml_object_id_filter( $post_id, 'page', true, $language );
        }

        if ( (int) $lang_post_id !== 0 ) {
            $url = get_permalink( $lang_post_id );
        } else {
            $url = apply_filters( 'wpml_home_url', get_option( 'home' ) );
        }

        if ( $name ) {
	        $urlParts         = wp_parse_url( $url );
	        $urlParts['path'] = $urlParts['path'] . $name;
	        $url              = http_build_url( '', $urlParts );
        }

        return $url;
    }

    /**
     * Set Language switcher copy param
     *
     * @since 1.0.11
     *
     * @param array $params Copy params.
     *
     * @return array
     */
    public function set_language_switcher_copy_param( $params ) {
        $dokan_params = [
            'product_listing_search',
            '_product_listing_filter_nonce',
            'product_search_name',
            'product_cat',
            'post_status',
            'date',
            'product_type',
            'pagenum',
            'product_id',
            'action',
            '_dokan_edit_product_nonce',
            'customer_id',
            'search',
            'order_date_start',
            'order_date_end',
            'order_status',
            'dokan_order_filter',
            'seller_order_filter_nonce',
            'order_id',
            '_wpnonce',
            'order_date',
            'security',
            'subscription_id',
            'coupons_type',
            'post',
            'view',
            'coupon_nonce_url',
            'delivery_type_filter',
            'chart',
            'start_date_alt',
            'start_date',
            'end_date',
            'end_date_alt',
            'dokan_report_filter_nonce',
            'dokan_report_filter',
            'comment_status',
            'type',
            '_withdraw_link_nonce',
            'status',
            'request',
            'staff_id',
            'booking_id',
            'booking_status',
            'calendar_month',
            'tab',
            'filter_bookings',
            'calendar_year',
            'id',
            'tab',
            'step',
            'file',
            'delimiter',
            'character_encoding',
            'products-imported',
            'products-imported-variations',
            'products-failed',
            'products-updated',
            'products-skipped',
            'file-name',
            'ticket_start_date',
            'ticket_end_date',
            'ticket_keyword',
            'ticket_status',
            'dokan-support-listing-search-nonce',
        ];

        return array_merge( $params, $dokan_params );
    }

    /**
     * Get vendor dashboard settings submenu query vars.
     *
     * @since 1.1.1
     *
     * @param string|null $lang
     *
     * @return array
     */
    public function get_translated_query_vars_map( $lang = null ): array {
        $query_vars     = [
            'store',
            'payment',
            'rma',
            'shipping',
            'social',
            'seo',
            'regular-shipping',
            'delivery-time',
            'product-addon',
            'payment-manage-dokan_razorpay',
            'payment-manage-dokan_razorpay-edit',
            'payment-manage-dokan_mangopay',
            'payment-manage-dokan_mangopay-edit',
            'payment-manage-dokan-paypal-marketplace',
            'payment-manage-dokan-paypal-marketplace-edit',
            'payment-manage-paypal',
            'payment-manage-paypal-edit',
            'payment-manage-skrill',
            'payment-manage-skrill-edit',
            'payment-manage-bank',
            'payment-manage-bank-edit',
            'payment-manage-dokan_stripe_express',
            'payment-manage-dokan_stripe_express-edit',
            'payment-manage-dokan-stripe-connect',
            'payment-manage-dokan-stripe-connect-edit',
            'payment-manage-dokan_custom',
            'payment-manage-dokan_custom-edit',
            'requested-quotes',
            'distance-rate-shipping',
            'table-rate-shipping',
            'verification',
            'printful',
            'toc',
            'biography',
            'reviews',
        ];

        $store_url = dokan_get_option( 'custom_store_url', 'dokan_general', 'store' );

        if ( ! isset( $query_vars[ $store_url ] ) ) {
            $query_vars[] = $store_url;
        }

        $query_vars = apply_filters( 'dokan_wpml_settings_query_var_map', $query_vars );

        $query_vars_map = [];

        foreach ( $query_vars as $query_var ) {
            if ( function_exists( 'icl_register_string' ) ) {
                try {
                    icl_register_string( $this->wp_endpoints, $query_var, $query_var );
                } catch ( Exception $e ) {
                    // Do nothing.
                }
            }

            $query_vars_map[ $query_var ] = $this->translate_endpoint( $query_var, $lang );
        }

        return $query_vars_map;
    }

    /**
     * Add Dokan Dashboard body class when change language
     *
     * @since 1.0.0
     *
     * @param array $classes
     */
    public function add_dashboard_template_class_if_wpml( $classes ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $classes;
        }

        global $post;

        if ( ! is_object( $post ) ) {
            return $classes;
        }

        $page_id         = $this->get_raw_option( 'dashboard', 'dokan_pages' );
        $current_page_id = wpml_object_id_filter( $post->ID, 'page', true, wpml_get_default_language() );

        if ( ( $current_page_id == $page_id ) ) {
            $classes[] = 'dokan-dashboard';
        }

        return $classes;
    }

    /**
     * Load All dashboard styles and scripts
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function load_scripts_and_style() {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return false;
        }

        global $post;

        if ( ! is_object( $post ) ) {
            return false;
        }

        $page_id         = (int) $this->get_raw_option( 'dashboard', 'dokan_pages' );
        $current_page_id = (int) wpml_object_id_filter( $post->ID, 'page', true, wpml_get_default_language() );

        if ( ( $current_page_id === $page_id ) || ( get_query_var( 'edit' ) && is_singular( 'product' ) ) ) {
            return true;
        }

        return false;
    }

    /**
     * Dokan set current page id
     *
     * @since 1.0.2
     *
     * @param  int page_id
     *
     * @return int
     */
    public function dokan_set_current_page_id( $page_id ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $page_id;
        }

        return wpml_object_id_filter( $page_id, 'page', true, wpml_get_default_language() );
    }

    /**
     * Dokan get translated page id.
     *
     * @since 1.0.9
     *
     * @param  int $page_id Page ID to be translated.
     *
     * @return int
     */
    public function dokan_get_translated_page_id( $page_id ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $page_id;
        }

        return wpml_object_id_filter( $page_id, 'page', true, ICL_LANGUAGE_CODE );
    }

    /**
     * Set store categories with default language to store.
     *
     * @since 1.1.3
     *
     * @param array $categories Store Categories.
     *
     * @return array
     */
    public function set_translated_category( $categories ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) || ! function_exists( 'wpml_get_active_languages' ) ) {
            return $categories;
        }

        $all_categories = [];
        $languages      = wpml_get_active_languages();

        foreach ( $categories as $store_cat_id ) {
            foreach ( $languages as $code => $language ) {
                $translated_cat_id = wpml_object_id_filter( $store_cat_id, 'store_category', true, $code );
                $all_categories[] = $translated_cat_id;
            }
        }

        return array_unique( $all_categories );
    }

    /**
     * Get store categories with current translation to store.
     *
     * @since 1.1.3
     *
     * @param WP_Term[] $categories Store Categories.
     *
     * @return array
     */
    public function get_translated_category( $categories ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $categories;
        }

        $category_ids = array_unique(
            array_map(
                function ( $store_cat ) {
                    return wpml_object_id_filter( $store_cat->term_id, 'store_category', true, null );
                    },
                $categories
            )
        );

        return array_map(
            function ( $category_id ) {
                return get_term( $category_id, 'store_category' );
            },
            $category_ids
        );
    }

    /**
     * Set Vendor Subscription product count query on based language.
     *
     * @since 1.1.1
     *
     * @param string $query Product Query.
     * @param int $vendor_id Vendor Id.
     * @param array $allowed_status Allowed Status.
     *
     * @return string
     */
    public function set_vendor_subscription_product_count_query( $query, $vendor_id, $allowed_status ) {
        global $wpdb;

        $status = "'" . implode( "','", $allowed_status ) . "'";

        return $wpdb->prepare(
            "SELECT count( DISTINCT wpml_translations.trid ) as total
                FROM {$wpdb->prefix}posts as posts
                    JOIN {$wpdb->prefix}icl_translations wpml_translations
                        ON posts.ID = wpml_translations.element_id
                        AND wpml_translations.element_type = CONCAT('post_', posts.post_type)
                WHERE 1=1
                  AND posts.post_type = 'product'
                  AND posts.post_author = %d
                  AND posts.post_status IN ( {$status} )
                  ",
            $vendor_id
        );
    }

    /**
     * Store Vendor Subscription pack in default language.
     *
     * @since 1.0.11
     *
     * @param mixed $meta_value Meta Value
     * @param string $meta_key Meta Key
     * @param string $object_type Object Type
     *
     * @return int
     */
    public function set_subscription_pack_id_in_base_language( $meta_value, $meta_key, $object_type ) {
        if ( 'product_package_id' !== $meta_key || 'user' !== $object_type ) {
            return $meta_value;
        }

        return $this->get_product_id_in_base_language( absint( $meta_value ) );
    }

    /**
     * Dokan get base product id from translated product id.
     *
     * @since 1.0.11
     *
     * @param int $product_id Product ID.
     *
     * @return int
     */
    public function get_product_id_in_base_language( $product_id ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $product_id;
        }

        $default_lang = apply_filters('wpml_default_language', null );

        return wpml_object_id_filter( $product_id, 'product', true, $default_lang );
    }

    /**
     * Get product id in current language.
     *
     * @since 1.0.11
     *
     * @param int $product_id Product ID.
     *
     * @return int
     */
    public function get_product_id_in_current_language( $product_id ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $product_id;
        }

        return wpml_object_id_filter( $product_id, 'product', true, ICL_LANGUAGE_CODE );
    }

    /**
     * Vendor Subscription pack title translation.
     *
     * @since 1.0.11
     *
     * @param string $title Title.
     * @param \WC_Product|bool $product Product.
     *
     * @return string
     */
    public function vendor_subscription_pack_title_translation( $title, $product ) {
        if ( ! $product || ! function_exists( 'wc_get_product' ) ) {
            return $title;
        }

        $product_id = $this->get_product_id_in_current_language( $product->get_id() );
        $product    = wc_get_product( $product_id );

        return $product ? $product->get_title() : $title;
    }

    /**
     * Get raw value from database
     *
     * @since  1.0.3
     *
     * @param  string $option
     * @param  string $section
     * @param  mixed $default
     *
     * @return mixed
     */
    public function get_raw_option( $option, $section, $default = '' ) {
        if ( ! class_exists( 'WPML_Multilingual_Options_Utils' ) ) {
            return dokan_get_option( $option, $section, $default );
        }

        global $wpdb;

        $util    = new WPML_Multilingual_Options_Utils( $wpdb );
        $options = $util->get_option_without_filtering( $section );

        return isset( $options[ $option ] ) ? $options[ $option ] : $default;
    }

    /**
     * Remove callback links with WPML on vendor dashboard
     *
     * @since 1.0.3
     *
     * @return void
     */
    public function dokan_wpml_remove_fix_fallback_links() {
        if ( function_exists( 'dokan_is_seller_dashboard' ) && ! dokan_is_seller_dashboard() ) {
            return;
        }

        if ( ! class_exists( 'WPML_Fix_Links_In_Display_As_Translated_Content' ) || ! function_exists( 'dokan_remove_hook_for_anonymous_class' ) ) {
            return;
        }

        dokan_remove_hook_for_anonymous_class( 'the_content', 'WPML_Fix_Links_In_Display_As_Translated_Content', 'fix_fallback_links', 99 );
    }

	/**
     * Load store page language switcher filter
     *
     * @since 1.0.4
     *
	 * @param \WP_query $query
	 * @param array     $store_info
     *
     * @return void
	 */
    public function load_store_page_language_switcher_filter( $query, $store_info ) {
		// This needs to be improved, I am probably missing a smarter way to get the current store URL.
		// Perhaps the current store URL could be included in the $store_info (2nd argument).
		$custom_store_url = dokan_get_option( 'custom_store_url', 'dokan_general', 'store' );
		$store_slug       = $query->get( $custom_store_url );
		$store_user       = get_user_by( 'slug', $store_slug );
		$store_url        = dokan_get_store_url( $store_user->ID );

		add_filter( 'wpml_ls_language_url', function( $url, $data ) use ( $store_url ) {
		    return apply_filters( 'wpml_permalink', $store_url, $data['code'] );
	    }, 10, 2 );
    }

    /**
	 * @param string $settings_key
	 *
	 * @return string
	 */
    public function filter_dashboard_settings_key( $settings_key ) {
    	return $this->translate_endpoint( $settings_key );
    }

    /**
     * Register custom endpoint translation support for store page.
     *
     * @since 1.1.8
     *
     * @param  string  $store_endpoint Store endpoint.
     *
     * @return void
     */
    public function register_custom_endpoint( string $store_endpoint ) {
        if ( ! function_exists( 'wpml_get_active_languages' ) ) {
            return;
        }

        add_rewrite_endpoint( 'store_single_custom_param', EP_PAGES );

        // Register custom endpoint for each language. This is required to make sure the endpoint is available in all languages.
        // eg: /store/store_slug/endpoint/
        foreach ( wpml_get_active_languages() as $code => $language ) {
            if ( $code === wpml_get_default_language() ) {
                continue;
            }

            $translated_store_endpoint = $this->translate_endpoint( $store_endpoint, $code );

            add_rewrite_rule( $translated_store_endpoint . '/([^/]+)/([^/]+)?$', 'index.php?' . $store_endpoint . '=$matches[1]&store_single_custom_param=$matches[2]', 'top' );
        }
        add_rewrite_rule( $store_endpoint . '/([^/]+)/([^/]+)?$', 'index.php?' . $store_endpoint . '=$matches[1]&store_single_custom_param=$matches[2]', 'top' );
    }

    /**
     * Translate store URL with endpoint support.
     *
     * @since 1.1.8
     *
     * @param  string  $url               URL.
     * @param  string  $custom_store_slug Store slug.
     * @param  int     $store_user_id     Store user ID.
     * @param  string  $tab               Tab or custom endpoint.
     *
     * @return string
     */
    public function handle_store_url_translation( string $url, string $custom_store_slug, $store_user_id, string $tab ): string {
        if ( empty( $store_user_id ) || empty( $custom_store_slug ) ) {
            return $url;
        }

        $tab = untrailingslashit( trim( $tab, " \n\r\t\v\0/\\" ) );

        $translated_store_slug = $this->translate_endpoint( $custom_store_slug );
        $translated_tab        = empty($tab) ? $tab: $this->translate_endpoint( $tab );
        $base_url              = home_url();

        $url_last_part = trim( str_replace( $base_url, '', $url ), '/' );

        $url_last_part_arr = explode( '/', $url_last_part );

        if ( count( $url_last_part_arr ) > 2 ) {
            $url_last_part_arr[0] = $translated_store_slug;
            $url_last_part_arr[2] = $translated_tab;
        } else {
            $url_last_part_arr[0] = $translated_store_slug;
        }

        return trailingslashit( $base_url ) . trailingslashit( implode( '/', $url_last_part_arr ) );
    }


    /**
     * Set translated query var to default query var for `store_single_custom_param` query var.
     *
     * @since 1.1.8
     *
     * @return void
     */
    public function set_custom_store_query_var() {
        global $wp;

        if ( empty( $wp->query_vars['store_single_custom_param'] ) ) {
            return;
        }

        $query_var_value = $wp->query_vars['store_single_custom_param'];
        $query_var_value = urldecode_deep( $query_var_value );

        $probable_default_query_var = $this->get_default_query_var( $query_var_value );

        if ( $probable_default_query_var === $query_var_value ) {
            return;
        }


        $wp->query_vars[ $probable_default_query_var ] = 'true';
        set_query_var( $probable_default_query_var, 'true' );
    }

	/**
	 * Add vendor capability for WooCommerce WPML
	 *
	 * @since 1.0.6
	 *
	 * @return array
	 */
	public function add_vendor_capability() {
		return [
			'vendor_capability' => 'seller',
		];
	}

	/**
	 * Remove home URL translation.
	 *
	 * @since 1.0.7
	 *
	 * @return void
	 */
	public static function remove_url_translation() {
		global $wpml_url_filters;

		if ( class_exists( 'WPML_URL_Filters' ) ) {
			remove_filter( 'home_url', [ $wpml_url_filters, 'home_url_filter' ], -10 );
			if ( $wpml_url_filters->frontend_uses_root() === true ) {
				remove_filter( 'page_link', array( $wpml_url_filters, 'page_link_filter_root' ), 1 );
			} else {
				remove_filter( 'page_link', array( $wpml_url_filters, 'page_link_filter' ), 1 );
			}
		}

		if ( function_exists( 'wpml_get_home_url_filter' ) ) {
			remove_filter( 'wpml_home_url', 'wpml_get_home_url_filter', 10 );
		}

		remove_filter( 'dokan_get_page_url', [ self::init(), 'reflect_page_url' ], 10 );
		remove_filter( 'dokan_get_navigation_url', [ self::init(), 'load_translated_url'], 10 );
	}

	/**
	 * Restore home URL translation.
	 *
	 * @since 1.0.7
	 *
	 * @return void
	 */
	public static function restore_url_translation() {
		global $wpml_url_filters;

		if ( class_exists( 'WPML_URL_Filters' ) ) {
			add_filter( 'home_url', [ $wpml_url_filters, 'home_url_filter' ], -10, 4 );
			if ( $wpml_url_filters->frontend_uses_root() === true ) {
				add_filter( 'page_link', array( $wpml_url_filters, 'page_link_filter_root' ), 1, 2 );
			} else {
				add_filter( 'page_link', array( $wpml_url_filters, 'page_link_filter' ), 1, 2 );
			}
		}

		if ( function_exists( 'wpml_get_home_url_filter' ) ) {
			add_filter( 'wpml_home_url', 'wpml_get_home_url_filter', 10 );
		}

		add_filter( 'dokan_get_page_url', [ self::init(), 'reflect_page_url' ], 10, 4 );
		add_filter( 'dokan_get_navigation_url', [ self::init(), 'load_translated_url' ], 10, 2 );
	}

    /**
     * Add tax_query arg in WP_User_Query used in dokan()->vendor->get_vendors()
     *
     * @since 1.0.8
     *
     * @param string[] $args
     *
     * @return void
     */
    public function fix_store_category_query_arg() {
        // return if dokan pro is not active or store category object is not available
        if (
            ! function_exists( 'dokan_is_store_categories_feature_on' ) ||
            ! dokan_is_store_categories_feature_on() ||
            ! empty( dokan_pro()->store_category ) ) {
            return;
        }

        // remove existing pre_user_query action
        remove_action( 'pre_user_query', [ dokan_pro()->store_category, 'add_store_category_query' ] );

        // translated store category slug was unicode encoded, so we needed to decode it to get the correct slug
        add_filter(
            'dokan_get_store_categories', function ( $store_categories ) {
                foreach ( $store_categories as &$category ) {
                    $slug             = urldecode( $category['slug'] ); // decode the percent encoding
                    $slug             = str_replace( '\\', '\\\\', $slug ); // escape the backslashes
                    $category['slug'] = json_decode( '"' . $slug . '"' ); // parse as JSON
                }

                return $store_categories;
            }
        );

        // add pre_user_query action with WPML filter
        add_action(
            'pre_user_query', function ( $wp_user_query ) {
                if ( ! empty( $wp_user_query->query_vars['store_category_query'] ) ) {
                    global $sitepress, $wpdb;

                    $current_language = wpml_get_current_language();
                    $sitepress->switch_lang( $sitepress->get_default_language() );
                    $store_category_query = new WP_Tax_Query( $wp_user_query->query_vars['store_category_query'] );
                    $clauses              = $store_category_query->get_sql( $wpdb->users, 'ID' );

                    $wp_user_query->query_fields = 'DISTINCT ' . $wp_user_query->query_fields;
                    $wp_user_query->query_from   .= $clauses['join'];
                    $wp_user_query->query_where  .= $clauses['where'];
                    $sitepress->switch_lang( $current_language );
                }
            }
        );
    }

	/**
	 * Load wpml post actions on frontend
	 *
	 * @since 1.0.8
	 *
	 * @return void
	 */
	public function load_wpml_admin_post_actions() {
		if ( is_admin() ) {
			return;
		}

		global $wpdb, $sitepress;

		if ( class_exists( 'WPML_Admin_Post_Actions' ) && method_exists( $sitepress, 'get_settings' ) ) {
			$settings = $sitepress->get_settings();
			$wpml_post_translations = new WPML_Admin_Post_Actions( $settings, $wpdb );
			$wpml_post_translations->init();
		}
	}

	/**
	 * Change product status if base product status is changed.
	 *
	 * @since 1.0.8
	 *
	 * @param WC_Product $product
	 * @param string $status
	 *
	 * @return void
	 */
	public function change_product_status( $product, $status ) {
		$type         = apply_filters( 'wpml_element_type', get_post_type( $product->get_id() ) );
		$trid         = apply_filters( 'wpml_element_trid', false, $product->get_id(), $type );
		$translations = apply_filters( 'wpml_get_element_translations', array(), $trid, $type );

		foreach ( $translations as $lang => $translation ) {
			if ( $translation->original ) {
				continue;
			}

			// get product id
			$translated_product = wc_get_product( $translation->element_id );
			if ( ! $translated_product ) {
				continue;
			}

			// set product status
			$translated_product->set_status( $status );
			$translated_product->save();
		}
	}

    /**
     * Register single string.
     *
     * @since 1.0.11
     *
     * @param string $context This value gives the string you are about to register a context.
     * @param string $name The name of the string which helps the translator understand what’s being translated.
     * @param string $value The string that needs to be translated.
     *
     * @return void
     */
    public function register_single_string( $context, $name, $value ) {
        do_action( 'wpml_register_single_string', $context, $name, $value );
    }

    /**
     * Get translated single string.
     *
     * @since 1.0.11
     *
     * @param string $original_value The string’s original value.
     * @param string $domain The string’s registered domain.
     * @param string $name The string’s registered name.
     * @param $language_code
     *
     * @return string
     */
    public function get_translated_single_string( $original_value, $domain, $name, $language_code = null ) {
        return apply_filters( 'wpml_translate_single_string', $original_value, $domain, $name, $language_code );
    }

    /**
     * Register shipping status single string.
     *
     * @since 1.0.11
     *
     * @param string $status Shipping Status.
     *
     * @return void
     */
    public function register_shipping_status_single_string( $status ) {
        $this->register_single_string( 'dokan', 'Dokan Shipping Status: ' . $status, $status );
    }

    /**
     * Register abuse report single string.
     *
     * @since 1.0.11
     *
     * @param string $reason Abuse report reason.
     *
     * @return void
     */
    public function register_abuse_report_single_string( $reason ) {
        $this->register_single_string( 'dokan', 'Dokan Abuse Reason: ' . $reason, $reason );
    }

    /**
     * Register RMA reason single string.
     *
     * @since 1.0.11
     *
     * @param string $reason RMA reason.
     *
     * @return void
     */
    public function register_rma_single_string( $reason ) {
        $this->register_single_string( 'dokan', 'Dokan Refund and Returns Reason: ' . $reason, $reason );
    }

    /**
     * Get translated shipping status.
     *
     * @since 1.0.11
     *
     * @param string $status Shipping Status.
     *
     * @return string
     */
    public function get_translated_shipping_status( $status ) {
        return $this->get_translated_single_string( $status, 'dokan', 'Dokan Shipping Status: ' . $status );
    }

    /**
     * Get translated abuse report reason.
     *
     * @since 1.0.11
     *
     * @param string $reason Abuse report reason.
     *
     * @return string
     */
    public function get_translated_abuse_report_reason( $reason ) {
        return $this->get_translated_single_string( $reason, 'dokan', 'Dokan Abuse Reason: ' . $reason );
    }

    /**
     * Get translated RMA reason.
     *
     * @since 1.0.11
     *
     * @param string $reason RMA reason.
     *
     * @return string
     */
    public function get_translated_rma_reason( $reason ) {
        return $this->get_translated_single_string( $reason, 'dokan', 'Dokan Refund and Returns Reason: ' . $reason );
    }

    /**
     * Disables Dokan URL translation temporarily.
     *
     * @since 1.1.7
     */
    public function disable_url_translation() {
        self::remove_url_translation();
    }

    /**
     * Enables Dokan URL translation temporarily.
     *
     * @since 1.1.7
     */
    public function enable_url_translation() {
        self::restore_url_translation();
    }

    /**
     * Get translated query variable and set default value.
     *
     * @since 1.1.1
     */
    public function set_translated_query_var_to_default_query_var() {
        global $wp;

        if ( empty( $wp->query_vars['settings'] ) ) {
            return;
        }
        $settings_query_var_value = $wp->query_vars['settings'];
        $settings_query_var_value = urldecode_deep( $settings_query_var_value );

        $wp->query_vars['settings'] = $this->get_default_query_var( $settings_query_var_value );
    }

    /**
     * Get default query variable from translated variable.
     *
     * @since 1.1.1
     *
     * @param string $query_var Query Variable.
     *
     * @return string
     */
    public function get_default_query_var( $query_var, $lang = null ) {
        $query_var_map     = $this->get_translated_query_vars_map( $lang );
        $default_query_var = array_search( $query_var, $query_var_map, true );

        if ( false === $default_query_var ) {
            return $query_var;
        }

        return $default_query_var;
    }

    /**
     * Add High Performance Order Storage Support
     *
     * @since 1.0.10
     *
     * @return void
     */
    public function declare_woocommerce_feature_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
        }
    }

    /**
     * Get translated allowed categories.
     *
     * @since 1.1.1
     *
     * @param array $cats Categories.
     *
     * @return array|int[]
     */
    public function get_translated_allowed_categories( $cats ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $cats;
        }

        return array_map(
            function ( $cat ) {
                return wpml_object_id_filter(
                    $cat,
                    'product_cat',
                    true,
                    ICL_LANGUAGE_CODE
                );
            },
            $cats
        );
    }

    /**
     * Register vendor vetification method for string translation.
     *
     * @since 1.1.6
     *
     * @param int $method_id Method ID.
     *
     * @return void
     */
    public function register_vendor_verification_method( int $method_id ) {
        if ( ! class_exists( VerificationMethod::class ) ) {
            return;
        }

        $method = new VerificationMethod( $method_id );
        $this->register_single_string( 'dokan', 'Dokan Vendor Verification Method Title: ' . $method->get_title(), $method->get_title() );
        $this->register_single_string( 'dokan', 'Dokan Vendor Verification Method Help Text: ' . $method->get_help_text(), $method->get_help_text() );
    }

    /**
     * Register shipping method title for string translation.
     *
     * @since 1.1.9
     *
     * @param string $title Shipping Method Title.
     * @param int $instance_id Shipping Method Instance ID.
     *
     * @return void
     */
    public function register_shipping_method_title( string $title, int $instance_id ){
        $this->register_single_string( 'dokan', 'Dokan Shipping Method Title: ' . $instance_id, $title );
    }

    /**
     * Get translated shipping method title.
     *
     * @since 1.1.9
     *
     * @param string $title Shipping Method Title.
     * @param int $instance_id Shipping Method Instance ID.
     *
     * @return string
     */
    public function get_translated_shipping_method_title ( string $title, int $instance_id ): string {
        return $this->get_translated_single_string( $title, 'dokan', 'Dokan Shipping Method Title: ' . $instance_id );
    }
    /**
     * Get translated Verification Method Title.
     *
     * @since 1.1.6
     *
     * @param string $title Verification Method Title.
     *
     * @return string
     */
    public function get_translated_verification_method_title( $title ) {
        return $this->get_translated_single_string( $title, 'dokan', 'Dokan Vendor Verification Method Title: ' . $title );
    }

    /**
     * Get translated Verification Method help text.
     *
     * @since 1.1.6
     *
     * @param string $help_text Verification Method help text.
     *
     * @return string
     */
    public function get_translated_verification_method_help_text( $help_text ) {
        return $this->get_translated_single_string( $help_text, 'dokan', 'Dokan Vendor Verification Method Help Text: ' . substr( $help_text, 0, 116 ) );
    }

    /**
     * Translate Vendor Vacation Message
     *
     * @param $text
     * @param $name
     *
     * @return void
     */
    public function dokan_vendor_vacation_message_updated($text, $name) {
        $this->register_single_string(
            'dokan',
            'Vendor Vacation Message: ' . $name,
            $text
        );
    }

    /**
     * Translated Vendor Vacation Message
     *
     * @param string $text
     * @param $name
     *
     * @return string
     */
    public function get_translated_dokan_vendor_vacation_message(string $text , $name) {
        return $this->get_translated_single_string( $text, 'dokan', 'Vendor Vacation Message: '.$name );
    }


    /**
     * Translate Vendor Biography Text
     *
     * @param $store_info array
     * @param $name string
     *
     * @return void
     */
    public function dokan_vendor_biography_updated($store_info, $name) {
        $this->register_single_string(
            'dokan',
            'Vendor Biography Text: ' . $name,
            $store_info['vendor_biography']
        );
    }

    /**
     * Translated Vendor Biography Text
     *
     * @param string $text
     * @param $name
     *
     * @return string
     */
    public function get_translated_dokan_vendor_biography_text(string $text , $name) {
        return $this->get_translated_single_string( $text, 'dokan', 'Vendor Biography Text: '.$name );
    }

    /**
     * Register Single Store Custome Endpoint.
     *
     * @since 1.1.9
     *
     * @param string $option_name URL slug.
     * @param array $option_value URL slug.
     * @param array $old_options URL slug.
     *
     * @return void
     */
    public function register_single_store_custom_endpoint( string $option_name, array $option_value, array $old_options ) {
        if ( 'dokan_general' !== $option_name || ! isset( $old_options['custom_store_url'] ) || $old_options['custom_store_url'] === $option_value['custom_store_url'] ) {
            return;
        }

        try {
            $this->register_single_string(
                $this->wp_endpoints,
                $option_value['custom_store_url'],
                $option_value['custom_store_url'],
            );
        } catch ( Exception $e ) {
            dokan_log(
                sprintf(
                    __( 'Dokan WPML - Error on registering vendor store URL endpoint: %s', 'dokan-wpml' ),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Handle Translated Single Store Endpoint Rewrite.
     *
     * @since 1.1.9
     *
     * @param string $url_slug URL slug.
     *
     * @return void
     */
    public function handle_translated_single_store_endpoint_rewrite( string $url_slug ) {
        global $sitepress;

        if ( ! $sitepress ) {
            return;
        }

        $languages = $sitepress->get_active_languages();

        $slug_list = [];

        foreach ( $languages as $code => $language ) {
            $translated_slug = $this->translate_endpoint( $url_slug, $code );

            if ( $translated_slug === $url_slug ) {
                continue;
            }

            $slug_list[] = $translated_slug;
        }

        if ( empty( $slug_list ) ) {
            return;
        }

        foreach ( $slug_list as $translated_slug ) {
            $this->add_rewrite_rules( $translated_slug, $url_slug );
        }
    }

    /**
     * Add Rewrite Rules.
     *
     * @since 1.1.9
     *
     * @param string $regex_slug Regex Slug
     * @param string $query_slug Query Slug
     * @param string $after      Position after
     *
     * @return void
     */
    public function add_rewrite_rules( string $regex_slug, string $query_slug, string $after = 'top' ) {
        if ( empty( $regex_slug ) || empty( $query_slug ) ) {
            return;
        }

        if ( ! in_array( $after, [ 'top', 'bottom' ], true ) ) {
            $after = 'top';
        }

        add_rewrite_rule( $regex_slug . '/([^/]+)/?$', 'index.php?' . $query_slug . '=$matches[1]', $after );
        add_rewrite_rule( $regex_slug . '/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?' . $query_slug . '=$matches[1]&paged=$matches[2]', $after );

        add_rewrite_rule( $regex_slug . '/([^/]+)/section/?([0-9]{1,})/?$', 'index.php?' . $query_slug . '=$matches[1]&term=$matches[2]&term_section=true', $after );
        add_rewrite_rule( $regex_slug . '/([^/]+)/section/?([0-9]{1,})/page/?([0-9]{1,})/?$', 'index.php?' . $query_slug . '=$matches[1]&term=$matches[2]&paged=$matches[3]&term_section=true', $after );

        add_rewrite_rule( $regex_slug . '/([^/]+)/toc?$', 'index.php?' . $query_slug . '=$matches[1]&toc=true', $after );
        add_rewrite_rule( $regex_slug . '/([^/]+)/toc/page/?([0-9]{1,})/?$', 'index.php?' . $query_slug . '=$matches[1]&paged=$matches[2]&toc=true', $after );
    }

    /**
     * Handle Single Store Endpoint Translation Update.
     *
     * @since 1.1.9
     *
     * @param int $translated_string_id Translated string ID.
     *
     * @return void
     */
    public function handle_single_store_endpoint_translation_update( int $translated_string_id ) {
        $custom_store_url = dokan_get_option( 'custom_store_url', 'dokan_general', 'store' );

        // Check if translation updated for the custom store URL.
        if ( $custom_store_url !== $this->get_original_string_using_translated_string_id( $translated_string_id ) ) {
            return;
        }

        // Set transient to flush rewrite rules on next request.
        set_transient( 'dokan_wpml_flush_rewrite_rules_needed', 1 );
    }

    /**
     * Get Original String by Translated String ID.
     *
     * @since 1.1.9
     *
     * @param string $translated_string_id Translated String ID
     *
     * @return string|null Orininal string or null if not found
     */
    public function get_original_string_using_translated_string_id( string $translated_string_id ): ?string {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT
                        s.name
                    FROM
                        {$wpdb->prefix}icl_strings AS s
                        INNER JOIN {$wpdb->prefix}icl_string_translations AS st
                    WHERE
                        s.id = st.string_id AND st.id = %d",
                $translated_string_id
            )
        );
    }

		/**
		 * Support WPML delete post actions on the frontend dashboard.
		 *
		 * @since 1.1.7
		 *
		 * @return void
		 */
		public function before_product_delete() {
			if ( class_exists( 'WPML_Frontend_Post_Actions' ) ) {
				global $wpml_post_translations;
				add_action( 'delete_post', [ $wpml_post_translations, 'delete_post_actions' ] );
			}
		}
    /**
     * Filter language switcher URLs for Dokan store and dashboard pages
     *
     * @since 1.1.10
     *
     * @param string $url  The language URL
     * @param array  $lang Language data array
     *
     * @return string
     */
    public function filter_language_switcher_url( $url, $lang ) {
        $lang_code = $lang['code'] ?? '';
        if ( empty( $url ) || empty( $lang_code ) ) {
            return $url; // Return early if URL or language code is empty
        }

        // Get home URL without WPML modifications.
        $this->disable_url_translation();
        $home_url = home_url();
        $this->enable_url_translation();

        // Get language negotiation type and build base URL
        $default_language_code     = wpml_get_default_language();
        $language_negotiation_type = (int) apply_filters( 'wpml_setting', 1, 'language_negotiation_type' );
        $is_parameter_based        = ( WPML_LANGUAGE_NEGOTIATION_TYPE_PARAMETER === $language_negotiation_type );

        // If the language negotiation type is parameter-based, we need to use the home URL as the base URL.
        if ( ! $is_parameter_based && $default_language_code !== $lang_code ) {
            $base_url = trailingslashit( $home_url ) . $lang_code;
        } else {
            $base_url = $home_url;
        }

        // Remove query parameters for parameter-based negotiation.
        $url_path = trim( str_replace( $base_url, '', $url ), '/' );
        if ( $is_parameter_based && strpos( $url_path, '?' ) !== false ) {
            $url_path = explode( '?', $url_path, 2 )[0];
        }

        if ( empty( $url_path ) ) {
            return $url;
        }

        // Translate path segments to the target language.
        $path_segments         = explode( '/', $url_path );
        $translated_segments   = $this->translate_path_segments( $path_segments, $lang_code );
        $language_switcher_url = trailingslashit( $base_url ) . trailingslashit( implode( '/', $translated_segments ) );

        // If the language negotiation type is parameter-based, append the language code as a query parameter.
        if ( $is_parameter_based ) {
            $language_switcher_url = add_query_arg(
                [ 'lang' => $lang_code ],
                $language_switcher_url
            );
        }

        return apply_filters(
            'dokan_wpml_get_language_switcher_url',
            $language_switcher_url,
            $path_segments,
            $translated_segments,
            $lang,
            $base_url
        );
    }

    /**
     * Translate path segments in URL.
     *
     * @since 1.1.9
     *
     * @param array  $path_segments Path segments to translate
     * @param string $lang_code     Target language code
     *
     * @return array Translated path segments
     */
    public function translate_path_segments( $path_segments, $lang_code ) {
        foreach ( $path_segments as $key => $segment ) {
            $path_segments[ $key ] = $this->translate_endpoint( urldecode_deep( $segment ), $lang_code );
        }

        return $path_segments;
    }
} // Dokan_WPML

function dokan_load_wpml() { // phpcs:ignore
    return Dokan_WPML::init();
}

dokan_load_wpml();
