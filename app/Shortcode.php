<?php
/**
 * All Shortcode related functions
 */
namespace Worzen\B2B_Car_Enhancements\App;
use Codexpert\Plugin\Base;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @package Plugin
 * @subpackage Shortcode
 * @author Codexpert <hi@codexpert.io>
 */
class Shortcode extends Base {

    public $plugin;

    /**
     * Constructor function
     */
    public function __construct( $plugin ) {
        $this->plugin   = $plugin;
        $this->slug     = $this->plugin['TextDomain'];
        $this->name     = $this->plugin['Name'];
        $this->version  = $this->plugin['Version'];
    }

    public function render_complete_profile_form() {
        if ( ! is_user_logged_in() ) return "<p>You must be logged in to view this page.</p>";

        $user_id = get_current_user_id();

        if ( isset( $_POST['b2b_update_profile'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'b2b_update_profile_nonce' ) ) {
            update_user_meta( $user_id, 'b2b_phone_number', sanitize_text_field( $_POST['b2b_phone_number'] ) );
            update_user_meta( $user_id, 'b2b_vat_number', sanitize_text_field( $_POST['b2b_vat_number'] ) );
            update_user_meta( $user_id, 'b2b_commercial_name', sanitize_text_field( $_POST['b2b_commercial_name'] ) );
            echo '<div class="b2b-notice success"><p>Profile updated successfully!</p></div>';
            
            // Redirect to home page after successful update
            wp_redirect( home_url() );
            exit;
        }

        $phone      = get_user_meta( $user_id, 'b2b_phone_number', true );
        $vat        = get_user_meta( $user_id, 'b2b_vat_number', true );
        $company    = get_user_meta( $user_id, 'b2b_commercial_name', true );

        ob_start();
        ?>
        <div class="b2b-notice info"><p>Please complete your profile to gain full access to the site.</p></div>
        <form id="b2b-profile-completion-form" method="post">
            <?php wp_nonce_field( 'b2b_update_profile_nonce' ); ?>
            <p>
                <label for="b2b_phone_number">Phone Number *</label>
                <input type="text" name="b2b_phone_number" value="<?php echo esc_attr($phone); ?>" required>
            </p>
            <p>
                <label for="b2b_vat_number">VAT Number *</label>
                <input type="text" name="b2b_vat_number" value="<?php echo esc_attr($vat); ?>" required>
            </p>
            <p>
                <label for="b2b_commercial_name">Commercial/Company Name *</label>
                <input type="text" name="b2b_commercial_name" value="<?php echo esc_attr($company); ?>" required>
            </p>
            <p>
                <input type="submit" name="b2b_update_profile" value="Save and Continue">
            </p>
        </form>
        <?php
        return ob_get_clean();
    }

    public function render_favorites_button( $atts ) {
        if ( ! is_user_logged_in() ) return '';

        $atts           = shortcode_atts( [ 'post_id' => get_the_ID() ], $atts );
        $post_id        = intval( $atts['post_id'] );
        $user_id        = get_current_user_id();
        
        $favorites      = get_user_meta( $user_id, 'b2b_favorites', true );
        $is_favorite    = is_array($favorites) && in_array( $post_id, $favorites );

        $text           = $is_favorite ? '★ Remove from Favorites' : '☆ Add to Favorites';
        $class          = $is_favorite ? 'is-favorite' : '';

        return sprintf(
            '<a href="#" class="b2b-favorite-btn %s" data-post-id="%d">%s</a>',
            esc_attr($class),
            esc_attr($post_id),
            esc_html($text)
        );
    }

    public function render_my_favorites_list() {
        if ( ! is_user_logged_in() ) {
            return '<p>Please log in to see your favorites.</p>';
        }

        $user_id    = get_current_user_id();
        $favorites  = get_user_meta( $user_id, 'b2b_favorites', true );

        if ( empty($favorites) || !is_array($favorites) ) {
            return '<p>You have not added any cars to your favorites yet.</p>';
        }

        $args = [
            'post_type'         => B2B_CAR_POST_TYPE,
            'post__in'          => $favorites,
            'posts_per_page'    => -1,
            'orderby'           => 'post__in'
        ];
        $query = new \WP_Query($args);

        if ( !$query->have_posts() ) {
            return '<p>No favorited cars found.</p>';
        }

        ob_start();
        echo '<ul class="b2b_favorites">';
        while ( $query->have_posts() ) {
            $query->the_post();
            printf(
                '<li class="b2b_favorite"><a href="%s">%s</a></li>',
                esc_url(get_permalink()),
                esc_html(get_the_title())
            );
        }
        echo '</ul>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    public function get_user_vat_shortcode() {
        if (!is_user_logged_in()) return '';
        return esc_attr(get_user_meta(get_current_user_id(), 'b2b_vat_number', true));
    }

    public function get_user_company_shortcode() {
        if (!is_user_logged_in()) return '';
        return esc_attr(get_user_meta(get_current_user_id(), 'b2b_commercial_name', true));
    }

    public function client_list() {
        $user_query = new \WP_User_Query([
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
                    <th>Phone number</th>
                    <th>Company name</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($user_query->get_results() as $user) {
                $first_name             = get_user_meta($user->ID, 'first_name', true);
                $last_name              = get_user_meta($user->ID, 'last_name', true);
                $b2b_phone_number       = get_user_meta($user->ID, 'b2b_phone_number', true);
                $b2b_commercial_name    = get_user_meta($user->ID, 'b2b_commercial_name', true);
                $tva_number             = get_user_meta($user->ID, 'tva_number', true);
                $email                  = $user->user_email;
                $approval               = get_user_meta($user->ID, 'new_user_approve', true) ?: 'pending';
                $status                 = $approval; // keep this if you still want to use $status separately
                $status_label           = [
                    'approved' => '<span style="color:green;">Approuvé</span>',
                    'denied' => '<span style="color:red;">Rejeté</span>',
                    'pending' => '<span style="color:orange;">En attente</span>'
                ][$status];

                echo '<tr>';
                echo '<td data-label="Nom complet">' . esc_html($first_name . ' ' . $last_name) . '</td>';
                echo '<td data-label="Email">' . esc_html($email) . '</td>';
                echo '<td data-label="Numéro de TVA">' . esc_html($tva_number) . '</td>';
                echo '<td data-label="Phone number">' . $b2b_phone_number . '</td>';
                echo '<td data-label="Company name">' . $b2b_commercial_name . '</td>';
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
    }

    public function reservation_list() {
        // Vérifie que l'utilisateur est connecté
        if (!is_user_logged_in()) {
            return '<p>Vous devez être connecté pour voir les réservations.</p>';
        }

        // Optionnel : restreindre l'affichage à un seul rôle utilisateur (ex: 'administrator' ou 'um_patron')
        $user = wp_get_current_user();
        if (!in_array('administrator', $user->roles) && !in_array('um_patron', $user->roles)) {
            return '<p>Accès réservé.</p>';
        }

        $reservations = get_posts([
            'post_type'   => 'reservation',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'date',
            'order'       => 'DESC'
        ]);

        if (empty($reservations)) {
            return '<p>Aucune réservation pour le moment.</p>';
        }

        $output = '<table class="reservations-table" style="width:100%; border-collapse:collapse;">';
        $output .= '<thead><tr>
            <th style="border:1px solid #ccc; padding:8px;">Nom</th>
            <th style="border:1px solid #ccc; padding:8px;">Email</th>
            <th style="border:1px solid #ccc; padding:8px;">Phone number</th>
            <th style="border:1px solid #ccc; padding:8px;">Company name</th>
            <th style="border:1px solid #ccc; padding:8px;">Véhicule</th>
            <th style="border:1px solid #ccc; padding:8px;">Date</th>
        </tr></thead><tbody>';

        foreach ($reservations as $r) {
            $prenom     = get_post_meta($r->ID, 'prenom_client', true);
            $nom        = get_post_meta($r->ID, 'nom_client', true);
            $email      = get_post_meta($r->ID, 'email_client', true);
            $veh_id     = get_post_meta($r->ID, 'vehicule_id', true);
            $veh_title  = $veh_id ? get_the_title($veh_id) : 'Indisponible';
            $veh_link   = $veh_id ? get_permalink($veh_id) : '#';
            $date       = mysql2date('d/m/Y', $r->post_date);
            $user_id    = get_post_field( 'post_author', $r->ID );
            $b2b_phone_number    = get_user_meta( $user_id, 'b2b_phone_number', true );
            $b2b_commercial_name = get_user_meta( $user_id, 'b2b_commercial_name', true );

            $output .= "<tr>
                <td style='border:1px solid #ccc; padding:8px;'>".esc_html("$prenom $nom")."</td>
                <td style='border:1px solid #ccc; padding:8px;'>".esc_html($email)."</td>
                <td style='border:1px solid #ccc; padding:8px;'>".esc_html($b2b_phone_number)."</td>
                <td style='border:1px solid #ccc; padding:8px;'>".esc_html($b2b_commercial_name)."</td>
                <td style='border:1px solid #ccc; padding:8px;'><a href='".esc_url($veh_link)."' target='_blank'>".esc_html($veh_title)."</a></td>
                <td style='border:1px solid #ccc; padding:8px;'>".esc_html($date)."</td>
            </tr>";
        }

        $output .= '</tbody></table>';
        return $output;
    }

    public function reservation_count() {
        $count = wp_count_posts('reservation')->publish;
        return esc_html( $count ) . ' réservation' . ($count > 1 ? 's' : '');
    }

    public function client_count() {
        $user_query = new \WP_User_Query([
            // 'meta_key'     => 'new_user_approve',
            // 'meta_value'   => 'approved',
            'number'       => -1,
            'fields'       => 'ID',
        ]);

        return count($user_query->get_results());
    }

    public function b2b_voiture_count() {
        return wp_count_posts('voiture')->publish;
    }
}