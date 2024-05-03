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
                    value="<?= esc_attr(get_user_meta($user->ID, 'certificat_expire_date', true)) ?>"
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
                    value="<?= esc_attr(get_user_meta($user->ID, 'attestation_expire_date', true)) ?>"
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
        'certificat_expire_date',
        $_POST['certificat_expire_date']
    );
    update_user_meta(
        $user_id,
        'attestation_expire_date',
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
        'certificat_expire_date',
        $_POST['certificat_expire_date']
    );
    update_user_meta(
        $user_id,
        'attestation_expire_date',
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
}

// # ------------ HOOCKS ------------ #

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