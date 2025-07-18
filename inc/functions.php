<?php
if( ! function_exists( 'get_plugin_data' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}
use Worzen\B2B_Car_Enhancements\Helper;

/**
 * Gets the site's base URL
 * 
 * @uses get_bloginfo()
 * 
 * @return string $url the site URL
 */
if( ! function_exists( 'pc_site_url' ) ) :
function pc_site_url() {
	$url = get_bloginfo( 'url' );

	return $url;
}
endif;

// um_client

// 3. Add column to Users admin panel
add_filter('manage_users_columns', function ($columns) {
    $columns['user_approved'] 	= 'Approval Status';
    $columns['approval_action'] = 'Action';
    return $columns;
});

add_filter('manage_users_custom_column', function ($value, $column_name, $user_id) {
    if ($column_name == 'user_approved') {
        $approved = get_user_meta($user_id, 'new_user_approve', true);
        // $approved = get_user_meta($user_id, '_user_approved', true);
        return $approved === 'approved' ? '✅ Approved' : '❌ Pending';
    }
    return $value;
}, 10, 3);

// 4. Add Approve/Reject bulk actions
add_filter('bulk_actions-users', function ($bulk_actions) {
    $bulk_actions['approve_users'] = 'Approve Users';
    $bulk_actions['reject_users'] = 'Reject Users';
    return $bulk_actions;
});

add_filter('handle_bulk_actions-users', function ($redirect_to, $doaction, $user_ids) {
    if ($doaction === 'approve_users') {
        foreach ($user_ids as $user_id) {
            update_user_meta($user_id, 'new_user_approve', 'approved');
            update_user_meta($user_id, 'approval_status', 'approved');
            update_user_meta($user_id, 'account_status', 'approved');
            // update_user_meta($user_id, '_user_approved', 'yes');
        }
        $redirect_to = add_query_arg('approved_users', count($user_ids), $redirect_to);
    }
    if ($doaction === 'reject_users') {
        foreach ($user_ids as $user_id) {
            wp_delete_user($user_id); // Or just update meta to rejected if you prefer
        }
        $redirect_to = add_query_arg('rejected_users', count($user_ids), $redirect_to);
    }
    return $redirect_to;
}, 10, 3);

// 6. Display Approve/Reject buttons in the new column
add_filter('manage_users_custom_column', function ($value, $column_name, $user_id) {
    if ($column_name === 'approval_action') {
        $status = get_user_meta($user_id, 'new_user_approve', true);
        // $status = get_user_meta($user_id, '_user_approved', true);

        if ($status !== 'approved') {
            $approve_url = wp_nonce_url(add_query_arg([
                'action' => 'approve_user',
                'user_id' => $user_id,
            ]), 'approve_user_' . $user_id);

            $reject_url = wp_nonce_url(add_query_arg([
                'action' => 'reject_user',
                'user_id' => $user_id,
            ]), 'reject_user_' . $user_id);

            return '<a href="' . esc_url($approve_url) . '" class="button button-primary" style="margin-right:5px;">Approve</a>' .
                   '<a href="' . esc_url($reject_url) . '" class="button button-secondary">Reject</a>';
        } else {
            return '<span style="color: green; font-weight: bold;">Approved</span>';
        }
    }

    return $value;
}, 10, 3);

// 7. Handle Approve and Reject actions
add_action('admin_init', function () {
    if (!current_user_can('edit_users')) {
        return;
    }

    if (isset($_GET['action'], $_GET['user_id']) && $_GET['action'] === 'approve_user') {
        $user_id = intval($_GET['user_id']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'approve_user_' . $user_id)) {
            update_user_meta($user_id, 'new_user_approve', 'approved');
            update_user_meta($user_id, 'approval_status', 'approved');
            update_user_meta($user_id, 'account_status', 'approved');
            // update_user_meta($user_id, '_user_approved', 'yes');
            wp_redirect(add_query_arg(['user_approved_single' => 1], remove_query_arg(['action', 'user_id', '_wpnonce'])));
            exit;
        }
    }

    if (isset($_GET['action'], $_GET['user_id']) && $_GET['action'] === 'reject_user') {
        $user_id = intval($_GET['user_id']);
        if (wp_verify_nonce($_GET['_wpnonce'], 'reject_user_' . $user_id)) {
            wp_delete_user($user_id);
            wp_redirect(add_query_arg(['user_rejected_single' => 1], remove_query_arg(['action', 'user_id', '_wpnonce'])));
            exit;
        }
    }
});

