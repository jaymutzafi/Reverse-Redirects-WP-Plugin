<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class RR_Meta_Box_Redirect_Source
 */
class RR_Meta_Box_Redirect_Source {

    protected static $_should_replace_target_uri = false;

    /**
     * Output the metabox
     *
     * @param $post
     */
    public static function output( $post ) {

        // Firstly, check if there is previous submitted, but not yet saved meta data
        $redirect_sources = unserialize( get_post_meta( $post->ID, 'redirect_sources_form_data', true ) );
        delete_post_meta( $post->ID, 'redirect_sources_form_data' );
        if ( !$redirect_sources ) {
            // If there isn't form data, then retrieve it from post meta data
            $redirect_sources = unserialize( get_post_meta( $post->ID, 'redirect_sources', true ) );
        }

        if ( $redirect_sources ) {
            $redirect_sources = implode( "\r\n", $redirect_sources );
        }
        ?>

        <?php wp_nonce_field( 'redirectsource_save_data', 'reverseredirect_meta_nonce' ) ?>

        <div id="coupon_options" class="panel-wrap coupon_data">
            <div class="panel">
                <p>
                    <label for="redirect-sources">Redirect Sources</label>
                </p>
                <textarea name="redirect-sources" id="redirect-sources" cols="60"
                          rows="10"><?php echo $redirect_sources ?></textarea>
            </div>
            <div class="clear"></div>
        </div>

        <?php
    }

    /**
     * Save meta box data
     *
     * @param $post_id
     * @param $post
     */
    public static function save( $post_id, $post ) {

        $redirect_sources = explode( ' ', sanitize_text_field( $_POST[ 'redirect-sources' ] ) );
        // trim out slashes at the front and end of URI
        // because $wp-request will return URI without them when checking for redirect rule
        foreach ( $redirect_sources as &$source ) {
            $source = trim( $source, " \t\n\r\0\x0B/" );
        }

        // If there is no redirect_sources value, then no need to check for duplicates
        if ( !$redirect_sources ) {
            if ( self::_clear_redirect_rules( $post ) === false ) {
                RR_Admin_Meta_Boxes::add_error( 'Unable to clear out old redirect rules. Please update post/page again.' );
            }
            update_post_meta( $post_id, 'redirect_sources', '' );
            return;
        }

        // Check if there already exists a target with entered sources
        $duplicate_rules = self::_get_duplicate_redirect_rules( $redirect_sources, $post );

        if ( $duplicate_rules ) {
            foreach ( $duplicate_rules as $duplicate ) {
                RR_Admin_Meta_Boxes::add_error( sprintf(
                    'Redirect rule already exists ("%s" => "%s")', $duplicate[ 'source_uri' ], $duplicate[ 'target_uri' ]
                ) );
            }
        }

        // Check if there already exists a post/page with entered sources
        $duplicate_posts = self::_get_duplicate_posts( $redirect_sources );

        if ( $duplicate_posts ) {
            foreach ( $duplicate_posts as $duplicate ) {
                RR_Admin_Meta_Boxes::add_error( sprintf(
                    'Post/Page with URI "%s" already exists', $duplicate[ 'post_name' ]
                ) );
            }
        }

        // If there exists any duplicate, show error messages without saving them to post
        if ( $duplicate_rules || $duplicate_posts ) {
            update_post_meta( $post_id, 'redirect_sources_form_data', serialize( $redirect_sources ) );
        } else {
            // If there is no duplicate, delete old rules and then save new rules
            $result = self::_clear_redirect_rules( $post );

            // If there was an error deleting old rules, do not proceed
            if ( $result === false ) {
                RR_Admin_Meta_Boxes::add_error( 'Unable to clear out old redirect rules. Please update post/page again.' );
            } else {
                $result = self::_create_redirect_rules( $redirect_sources, $post );
                if ($result !== true) {
                    foreach ($result as $source_uri) {
                        RR_Admin_Meta_Boxes::add_error( sprintf(
                            'Unable to create a redirect rule for "%s"', $source_uri
                        ) );
                    }
                }
            }

            update_post_meta( $post_id, 'redirect_sources', serialize( $redirect_sources ) );
        }
    }

