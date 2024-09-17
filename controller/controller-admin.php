<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Controller - Add a booking for an user by an admin
 */
function ba_plus_ajax_add_booking()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('status' => 'error', 'message' => 'You do not have permission to perform this action.'));
    }

    if (!isset($_POST['event_id']) || !isset($_POST['user_id']) || !isset($_POST['event_start']) || !isset($_POST['event_end'])) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Missing parameters.'));
    }

    $event_id = sanitize_text_field($_POST['event_id']);
    $user_id = sanitize_text_field($_POST['user_id']);
    $event_start = sanitize_text_field($_POST['event_start']);
    $event_end = sanitize_text_field($_POST['event_end']);

    // Check if the user has a booking pass
    $filters = array(
        'user_id' => $user_id,
        'active' => 1
    );
    $filters = bapap_format_booking_pass_filters($filters);
    $pass = bapap_get_booking_passes($filters);

    if (empty($pass)) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Le forfait de ce client a une date de validité dépassée'));
    }

    // remove the booking pass that has no more credits
    $pass = array_filter($pass, function ($p) {
        return $p->credits_current > 0;
    });

    if (empty($pass)) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Le forfait de ce client est à 0'));
    }

    // sort the booking pass by expiration date, shorter first
    usort($pass, function ($a, $b) {
        return strtotime($a->expiration_date) - strtotime($b->expiration_date);
    });

    $pass = $pass[0];

    if ($pass->credits_current <= 0) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Le forfait de ce client est à 0'));
    }

    $timezone = new DateTimeZone('Europe/Paris');
    $today = new DateTime('now', $timezone);

    $certi_date = get_user_meta($user_id, "certif_med", true);
    $attest_date = get_user_meta($user_id, "attest_med", true);

    $certif_expire_date = new DateTime($certi_date, $timezone);
    $attest_expire_date = new DateTime($attest_date, $timezone);

    $certif_diff = date_diff($today, $certif_expire_date);
    $attest_diff = date_diff($today, $attest_expire_date);
    if (empty($certi_date) || empty($attest_date)) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Ce client n\'a pas de certificat médical ou d\'attestation médicale.'));
    } else if ($certif_diff->invert == 1) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Le certificat médical de ce client doit être renouvelé'));
    } else if ($attest_diff->invert == 1) {
        wp_send_json_error(array('status' => 'error', 'message' => 'L\'attestation médicale de ce client doit être renouvelée'));
    }



    $booking_data = bookacti_sanitize_booking_data(
        array(
            'user_id' => $user_id,
            'form_id' => 0,
            'event_id' => $event_id,
            'event_start' => $event_start,
            'event_end' => $event_end,
            'quantity' => 1,
            'status' => "booked",
            'payment_status' => "paid",
            'active' => 'according_to_status'
        )
    );
    $booking_id = bookacti_insert_booking($booking_data);
    if ($booking_id) {
        // remove a credit from the pass
        $credited = bapap_add_booking_pass_credits($pass->id, -1);
        if (!$credited) {
            wp_send_json_error(array('status' => 'error', 'message' => 'Une erreur est survenue lors du débit de la réservation.'));
        }
        $new_booking_meta = array(
            'booking_pass_id' => $pass->id,
            'booking_pass_credits' => 1
        );

        bookacti_update_metadata("booking", $booking_id, $new_booking_meta);

        // add to the log
        $log_data = array(
            'credits_delta' => -1,
            'credits_current' => $pass->credits_current - 1,
            'credits_total' => $pass->credits_total,
            'reason' => "Réservation ADMIN (depuis le planning) - " . $booking_data['event_title'] . " (" . $booking_data['event_start'] . ")",
            'context' => 'updated_from_server',
            'lang_switched' => 1
        );
        bapap_add_booking_pass_log($pass->id, $log_data);

        // send email
        bookacti_send_notification('customer_booked_booking', $booking_id, "single");


        wp_send_json_success(array('status' => 'success', 'message' => 'Booking added successfully.'));
    } else {
        wp_send_json_error(array('status' => 'error', 'message' => 'An error occurred while adding the booking.'));
    }
}
add_action('wp_ajax_baPlusAdminBooking', 'ba_plus_ajax_add_booking');



