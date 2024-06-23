<?php
if (!defined('ABSPATH')) {
    exit;
}

// connect to bapap_booking_pass_created w/ $booking_pass_id, $booking_pass_data
function ba_plus_booking_pass_created($booking_pass_id, $booking_pass_data)
{
    // add free cancellation to user meta, depending on the pass type
    $user_id = $booking_pass_data['user_id'];
    $free_cancellation = 0;
    if ($booking_pass_data['credits_total'] == 10) {
        $free_cancellation = 5;
    } else if ($booking_pass_data['credits_total'] == 20) {
        $free_cancellation = 10;
    } else if ($booking_pass_data['credits_total'] == 24) {
        $free_cancellation = 12;
    } else if ($booking_pass_data['credits_total'] == 44) {
        $free_cancellation = 22;
    }
    update_user_meta($user_id, 'nb_cancel_left', $free_cancellation);
    update_user_meta($user_id, 'send_mail_cancel', 'false');
    wp_mail( "urbain.lantres@gmail.com", "Création d'un passe", "Un passe a été créé pour l'utilisateur $user_id. <br> Il a $free_cancellation annulations gratuites.", "Content-Type: text/html\r\n");
} 
add_action('bapap_booking_pass_created','ba_plus_booking_pass_created', 10, 2);
