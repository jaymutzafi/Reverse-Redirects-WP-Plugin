<?php

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * RR_Install
 */
class RR_Install
{
    public static function install()
    {
        self::_create_tables();
        add_option('rr_db_version', RR()->version);
    }

    /**
     * Set up the database tables which the plugin needs to function.
     */
    protected static function _create_tables()
    {
        global $wpdb;

        $wpdb->hide_errors();

        $table_name = $wpdb->prefix . 'reverse_redirect_index';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL auto_increment,
            post_id bigint(20) unsigned,
            source_uri varchar(255) NOT NULL,
            target_uri varchar(255) NOT NULL,
            created_at timestamp NOT NULL,
            PRIMARY KEY  (id),
            KEY source_uri (source_uri)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