/**
 * AJAX Controller - Cancel a booking by an admin
 */
function ba_plus_ajax_cancel_booking()
{
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('status' => 'error', 'message' => 'You do not have permission to perform this action.'));
    }

    // Check if all required parameters are set (booking_id)
    if (!isset($_POST['booking_id'])) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Missing parameters.'));
    }

    // Sanitize all parameters
    $booking_id = intval(sanitize_text_field($_POST['booking_id']));
    $booking = bookacti_get_booking_by_id($booking_id, true);
    $user = get_user_by('id', $booking->user_id);

    
    $cancelled = ba_plus_set_cancel_booking($booking_id);

    if (!$cancelled) {
        wp_send_json_error(array('status' => 'error', 'message' => 'An error occurred while refunding the booking.'));
    }

    // get new booking 
    $new_booking = bookacti_get_booking_by_id($booking_id, false);
    do_action('bookacti_booking_state_changed', $new_booking, 'cancelled', array('is_admin' => true));

    wp_send_json_success(array('status' => 'success', 'message' => 'La réservation a été annulée avec succès.'));
}
add_action('wp_ajax_baPlusCancelBooking', 'ba_plus_ajax_cancel_booking');


/**
 * AJAX Controller - Edit event by an admin
 */
function ba_plus_ajax_edit_event()
{
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('status' => 'error', 'message' => 'You do not have permission to perform this action.'));
    }

    // Check if all required parameters are set (event_id, event_title, event_start, event_end, event_dispo)
    if (!isset($_POST['event_id']) || !isset($_POST['event_title']) || !isset($_POST['event_state']) || !isset($_POST['event_start']) || !isset($_POST['event_end'])) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Missing parameters.'));
    }

    // Sanitize all parameters
    $event_id = intval(sanitize_text_field($_POST['event_id']));
    $event_start = sanitize_text_field($_POST['event_start']);
    $event_end = sanitize_text_field($_POST['event_end']);

    // unbind the event if recurent
    if (isset($_POST['is_recurring']) && intval($_POST['is_recurring']) == 1) {
        $event = bookacti_get_event_by_id($event_id);
        $new_id = bookacti_unbind_selected_event_occurrence($event, $event_start, $event_end);
        if ($new_id) {
            ba_plus_update_event_id_waiting_list($event_id, $event_start, $event_end, $new_id);
            $event_id = $new_id;
        } else {
            wp_send_json_error(array('status' => 'error', 'message' => 'An error occurred while unbinding the event.'));
        }
    }


    $event_title = sanitize_text_field($_POST['event_title']);
    $ret = ba_plus_change_event_title($event_id, $event_start, $event_end, $event_title);

    $event_state = sanitize_text_field($_POST['event_state']);
    $filters = array('event_id' => $event_id, 'status' => 'booked', 'from' => $event_start, 'to' => $event_end);
    $filters = bookacti_format_booking_filters($filters);
    $current_booking = count(bookacti_get_bookings($filters));
    if ($event_state == 'actif') {
        $ret = ba_plus_restore_event_availability($event_id, $event_start, $event_end);
    } else if ($event_state == 'complet') {
        $ret = ba_plus_change_event_availability($event_id, $event_start, $event_end, $current_booking);
    } else if ($event_state == 'ferme') {
        $ret = ba_plus_disable_event($event_id, $event_start, $event_end);
    }

    if (isset($_POST['new_availability'])) {
        if (!is_numeric($_POST['new_availability'])) {
            wp_send_json_error(array('status' => 'error', 'message' => 'Invalid availability. (must be a number)'));
        }
        if (intval($_POST['new_availability']) < 0) {
            wp_send_json_error(array('status' => 'error', 'message' => 'Invalid availability. (must be >= 0)'));
        }
        $availability = intval(sanitize_text_field($_POST['new_availability']));
        $ret = ba_plus_change_event_availability($event_id, $event_start, $event_end, $availability);
    }

    wp_send_json_success(array('status' => 'success', 'message' => 'Event edited successfully.'));
}
add_action('wp_ajax_baPlusUpdateEvent', 'ba_plus_ajax_edit_event');


