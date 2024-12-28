<?php
add_action('admin_menu', function () {
    add_menu_page('Knowledgebase Settings', 'KB Settings', 'manage_options', 'kb-settings', 'knowledgebase_render_settings_page');
});

add_action('admin_head', function () {
    echo '
    <style>
        .kb-form {
            margin-bottom: 20px;
        }
        .kb-form input, .kb-form select, .kb-form button {
            margin-right: 10px;
        }
        .kb-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kb-table th, .kb-table td {
            border: 1px solid #ccc;
            padding: 10px;
        }
        .kb-table th {
            background-color: #f4f4f4;
        }
        #kb-modal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        width: 500px;
        max-width: 90%;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        z-index: 9999;
    }
    #kb-modal h2 {
        text-align: center;
        margin-bottom: 20px;
        font-size: 1.5em;
    }
    #kb-modal form label {
        display: block;
        margin-bottom: 10px;
        font-weight: bold;
    }
    #kb-modal form input, 
    #kb-modal form select, 
    #kb-modal form button {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 1em;
    }
    #kb-modal form button {
        background: #0073aa;
        color: white;
        border: none;
        cursor: pointer;
    }
    #kb-modal form button:hover {
        background: #005f8d;
    }
    #kb-modal button#modal-close {
        background: #d63638;
        margin-top: 10px;
    }
    #kb-modal button#modal-close:hover {
        background: #b32d2e;
    }
    </style>';
});

