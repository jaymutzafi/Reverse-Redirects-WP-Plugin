<?php
/**
 * WooCommerce Meta Boxes
 *
 * Sets up the redirect meta box used by posts and pages
 *
 * @author      Kidon Chu
 * @category    Admin
 * @package     ReverseRedirect/Admin/Meta Boxes
 * @version     0.1.0
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * RR_Admin_Meta_Boxes
 */
class RR_Admin_Meta_Boxes {

    private static $saved_meta_boxes = false;
    public static $meta_box_errors = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->includes();

        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10 );

        // Replace target uri of existing redirect rules if post permalink gets updated
        add_action( 'pre_post_update', 'RR_Meta_Box_Redirect_Source::set_replace_target_uri_flag', 10, 2 );

        add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );

        add_action( 'reverseredirect_process_meta', 'RR_Meta_Box_Redirect_Source::replace_target_uri', 5, 2 );
        add_action( 'reverseredirect_process_meta', 'RR_Meta_Box_Redirect_Source::save', 10, 2 );

        // Error handling (for showing errors from meta boxes on next page load)
        add_action( 'admin_notices', array( $this, 'output_errors' ) );
        add_action( 'shutdown', array( $this, 'save_errors' ) );
    }

    /**
     * Include any classes we need within admin.
     */
    public function includes() {
        include_once( 'meta-boxes/class-rr-meta-box-redirect-source.php' );
    }

    /**
     * Add an error message
     * @param string $text
     */
    public static function add_error( $text ) {
        self::$meta_box_errors[] = $text;
    }

    /**
     * Save errors to an option
     */
    public function save_errors() {
        update_option( 'reverseredirect_meta_box_errors', self::$meta_box_errors );
    }

    /**
     * Show any stored error messages.
     */
    public function output_errors() {
        $errors = maybe_unserialize( get_option( 'reverseredirect_meta_box_errors' ) );

        if ( !empty( $errors ) ) {

            echo '<div id="reverseredirect_errors" class="error">';

            foreach ( $errors as $error ) {
                echo '<p>' . wp_kses_post( $error ) . '</p>';
            }

            echo '</div>';

            // Clear
            delete_option( 'reverseredirect_meta_box_errors' );
        }
    }

    /**
     * Add RR Meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box( 'reverseredirect-redirect-source', 'Redirect Sources', 'RR_Meta_Box_Redirect_Source::output', null, 'normal', 'high' );
    }

    /**
     * Check if we're saving, the trigger an action based on the post type
     *
     * @param  int $post_id
     * @param  object $post
     */
    public function save_meta_boxes( $post_id, $post ) {

        // $post_id and $post are required
        if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
            return;
        }

        // Dont' save meta boxes for revisions or autosaves
        if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
            return;
        }

        // Check the nonce
        if ( empty( $_POST[ 'reverseredirect_meta_nonce' ] ) ||
            !wp_verify_nonce( $_POST[ 'reverseredirect_meta_nonce' ], 'redirectsource_save_data' )
        ) {
            return;
        }

        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if ( empty( $_POST[ 'post_ID' ] ) || $_POST[ 'post_ID' ] != $post_id ) {
            return;
        }

        // Check user has permission to edit
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        self::$saved_meta_boxes = true;

        do_action( 'reverseredirect_process_meta', $post_id, $post );
    }

}

new RR_Admin_Meta_Boxes();
