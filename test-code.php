<?php 

add_shortcode('custom_login_popup', function () {
    if (is_user_logged_in()) {
        return '<p>Vous êtes déjà connecté.</p>';
    }

    ob_start(); ?>
    <form method="post" class="custom-login-form">
        <label for="username">Nom d'utilisateur ou Email</label>
        <input type="text" name="username" id="username" required>

        <label for="password">Mot de passe</label>
        <input type="password" name="password" id="password" required>

        <input type="submit" name="custom_login_submit" value="Se connecter">
    </form>

    <?php if (isset($_POST['custom_login_submit'])) {
        $creds = array(
            'user_login'    => sanitize_user($_POST['username']),
            'user_password' => $_POST['password'],
            'remember'      => true
        );

        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {
            echo '<p style="color:red;">Erreur : ' . $user->get_error_message() . '</p>';
        } else {
            // Check approval meta
            $approved = get_user_meta($user->ID, 'approval_status', true);
            if ($approved !== 'approved') {
                wp_logout();
                echo '<p style="color:red;">Votre compte est en attente d\'approbation.</p>';
            } else {
                wp_redirect(home_url()); // Or redirect to dashboard
                exit;
            }
        }
    } ?>

    <?php return ob_get_clean();
});

//Login restriction for unapproved users
add_action('wp_login', 'check_user_approval_status_on_login', 10, 2);
function check_user_approval_status_on_login($user_login, $user) {
    $approval_status = get_user_meta($user->ID, 'approval_status', true);
    if ($approval_status !== 'approved') {
        wp_logout();
        wp_redirect(site_url('/approval-pending'));
        exit;
    }
}

// Fix login redirect to homepage after login
add_filter('login_redirect', 'custom_login_redirect', 10, 3);
function custom_login_redirect($redirect_to, $requested_redirect_to, $user) {
    // Only for successful login
    if (isset($user->roles) && is_array($user->roles)) {
        // Change 'home_url()' to your desired page if needed
        return home_url('/');
    }
    
    return $redirect_to;
}

// Force SSL on login and admin pages
if (!defined('FORCE_SSL_ADMIN')) {
    define('FORCE_SSL_ADMIN', true);
}

// Ensure WordPress uses the proper cookie domain
add_filter('site_url', function($url, $path, $orig_scheme, $blog_id) {
    return str_replace('http://', 'https://', $url);
}, 10, 4);