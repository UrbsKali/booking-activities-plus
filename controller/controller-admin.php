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
    if (!isset($_POST['event_id']) || !isset($_POST['event_title']) || !isset($_POST['event_state']) || !isset($_POST['event_start']) || !isset($_POST['event_end']) || !isset($_POST['ba_action'])) {
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

    $ba_action = sanitize_text_field($_POST['ba_action']);
    $ba_action = explode(',', $ba_action);
    foreach ($ba_action as $action) {
        $action = sanitize_text_field($action);
        if ($action == 'title') {
            $event_title = sanitize_text_field($_POST['event_title']);
            $ret = ba_plus_change_event_title($event_id, $event_title);
        } else if ($action == 'state') {
            $event_state = sanitize_text_field($_POST['event_state']);
            if ($event_state == 'actif') {
                $ret = ba_plus_restore_event_availability($event_id);
            } else if ($event_state == 'complet') {
                $ret = ba_plus_change_event_availability($event_id, 0);
            } else if ($event_state == 'ferme') {
                $ret = ba_plus_disable_event($event_id);
            }
        }
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

    $cancelled = ba_plus_set_refunded_booking($booking_id);

    if (!$cancelled) {
        wp_send_json_error(array('status' => 'error', 'message' => 'An error occurred while refunding the booking.'));
    }

    // Send mail
    $user = get_user_by('id', $booking->user_id);
    $to = $user->user_email;
    $subject = get_option('ba_plus_mail_cancel_title');
    $body = get_option('ba_plus_mail_cancel_body');
    $body = str_replace('%event%', $booking->event_title, $body);
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($to, $subject, $body, $headers);

    wp_send_json_success(array('status' => 'success', 'message' => 'Booking refunded successfully.'));
}
add_action('wp_ajax_baPlusRefundBooking', 'ba_plus_ajax_refund_booking');