/**
 * AJAX Controller - Refund a booking by an admin
 */
function ba_plus_ajax_refund_booking()
{
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('status' => 'error', 'message' => 'You do not have permission to perform this action.'));
    }

    // Check if all required parameters are set (booking_id)
    if (!isset($_POST['booking_id'])) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Missing parameters.'));
    }

    // Sanitize all parameters
    $booking_id = intval(sanitize_text_field($_POST['booking_id']));


    // Get the booking
    $booking = bookacti_get_booking_by_id($booking_id, true);
    $user = get_user_by('id', $booking->user_id);

    $nb_cancelled_events = get_user_meta($booking->user_id, 'nb_cancel_left', true);
    if (empty($nb_cancelled_events) || intval($nb_cancelled_events) <= 0) {
        wp_send_json_error(array('status' => 'error', 'message' => 'ce client a utilisé tout son quota d\'annulations sans frais'));
    }

    // get the most recent booking pass
    $filters = array(
        'user_id' => $booking->user_id,
        'active' => 1
    );
    $filters = bapap_format_booking_pass_filters($filters);
    $pass = bapap_get_booking_passes($filters);

    if (empty($pass)) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Le forfait de ce client a une date de validité dépassée'));
    }

    // sort the booking pass by expiration date, longer first
    usort($pass, function ($a, $b) {
        return -strtotime($a->expiration_date) + strtotime($b->expiration_date);
    });



    // Refund the booking    
    $credited = bapap_add_booking_pass_credits($pass[0]->id, intval($booking->booking_pass_credits));
    if (!$credited) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Il y a eu une erreur lors du remboursement de la réservation.'));
    }

    // add to the log
    $log_data = array(
        'credits_delta' => $booking->booking_pass_credits,
        'credits_current' => intval($pass[0]->credits_current) + intval($booking->booking_pass_credits),
        'credits_total' => $pass[0]->credits_total,
        'reason' => "Annulation ADMIN (depuis le planning) - " . $booking->event_title . " (" . $booking->event_start . ")",
        'context' => 'updated_from_server',
        'lang_switched' => 1
    );
    bapap_add_booking_pass_log($pass[0]->id, $log_data);

    $cancelled = ba_plus_set_refunded_booking($booking_id);

    if (!$cancelled) {
        wp_send_json_error(array('status' => 'error', 'message' => 'An error occurred while refunding the booking.'));
    }
    do_action('bookacti_booking_state_changed', $booking, 'refunded', array('is_admin' => true, 'refund_action' => "booking_passes"));


    wp_send_json_success(array('status' => 'success', 'message' => 'Booking refunded successfully.'));
}
add_action('wp_ajax_baPlusRefundBooking', 'ba_plus_ajax_refund_booking');

/**
 * AJAX Controller - Edit Settings by an admin
 */
