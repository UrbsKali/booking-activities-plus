<?php

/** 
 * AJAX controller to cancel waiting list booking
 */
function ba_plus_cancel_waiting_list_booking()
{
    $waiting_id = intval($_POST['waiting_id']);
    $user_id = get_current_user_id();
    $result = ba_plus_remove_waiting_list($waiting_id, $user_id);
    
    if ($result) {
        wp_send_json(array('status' => 'success', 'message' => 'Vous avez bien annulé votre réservation en liste d\'attente.'));
    } else {
        wp_send_json(array('status' => 'error', 'message' => 'Une erreur est survenue, veuillez réessayer.'));
    }
}
add_action('wp_ajax_ba_plus_cancel_waiting_list_booking', 'ba_plus_cancel_waiting_list_booking');
add_action("wp_ajax_nopriv_ba_plus_cancel_waiting_list_booking", "ba_plus_cancel_waiting_list_booking");
