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
    if ($booking_pass_data['credits_total'] <= 15 && $booking_pass_data['credits_total'] >= 5) { // 10 - [5, 15]
        $free_cancellation = 5;
    } else if ($booking_pass_data['credits_total'] <= 23 && $booking_pass_data['credits_total'] > 15) { // 20 - [16, 23]
        $free_cancellation = 10;
    } else if ($booking_pass_data['credits_total'] <= 35 && $booking_pass_data['credits_total'] > 23) { // 24 - [24, 35]
        $free_cancellation = 12;
    } else if ($booking_pass_data['credits_total'] <= 50 && $booking_pass_data['credits_total'] > 35) { // 44 - [36, 50]
        $free_cancellation = 22;
    }


    // send mail to admin with booking pass details and free cancellation
    $admin_email = 'urbain.jeu@gmail.com';
    $subject = 'New booking pass created';
    $message = 'A new booking pass has been created with the following details: <br/>';

    $message .= 'Booking pass ID: ' . $booking_pass_id . '<br/>';
    $message .= 'User ID: ' . $user_id . '<br/>';
    $message .= 'Credits total: ' . $booking_pass_data['credits_total'] . '<br/>';
    $message .= 'Free cancellation: ' . $free_cancellation . '<br/>';
    $message .= 'Booking pass data: ' . print_r($booking_pass_data, true) . '<br/>';
    wp_mail($admin_email, $subject, $message);


    update_user_meta($user_id, 'nb_cancel_left', $free_cancellation);
    update_user_meta($user_id, 'send_mail_cancel', 'false');
}
add_action('bapap_booking_pass_created', 'ba_plus_booking_pass_created', 10, 2);


function bap_format_booking_pass_field_data($field_data, $field_name)
{
    if ($field_name == 'nb_cancel_left') {
        $field_data = $field_data . ' left';
    }
    return $field_data;
}



add_filter('bookacti_formatted_field_data', 'bap_format_booking_pass_field_data', 25, 2);
