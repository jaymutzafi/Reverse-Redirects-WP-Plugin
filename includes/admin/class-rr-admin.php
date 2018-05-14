<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class RR_Admin
 */
class RR_Admin
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('init', array($this, 'includes'));
    }

    /**
     * Include any classes we need within admin.
     */
    public function includes()
    {
        include_once('class-rr-admin-meta-boxes.php');
    }
}

new RR_Admin();
