<?php

/** 
 * AJAX controller to cancel waiting list booking
 */
function ba_plus_cancel_waiting_list_booking()
{
    $waiting_id = intval($_POST['waiting_id']);
    $user_id = $_POST['user_id'];


    if (!isset($user_id)){
        $user_id = get_current_user_id();
        $result = ba_plus_remove_waiting_list($waiting_id, $user_id);
    } else {
        // dans ce cas on est en mode admin, et waiting_id est en fait l'event_id
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $result = ba_plus_remove_waiting_list_by_event_id($waiting_id, $user_id, $start_date, $end_date);
    }
    
    if ($result) {
        wp_send_json(array('status' => 'success', 'message' => 'Vous avez bien annulé votre réservation en liste d\'attente.'));
    } else {
        wp_send_json(array('status' => 'error', 'message' => 'Une erreur est survenue, veuillez réessayer. '. $result . ' - ' . $waiting_id . ' - ' . $user_id .''));
    }
}
add_action('wp_ajax_baPlusCancelWaitingList', 'ba_plus_cancel_waiting_list_booking');
add_action("wp_ajax_nopriv_baPlusCancelWaitingList", "ba_plus_cancel_waiting_list_booking");
