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

    if (!isset($_POST['event_id']) || !isset($_POST['user_id']) || !isset($_POST['event_start']) || !isset($_POST['event_end']) ) {
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
function ba_plus_ajax_edit_event(){
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('status' => 'error', 'message' => 'You do not have permission to perform this action.'));
    }

    // Check if all required parameters are set (event_id, event_title, event_start, event_end, event_dispo)
    if (!isset($_POST['event_id']) || !isset($_POST['event_title']) || !isset($_POST['event_state'])){
        wp_send_json_error(array('status' => 'error', 'message' => 'Missing parameters.'));
    }

    // Sanitize all parameters
    $event_id = sanitize_text_field($_POST['event_id']);;

    $event_title = sanitize_text_field($_POST['event_title']);
    $ret = ba_plus_change_event_title($event_id, $event_title);

    $event_state = sanitize_text_field($_POST['event_state']);
    if ($event_state == 'actif') {
        $ret = ba_plus_restore_event_availability($event_id);
    } else if ($event_state == 'complet') {
        $ret = ba_plus_change_event_availability($event_id, 0);
    } else if ($event_state == 'ferme') {
        // unlink the event if recurent
        
        $ret = ba_plus_disable_event($event_id);
        
    }
    wp_send_json_success(array('status' => 'success', 'message' => 'Event edited successfully.'));

}
add_action('wp_ajax_baPlusUpdateEvent', 'ba_plus_ajax_edit_event');