// 8. Admin notice for single approve/reject
add_action('admin_notices', function () {
    if (isset($_GET['user_approved_single'])) {
        echo '<div class="notice notice-success is-dismissible"><p>User approved successfully.</p></div>';
    }
    if (isset($_GET['user_rejected_single'])) {
        echo '<div class="notice notice-warning is-dismissible"><p>User rejected and deleted successfully.</p></div>';
    }
});

add_action( 'init', function() {

    if ( isset($_COOKIE['b2b_pending_approval']) ) {
        $user_id = intval($_COOKIE['b2b_pending_approval']);


        // Validate user ID exists and is logged in (optional)
        if ( $user_id && get_user_by('id', $user_id) ) {

            $approval_status = get_user_meta( $user_id, 'new_user_approve', true );

            if ( $approval_status === 'approved' ) {
                // ✅ Delete cookie if approved
                setcookie( 'b2b_pending_approval', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
            }
        }
    }

});

add_shortcode('liste_clients', function () {
    $user_query = new WP_User_Query([
        'role'    => '',
        'orderby' => 'registered',
        'order'   => 'DESC'
    ]);

    if (empty($user_query->get_results())) {
        return '<p>Aucun client trouvé.</p>';
    }

    ob_start();
    ?>
    <div class="client-search-wrapper">
        <input type="text" id="clientSearch" placeholder="Rechercher par nom, prénom, email ou TVA..." />
    </div>

    <table class="client-table" id="clientTable">
        <thead>
            <tr>
                <th>Nom complet</th>
                <th>Email</th>
                <th>Numéro de TVA</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        foreach ($user_query->get_results() as $user) {
            $first_name  = get_user_meta($user->ID, 'first_name', true);
            $last_name   = get_user_meta($user->ID, 'last_name', true);
            $tva_number  = get_user_meta($user->ID, 'tva_number', true);
            $email       = $user->user_email;
            $approval = get_user_meta($user->ID, 'new_user_approve', true) ?: 'pending';
            $status = $approval; // keep this if you still want to use $status separately
            $status_label = [
                'approved' => '<span style="color:green;">Approuvé</span>',
                'denied' => '<span style="color:red;">Rejeté</span>',
                'pending' => '<span style="color:orange;">En attente</span>'
            ][$status];

            echo '<tr>';
            echo '<td data-label="Nom complet">' . esc_html($first_name . ' ' . $last_name) . '</td>';
            echo '<td data-label="Email">' . esc_html($email) . '</td>';
            echo '<td data-label="Numéro de TVA">' . esc_html($tva_number) . '</td>';
            echo '<td data-label="Statut">' . $status_label . '</td>';
            echo '<td data-label="Actions">';

            if ($approval === 'pending') {
                echo '<a style="color:green;" href="?approve_user=' . $user->ID . '" onclick="return confirm(\'Approuver ce client ?\');">Approuver</a> | ';
                echo '<a style="color:red;" href="?reject_user=' . $user->ID . '" onclick="return confirm(\'Rejeter ce client ?\');">Rejeter</a>';
            } else {
                echo '<a href="?delete_user=' . $user->ID . '" onclick="return confirm(\'Supprimer ce client ?\');">Supprimer</a>';
            }

            echo '</td>';
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const searchInput = document.getElementById("clientSearch");
        const rows = document.querySelectorAll("#clientTable tbody tr");

        searchInput.addEventListener("input", function () {
            const searchTerm = this.value.toLowerCase().trim();

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(searchTerm) ? "" : "none";
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
});
