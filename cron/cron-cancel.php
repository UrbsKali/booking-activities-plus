<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'bookacti_cron_check_cancel', 'ba_plus_check_cancel' );

if ( ! wp_next_scheduled( 'bookacti_cron_check_cancel' ) ) {
    wp_schedule_event( time(), 'five_seconds', 'bookacti_cron_check_cancel' );
}

add_action( 'bookacti_cron_test', 'ba_plus_test_cron' );

if ( ! wp_next_scheduled( 'bookacti_cron_test' ) ) {
    wp_schedule_event( time(), 'five_seconds', 'bookacti_cron_test' );
}

function ba_plus_check_cancel(){
    echo "Checking for cancel number<br>";
    global $wpdb;
    $users = get_users();
    foreach($users as $user){
        // if the user has a cancel number == 3, send mail
        $user_nb_cancle = get_user_meta( $user->id, 'nb_cancel_left', true );
        $send_mail = get_user_meta( $user->id, 'send_mail_cancel', true );
        if ( $user_nb_cancle <= 3 && $send_mail == 'false' && $user_nb_cancle > 0) {
            $to = $user->user_email;
            echo $to . "<br>";
            $subject = get_option( 'ba_plus_mail_tree_cancel_left_title' );
            $body = get_option( 'ba_plus_mail_tree_cancel_left_body' );
            $body = str_replace( '%user%', $user->display_name, $body );
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail( $to, $subject, $body, $headers );
            update_user_meta( $user->id, 'send_mail_cancel', 'true' );
        }
        if ( $user_nb_cancle > 3 && $send_mail == 'true' ){
            update_user_meta( $user->id, 'send_mail_cancel', 'false' );
        }
    }
}