function ba_plus_ajax_edit_settings()
{
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('status' => 'error', 'message' => 'You do not have permission to perform this action.'));
    }

    // Check if all required parameters are set (settings)
    if (!isset($_POST['settings'])) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Missing parameters.'));
    }

    // Sanitize all parameters
    $settings = $_POST['settings'];

    // Update the settings
    if (!is_array($settings)) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Invalid settings.'));
    }

    $updated = false;

    if (isset($settings['free_cancel_delay'])) {
        if (!is_numeric($settings['free_cancel_delay'])) {
            wp_send_json_error(array('status' => 'error', 'message' => 'Invalid free cancel delay. (must be a number)'));
        }
        if (intval($settings['free_cancel_delay']) < 0) {
            wp_send_json_error(array('status' => 'error', 'message' => 'Invalid free cancel delay. (must be >= 0)'));
        }

        $settings['free_cancel_delay'] = intval($settings['free_cancel_delay']);
        $updated = update_option("ba_plus_refund_delay", $settings['free_cancel_delay']);
    }

    if (isset($settings['nb_cancel_left'])) {
        if (!is_numeric($settings['nb_cancel_left'])) {
            wp_send_json_error(array('status' => 'error', 'message' => 'Invalid number of free cancels. (must be a number)'));
        }
        $settings['nb_cancel_left'] = intval($settings['nb_cancel_left']);

        if ($settings['nb_cancel_left'] < 0) {
            wp_send_json_error(array('status' => 'error', 'message' => 'Invalid number of free cancels. (must be >= 0)'));
        }

        $user_id = intval($_POST['user_id']);
        $updated = update_user_meta($user_id, 'nb_cancel_left', $settings['nb_cancel_left']);
    }

    if (isset($settings["forfait"])) {
        $user_id = intval($_POST['user_id']);
        $start_date = $settings['start_date'];

        if (!is_numeric($settings['forfait'])) {
            wp_send_json_error(array('status' => 'error', 'message' => 'Invalid booking pass template. (must be a number)'));
        }

        if ($settings["start_date"] == "" || strtotime($settings["start_date"]) === false || !$settings["start_date"]) {
            wp_send_json_error(array('status' => 'error', 'message' => 'Invalid start date.'));
        }

        $booking_pass_template_id = $settings['forfait'];
        if ($booking_pass_template_id != 'none' && $booking_pass_template_id != '' && intval($booking_pass_template_id) > 0) {
            $booking_pass = bapap_get_booking_pass_template(intval($booking_pass_template_id));
            if (!empty($booking_pass)) {
                $validity = $booking_pass['validity_period'];
                $end_date = date('Y-m-d H:i:s', strtotime("+$validity days", strtotime($start_date)));
                $user = get_user_by('id', $user_id);
                $data = array(
                    'id' => 0,
                    'title' =>  $user->display_name . " - " . $booking_pass['title'],
                    'pass_template_id' => $booking_pass_template_id,
                    'credits_total' => $booking_pass['credits'],
                    'credits_current' => $booking_pass['credits'],
                    'user_id' => $user_id,
                    'creation_date' => date('Y-m-d H:i:s', strtotime($start_date)),
                    'expiration_date' => $end_date,
                );
                $data = bapap_sanitize_booking_pass_data(array_merge($_POST, $data));
                $booking_pass_id = bapap_create_booking_pass($data);

                if ($booking_pass_id) {
                    $log_data = array(
                        'credits_current' => $booking_pass['credits'],
                        'credits_total' => $booking_pass['credits'],
                        'reason' => esc_html__('Booking pass created from the admin panel.', 'ba-prices-and-credits'),
                        'context' => 'created_from_admin',
                        'lang_switched' => 1
                    );
                    bapap_add_booking_pass_log($booking_pass_id, $log_data);
                    $updated = 1;
                } else {
                    update_user_meta($user_id, 'debug', print_r($data, true));
                    $updated = 0;
                }
            } else {
                update_user_meta($user_id, 'debug', "error");
                $updated = 0;
            }
        } else {
            update_user_meta($user_id, 'debug', "error");
            $updated = 0;
        }
    }



    if (!$updated) {
        wp_send_json_error(array('status' => 'error', 'message' => 'An error occurred while updating the settings.'));
    }

    wp_send_json_success(array('status' => 'success', 'message' => 'Settings updated successfully.'));
}
add_action('wp_ajax_baPlusUpdateSettings', 'ba_plus_ajax_edit_settings');
