<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class RR_ReverseRedirect
 */
class RR_ReverseRedirect
{

    /**
     * Hook in methods
     */
    public function __construct()
    {
        add_action('template_redirect', array($this, 'reverse_redirect_to_target'));
    }

    public function reverse_redirect_to_target()
    {
        global $wp, $wpdb;

        // Try to find a redirect rule based on the request URI.
        // There should only be at most one rule per request URI.
        $uri = $wp->request;
        $table_name = $wpdb->prefix . 'reverse_redirect_index';
        $sql = "SELECT * FROM {$table_name} WHERE source_uri = '{$uri}'";

        $result = $wpdb->get_row($sql);

        // If there is no redirect rule, do nothing
        if (empty($result->id)) {
            return;
        }

        $redirect_url = trailingslashit(home_url($result->target_uri));

        wp_safe_redirect(esc_url_raw($redirect_url), 302);
        exit;
    }
}

new RR_ReverseRedirect();