function knowledgebase_render_settings_page() {
    global $wpdb;
    $partner_table = $wpdb->prefix . 'kb_partner_codes';
    $shared_links_table = $wpdb->prefix . 'kb_shared_links';
    $categories = get_terms(['taxonomy' => 'category', 'hide_empty' => false]);

    $partner_codes = $wpdb->get_results("SELECT * FROM $partner_table");
    $shared_links = $wpdb->get_results("SELECT * FROM $shared_links_table");

    echo '<div class="wrap">';
    echo '<h1>Knowledgebase Settings</h1>';

    // Add/Edit Entry Button
    echo '<button class="button-primary" id="open-modal-add" style="margin-bottom: 20px;">Add New Entry</button>';

    // Partner Codes Table
    echo '<h2>Partner Codes</h2>';
    echo '<table class="kb-table">';
    echo '<tr><th>Code</th><th>Expiration</th><th>Categories</th><th>Actions</th></tr>';
    foreach ($partner_codes as $code) {
        echo '<tr>';
        echo '<td>' . esc_html($code->code) . '</td>';
        echo '<td>' . esc_html(date('Y-m-d', strtotime($code->expiration))) . '</td>';
        echo '<td>' . esc_html(implode(', ', array_map(function ($id) {
            $term = get_term($id);
            return $term ? $term->name : '';
        }, explode(',', $code->categories)))) . '</td>';
        echo '<td>
                <button class="button-primary open-modal-edit" 
                    data-id="' . esc_attr($code->id) . '" 
                    data-type="partner_code" 
                    data-code="' . esc_attr($code->code) . '" 
                    data-expiration="' . esc_attr(date('Y-m-d', strtotime($code->expiration))) . '" 
                    data-categories="' . esc_attr($code->categories) . '">Edit</button>
                <button class="button-secondary delete-entry" 
                    data-id="' . esc_attr($code->id) . '" 
                    data-table="' . esc_attr($partner_table) . '">
                    Delete
                </button>
              </td>';
        echo '</tr>';
    }
    echo '</table>';

    // Shared Links Table
    echo '<h2>Shared Links</h2>';
    echo '<table class="kb-table">';
    echo '<tr><th>Link</th><th>Expiration</th><th>Categories</th><th>Actions</th></tr>';
    foreach ($shared_links as $link) {
        $auth_url = home_url('/auth?shared_link=' . $link->hash);
        echo '<tr>';
        echo '<td><a href="' . esc_url($auth_url) . '">' . esc_html($auth_url) . '</a></td>';
        echo '<td>' . esc_html(date('Y-m-d', strtotime($link->expiration))) . '</td>';
        echo '<td>' . esc_html(implode(', ', array_map(function ($id) {
            $term = get_term($id);
            return $term ? $term->name : '';
        }, explode(',', $link->categories)))) . '</td>';
        echo '<td>
                <button class="button-primary open-modal-edit" 
                    data-id="' . esc_attr($link->id) . '" 
                    data-type="shared_link" 
                    data-hash="' . esc_attr($link->hash) . '" 
                    data-expiration="' . esc_attr(date('Y-m-d', strtotime($link->expiration))) . '" 
                    data-categories="' . esc_attr($link->categories) . '">Edit</button>
                <button class="button-secondary delete-entry" 
                    data-id="' . esc_attr($link->id) . '" 
                    data-table="' . esc_attr($shared_links_table) . '">
                    Delete
                </button>
              </td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';

    // Shared Modal
    echo '
    <div id="kb-modal">
        <h2 id="modal-title">Add New Entry</h2>
        <form id="kb-modal-form">
            <input type="hidden" name="id" id="entry-id">
            <label>Entry Type:
                <select name="entry_type" id="entry-type" required>
                    <option value="partner_code">Partner Code</option>
                    <option value="shared_link">Shared Link</option>
                </select>
            </label>
            <label id="code-label">Code/Hash:
                <input type="text" name="code" id="entry-code">
            </label>
            <label>Expiration:
                <input type="date" name="expiration" id="entry-expiration" required>
            </label>
            <label>Categories:
                <select name="categories[]" id="entry-categories" multiple>';
    foreach ($categories as $category) {
        echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
    }
    echo '
                </select>
            </label>
            <button type="submit">Save</button>
            <button type="button" id="modal-close">Cancel</button>
        </form>
    </div>';

    // JavaScript
    echo '
    <script>
       document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("kb-modal");
    const closeModal = document.getElementById("modal-close");
    const modalForm = document.getElementById("kb-modal-form");
    const entryType = document.getElementById("entry-type");
    const entryCode = document.getElementById("entry-code");
    const codeLabel = document.getElementById("code-label");
    const entryExpiration = document.getElementById("entry-expiration");
    const entryCategories = document.getElementById("entry-categories");

    closeModal.addEventListener("click", () => modal.style.display = "none");

    document.getElementById("open-modal-add").addEventListener("click", function () {
        modal.style.display = "block";
        document.getElementById("modal-title").textContent = "Add New Entry";
        modalForm.reset();
        codeLabel.style.display = "block"; // Ensure code label is shown for default
    });

    document.querySelectorAll(".open-modal-edit").forEach(btn => btn.addEventListener("click", function () {
        modal.style.display = "block";
        document.getElementById("modal-title").textContent = "Edit Entry";

        // Prefill modal fields with data from the button
        const dataType = this.dataset.type;
        const dataCode = this.dataset.code || this.dataset.hash || "";
        const dataExpiration = this.dataset.expiration || "";
        const dataCategories = this.dataset.categories ? this.dataset.categories.split(",") : [];

        entryType.value = dataType;
        entryCode.value = dataCode;
        entryExpiration.value = dataExpiration;

        // Populate categories
        [...entryCategories.options].forEach(option => {
            option.selected = dataCategories.includes(option.value);
        });

        // Show/hide code input field based on entry type
        if (dataType === "shared_link") {
            codeLabel.style.display = "none";
        } else {
            codeLabel.style.display = "block";
        }
    }));

    entryType.addEventListener("change", function () {
        if (this.value === "shared_link") {
            entryCode.value = ""; // Clear the code field
            codeLabel.style.display = "none"; // Hide the code field
        } else {
            codeLabel.style.display = "block"; // Show the code field for partner_code
        }
    });

    modalForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(modalForm);
        formData.append("action", "kb_save_entry");

        fetch(ajaxurl, {
            method: "POST",
            body: formData
        }).then(res => res.json()).then(data => {
            if (data.success) location.reload();
        });
    });

    document.querySelectorAll(".delete-entry").forEach(btn => btn.addEventListener("click", function () {
        if (!confirm("Are you sure you want to delete this?")) return;
        fetch(ajaxurl, {
            method: "POST",
            body: new URLSearchParams({
                action: "kb_delete_entry",
                id: this.dataset.id,
                table: this.dataset.table
            })
        }).then(res => res.json()).then(data => {
            if (data.success) location.reload();
        });
    }));
});
    </script>';
}

