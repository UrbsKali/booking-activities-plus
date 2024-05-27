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
            $before = date('Y-m-d', strtotime('+' . $i - 1 . ' day'));
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
            // sort by start date
            usort($events, function ($a, $b) {
                return strtotime($a['start']) - strtotime($b['start']);
            });
            echo ba_plus_create_day_col($events, $day);
        }

        ?>
    </div>
    <div class="ba-planning-popup-bg">
        <div class="ba-planning-popup">
            <!-- Close btn -->
            <div class="ba-planning-popup-close">X</div>
            <div class="ba-planning-popup-header">
                <h3>TMP</h3>
                <p>lorem ipsum</p>
            </div>
            <div class="ba-planning-popup-content">

            </div>
        </div>
    </div>
    <?php
    return ob_get_clean() . ba_plus_style_planning() . ba_plus_script_planning();
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
    $str_date = ucfirst($str_date);
    $str_date = explode(" ", $str_date);
    $str_date = $str_date[0] . " " . $str_date[1] . " " . $str_date[2];


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
    $pretty_start = str_replace(":", "h", $pretty_start);
    $pretty_end = str_replace(":", "h", $pretty_end);

    // get the booking list
    $filters = array('event_id' => $id, 'status' => 'booked', 'from' => $start, 'to' => $end);
    $filters = bookacti_format_booking_filters($filters);
    $event['booked'] = bookacti_get_bookings($filters);
    $event['waiting'] = ba_plus_get_event_waiting_list($id, $start, $end);

    ob_start();
    ?>
    <div class="ba-planning-event-box" data-event-id="<? echo $id; ?>" data-event-start="<? echo $start; ?>"
        data-event-end="<? echo $end; ?>">
        <p><?php echo $pretty_start . "/" . $pretty_end; ?></p>
        <p><?php echo $event['title']; ?></p>
        <?php if (count($event['booked']) > 0) {
            echo '<p class="ba-booked-title">Inscrits</p>';
        } ?>

        <ul class="ba-booked">
            <?php
            foreach ($event['booked'] as $booked) {
                $user = get_user_by('id', $booked->user_id);
                echo "<li data-user-id='" . $booked->user_id . "' data-booking-id='" . $booked->id . "'>" . $user->display_name . "</li>";
            }
            ?>
        </ul>
        <?php if (count($event['waiting']) > 0) {
            echo "<p class=\"ba-wl-title\">En attente</p>";
        } ?>
        <ul class="ba-wl">
            <?php
            foreach ($event['waiting'] as $waiting) {
                $user = get_user_by('id', $waiting->user_id);
                echo "<li data-user-id='" . $waiting->user_id . "' data-waiting-id='" . $waiting->id . "'>" . $user->display_name . "</li>";
            }
            ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

