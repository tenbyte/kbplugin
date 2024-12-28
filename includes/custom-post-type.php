<?php
function knowledgebase_register_post_type() {
    register_post_type('knowledgebase', [
        'label' => 'Knowledgebase',
        'public' => true,
        'rewrite' => ['slug' => 'kb'],
        'show_ui' => true,
        'supports' => ['title', 'editor', 'thumbnail', 'page-attributes'],
        'taxonomies' => ['category'],
    ]);

    add_action('add_meta_boxes', function() {
        add_meta_box('secured_checkbox', 'Secured', function($post) {
            $value = get_post_meta($post->ID, '_secured', true);
            echo '<label><input type="checkbox" name="secured" ' . checked($value, 'yes', false) . '> Ja</label>';
        });
    });

    add_action('save_post', function($post_id) {
        if (array_key_exists('secured', $_POST)) {
            update_post_meta($post_id, '_secured', 'yes');
        } else {
            delete_post_meta($post_id, '_secured');
        }
    });
}

add_action('init', 'knowledgebase_register_post_type');

function knowledgebase_handle_shared_link() {
    if (isset($_GET['shared_link'])) {
        $hash = sanitize_text_field($_GET['shared_link']);
        if (knowledgebase_verify_shared_link($hash)) {
            knowledgebase_set_secure_cookie('kb_shared_link', $hash, WEEK_IN_SECONDS);
            $redirect = isset($_GET['redirect_back']) ? esc_url_raw($_GET['redirect_back']) : home_url();
            wp_safe_redirect($redirect);
            exit;
        }
        wp_safe_redirect(home_url('/auth?error=invalid_link'));
        exit;
    }
}
add_action('template_redirect', 'knowledgebase_handle_shared_link', 5);

add_filter('rest_post_query', function ($args, $request) {
    // Check if the user is authenticated with codes, links, or is an administrator
    if (!current_user_can('manage_options') && !is_user_authenticated()) {
        // Add meta query to exclude secured posts
        $meta_query = isset($args['meta_query']) ? $args['meta_query'] : [];
        $meta_query[] = [
            'key' => '_secured',
            'value' => 'yes',
            'compare' => '!='
        ];
        $args['meta_query'] = $meta_query;
    }

    return $args;
}, 10, 2);

// Helper function to check user authentication
function is_user_authenticated() {
    // Check if the user has a valid partner code
    if (isset($_COOKIE['kb_partner_code']) && knowledgebase_verify_partner_code(sanitize_text_field($_COOKIE['kb_partner_code']))) {
        return true;
    }

    // Check if the user has a valid shared link
    if (isset($_COOKIE['kb_shared_link']) && knowledgebase_verify_shared_link(sanitize_text_field($_COOKIE['kb_shared_link']))) {
        return true;
    }

    return false;
}

add_action('pre_get_posts', function ($query) {
    if ($query->is_feed && !is_user_authenticated() && !current_user_can('manage_options')) {
        $query->set('meta_query', [
            [
                'key' => '_secured',
                'value' => 'yes',
                'compare' => '!='
            ]
        ]);
    }
});

add_action('wp_head', function () {
    if (is_single() && get_post_meta(get_the_ID(), '_secured', true) === 'yes' && !is_user_authenticated()) {
        echo '<meta name="robots" content="noindex, nofollow">';
    }
});

//DONT FORGET THE HTACCESS!!!
