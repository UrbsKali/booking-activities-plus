<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'bookacti_cron_check_certif', 'ba_plus_check_certificate_expiration' );
add_action( 'bookacti_cron_check_attest', 'ba_plus_check_attestation_expiration' );

if ( ! wp_next_scheduled( 'bookacti_cron_check_certif' ) ) {
    wp_schedule_event( time(), 'hourly', 'bookacti_cron_check_certif' );
}   
if ( ! wp_next_scheduled( 'bookacti_cron_check_attest' ) ) {
    wp_schedule_event( time(), 'hourly', 'bookacti_cron_check_attest' );
}   

/**
 * Check all users' certificate expiration dates and send notification emails.
 * 
 * This function queries all users in the system and checks if any of their certificates 
 * are due to expire within the next 60 days. If a certificate is about to expire, 
 * the function sends an email notification to the user to inform them of the upcoming expiration.
 * 
 * This is intended to be run as a scheduled task via WordPress cron to ensure
 * users are reminded to renew their certificates before they expire.
 * 
 * @since 1.0.0
 * @return void
 */
function ba_plus_check_certificate_expiration(){
    echo "Checking for certificate expiration<br>";
    $users = get_users();
    $timezone = new DateTimeZone('Europe/Paris');
    $today = new DateTime('now', $timezone);
    foreach($users as $user){
        $user_id = $user->ID;
        $expire_date = get_user_meta($user_id, 'certif_med', true); // certif_med
        $send_mail = get_user_meta( $user_id, 'send_mail_certif_expire', true ); 
        // check if null 
        if ( $expire_date == '' || $send_mail == '' ){
            continue;
        }

        $expire_date = new DateTime($expire_date, $timezone);
        $diff = date_diff($today, $expire_date);
        if ( $send_mail == 'true' ){
            if ( $diff->invert == 0 && $diff->days > 60){
                update_user_meta( $user_id, 'send_mail_certif_expire', 'false' );
            }
            continue;
        } 

        if ( $diff->invert == 0 && $diff->days <= 60 ){
            $to = $user->user_email;
            echo "&nbsp;&nbsp;Send mail for certif to : " . $to . "<br>";
            $subject = get_option( 'ba_plus_mail_certi_expire_title' );
            $subject = str_replace( '%doc%', "certificat médical", $subject );
            $body = get_option( 'ba_plus_mail_certi_expire_body' );
            $body = str_replace( '%doc%', "certificat médical", $body );
            $body = str_replace( '%user%', $user->display_name, $body );
            $body = str_replace( '%expire_date%', $diff->days+1, $body );
            $headers = array('Content-Type: text/html; charset=UTF-8','From: ACADEMIE FRANCAISE DE PILATES <sarah.portiche@academie-pilates.com>');
            wp_mail( $to, $subject, $body, $headers );
            update_user_meta( $user_id, 'send_mail_certif_expire', 'true' );
        }
    }
}


/**
 * Check all users' attestation expiration dates and send notification emails.
 * 
 * This function queries all users in the system and checks if any of their attestation 
 * are due to expire within the next 7 days. If a attestation is about to expire, 
 * the function sends an email notification to the user to inform them of the upcoming expiration.
 * 
 * This is intended to be run as a scheduled task via WordPress cron to ensure
 * users are reminded to renew their attestation before they expire.
 * 
 * @since 1.0.0
 * @return void
 */
function ba_plus_check_attestation_expiration(){
    echo "Checking for attestation expiration<br>";
    $users = get_users();
    $timezone = new DateTimeZone('Europe/Paris');
    $today = new DateTime('now', $timezone);
    foreach($users as $user){
        $user_id = $user->ID;
        $expire_date = get_user_meta($user_id, 'attest_med', true); // attest_med
        $send_mail = get_user_meta( $user_id, 'send_mail_attes_expire', true );
        
        // check if null 
        if ( $expire_date == '' || $send_mail == '' ){
            continue;
        }

        $expire_date = new DateTime($expire_date, $timezone);
        $diff = date_diff($today, $expire_date);

        if ( $send_mail == 'true' ){
            if ( $diff->invert == 0 && $diff->days > 7){
                update_user_meta( $user_id, 'send_mail_attes_expire', 'false' );
            }
            continue;
        } 
        
        if ( $diff->invert == 0 && $diff->days <= 7 ){
            $to = $user->user_email;
            echo "&nbsp;&nbsp;Send mail for attest to : " . $to . "<br>";
            $subject = get_option( 'ba_plus_mail_certi_expire_title' );
            $subject = str_replace( '%doc%', "attestation médicale", $subject );
            $body = get_option( 'ba_plus_mail_certi_expire_body' );
            $body = str_replace( '%doc%', "attestation médicale", $body );
            $body = str_replace( '%user%', $user->display_name, $body );
            $body = str_replace( '%expire_date%', $diff->days+1, $body );
            $body = str_replace('à le', 'à la', $body);
            $body = str_replace('scanné', 'scannée', $body);

            $headers = array('Content-Type: text/html; charset=UTF-8','From: ACADEMIE FRANCAISE DE PILATES <sarah.portiche@academie-pilates.com>');
            wp_mail( $to, $subject, $body, $headers );
            update_user_meta( $user_id, 'send_mail_attes_expire', 'true' );
        }
    }
}