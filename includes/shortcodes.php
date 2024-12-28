<?php
function knowledgebase_auth_form() {
    $redirect_back = isset($_GET['redirect_back']) ? esc_url_raw($_GET['redirect_back']) : home_url();

    // Validate redirect_back
    if (!str_starts_with($redirect_back, home_url())) {
        $redirect_back = home_url();
    }

    $license_code = isset($_GET['shared_link']) ? sanitize_text_field($_GET['shared_link']) : '';

    if (!empty($license_code)) {
        knowledgebase_set_secure_cookie('kb_shared_link', $license_code, WEEK_IN_SECONDS);
    }

    return '
        <form id="kb-auth-form">
            <label>
                Wählen Sie einen Code-Typ:
                <select name="auth_type" id="auth-type-selector">
                    <option value="partner_code">Partner Code</option>
                    <option value="license_code" disabled>Lizenzcode</option>
                </select>
            </label>
            <label id="auth-code-label">
                Geben Sie den Code ein:
                <input type="text" name="auth_value" placeholder="Enter Partner Code" required>
            </label>
            <input type="hidden" name="redirect_back" value="' . esc_attr($redirect_back) . '">
            <button type="submit">Absenden</button>
        </form>
        <script>
            jQuery("#auth-type-selector").on("change", function () {
                const selectedType = jQuery(this).val();
                if (selectedType === "license_code") {
                    jQuery("#auth-code-label input").prop("placeholder", "Enter Lizenzcode").prop("disabled", true);
                } else {
                    jQuery("#auth-code-label input").prop("placeholder", "Enter Partner Code").prop("disabled", false);
                }
            });

            jQuery("#kb-auth-form").on("submit", function(e) {
                e.preventDefault();
                const formData = {
                    action: "kb_authenticate",
                    auth_type: jQuery("#auth-type-selector").val(),
                    auth_value: jQuery(this).find("[name=\'auth_value\']").val(),
                    redirect_back: jQuery(this).find("[name=\'redirect_back\']").val()
                };
                fetch("' . esc_url(rest_url('kb/v1/authenticate')) . '", {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
				},
				body: JSON.stringify(formData),
			})
			.then(response => response.json())
			.then(data => {
				if (data.redirect) {
					window.location.href = data.redirect;
				} else {
					alert(data.message || "Authentication failed.");
				}
			});

            });
        </script>
    ';
}
add_shortcode('kb_auth_form', 'knowledgebase_auth_form');

function knowledgebase_user_status_shortcode() {
    if (isset($_COOKIE['kb_partner_code'])) {
        $partner_code = sanitize_text_field($_COOKIE['kb_partner_code']);
        return 'Eingeloggt als ' . esc_html($partner_code);
    }

    if (isset($_COOKIE['kb_shared_link'])) {
        $shared_link = sanitize_text_field($_COOKIE['kb_shared_link']);
        return 'Eingeloggt als ' . esc_html($shared_link);
    }

    return 'Gast Modus';
}
add_shortcode('kb_user_status', 'knowledgebase_user_status_shortcode');

function knowledgebase_logout_shortcode() {
    // Check if the user is logged in
    $is_authenticated = isset($_COOKIE['kb_partner_code']) || isset($_COOKIE['kb_shared_link']);

    if (!$is_authenticated) {
        // Return nothing if the user is in guest mode
        return '';
    }

    // Check if a logout action is triggered
    if (isset($_GET['kb_logout']) && $_GET['kb_logout'] === '1') {
        // Clear authentication cookies
        setcookie('kb_partner_code', '', time() - 3600, '/', '', is_ssl(), true);
        setcookie('kb_shared_link', '', time() - 3600, '/', '', is_ssl(), true);

        // Redirect to prevent multiple logout requests
        wp_redirect(home_url('/?logout=success'));
        exit;
    }

    // Display logout link
    $logout_url = add_query_arg('kb_logout', '1', home_url());
    return '<a href="' . esc_url($logout_url) . '">Logout</a>';
}
add_shortcode('kb_logout', 'knowledgebase_logout_shortcode');