function ba_plus_script_planning()
{
    ob_start();
    ?>
    <script>
        $j('.ba-booked li').click(function (e) {
            e.preventDefault();
            // open the popup
            $j('.ba-planning-popup-bg').css('display', 'block');
            // get the user id
            var user_id = $j(this).data('user-id');
            // get the user name
            var user_name = $j(this).text();
            // get the event id
            var event_id = $j(this).closest('.ba-planning-event-box').data('event-id');
            // get the event name
            var event_name = $j(this).closest('.ba-planning-event-box').find('p').eq(1).text();
            // get the event start date
            var event_start = $j(this).closest('.ba-planning-event-box').data('event-start');
            // get the event end date
            var event_end = $j(this).closest('.ba-planning-event-box').data('event-end');
            var booking_id = $j(this).data('booking-id');

            // set the data to the popup
            $j('.ba-planning-popup-content').data('event-id', event_id);
            $j('.ba-planning-popup-content').data('user-id', user_id);
            $j('.ba-planning-popup-content').data('event-start', event_start);
            $j('.ba-planning-popup-content').data('event-end', event_end);
            $j('.ba-planning-popup-content').data('booking-id', booking_id);

            // set the popup header
            $j('.ba-planning-popup-header h3').text(user_name);
            $j('.ba-planning-popup-header p').text(event_name + " - " + event_start + " / " + event_end);
            // add two button to the popup
            $j('.ba-planning-popup-content').html('<button class="ba-planning-popup-booking-delete">Supprimer</button><button class="ba-planning-popup-booking-refund">Rembourser</button>');
            $j('.ba-planning-popup-booking-delete').click(ba_plus_cancel_booking_callback);
            $j('.ba-planning-popup-booking-refund').click(ba_plus_refund_booking_callback);
        });
        $j('.ba-planning-popup-close').click(function (e) {
            e.preventDefault();
            // close the popup
            $j('.ba-planning-popup-bg').css('display', 'none');
        });


        $j('.ba-wl li').click(function (e) {
            e.preventDefault();
            // open the popup
            $j('.ba-planning-popup-bg').css('display', 'block');
            // get the user id
            var user_id = $j(this).data('user-id');
            // get the user name
            var user_name = $j(this).text();
            // get the event id
            var event_id = $j(this).closest('.ba-planning-event-box').data('event-id');
            // get the event name
            var event_name = $j(this).closest('.ba-planning-event-box').find('p').eq(1).text();
            // get the event start date
            var event_start = $j(this).closest('.ba-planning-event-box').data('event-start');
            // get the event end date
            var event_end = $j(this).closest('.ba-planning-event-box').data('event-end');

            // set the data to the popup
            $j('.ba-planning-popup-content').data('event-id', event_id);
            $j('.ba-planning-popup-content').data('user-id', user_id);
            $j('.ba-planning-popup-content').data('event-start', event_start);
            $j('.ba-planning-popup-content').data('event-end', event_end);

            // set the popup header
            $j('.ba-planning-popup-header h3').text(user_name);
            $j('.ba-planning-popup-header p').text(event_name + " - " + event_start + " / " + event_end);
            // add two button to the popup
            $j('.ba-planning-popup-content').html('<button class="ba-planning-popup-wl-delete">Supprimer</button>');
            $j('.ba-planning-popup-wl-delete').click(ba_plus_cancel_wl_callback);

        });

        function ba_plus_cancel_booking_callback(e) {
            console.log('cancel booking');
            e.preventDefault();
            // get the user id
            var user_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-header h3').text();
            // get the event id
            var event_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-header p').text();
            // get the booking id
            var booking_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('booking-id');
            // send the ajax request
            $j.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bookactiDeleteBooking',
                    user_id: user_id,
                    booking_id: booking_id,
                    context: 'admin_booking_list',
                    nonce: '<?php echo wp_create_nonce('bookacti_delete_booking'); ?>'
                },
                success: function (response) {
                    if (response.status === 'success') {
                        location.reload();
                    } else {
                        console.log(response);
                    }
                },
                error: function (response) {
                    console.log(response);
                }
            });
        }

        function ba_plus_refund_booking_callback(e) {
            e.preventDefault();
            // get the user id
            var user_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('user-id');
            // get the event data
            var event_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('event-id');
            var booking_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('booking-id');
            // send the ajax request
            $j.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bookactiRefundBooking',
                    user_id: user_id,
                    event_id: event_id,
                    booking_id: booking_id,
                    nonce: '<?php echo wp_create_nonce('bookacti_refund_booking'); ?>',
                    is_admin: 1,
                    refund_action: 'booking_pass'
                },
                success: function (response) {
                    if (response.status === 'success') {
                        location.reload();
                    } else {
                        console.log(response);
                    }
                },
                error: function (response) {
                    console.log(response);
                }
            });
        }

        function ba_plus_cancel_wl_callback(e) {
            e.preventDefault();
            // get the user id
            var user_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('user-id');
            // get the event data
            var event_id = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('event-id');
            var event_start = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('event-start');
            var event_end = $j(this).closest('.ba-planning-popup-bg').find('.ba-planning-popup-content').data('event-end');

            // send the ajax request
            $j.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'baPlusCancelWaitingList',
                    user_id: user_id,
                    waiting_id: event_id,
                    start_date: event_start,
                    end_date: event_end,
                },
                success: function (response) {
                    if (response.status === 'success') {
                        location.reload();
                    } else {
                        console.log(response);
                    }
                },
                error: function (response) {
                    console.log(response);
                }
            });
        }

    </script>

    <?php
    return ob_get_clean();
}

/**
 * Return the style for the planning
 * @return string
 */
function ba_plus_style_planning()
{
    ob_start();
    ?>
    <style>
        .ba-planning {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            max-width: 100vw !important;
        }

        .ba-planning-col:has(h3) {
            display: grid;
            grid-template-columns: 1fr;
            grid-template-rows: 50px 1fr;
            border: 1px solid black;
            border-right: 0px solid black;
            padding: 10px;
        }

        .ba-planning-col:has(h3):last-child {
            border: 1px solid black;
        }

        .ba-planning-col h3 {
            margin: 0;
            font-size: 1em;
        }

        .ba-planning-event-box {
            border: 1px solid black;
            padding: 10px;
            margin: 10px;
        }

        .ba-planning-event-box h4 {
            margin: 0;
        }

        .ba-planning-event-box ul {
            list-style-type: none;
            padding: 0;
        }

        .ba-planning-event-box li {
            cursor: pointer;
        }

        .ba-planning-event-box li:hover {
            background-color: #f1f1f1;
        }

        .ba-planning-event-box p {
            margin: 0;
            font-size: 1em;
            color: #333;
            text-align: center;
        }

        /*Popup style*/
        .ba-planning-popup-bg {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            backdrop-filter: blur(2px);
            z-index: 1000;
            transition: all 0.3s ease-in-out;
        }

        .ba-planning-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 40px;
            border-radius: 10px;
            background-color: white;
            z-index: 1001;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.3);
        }

        .ba-planning-popup-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr;
        }

        .ba-planning-popup-header h3 {
            margin: 0;
            font-size: 1.5em;
        }

        .ba-planning-popup-header p {
            margin: 0;
            font-size: 1em;
            color: #333;
            text-align: right;
        }

        .ba-planning-popup-content {
            overflow-y: auto;
            padding: 10px;
            display: flex;
            justify-content: space-around;
        }

        .ba-planning-popup-close {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }
    </style>
    <?php
    return ob_get_clean();
}