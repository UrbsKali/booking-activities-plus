<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create an admin page for the waiting list (change delay, etc)
 */
function ba_plus_settings_page()
{
    ?>
    <h1>File d'attente</h1>
    <form method="post" action="options.php">
        <?php
        settings_fields('ba_plus_settings');
        do_settings_sections('ba_plus_settings');
        submit_button();
        ?>
    </form>
    <p> Hooks disponibles : </p>
    <ul>
        <?php

        if (has_filter("bookacti_validate_picked_event", "ba_plus_validate_picked_event")) {
            echo "<li>bookacti_validate_picked_event</li>";
        }
        if (has_action("bookacti_booking_form_before_booking", "ba_plus_add_user_to_waiting_list")) {
            echo "<li>bookacti_booking_form_before_booking</li>";
        }
        ?>
    </ul>
    <?php
}