    /**
     * Replaces old post name with new post name in redirect rule table if post name has been changed
     *
     * @param int $post_id
     * @param array $data
     */
    public static function set_replace_target_uri_flag( $post_id, $data ) {

        // Retrieve original post name and compare it with the new post name. If post name is changed,
        // then redirect rule table's original post name should be updated with new post name.
        $orig_post = get_post( $post_id );
        if ( $orig_post->post_name != $data[ 'post_name' ] ) {
            self::$_should_replace_target_uri = true;
        }
    }

    /**
     * Replaces old post name with new post name in redirect rule table if post name has been changed
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public static function replace_target_uri( $post_id, WP_Post $post ) {

        global $wpdb;

        if ( self::$_should_replace_target_uri ) {
            $wpdb->update(
                RR()->get_table_name(),
                array( 'target_uri' => $post->post_name ),
                array( 'post_id' => $post->ID )
            );
        }
    }

    /**
     * Gets duplicate redirect rules that are already assigned to other posts/pages
     *
     * @param array $redirect_sources
     * @param WP_Post $post
     * @return array
     */
    protected static function _get_duplicate_redirect_rules( $redirect_sources, WP_Post $post ) {

        global $wpdb;

        $table_name = RR()->get_table_name();
        $sources_placeholder = self::_get_sources_placeholder( $redirect_sources );

        $sql = "SELECT * FROM {$table_name}
          WHERE source_uri IN ({$sources_placeholder}) AND target_uri != '{$post->post_name}'";
        $sql = $wpdb->prepare( $sql, $redirect_sources );
        $duplicate_rules = $wpdb->get_results( $sql, ARRAY_A );

        return $duplicate_rules;
    }

    /**
     * Gets duplicate posts/pages that have same permalink as sources
     *
     * @param array $redirect_sources
     * @return array
     */
    protected static function _get_duplicate_posts( $redirect_sources ) {

        global $wpdb;

        $table_name = $wpdb->prefix . 'posts';
        $sources_placeholder = self::_get_sources_placeholder( $redirect_sources );

        $sql = "SELECT * FROM {$table_name} WHERE post_name IN ({$sources_placeholder})";
        $sql = $wpdb->prepare( $sql, $redirect_sources );
        $duplicate_posts = $wpdb->get_results( $sql, ARRAY_A );

        return $duplicate_posts;
    }

    /**
     * Returns a comma-separated %s for IN clause placeholder
     *
     * @param array $redirect_sources
     * @return string
     */
    protected static function _get_sources_placeholder( $redirect_sources ) {

        $num_sources = count( $redirect_sources );
        $sources_placeholder = array_fill( 0, $num_sources, '%s' );
        $sources_placeholder = implode( ', ', $sources_placeholder );
        return $sources_placeholder;
    }

    /**
     * @param WP_Post $post
     * @return false|int
     */
    protected static function _clear_redirect_rules( WP_Post $post ) {

        global $wpdb;

        return $wpdb->delete( RR()->get_table_name(), array(
            'target_uri' => $post->post_name,
        ) );
    }

    /**
     * @param array $redirect_sources
     * @param WP_Post $post
     * @return array|true array of errors if there were errors while saving to db, otherwise true
     */
    protected static function _create_redirect_rules( $redirect_sources, WP_Post $post ) {

        global $wpdb;
        
        $errors = array();
        foreach ( $redirect_sources as $source_uri ) {
            $result = $wpdb->insert( RR()->get_table_name(), array(
                'post_id' => $post->ID,
                'source_uri' => $source_uri,
                'target_uri' => $post->post_name,
            ) );

            if ($result === false) {
                $errors[] = $source_uri;
            }
        }

        if ($errors) {
            return $errors;
        }

        return true;
    }
}
