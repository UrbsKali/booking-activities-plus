<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function ba_plus_create_user_waiting_list($filters, $columns = array(), $per_page = 10)
{
    if (!$columns) {
        $columns = array("id", "Évènements", "Actions");
    }

    // Set a counter of bookings list displayed on the same page
    if (empty($GLOBALS['bookacti_booking_list_count'])) {
        $GLOBALS['bookacti_booking_list_count'] = 0;
    }
    global $bookacti_booking_list_count;
    ++$bookacti_booking_list_count;

    // Total number of bookings to display
    $bookings_nb = bookacti_get_number_of_booking_rows($filters);

    // Pagination
    $page_nb = !empty($_GET['bookacti_booking_list_paged_' . $bookacti_booking_list_count]) ? intval($_GET['bookacti_booking_list_paged_' . $bookacti_booking_list_count]) : 1;
    $page_max = ceil($bookings_nb / intval($per_page));
    $filters['per_page'] = intval($per_page);
    $filters['offset'] = ($page_nb - 1) * $filters['per_page'];

    $booking_list_items = bookacti_get_user_booking_list_items($filters, $columns);

    $user_id = get_current_user_id();

    if ($filters['user_id'] && $filters['user_id'] !== 'current') {
        $user_id = $filters['user_id'];
    }


    $waiting_list = ba_plus_get_user_waiting_list($user_id);


    foreach ($waiting_list as $key => $waiting) {
        if ($waiting->event_id == 0)
            continue; // Skip if event id is 0 (no event id)

        $event = bookacti_get_event_by_id($waiting->event_id);
        $waiting_list[$key]->event_name = $event->title;
        $waiting_list[$key]->event_start = $waiting->start_date;
        $waiting_list[$key]->event_end = $waiting->end_date;
    }

    ob_start();
    ?>
    <div id='bookacti-user-booking-list-<?php echo $bookacti_booking_list_count; ?>' class='bookacti-user-booking-list'
        data-user-id='<?php echo $filters['user_id'] ? $filters['user_id'] : ''; ?>'>
        <table class='bookacti-user-booking-list-table ba-wl-waiting-list'>
            <thead>
                <tr>
                    <?php
                    $columns_labels = bookacti_get_user_booking_list_columns_labels();
                    foreach ($columns as $column_id) {
                        ?>
                        <th class='bookacti-column-<?php echo sanitize_title_with_dashes($column_id); ?>'>
                            <div class='bookacti-column-title-<?php echo $column_id; ?>'>
                                <?php echo !empty($columns_labels[$column_id]) ? esc_html($columns_labels[$column_id]) : $column_id; ?>
                            </div>
                        </th>
                        <?php
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($waiting_list as $list_item) {
                    $tr_data = ' data-waiting-id="' . $list_item->id . '" class="bookacti-single-booking"';
                    ?>
                    <tr<?php echo $tr_data; ?>>
                        <td><?php
                        $date = datefmt_create(
                            "fr-FR",
                            IntlDateFormatter::FULL,
                            IntlDateFormatter::SHORT,
                            'Europe/Paris',
                            IntlDateFormatter::GREGORIAN
                        );
                        $str_date = datefmt_format($date, strtotime($list_item->event_start));

                        echo $list_item->event_name . " - " . $str_date . ""; ?></td>
                        <td>
                            <?php
                            $actions = bookacti_get_waiting_list_actions_html($list_item);

                            echo $actions;
                            ?>
                        </td>
                        </tr>
                        <?php
                }
                if (count($waiting_list) == 0) {
                    ?>
                        <tr>
                            <td colspan='2'>
                                <?php esc_html_e("Vous n'êtes sur aucune liste d'attente", 'booking-activities'); ?>
                            </td>
                        </tr>
                        <?php
                }
                ?>
            </tbody>
        </table>
        <?php

        if ($page_max > 1) { ?>
            <div class='bookacti-user-booking-list-pagination'>
                <?php
                if ($page_nb > 1) {
                    ?>
                    <span class='bookacti-user-booking-list-previous-page'>
                        <a href='<?php echo esc_url(add_query_arg('bookacti_booking_list_paged_' . $bookacti_booking_list_count, ($page_nb - 1))); ?>'
                            class='button'>
                            <?php esc_html_e('Previous', 'booking-activities'); ?>
                        </a>
                    </span>
                    <?php
                }
                ?>
                <span class='bookacti-user-booking-list-current-page'>
                    <span class='bookacti-user-booking-list-page-counter'><strong><?php echo $page_nb; ?></strong><span> /
                        </span><em><?php echo $page_max; ?></em></span>
                    <span
                        class='bookacti-user-booking-list-total-bookings'><?php /* translators: %s is the number of bookings */ echo esc_html(sprintf(_n('%s booking', '%s bookings', $bookings_nb, 'booking-activities'), $bookings_nb)); ?></span>
                </span>
                <?php
                if ($page_nb < $page_max) {
                    ?>
                    <span class='bookacti-user-booking-list-next-page'>
                        <a href='<?php echo esc_url(add_query_arg('bookacti_booking_list_paged_' . $bookacti_booking_list_count, ($page_nb + 1))); ?>'
                            class='button'>
                            <?php esc_html_e('Next', 'booking-activities'); ?>
                        </a>
                    </span>
                    <?php
                }
                ?>
            </div>
        <?php } ?>
    </div>
    <?php

    return apply_filters('bookacti_user_booking_list_html', ob_get_clean(), $booking_list_items, $columns, $filters, $per_page);
}

/**
 * Get the rows for the waiting list
 * @since 1.7.6
 * @version 1.8.0
 * @return string
 */
function bookacti_get_waiting_list_actions_html($waiting_item)
{
    ob_start();
    ?>
    <div class='bookacti-booking-actions'>
        <?php
        // cancel waiting list
        echo "<a href='#' class='bookacti-cancel-waiting-list' data-waiting-id='" . $waiting_item->id . "' data-start-date='" . $waiting_item->start_date . "' data-end-date='" . $waiting_item->end_date . "' >Annuler</a>";
        ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Create the admin planning 
 * @return string
 */
function ba_plus_create_planning($args)
{
    $today = new DateTime();
    $today = $today->format('Y-m-d h:i:s');
    $next_week = new DateTime();
    $next_week = date('Y-m-d H:i:s', strtotime('+7 day'));
    $interval = array(
        'start' => $today,
        'end' => $next_week
    );
    $args = array(
        'interval' => $interval,
        'active' => 1
    );

    $events = bookacti_fetch_events($args);
    $events_by_day = array();
    $events = $events['events'];
    foreach ($events as $event) {
        // check start date of the event
        for ($i = 1; $i <= 7; $i++) {
            $after = date('Y-m-d', strtotime('+' . $i . ' day'));
            $before = date('Y-m-d', strtotime('+' . $i-1 . ' day'));
            if (!isset($events_by_day[$before]))
                $events_by_day[$before] = array();
            if ($event['start'] > $before && $event['start'] < $after) {
                $events_by_day[$before][] = $event;
            }
        }
    }

    ob_start();
    ?>
    <div class="ba-planning">
        <?php
        foreach ($events_by_day as $day => $events) {
            echo ba_plus_create_day_col($events, $day);
        }

        ?>
    </div>
    <?php
    return ob_get_clean() . ba_plus_style_planning();
}

/**
 * Create an day column, with multiple events in it
 * @param array $events the events to display, agregated with user booked and waiting list
 * @return string
 */
function ba_plus_create_day_col($events, $day)
{
    $date = datefmt_create(
        "fr-FR",
        IntlDateFormatter::FULL,
        IntlDateFormatter::NONE,
        'Europe/Paris',
        IntlDateFormatter::GREGORIAN
    );
    $str_date = datefmt_format($date, strtotime($day));

    ob_start();
    ?>
    <div class="ba-planning-col">
        <h3><?php echo $str_date; ?></h3>
        <div class="ba-planning-col">
            <?php
            foreach ($events as $event) {
                echo ba_plus_create_event_div($event);
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Create an event box, with Event name, and list of booked users
 * @param array $event the event to display, agregated with user booked and waiting list
 * @return string
 */
function ba_plus_create_event_div($event)
{
    $id = $event['id'];
    $start = $event['start'];
    $end = $event['end'];

    $pretty_start = date('H:i', strtotime($start));
    $pretty_end = date('H:i', strtotime($end));

    // get the booking list
    $filters = array('event_id' => $id, 'status' => 'booked');
    $filters = bookacti_format_booking_filters($filters);
    $event['booked'] = bookacti_get_bookings($filters);
    $event['waiting'] = ba_plus_get_event_waiting_list($id, $start, $end);

    ob_start();
    ?>
    <div class="ba-planning-event-box">
        <h4><?php echo $event['title'] . " - " . $pretty_start . " → " . $pretty_end; ?></h4>
        <ul>
            <?php
            foreach ($event['booked'] as $booked) {
                $user = get_user_by('id', $booked->user_id);
                echo "<li>" . $user->display_name . "</li>";
            }
            ?>
        </ul>
        <hr>
        <ul>
            <?php
            foreach ($event['waiting'] as $waiting) {
                $user = get_user_by('id', $booked->user_id);
                echo "<li>" . $user->display_name . "</li>";
            }
            ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Return the style for the planning
 * @return string
 */
function ba_plus_style_planning(){
    ob_start();
    ?>
    <style>
        .ba-planning {
            display: grid;
            grid-template-columns: repeat(7, 1fr);

        }

        .ba-planning-col:has(h3) {
            display: grid;
            grid-template-columns: 1fr;
            grid-template-rows: 100px 1fr;

            border: 1px solid black;
            padding: 10px;
            margin: 10px;
        }

        .ba-planning-event-box {
            border: 1px solid black;
            padding: 10px;
            margin: 10px;
        }
    </style>
    <?php
    return ob_get_clean();
}