<?php
if (!defined('ABSPATH')) {
    exit;
}

// connect to bapap_booking_pass_created w/ $booking_pass_id, $booking_pass_data
function ba_plus_booking_pass_created($booking_pass_id, $booking_pass_data)
{
    // add free cancellation to user meta, depending on the pass type
    $pass_type = $booking_pass_data['pass_type'];
    $user_id = $booking_pass_data['user_id'];
    $free_cancellation = 0;
    if ($pass_type == 'free') {
        $free_cancellation = 1;
    }
    update_user_meta($user_id, 'free_cancellation', $free_cancellation);

} 

// bapap_booking_pass_updated 

// do_action( 'bapap_booking_pass_deleted', $booking_pass_id );

// 		do_action( 'bapap_booking_pass_deactivated', $booking_pass_id );


// 		do_action( 'bapap_booking_pass_restored', $booking_pass_id );
