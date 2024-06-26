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
        wp_send_json_success(array('status' => 'success', 'message' => 'Booking added successfully.'));
    } else {
        wp_send_json_error(array('status' => 'error', 'message' => 'An error occurred while adding the booking.'));
    }
}
add_action('wp_ajax_baPlusAdminBooking', 'ba_plus_ajax_add_booking');

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
            $event_id = $new_id;
        } else {
            wp_send_json_error(array('status' => 'error', 'message' => 'An error occurred while unbinding the event.'));
        }
    }


    $event_title = sanitize_text_field($_POST['event_title']);
    $ret = ba_plus_change_event_title($event_id, $event_start, $event_end, $event_title);

    $event_state = sanitize_text_field($_POST['event_state']);
    if ($event_state == 'actif') {
        $ret = ba_plus_restore_event_availability($event_id, $event_start, $event_end);
    } else if ($event_state == 'complet') {
        $ret = ba_plus_change_event_availability($event_id, $event_start, $event_end, 0);
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

    // Get the booking pass
    if (!$booking->booking_pass_id) {
        //wp_send_json_error(array('status' => 'error', 'message' => 'La réservation n\'est pas liée à un pass'));
    } else {
        $booking_pass = bapap_get_booking_pass($booking->booking_pass_id);

        // Refund the booking    
        $credited = bapap_add_booking_pass_credits($booking->booking_pass_id, intval($booking->booking_pass_credits));
        if (!$credited) {
            wp_send_json_error(array('status' => 'error', 'message' => 'An error occurred while refunding the booking.'));
        }

        // add to the log
        $log_data = array(
            'credits_delta' => $booking->booking_pass_credits,
            'credits_current' => intval($booking_pass['credits_current']) + intval($booking->booking_pass_credits),
            'credits_total' => $booking_pass['credits_total'],
            'reason' => "Annulation ADMIN (depuis le planning) - " . $booking->event_title . " (" . $booking->event_start . ")",
            'context' => 'updated_from_server',
            'lang_switched' => 1
        );
        bapap_add_booking_pass_log($booking_pass['id'], $log_data);
    }

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



    if (!$updated) {
        wp_send_json_error(array('status' => 'error', 'message' => 'An error occurred while updating the settings.'));
    }

    wp_send_json_success(array('status' => 'success', 'message' => 'Settings updated successfully.'));
}
add_action('wp_ajax_baPlusUpdateSettings', 'ba_plus_ajax_edit_settings');