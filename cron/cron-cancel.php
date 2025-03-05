<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'bookacti_cron_check_cancel', 'ba_plus_check_cancel' );

if ( ! wp_next_scheduled( 'bookacti_cron_check_cancel' ) ) {
    wp_schedule_event( time(), 'hourly', 'bookacti_cron_check_cancel' );
}




/**
 * Checks users who have limited cancellations left on their pass.
 * 
 * This function is executed via a scheduled cron job to identify users
 * with 3 or fewer cancellations left and sends them a reminder email.
 * The function ensures each user only receives one notification until 
 * their cancellation count changes.
 * 
 * @since 1.0.0
 * @return void
 */
function ba_plus_check_cancel(){
    echo "Checking for cancel number<br>";
    $users = get_users();
    foreach($users as $user){
        // if the user has a cancel number == 3, send mail
        $user_cancel = get_user_meta( $user->ID, 'nb_cancel_left', true );
        $send_mail = get_user_meta( $user->ID, 'send_mail_cancel', true );


        if ( $user_cancel == '' || !$user_cancel ) {
            continue;
        }

        if ( $user_cancel <= 3 && $send_mail == 'false' && $user_cancel > 0) {
            $to = $user->user_email;
            echo "Send cancel mail to: " . $to . "<br>";
            $subject = get_option( 'ba_plus_mail_tree_cancel_left_title' );
            $body = get_option( 'ba_plus_mail_tree_cancel_left_body' );
            $body = str_replace( '%user%', $user->display_name, $body );
            $headers = array('Content-Type: text/html; charset=UTF-8','From: ACADEMIE FRANCAISE DE PILATES <sarah.portiche@academie-pilates.com>');
            wp_mail( $to, $subject, $body, $headers );
            update_user_meta( $user->ID, 'send_mail_cancel', 'true' );
        }
        if ( $user_cancel > 3 && $send_mail == 'true' ){
            update_user_meta( $user->ID, 'send_mail_cancel', 'false' );
        }   
    }
}