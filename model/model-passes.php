<?php
if (!defined('ABSPATH')) {
    exit;
}

function ba_plus_get_passes_data($booking_pass_id)
{
    global $wpdb;

    $query = 'SELECT * FROM ' . BOOKACTI_TABLE_PASSES . 'as P JOIN .' . BOOKACTI_TABLE_PASSES_TEMPLATES . ' WHERE booking_pass_id = %d';
    $query = $wpdb->prepare($query, $booking_pass_id);
    $passes = $wpdb->get_results($query, OBJECT);

    return $passes;
}
