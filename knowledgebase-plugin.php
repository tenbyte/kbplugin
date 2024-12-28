<?php
/*
Plugin Name: Knowledgebase Plugin
Description: BETA KB Custom Plugin w/ shares and code access restriction
Author: Tenbyte
Version: 1.0
*/

defined('ABSPATH') or exit;

define('KNOWLEDGEBASE_PLUGIN_DIR', realpath(plugin_dir_path(__FILE__)));
define('KNOWLEDGEBASE_PLUGIN_URL', esc_url(plugin_dir_url(__FILE__)));

if (!KNOWLEDGEBASE_PLUGIN_DIR || strpos(KNOWLEDGEBASE_PLUGIN_DIR, realpath(WP_CONTENT_DIR)) !== 0) {
    exit('Invalid plugin directory.');
}

require_once KNOWLEDGEBASE_PLUGIN_DIR . '/includes/custom-post-type.php';
require_once KNOWLEDGEBASE_PLUGIN_DIR . '/includes/auth-handlers.php';
require_once KNOWLEDGEBASE_PLUGIN_DIR . '/includes/shortcodes.php';
require_once KNOWLEDGEBASE_PLUGIN_DIR . '/includes/settings.php';

register_activation_hook(__FILE__, function () {
    if (function_exists('knowledgebase_create_tables')) {
        knowledgebase_create_tables();
    }
    if (function_exists('knowledgebase_register_post_type')) {
        knowledgebase_register_post_type();
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

function knowledgebase_enqueue_scripts() {
    wp_enqueue_script('jquery');

    $style_url = esc_url(KNOWLEDGEBASE_PLUGIN_URL . 'assets/css/styles.css');

    wp_enqueue_script(
        $script_url,
        array('jquery'),
        null,
        true
    );

    wp_enqueue_style(
        'knowledgebase-styles',
        $style_url
    );
}
add_action('wp_enqueue_scripts', 'knowledgebase_enqueue_scripts');
