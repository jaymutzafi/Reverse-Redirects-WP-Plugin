<?php
/**
 * Plugin Name: Reverse Redirect
 * Plugin URI: http://github.com/kidonchu
 * Description: Reverse redirect plugin for posts and pages
 * Version: 0.1.0
 * Author: Kidon Chu
 * Author URI: http://github.com/kidonchu
 * Requires at least: 4.1
 * Tested up to: 4.3
 *
 * @package ReverseRedirect
 * @author Kidon Chu
 */
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( 'ReverseRedirect' ) ) :

    /**
     * Main ReverseRedirect Class
     *
     * @class ReverseRedirect
     * @version 0.1.0
     */
    final class ReverseRedirect {
        /**
         * @var string
         */
        public $version = '0.1.0';

        /**
         * @var ReverseRedirect The single instance of the class
         */
        protected static $_instance = null;

        /**
         * Main ReverseRedirect Instance
         *
         * @return ReverseRedirect
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        /**
         * ReverseRedirect Constructor.
         */
        public function __construct() {
            $this->includes();
            $this->init_hooks();
        }

        /**
         * Include required core files used in admin and on the frontend.
         */
        public function includes() {
            if (is_admin()) {
                include_once( 'includes/admin/class-rr-admin.php' );
                include_once( 'includes/class-rr-install.php' );
            }

            if ( !is_admin() ) {
                include_once( 'includes/class-rr-reverse-redirect.php' );
            }
        }

        public function init_hooks() {
            register_activation_hook( __FILE__, array( 'RR_Install', 'install' ) );
        }

        /**
         * Get the plugin url.
         *
         * @return string
         */
        public function plugin_url() {
            return untrailingslashit( plugins_url( '/', __FILE__ ) );
        }

        /**
         * Get the plugin path.
         *
         * @return string
         */
        public function plugin_path() {
            return untrailingslashit( plugin_dir_path( __FILE__ ) );
        }

        /**
         * Get the template path.
         * @return string
         */
        public function template_path() {
            return apply_filters( 'woocommerce_template_path', 'woocommerce/' );
        }

        /**
         * Get Ajax URL.
         * @return string
         */
        public function ajax_url() {
            return admin_url( 'admin-ajax.php', 'relative' );
        }

        /**
         * Gets redirect index table name
         *
         * @return string
         */
        public function get_table_name()
        {
            global $wpdb;
            return $wpdb->prefix . 'reverse_redirect_index';
        }
    }

endif;

/**
 * @return ReverseRedirect
 */
function RR() {
    return ReverseRedirect::instance();
}

RR();
