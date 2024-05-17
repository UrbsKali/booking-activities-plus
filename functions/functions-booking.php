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
    $user_id = get_current_user_id();

    // check if event is fully booked
    if (!ba_plus_check_if_event_is_full($event_id)) {
        return $validated;
    }



    if (!empty(ba_plus_check_if_user_is_in_waiting_list($user_id, $event_id))) {
        $error = 'already_in_waiting_list';
        $validated['messages'][$error] = array('Vous êtes déjà dans la liste d\'attente pour cet événement');
        $validated['status'] = 'error';
    } else if (ba_plus_check_if_already_booked($user_id, $event_id)) {
        $error = 'already_booked';
        $validated['messages'][$error] = array('Vous avez déjà réservé cet événement');
        $validated['status'] = 'error';
    } else {
        $validated['status'] = 'success';
        $validated['waiting_list'] = true;
    }

    return $validated;
}
add_filter("bookacti_validate_picked_event", "ba_plus_validate_picked_event", 5, 3);


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
    $certi_date = get_user_meta(get_current_user_id(), "certificat_expire_date", true);
    $attest_date = get_user_meta(get_current_user_id(), "attestation_expire_date", true);
    if (empty($certi_date) || empty($attest_date)) {
        // send error message
        $validated['status'] = 'error';
        $validated['messages']['no_certificate'] = array('Vous devez renseigner vos informations médicales pour pouvoir réserver un événement (Certificat médical et Attestation)');
    } else if (date('Y-m-d', strtotime($certi_date)) < date('Y-m-d')) {
        $validated['status'] = 'error';
        $validated['messages']['old_certificate'] = array('Votre certificat médical est expiré, veuillez le renouveler pour pouvoir réserver un événement');
    } else if (date('Y-m-d', strtotime($attest_date)) < date('Y-m-d')) {
        $validated['status'] = 'error';
        $validated['messages']['old_attestation'] = array('Votre attestation médical est expiré, veuillez le renouveler pour pouvoir réserver un événement');
    }
    return $validated;
}
add_filter("bookacti_validate_picked_events", "ba_plus_validate_picked_events", 5, 3);



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
add_action("bookacti_booking_form_before_booking", "ba_plus_add_user_to_waiting_list", 5, 3);



/**
 * Verify that user can cancel the event
 */
function ba_plus_can_cancel_event($is_allowed, $booking, $context, $allow_grouped_booking)
{
    // get the current hours, if under 24 h before event return false
    $event_start = strtotime($booking->event_start);
    $current_time = time();
    $diff = $event_start - $current_time;
    if ($diff < get_option('ba_plus_refund_delay', 24) * 3600) {
        return false;
    }
    if ($booking->state == 'cancelled') {
        return false;
    }
    return $is_allowed;
}
add_filter("bookacti_booking_can_be_cancelled", "ba_plus_can_cancel_event", 5, 4);


/**
 * Cancel the event
 */
function ba_plus_cancel_event_individual($booking, $new_state, $is_admin)
{
    if ($new_state != 'cancelled') {
        return;
    }
    $user_id = $booking->user_id;
    $nb_cancelled_events = get_user_meta($user_id, 'nb_cancel_left', true);
    if (empty($nb_cancelled_events)) {
        return;
    } else if ($nb_cancelled_events <= 0) {
        return;
    }

    $nb_cancelled_events--;
    update_user_meta($user_id, 'nb_cancel_left', $nb_cancelled_events);
    // refund the cost of the event (1)
    $filters = array(
        'user_id' => $user_id,
        'event_id' => $booking->event_id
    );
    $filters = bapap_format_booking_pass_filters($filters);
    $pass = bapap_get_booking_passes($filters);
    if (empty($pass)) {
        return;
    }
    foreach ($pass as $p) {
        $pass = $p;
        break;
    }
    $pass->credits_current += 1;
    bapap_update_booking_pass_data($pass->id, array('credits_current' => $pass->credits_current));
    update_user_meta( $user_id, "debug", $pass->id ." + ". $pass->credits_total);
    // add to the log 
    $log_data = array( 
        'credits_current' => $pass->credits_current,
        'credits_total' => $pass->credits_total,
        'reason' => "Annulation de l'événement - Remboursement d'un crédit",
        'context' => 'updated_from_server',
        'lang_switched' => 1
    );
    bapap_add_booking_pass_log( $pass->id, $log_data );

}
add_action("bookacti_booking_state_changed", "ba_plus_cancel_event_individual", 10, 3);