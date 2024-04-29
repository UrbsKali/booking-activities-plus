<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}




/**
 * Validate the picked event
 * 
 * @param array $validated The current state of validation
 * @param array $picked_event The selected event
 * @param string $args The others arguments
 * @return array $validated The updated state of validation
 */
function ba_plus_validate_picked_event($validated, $picked_event, $args)
{
    $validated["messages"]["debug"] = array("eeeeeeeeeeeeee");
    if (!$validated["messages"]["qty_sup_to_avail"]) {
        return $validated;
    }

    if (ba_plus_check_if_user_is_in_waiting_list(get_current_user_id(), $picked_event['id'])) {
        $error = 'already_in_waiting_list';
        if (!isset($validated['messages'][$error])) {
            $validated['messages'][$error] = array();
        }
        $validated['messages'][$error][] = 'Vous êtes déjà dans la liste d\'attente pour cet événement';
        // delete the old no_availability error
        unset($validated["messages"]["no_availability"]);
    } else {
        $validated['status'] = 'success';
        $validated['waiting_list'] = true;
    }
    $validated["messages"]["no_availability"] = array("hehe");


    return $validated;
}

/**
 * Validate the picked events
 * @param array $validated The current state of validation
 * @param array $picked_events The selected events
 * @param string $args The others arguments
 * @return array $validated The updated state of validation
 */
function ba_plus_validate_picked_events($validated, $picked_events, $args)
{
    // check if user certificate is not expired
    $certi_date = get_user_meta(get_current_user(), "expire_date", true);
    if (empty($certi_date)) {
        // send error message
        $validated['error'] = 'no_certificate';
    } else if (date('', strtotime($certi_date)) > date('')) {
        $validated['error'] = '';
    } else {
        $validated['error'] = array('');
    }
    return $validated;
}




/**
 * Add the user to the waiting list
 * 
 * @param int $form_id The form id
 * @param array $booking_form_values The booking form values
 * @param array $return_array The return array
 * @return none
 */
function ba_plus_add_user_to_waiting_list($form_id, $booking_form_values, $return_array)
{
    // Refetch to check to get the waiting list state
    $response = bookacti_validate_picked_events($booking_form_values['picked_events'], $booking_form_values);
    if (!isset($response['waiting_list'])) {
        return;
    }

    $picked_events = bookacti_format_picked_events($booking_form_values['picked_events'], true);

    $booking_data = bookacti_sanitize_booking_data(
        array(
            'user_id' => $booking_form_values['user_id'],
            'form_id' => $booking_form_values['form_id'],
            'event_id' => $picked_events[0]['events'][0]['id'],
            'event_start' => $picked_events[0]['events'][0]['start'],
            'event_end' => $picked_events[0]['events'][0]['end'],
            'quantity' => $booking_form_values['quantity'],
            'status' => $booking_form_values['status'],
            'payment_status' => $booking_form_values['payment_status'],
            'active' => 'according_to_status'
        )
    );
    $waiting_list_id = ba_plus_insert_waiting_list($booking_data["user_id"], $booking_data["event_id"]);
    if ($waiting_list_id) {
        $return_array['status'] = 'success';
        $return_array['messages']['booked'] = "Vous êtes bien dans la liste d'attente !";
    } else {
        $return_array['error'] = 'unknown';
        $return_array['messages']['unknown'] = esc_html__('An error occurred, please try again.', 'booking-activities');
    }
    $return_array['message'] = implode('</li><li>', $return_array['messages']);
    bookacti_send_json($return_array, 'submit_booking_form');	 // return success
}
