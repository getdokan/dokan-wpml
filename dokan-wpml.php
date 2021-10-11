<?php
/**
 * Plugin Name: Dokan - WPML Integration
 * Plugin URI: https://wedevs.com/
 * Description: WPML and Dokan compitable package
 * Version: 1.0.4
 * Author: weDevs
 * Author URI: https://wedevs.com/
 * Text Domain: dokan-wpml
 * WC requires at least: 3.0
 * WC tested up to: 5.2.2
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
    public $wp_endpoints = 'WP Endpoints';

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

        // Load classes
        add_action( 'dokan_loaded', [ $this, 'init_plugin' ] );

        // Load all actions hook
        add_filter( 'dokan_forced_load_scripts', [ $this, 'load_scripts_and_style' ] );
        add_filter( 'dokan_force_load_extra_args', [ $this, 'load_scripts_and_style' ] );
        add_filter( 'dokan_seller_setup_wizard_url', [ $this, 'render_wmpl_home_url' ], 70 );
        add_filter( 'dokan_get_page_url', [ $this, 'reflect_page_url' ], 10, 4 );
        add_filter( 'dokan_get_terms_condition_url', [ $this, 'get_terms_condition_url' ], 10, 2 );
        add_filter( 'dokan_redirect_login', [ $this, 'redirect_if_not_login' ], 90 );
        add_filter( 'dokan_force_page_redirect', [ $this, 'force_redirect_page' ], 90, 2 );

        // Load all filters hook
        add_filter( 'dokan_get_navigation_url', [ $this, 'load_translated_url' ], 10 ,2 );
        add_filter( 'body_class', [ $this, 'add_dashboard_template_class_if_wpml' ], 99 );
        add_filter( 'dokan_get_current_page_id', [ $this, 'dokan_set_current_page_id' ] );
        add_filter( 'dokan_get_dashboard_nav', [ $this, 'replace_dokan_dashboard_nav_key' ] );
        add_action( 'wp_head', [ $this, 'dokan_wpml_remove_fix_fallback_links' ] );

        add_action( 'dokan_store_page_query_filter', [ $this, 'load_store_page_language_switcher_filter' ], 10, 2 );
        add_filter( 'dokan_dashboard_nav_settings_key', [ $this, 'filter_dashboard_settings_key' ] );
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
     * Initialize products count class
     *
     * @since 1.0.5
     *
     * @return void
     */
    public function init_plugin() {
        $this->includes();
        $this->init_classes();
    }

    /**
     * Include all the required files
     *
     * @since 1.0.5
     *
     * @return void
     */
    public function includes() {
        require __DIR__ . '/Dokan_Wpml_Products_Count.php';
    }

    /**
     * Init all the classes
     *
     *  @since 1.0.5
     *
     * @return void
     */
    public function init_classes() {
        new Dokan_Wpml_Products_Count();
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

        if ( ! class_exists( 'WeDevs_Dokan' ) ) {
            $error   = sprintf( __( '<b>Dokan - WPML Integration</b> requires %sDokan plugin%s to be installed & activated!' , 'dokan-wpml' ), '<a target="_blank" href="https://wedevs.com/products/plugins/dokan/">', '</a>' );
            $message = '<div class="error"><p>' . $error . '</p></div>';
            wp_die( $message );
        }

        if ( ! class_exists( 'SitePress' ) ) {
            $error   = sprintf( __( '<b>Dokan - WPML Integration</b> requires %sWPML Multilingual CMS%s to be installed & activated!' , 'dokan-wpml' ), '<a target="_blank" href="https://wpml.org/">', '</a>' );
            $message = '<div class="error"><p>' . $error . '</p></div>';
            wp_die( $message );
        }
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
     * @return void
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
    function load_translated_url( $url, $name ) {
        $current_lang = apply_filters( 'wpml_current_language', NULL );

        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $url;
        }

        if ( ! empty( $name ) ) {

            if ( $current_lang ) {
                $name_arr = explode( '/', $name );

                if ( isset( $name_arr[1] ) ) {
                    $name = apply_filters( 'wpml_translate_single_string', $name_arr[0], $this->wp_endpoints, $name_arr[0], $current_lang ).'/'.$name_arr[1];
                } else {
                    $get_name = ( ! empty( $name_arr[0] ) ) ? $name_arr[0] : $name;
                    $name     = apply_filters( 'wpml_translate_single_string', $get_name, $this->wp_endpoints, $get_name, $current_lang );
                }
            }

            $url = $this->get_dokan_url_for_language( ICL_LANGUAGE_CODE,  $name.'/' );

        } else {
            $url = $this->get_dokan_url_for_language( ICL_LANGUAGE_CODE );
        }

        return $url;
    }

    /**
     * Replace dashboard key language wise
     *
     * @param array $urls
     *
     * @since 2.4
     *
     * @return array $urls
     */
    public function replace_dokan_dashboard_nav_key( $urls ) {
        $new_urls = $urls;

        foreach ( $urls as $get_key => $item ) {
            $new_key = $this->translate_endpoint( $get_key );
            if ( $get_key != $new_key ) {
                $new_urls[$new_key] = $new_urls[$get_key];
                unset($new_urls[$get_key]);
            }
        }

        return $new_urls;
    }

	/**
	 * @param string $endpoint
	 *
	 * @return string
	 */
    private function translate_endpoint( $endpoint ) {
    	return apply_filters( 'wpml_translate_single_string', $endpoint, $this->wp_endpoints, $endpoint );
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

        $page_id = wpml_object_id_filter( $page_id , 'page', true, ICL_LANGUAGE_CODE );

        $url = get_permalink( $page_id );

        if ( $subpage ) {
	        $subpage = $this->translate_endpoint( $subpage );
	        $url     = function_exists( 'dokan_add_subpage_to_url' ) ? dokan_add_subpage_to_url( $url, $subpage ) : $url;
        }

        return $url;
    }

    /**
     * Get terms and condition page url
     *
     * @since 1.0.1
     *
     * @return url
     */
    public function get_terms_condition_url( $url, $page_id ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $url;
        }

        $page_id = wpml_object_id_filter( $page_id , 'page', true, ICL_LANGUAGE_CODE );

        return get_permalink( $page_id );
    }

    /**
     * Redirect if not login
     *
     * @since 1.0.1
     *
     * @return void
     */
    public function redirect_if_not_login( $url ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return $url;
        }

        $page_id      = wc_get_page_id( 'myaccount' );
        $lang_post_id = wpml_object_id_filter( $page_id , 'page', true, ICL_LANGUAGE_CODE );

        return get_permalink( $lang_post_id );
    }

    /**
     * Undocumented function
     *
     * @since 1.0.1
     *
     * @return void
     */
    public function force_redirect_page( $flag, $page_id ) {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return false;
        }

        $lang_post_id = wpml_object_id_filter( $page_id , 'page', true, ICL_LANGUAGE_CODE );

        if ( is_page( $lang_post_id ) ) {
            return true;
        }

        return false;
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
            $lang_post_id = wpml_object_id_filter( $post_id , 'page', true, $language );
        }

        if ( $lang_post_id != 0 ) {
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
     * @return void
     */
    public function load_scripts_and_style() {
        if ( ! function_exists( 'wpml_object_id_filter' ) ) {
            return false;
        }

        global $post;

        if ( ! is_object( $post ) ) {
            return false;
        }

        $page_id         = $this->get_raw_option( 'dashboard', 'dokan_pages' );
        $current_page_id = wpml_object_id_filter( $post->ID, 'page', true, wpml_get_default_language() );

        if ( ( $current_page_id == $page_id ) || ( get_query_var( 'edit' ) && is_singular( 'product' ) ) ) {
            return true;
        }
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
     * Get raw value from database
     *
     * @since  1.0.3
     *
     * @param  string $option
     * @param  string $section
     * @param  mix $default
     *
     * @return mix
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

} // Dokan_WPML

function dokan_load_wpml() {
    $dokan_wpml = Dokan_WPML::init();
}

dokan_load_wpml();
