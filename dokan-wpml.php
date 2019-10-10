<?php
/*
Plugin Name: Dokan - WPML Integration
Plugin URI: https://wedevs.com/
Description: WPML and Dokan compitable package
Version: 1.0.1
Author: weDevs
Author URI: https://wedevs.com/
License: GPL2
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
        register_activation_hook( __FILE__, array( $this, 'dependency_missing_notice' ) );

        // Localize our plugin
        add_action( 'init', array( $this, 'localization_setup' ) );

        // Load all actions hook
        add_filter( 'dokan_forced_load_scripts', array( $this, 'load_scripts_and_style') );
        add_filter( 'dokan_force_load_extra_args', array( $this, 'load_scripts_and_style') );
        add_filter( 'dokan_seller_setup_wizard_url', array( $this, 'render_wmpl_home_url' ), 70 );
        add_filter( 'dokan_get_page_url', array( $this, 'reflect_page_url' ), 10, 3 );
        add_filter( 'dokan_get_terms_condition_url', array( $this, 'get_terms_condition_url' ), 10, 2 );
        add_filter( 'dokan_redirect_login', array( $this, 'redirect_if_not_login' ), 90 );
        add_filter( 'dokan_force_page_redirect', array( $this, 'force_redirect_page' ), 90, 2 );

        // Load all filters hook
        add_filter( 'dokan_get_navigation_url', array( $this, 'load_translated_url' ), 10 ,2 );
        add_filter( 'body_class', array( $this, 'add_dashboard_template_class_if_wpml' ), 99 );
        add_filter( 'dokan_get_current_page_id', [ $this, 'dokan_set_current_page_id' ] );
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
    **/
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
        if ( function_exists('wpml_object_id_filter') ) {
            $page_id = dokan_get_option( 'dashboard', 'dokan_pages' );

            if ( ! empty( $name ) ) {
                $url = $this->get_dokan_url_for_language( ICL_LANGUAGE_CODE ).$name.'/';
            } else {
                $url = $this->get_dokan_url_for_language( ICL_LANGUAGE_CODE );
            }
            return $url;
        }

        return $url;
    }

    /**
    * Reflect page url
    *
    * @since 1.0.1
    *
    * @return void
    **/
    public function reflect_page_url( $url, $page_id, $context ) {
        $lang_post_id = wpml_object_id_filter( $page_id , 'page', true, ICL_LANGUAGE_CODE );
        return get_permalink( $lang_post_id );
    }

    /**
    * Get terms and condition page url
    *
    * @since 1.0.1
    *
    * @return url
    **/
    public function get_terms_condition_url( $url, $page_id ) {
        $page_id = wpml_object_id_filter( $page_id , 'page', true, ICL_LANGUAGE_CODE );

        return get_permalink( $page_id );
    }

    /**
    * Redirect if not login
    *
    * @since 1.0.1
    *
    * @return void
    **/
    public function redirect_if_not_login( $url ) {
        $page_id = wc_get_page_id( 'myaccount' );
        $lang_post_id = wpml_object_id_filter( $page_id , 'page', true, ICL_LANGUAGE_CODE );
        return get_permalink( $lang_post_id );
    }

    /**
    * undocumented function
    *
    * @since 1.0.1
    *
    * @return void
    **/
    public function force_redirect_page( $flag, $page_id ) {
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
     *
     * @return string [$url]
     */
    public function get_dokan_url_for_language( $language ) {
        $post_id = dokan_get_option( 'dashboard', 'dokan_pages' );
        $lang_post_id = wpml_object_id_filter( $post_id , 'page', true, $language );

        $url = "";
        if ($lang_post_id != 0) {
            $url = get_permalink( $lang_post_id );
        } else {
            // No page found, it's most likely the homepage
            $url = apply_filters( 'wpml_home_url', get_option( 'home' ) );

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
        if ( function_exists('wpml_object_id_filter') ) {
            global $post;

            if( !$post ) {
                return $classes;
            }

            $default_lang = apply_filters('wpml_default_language', NULL );

            $current_page_id = wpml_object_id_filter( $post->ID,'page',false, $default_lang );
            $page_id         = dokan_get_option( 'dashboard', 'dokan_pages' );

            if ( ( $current_page_id == $page_id ) ) {
                $classes[] = 'dokan-dashboard';
            }
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
        if ( function_exists('wpml_object_id_filter') ) {
            global $post;

            if( !$post ) {
                return false;
            }

            $default_lang    = apply_filters('wpml_default_language', NULL );
            $current_page_id = wpml_object_id_filter( $post->ID,'page',false, $default_lang );
            $page_id         = dokan_get_option( 'dashboard', 'dokan_pages' );

            if ( ( $current_page_id == $page_id ) || ( get_query_var( 'edit' ) && is_singular( 'product' ) ) ) {
                return true;
            }
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
        return wpml_object_id_filter( $page_id, 'page', true, wpml_get_default_language() );
    }

} // Dokan_WPML

function dokan_load_wpml() {
    $dokan_wpml = Dokan_WPML::init();
}

dokan_load_wpml();