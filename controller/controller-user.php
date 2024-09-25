<?php
if (!defined('ABSPATH')) {
    exit;
}
//bookacti_get_events_booking_lists

/**
 * AJAX controller for reverse the booking list on the booking list page
 */
function ba_plus_get_booking_list()
{
    if (!isset($_POST['order']) || !in_array($_POST['order'], array('asc', 'desc'))) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Les aguments ne sont incorrects'));
    }
    $order = $_POST['order'];

    // if user is admin, get user_id from request 
    if (current_user_can('manage_options') && isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];
    } else {
        $user_id = um_profile_id();
    }
    if (!$user_id) {
        wp_send_json_error(array('status' => 'error', 'message' => 'Impossible de récupérer l\'identifiant de l\'utilisateur'));
    }

    $html = do_shortcode('[bookingactivities_list columns="status,events,actions" order="' . $order . '" user_id=' . $user_id . ' order_by="event_start" ]');

    $html = str_replace(array("\r", "\n"), '', $html);
    // change wp-admin/admin-ajax.php? by the request url
    $html = str_replace('/wp-admin/admin-ajax.php?',  $_POST['uri'] . '?', $html);

    wp_send_json_success(array('status' => 'success', 'html' => $html, 'shortcode' => '[bookingactivities_list columns="status,events,actions" order="' . $order . '" user_id=' . $user_id . ' order_by="event_start" ]'));
}
add_action('wp_ajax_baPlusGetBookingList', 'ba_plus_get_booking_list');
add_action("wp_ajax_nopriv_baPlusGetBookingList", "ba_plus_get_booking_list");
