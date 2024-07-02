<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The field on the editing screens.
 *
 * @param $user WP_User user object
 */
function ba_plus_usermeta_form_field_certificat($user)
{
    ?>
    <h3>Certificat & Attestation</h3>
    <table class="form-table">
        <tr>
            <th>
                <label for="expire_date">Date d'expiration du certificat</label>
            </th>
            <td>
                <input type="date" class="regular-text ltr" id="certificat_expire_date" name="certificat_expire_date"
                    value="<?= esc_attr(get_user_meta($user->ID, 'certif_med', true)) ?>"
                    title="Please use YYYY-MM-DD as the date format."
                    pattern="(19[0-9][0-9]|20[0-9][0-9])-(1[0-2]|0[1-9])-(3[01]|[21][0-9]|0[1-9])" required>
                <p class="description">
                    Veuillez entrer la date d'expiration de votre certificat.
                </p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="expire_date">Date d'expiration de l'attestation</label>
            </th>
            <td>
                <input type="date" class="regular-text ltr" id="attestation_expire_date" name="attestation_expire_date"
                    value="<?= esc_attr(get_user_meta($user->ID, 'attest_med', true)) ?>"
                    title="Please use YYYY-MM-DD as the date format."
                    pattern="(19[0-9][0-9]|20[0-9][0-9])-(1[0-2]|0[1-9])-(3[01]|[21][0-9]|0[1-9])" required>
                <p class="description">
                    Veuillez entrer la date d'expiration de votre attestation.
                </p>
            </td>
        </tr>
    </table>
    <?php
}

function ba_plus_usermeta_form_field_certificat_new_user($user)
{
    ?>
    <h3>Informations Complémentaires</h3>
    <table class="form-table">
        <tr>
            <th>
                <label for="expire_date">Numéro de téléphone</label>
            </th>
            <td>
                <input type="tel" name="phone" class="regular-test ltr">
                <p class="description">
                    Veuillez entrer votre numéro de téléphone
                </p>
            </td>
        </tr>
    </table>

    <h3>Certificat & Attestation</h3>
    <table class="form-table">
        <tr class="form-required">
            <th>
                <label for="expire_date">Date d'expiration du certificat <span class="description">(nécessaire)</span></label>
            </th>
            <td>
                <input type="date" class="regular-text ltr" id="certificat_expire_date" name="certificat_expire_date"
                    title="Please use YYYY-MM-DD as the date format."
                    pattern="(19[0-9][0-9]|20[0-9][0-9])-(1[0-2]|0[1-9])-(3[01]|[21][0-9]|0[1-9])" required>
                <p class="description">
                    Veuillez entrer la date d'expiration de votre certificat
                </p>
            </td>
        </tr>
        <tr class="form-required">
            <th>
                <label for="expire_date">Date d'expiration de l'attestation<span class="description">(nécessaire)</span></label>
            </th>
            <td>
                <input type="date" class="regular-text ltr" id="attestation_expire_date" name="attestation_expire_date"
                    title="Please use YYYY-MM-DD as the date format."
                    pattern="(19[0-9][0-9]|20[0-9][0-9])-(1[0-2]|0[1-9])-(3[01]|[21][0-9]|0[1-9])" required>
                <p class="description">
                    Veuillez entrer la date d'expiration de votre attestation
                </p>
            </td>
        </tr>
    </table>
    <h3>Forfait de réservations</h3>
    <table class="form-table">
        <tr class="form-required">
            <?php
            $booking_pass_templates = bapap_get_booking_pass_templates( bapap_format_booking_pass_template_filters() );
            $booking_pass_templates_options = array( 'none' => esc_html__( 'Aucun', 'booking-activities' ) );
            foreach( $booking_pass_templates as $booking_pass_template ) {
                $booking_pass_templates_options[ $booking_pass_template->id ] = ! empty( $booking_pass_template->title ) ? apply_filters( 'bookacti_translate_text', $booking_pass_template->title ) : sprintf( esc_html__( 'Booking pass template #%s', 'ba-prices-and-credits' ), $booking_pass_template->id );
            }
            
            $current_booking_pass_template_ids = isset( $_GET[ 'booking_pass_template_id' ] ) ? $_GET[ 'booking_pass_template_id' ] : 0;
            $args = array( 
                'name'     => 'booking_pass_template_id',
                'id'       => 'bapap-booking-passes-filter-booking-pass-template',
                'type'     => 'select',
                'multiple' => 'maybe',
                'class'    => 'bookacti-select2-no-ajax',
                'options'  => $booking_pass_templates_options,
                'value'    => ! is_array( $current_booking_pass_template_ids ) ? intval( $current_booking_pass_template_ids ) : ( count( $current_booking_pass_template_ids ) > 1 ? array_map( 'intval', $current_booking_pass_template_ids ) : intval( $current_booking_pass_template_ids[ 0 ] ) )
            );
            bookacti_display_field( $args );
            ?>
        </tr>
    <?php
}

