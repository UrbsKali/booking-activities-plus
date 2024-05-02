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
    $event_id = $picked_event['events'][0]["id"];
    // delete the old messages
    if (! $validated[ 'messages' ]){
        return $validated;
    }
    
    if (!empty(ba_plus_check_if_user_is_in_waiting_list(get_current_user_id(), $event_id))) {
        $error = 'already_in_waiting_list';
        $validated['messages'][$error] = array('Vous êtes déjà dans la liste d\'attente pour cet événement');
        $validated['status'] = 'error';
    } else {
        $validated['status'] = 'success';
        $validated['waiting_list'] = true;
    }

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
    $certi_date = get_user_meta(get_current_user_id(), "expire_date", true);
    if (empty($certi_date)) {
        // send error message
        $validated['status'] = 'error';
        $validated['messages']['no_certificate'] = array('Vous devez renseigner votre certificat médical pour pouvoir réserver un événement', $certi_date);
    } else if (date('Y-m-d', strtotime($certi_date)) < date('Y-m-d')) {
        $validated['status'] = 'error';
        $validated['messages']['old_certificate'] = array('Votre certificat médical est expiré, veuillez le renouveler pour pouvoir réserver un événement');
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
    if (!isset($response['waiting_list']) || $response['waiting_list'] !== true) {
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


/**
 * Verify that user can cancel the event
 */
function ba_plus_can_cancel_event($is_allowed, $booking, $context, $allow_grouped_booking)
{
    // check the usermeta to see if the nb_cancelled_events is less than 3
    $user_id = get_current_user_id();
    $nb_cancelled_events = get_user_meta($user_id, 'nb_cancel_left', true);
    if (empty($nb_cancelled_events)) {
        $nb_cancelled_events = 0;
    }
    if ($nb_cancelled_events > 0) {
        return true;
    } else {
        return false;
    }
}