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
    <h3>Certificat / Attestation</h3>
    <table class="form-table">
        <tr>
            <th>
                <label for="expire_date">Date d'expiration</label>
            </th>
            <td>
                <input type="date" class="regular-text ltr" id="expire_date" name="expire_date"
                    value="<?= esc_attr(get_user_meta($user->ID, 'expire_date', true)) ?>"
                    title="Please use YYYY-MM-DD as the date format."
                    pattern="(19[0-9][0-9]|20[0-9][0-9])-(1[0-2]|0[1-9])-(3[01]|[21][0-9]|0[1-9])" required>
                <p class="description">
                    Veuillez entrer la date d'expiration de votre certificat ou attestation.
                </p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="doc_type">Type de document</label>
            </th>
            <td>
                <select name="doc_type" id="doc_type" required>
                    <option value="certificat" <?= get_user_meta($user->ID, 'doc_type', true) == "certificat" ? "selected" : "" ?>>Certificat</option>
                    <option value="attestation" <?= get_user_meta($user->ID, 'doc_type', true) == "attestation" ? "selected" : "" ?>>Attestation</option>
                </select>
                <p class="description">
                    Veuillez entrer le type de document (certificat ou attestation).
                </p>
            </td>
        </tr>
    </table>
    <?php
}

function ba_plus_usermeta_form_field_certificat_new_user($user)
{
    ?>
    <h3>Certificat / Attestation</h3>
    <table class="form-table">
        <tr class="form-required">
            <th>
                <label for="expire_date">Date d'expiration <span class="description">(nécessaire)</span></label>
            </th>
            <td>
                <input type="date" class="regular-text ltr" id="expire_date" name="expire_date"
                    title="Please use YYYY-MM-DD as the date format."
                    pattern="(19[0-9][0-9]|20[0-9][0-9])-(1[0-2]|0[1-9])-(3[01]|[21][0-9]|0[1-9])" required>
                <p class="description">
                    Veuillez entrer la date d'expiration de votre certificat ou attestation.
                </p>
            </td>
        </tr>
        <tr class="form-required">
            <th>
                <label for="doc_type">Type de document <span class="description">(nécessaire)</span></label>
            </th>
            <td>
                <select name="doc_type" id="doc_type" required>
                    <option value="certificat">Certificat</option>
                    <option value="attestation">Attestation</option>
                </select>
                <p class="description">
                    Veuillez entrer le type de document (certificat ou attestation).
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

    // create/update user meta for the $user_id
    update_user_meta(
        $user_id,
        'doc_type',
        $_POST['doc_type']
    );
    update_user_meta(
        $user_id,
        'expire_date',
        $_POST['expire_date']
    );
    update_user_meta(
        $user_id,
        'send_mail',
        'false'
    );
    update_user_meta($user_id, 'send_mail_cancel', 'true');
}

function ba_plus_create_user_certificate($user_id)
{
    update_user_meta(
        $user_id,
        'doc_type',
        $_POST['doc_type']
    );
    update_user_meta(
        $user_id,
        'expire_date',
        $_POST['expire_date']
    );
    update_user_meta(
        $user_id,
        'send_mail',
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