/**
 * The save action.
 *
 * @param $user_id int the ID of the current user.
 *
 * @return bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function ba_plus_usermeta_form_field_certificate_update($user_id)
{
    // check that the current user have the capability to edit the $user_id
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    update_user_meta(
        $user_id,
        'certif_med',
        $_POST['certificat_expire_date']
    );
    update_user_meta(
        $user_id,
        'attest_med',
        $_POST['attestation_expire_date']
    );
    update_user_meta(
        $user_id,
        'send_mail_certif_expire',
        'false'
    );
    update_user_meta(
        $user_id,
        'send_mail_attes_expire',
        'false'
    );
    update_user_meta($user_id, 'send_mail_cancel', 'false');
}

function ba_plus_create_user_certificate($user_id)
{
    update_user_meta(
        $user_id,
        'certif_med',
        $_POST['certificat_expire_date']
    );
    update_user_meta(
        $user_id,
        'attest_med',
        $_POST['attestation_expire_date']
    );
    update_user_meta(
        $user_id,
        'send_mail_certif_expire',
        'false'
    );
    update_user_meta(
        $user_id,
        'send_mail_attes_expire',
        'false'
    );

    update_user_meta($user_id, "nb_cancel_left", 0);
    update_user_meta($user_id, 'send_mail_cancel', 'false');

    update_user_meta(
        $user_id,
        'phone',
        $_POST['phone']
    );

    $booking_pass_template_id = $_POST['booking_pass_template_id'];
    if ( $booking_pass_template_id != 'none' && $booking_pass_template_id != '' && intval($booking_pass_template_id) > 0) {
        $booking_pass = bapap_get_booking_pass_template( intval( $booking_pass_template_id ) );
        if ( ! empty( $booking_pass ) ) {
            $validity = $booking_pass['validity_period'];
            $user = get_user_by('id', $user_id);
            $data = array (
                'id' => 0,
                'title' =>  $user->display_name . " - " . $booking_pass['title'],
                'pass_template_id' => $booking_pass_template_id,
                'credits_total' => $booking_pass['credits'],
                'credits_current' => $booking_pass['credits'],
                'user_id' => $user_id,
                'creation_date' => date('Y-m-d H:i:s'),
                'expiration_date' => date('Y-m-d H:i:s', strtotime("+$validity days")),
            );
            $data = bapap_sanitize_booking_pass_data( array_merge( $_POST, $data ) );
            $booking_pass_id = bapap_create_booking_pass( $data );

            if ( $booking_pass_id ) {
                $log_data = array( 
                    'credits_current' => $booking_pass['credits'],
                    'credits_total' => $booking_pass['credits'],
                    'reason' => esc_html__( 'Booking pass created from the admin panel.', 'ba-prices-and-credits' ),
                    'context' => 'created_from_admin',
                    'lang_switched' => 1
                );
                bapap_add_booking_pass_log( $booking_pass_id, $log_data );
            } else {
                update_user_meta($user_id, 'debug', print_r($data, true));
            }

        } else {
            update_user_meta($user_id, 'debug', "error");
        }
    }
}

// # ------------ HOOKS ------------ #

// Add the field to user profile editing screen.
add_action(
    'edit_user_profile',
    'ba_plus_usermeta_form_field_certificat'
);

// Add the field to user new profile screen. REMOVE IF NOT NEEDED
add_action(
    'show_user_profile',
    'ba_plus_usermeta_form_field_certificat'
);

// Add the save action to user's own profile editing screen update.
add_action(
    'personal_options_update',
    'ba_plus_usermeta_form_field_certificate_update'
);

// Add the save action to user profile editing screen update.
add_action(
    'edit_user_profile_update',
    'ba_plus_usermeta_form_field_certificate_update'
);

add_action("user_new_form", "ba_plus_usermeta_form_field_certificat_new_user");

add_action("user_register", "ba_plus_create_user_certificate");