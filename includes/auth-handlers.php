<?php
add_action('rest_api_init', function () {
    register_rest_route('kb/v1', '/authenticate', [
        'methods' => 'POST',
        'callback' => 'knowledgebase_authenticate_user_rest',
        'permission_callback' => '__return_true', // Allow public access
    ]);
});

function knowledgebase_authenticate_user_rest(WP_REST_Request $request) {
    // Rate limiting logic
    $attempts_key = 'kb_auth_attempts_' . md5($_SERVER['REMOTE_ADDR']);
    $attempts = get_transient($attempts_key);

    if ($attempts >= 10) {
        return new WP_Error('too_many_requests', 'Zu viele Anfragen. Bitte versuchen Sie es später erneut.', ['status' => 429]);
    }

    // Increment attempts counter
    set_transient($attempts_key, ($attempts ? $attempts + 1 : 1), HOUR_IN_SECONDS);

    $shared_link = $request->get_param('shared_link');
    $auth_type = $request->get_param('auth_type');
    $auth_value = $request->get_param('auth_value');
    $redirect_back = esc_url_raw($request->get_param('redirect_back')) ?: home_url();

    // Validate redirect_back to prevent open redirects
    if (!str_starts_with($redirect_back, home_url())) {
        $redirect_back = home_url();
    }

    // Handle shared link authentication
    if ($shared_link) {
        $hash = sanitize_text_field($shared_link);
        if (knowledgebase_verify_shared_link($hash)) {
            knowledgebase_set_secure_cookie('kb_shared_link', $hash, WEEK_IN_SECONDS);
            return rest_ensure_response(['redirect' => $redirect_back]);
        }
        return new WP_Error('invalid_link', 'Ungültiger Link', ['status' => 403]);
    }

    // Handle partner code authentication
    if ($auth_type === 'partner_code' && $auth_value) {
        $value = sanitize_text_field($auth_value);
        if (knowledgebase_verify_partner_code($value)) {
            knowledgebase_set_secure_cookie('kb_partner_code', $value, WEEK_IN_SECONDS);
            return rest_ensure_response(['redirect' => $redirect_back]);
        }
        return new WP_Error('invalid_code', 'Ungültiger Code', ['status' => 403]);
    }

    return new WP_Error('bad_request', 'Ungültige Anfrage', ['status' => 400]);
}

function knowledgebase_check_access($post_id) {
    $secured = get_post_meta($post_id, '_secured', true) === 'yes';
    $categories = wp_get_post_categories($post_id);
    return !$secured || knowledgebase_verify_credentials($categories);
}

function knowledgebase_verify_credentials($categories) {
    return isset($_COOKIE['kb_partner_code']) && knowledgebase_verify_partner_code($_COOKIE['kb_partner_code'], $categories)
        || isset($_COOKIE['kb_shared_link']) && knowledgebase_verify_shared_link($_COOKIE['kb_shared_link'], $categories);
}

function knowledgebase_verify_partner_code($code, $categories = []) {
    global $wpdb;

    // Rate limiting logic
    $attempts_key = 'kb_verify_partner_attempts_' . md5($_SERVER['REMOTE_ADDR']);
    $attempts = get_transient($attempts_key);

    if ($attempts >= 10) {
        return false; // Too many attempts
    }

    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}kb_partner_codes WHERE code = %s AND expiration > NOW()", $code));

    if ($result && (!array_diff($categories, explode(',', $result->categories)))) {
        return true;
    }

    // Increment attempts if validation fails
    set_transient($attempts_key, ($attempts ? $attempts + 1 : 1), HOUR_IN_SECONDS);
    return false;
}

function knowledgebase_verify_shared_link($hash, $categories = []) {
    global $wpdb;

    // Validate hash format for UUIDs
    if (!preg_match('/^[a-f0-9\-]{36}$/', $hash)) {
        error_log('Invalid hash format: ' . $hash);
        return false;
    }

    $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}kb_shared_links WHERE hash = %s AND expiration > NOW()", $hash);
    $result = $wpdb->get_row($query);

    if (!$result) {
        error_log('Hash not found or expired: ' . $hash);
    } else {
        error_log('Hash found: ' . print_r($result, true));
    }

    return $result && (!array_diff($categories, explode(',', $result->categories)));
}


function knowledgebase_set_secure_cookie($key, $value, $expiry) {
    setcookie($key, $value, [
        'expires' => time() + $expiry,
        'secure' => is_ssl(),
        'httponly' => true,
        'samesite' => 'Strict',
        'path' => '/',
    ]);
}

function knowledgebase_redirect_if_not_authenticated() {
    if (is_singular('knowledgebase') && !knowledgebase_check_access(get_the_ID())) {
        $redirect_url = add_query_arg(
            'redirect_back',
            rawurlencode(get_permalink()),
            home_url('/auth')
        );
        wp_safe_redirect($redirect_url);
        exit;
    }
}

function knowledgebase_create_shared_link($expiration, $categories) {
    global $wpdb;
    $shared_links_table = $wpdb->prefix . 'kb_shared_links';

    $hash = wp_generate_uuid4(); // Erzeugt einen einzigartigen Hash
    $categories_str = implode(',', array_map('sanitize_text_field', $categories));
    $expiration_date = sanitize_text_field($expiration);

    $wpdb->insert($shared_links_table, [
        'hash' => $hash,
        'expiration' => $expiration_date,
        'categories' => $categories_str,
    ]);

    return $hash;
}

add_action('template_redirect', 'knowledgebase_redirect_if_not_authenticated');
add_action('wp_ajax_kb_authenticate', 'knowledgebase_authenticate_user');
add_action('wp_ajax_nopriv_kb_authenticate', 'knowledgebase_authenticate_user');
