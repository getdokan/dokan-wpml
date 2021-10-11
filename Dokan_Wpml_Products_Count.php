<?php
/**
 * Products Count class
 *
 * @since 1.0.5
 *
 * @class Dokan_Wpml_Products_Count handle products count for each language
 */
class Dokan_Wpml_Products_Count {

    /**
     * Constructor for Dokan_Wpml_Products_Count class
     */
    public function __construct() {
        add_action( 'dokan_after_product_listing_status_filter', [ $this, 'show_products_count' ] );
    }

    /**
     * Language wise product count
     *
     * @since 1.0.5
     *
     * @return void
     */
    public function show_products_count() {
        global $wpdb;

        $user_id                    = dokan_get_current_user_id();
        $exclude_product_types      = esc_sql( array( 'booking' ) );
        $exclude_product_types_text = "'" . implode( "', '", $exclude_product_types ) . "'";
        $cache_group                = 'dokan_cache_seller_product_data_' . $user_id;
        $cache_key                  = 'dokan-products-count-' . $user_id;
        $counts                     = wp_cache_get( $cache_key, $cache_group );
        $tracked_cache_keys         = get_option( $cache_group, [] );

        if ( ! in_array( $cache_key, $tracked_cache_keys, true ) ) {
            $tracked_cache_keys[] = $cache_key;
            update_option( $cache_group, $tracked_cache_keys );
        }

        if ( false === $counts ) {
            $counts = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT language_code, COUNT(posts.ID) AS count FROM wp_icl_translations translations
                            INNER JOIN wp_posts posts ON translations.element_id=posts.ID AND translations.element_type = CONCAT('post_', posts.post_type)
                            INNER JOIN {$wpdb->term_relationships} AS term_relationships ON posts.ID = term_relationships.object_id
                            INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy ON term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id
                            INNER JOIN {$wpdb->terms} AS terms ON term_taxonomy.term_id = terms.term_id
                            WHERE
                                term_taxonomy.taxonomy = 'product_type'
                            AND terms.slug NOT IN ({$exclude_product_types_text})
                            AND posts.post_type = 'product'
                            AND posts.post_author = %d
                            AND post_status <> 'trash'
                            AND post_status <> 'auto-draft'
                            AND translations.language_code IN ('bn','en','all')
                                GROUP BY language_code",
                    $user_id
                ),
                ARRAY_A
            );

            wp_cache_set( $cache_key, $counts, $cache_group, 3600 * 6 );
        }

        $html      = '';
        $languages = wpml_get_active_languages();

        foreach ( $counts as $count ) {
            $html .= '<li>
                <a href="#">' . esc_html( $languages[ $count['language_code'] ]['display_name'] ) . ' (' . esc_html( $count['count'] ) . ')' . '</a>
            </li>';
        }

        echo $html;
    }
}