add_action('wp_ajax_kb_save_entry', 'knowledgebase_save_entry');
add_action('wp_ajax_kb_delete_entry', 'knowledgebase_delete_entry');

function knowledgebase_save_entry() {
	if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
        return;
    }
	
    global $wpdb;
    $partner_table = $wpdb->prefix . 'kb_partner_codes';
    $shared_links_table = $wpdb->prefix . 'kb_shared_links';

    $entry_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $entry_type = isset($_POST['entry_type']) ? sanitize_text_field($_POST['entry_type']) : '';
    $entry_code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';
    $entry_expiration = isset($_POST['expiration']) ? sanitize_text_field($_POST['expiration']) : '';
    $entry_categories = isset($_POST['categories']) ? array_map('sanitize_text_field', $_POST['categories']) : [];

    if (!$entry_type || !$entry_expiration) {
        wp_send_json_error(['message' => 'Required fields are missing.']);
        return;
    }

    $categories = implode(',', $entry_categories);
    $response = [];

    if ($entry_type === 'partner_code') {
        if ($entry_id > 0) {
            // Update existing partner code
            $updated = $wpdb->update(
                $partner_table,
                ['code' => $entry_code, 'expiration' => $entry_expiration, 'categories' => $categories],
                ['id' => $entry_id],
                ['%s', '%s', '%s'],
                ['%d']
            );
            if ($updated === false) {
                wp_send_json_error(['message' => 'Failed to update partner code.']);
                return;
            }
            $response['message'] = 'Partner code updated successfully.';
        } else {
            // Insert new partner code
            $inserted = $wpdb->insert(
                $partner_table,
                ['code' => $entry_code, 'expiration' => $entry_expiration, 'categories' => $categories],
                ['%s', '%s', '%s']
            );
            if (!$inserted) {
                wp_send_json_error(['message' => 'Failed to add partner code.']);
                return;
            }
            $response['message'] = 'Partner code added successfully.';
        }
    } elseif ($entry_type === 'shared_link') {
        if ($entry_id > 0) {
            // Update existing shared link
            $updated = $wpdb->update(
                $shared_links_table,
                ['expiration' => $entry_expiration, 'categories' => $categories],
                ['id' => $entry_id],
                ['%s', '%s'],
                ['%d']
            );
            if ($updated === false) {
                wp_send_json_error(['message' => 'Failed to update shared link.']);
                return;
            }
            $response['message'] = 'Shared link updated successfully.';
        } else {
            // Insert new shared link with auto-generated hash
            $hash = wp_generate_uuid4();
            $inserted = $wpdb->insert(
                $shared_links_table,
                ['hash' => $hash, 'expiration' => $entry_expiration, 'categories' => $categories],
                ['%s', '%s', '%s']
            );
            if (!$inserted) {
                wp_send_json_error(['message' => 'Failed to add shared link.']);
                return;
            }
            $response['message'] = 'Shared link added successfully.';
            $response['hash'] = $hash;
        }
    } else {
        wp_send_json_error(['message' => 'Invalid entry type.']);
        return;
    }

    wp_send_json_success($response);
}

// Delete Entry AJAX Handler
add_action('wp_ajax_kb_delete_entry', 'knowledgebase_delete_entry');
function knowledgebase_delete_entry() {
	if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
        return;
    }
	
    global $wpdb;

    $table = isset($_POST['table']) ? sanitize_text_field($_POST['table']) : '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if (empty($table) || $id <= 0) {
        wp_send_json_error(['message' => 'Invalid table or ID.']);
        return;
    }

    $deleted = $wpdb->delete($table, ['id' => $id], ['%d']);

    if ($deleted === false) {
        wp_send_json_error(['message' => 'Failed to delete entry.']);
        return;
    }

    wp_send_json_success(['message' => 'Entry deleted successfully.']);
}