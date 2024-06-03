<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Send data to the js for waiting list information on the booking form
 */
function ba_plus_get_waiting_data($booking_system_data, $atts)
{
    $waiting_list = ba_plus_get_all_waiting_list();
    $booking_system_data['waiting_list'] = array();
    foreach ($waiting_list as $waiting) {
        $event_id = $waiting->event_id;
        $user = get_userdata($waiting->user_id);
        $user_name = $user->display_name;

        if (!isset($booking_system_data['waiting_list'][$event_id])) {
            $booking_system_data['waiting_list'][$event_id] = array();
        }
        $booking_system_data['waiting_list'][$event_id][$waiting->start_date][] = array(
            'user_id' => $waiting->user_id,
            'user_name' => $user_name,
            'waiting_id' => $waiting->id,
            'start_date' => $waiting->start_date,
            'end_date' => $waiting->end_date,
        );
    }
    return $booking_system_data;
}
add_filter("bookacti_booking_system_data", "ba_plus_get_waiting_data", 2, 2);

/**
 * Add js to the booking list of the user
 */
function ba_plus_booking_list_scripts($booking_list, $raw_atts, $content){
    wp_enqueue_script('ba-wl-sort');
    return $booking_list;
}
add_filter("bookacti_shortcode_bookingactivities_list_output", "ba_plus_booking_list_scripts", 5, 3);


/**
 * Add js to the booking forms
 */
function ba_plus_booking_forms_scripts($output, $raw_atts, $content){
    wp_enqueue_script('ba-wl-enable');
    return $output;
}
add_filter("bookacti_shortcode_bookingactivities_form_output", "ba_plus_booking_forms_scripts", 5, 3);

