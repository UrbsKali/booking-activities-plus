<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Send data to the js for waiting list information on the booking form
 */
function ba_plus_get_waiting_data($booking_system_data, $atts)
{
    $booking_system_data['test'] = "test";
    for ($i = 0; $i < count($booking_system_data['events']); $i++) {
        $event_id = $booking_system_data['events'][$i]['id'];
        $waiting_list = ba_plus_get_waiting_list_count($event_id);
        $booking_system_data['events_data'][$event_id]['waiting_list'] = $waiting_list;
    }
    return $booking_system_data;
}
add_filter("bookacti_booking_system_data", "ba_plus_get_waiting_data", 2, 2);


/**
 * Send data to the js for waiting list information on the booking template
 */
function ba_plus_get_waiting_data_template($booking_system_data, $atts)
{
    $booking_system_data['test'] = "test";
    $waiting_list = ba_plus_get_all_waiting_list();
    $booking_system_data['waiting_list'] = array();
    foreach ($waiting_list as $waiting) {
        $event_id = $waiting->event_id;
        $user = get_userdata($waiting->user_id);
        $user_name = $user->display_name;

        if (!isset($booking_system_data['waiting_list'][$event_id])) {
            $booking_system_data['waiting_list'][$event_id] = array();
        }
        $booking_system_data['waiting_list'][$event_id][] = array(
            'user_id' => $waiting->user_id,
            'user_name' => $user_name,
            'waiting_id' => $waiting->id,
            'date' => $waiting->date
        );
    }
    return $booking_system_data;
}
add_filter("bookacti_editor_booking_system_data", "ba_plus_get_waiting_data_template", 2, 2);



/**
 * Add waiting users to the booking list
 */
function ba_plus_get_booking_list($booking_list, $filters, $filters_raw, $columns, $atts)
{
    return $booking_list;
}
add_filter("bookacti_events_booking_lists", "ba_plus_get_booking_list", 5, 5);