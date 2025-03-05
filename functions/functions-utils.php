<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create a user waiting list.
 *
 * This function generates a waiting list for users based on specific filters.
 *
 * @param array $filters An associative array of filters to apply on the user list.
 * @param array $columns Optional. An array defining the columns to be included in the waiting list. Defaults to an empty array.
 * @param int   $per_page Optional. The number of results to display per page. Defaults to 10.
 *
 * @return mixed Returns the waiting list data. The data is show in HTML to the client.
 */
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
                            'UTC',
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
    </div>
    <?php

    return apply_filters('bookacti_user_booking_list_html', ob_get_clean(), $booking_list_items, $columns, $filters, $per_page);
}

/**
 * Generates HTML output for waiting list actions.
 *
 * This function creates the HTML structure that displays the available actions for a waiting list item.
 * It is intended to be used within the booking activities module, providing users with interactive options
 * for managing waiting items.
 *
 * @param mixed $waiting_item The waiting item data, which can be an array or object containing the necessary details.
 * @return string The HTML markup representing the waiting list actions.
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
 * @return string HTML content
 */
function ba_plus_create_planning($args)
{
    // Get GET parameters for start date
    if (isset($_GET['start_date'])) {
        $today = sanitize_text_field($_GET['start_date']);
        $today = date('Y-m-d h:i:s', strtotime($today));
    } else {
        $today = new DateTime();
        $today = $today->format('Y-m-d h:i:s');
    }

    // snap to the beginning of the week
    if (date('N', strtotime($today)) != 1)
        $today = date('Y-m-d h:i:s', strtotime('last monday', strtotime($today)));


    $next_week = date('Y-m-d h:i:s', strtotime('+1 week', strtotime($today)));

    $interval = array(
        'start' => $today,
        'end' => $next_week
    );
    $args = array(
        'interval' => $interval,
        'active' => 1,
        'past_events' => 1,
    );

    $events = bookacti_fetch_events($args);
    $events_by_day = array();
    $data = $events["data"];
    $events = $events['events'];
    foreach ($events as $event) {
        $event["repeat_freq"] = $data[$event["id"]]["repeat_freq"];
        // check start date of the event
        for ($i = 1; $i <= 7; $i++) {
            $after = date('Y-m-d', strtotime('+' . $i . ' day', strtotime($today)));
            $before = date('Y-m-d', strtotime('+' . $i - 1 . ' day', strtotime($today)));
            if (!isset($events_by_day[$before]))
                $events_by_day[$before] = array();
            if ($event['start'] > $before && $event['start'] < $after) {
                $events_by_day[$before][] = $event;
            }
        }
    }
    // if no events, return 7 cols on the planning
    if (count($events) == 0) {
        for ($i = 1; $i <= 7; $i++) {
            $before = date('Y-m-d', strtotime('+' . $i - 1 . ' day', strtotime($today)));
            $events_by_day[$before] = array();
        }
    }

    ob_start();
    ?>
    <script>
        const nonce_change_booking = '<?php echo wp_create_nonce( 'bookacti_change_booking_status' ); ?>';
    </script>
    <div class="ba-planning-navbar ba-plus-ignore-print">
        <div>
            <button id="ba-planning-prev-week"><<</button>
            <button id="ba-planning-today">Aujourd'hui</button>
            <button id="ba-planning-next-week">>></button>
            <input type="date" id="ba-planning-date" value="<?php echo date('Y-m-d', strtotime($today)); ?>">
        </div>
        <button id="ba-planning-print">Imprimer</button>
    </div>
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
            <div class="user-add-popup">
                <?php
                $selected_user = isset($_REQUEST['user_id']) ? esc_attr($_REQUEST['user_id']) : '';
                $args = apply_filters(
                    'bookacti_booking_list_user_selectbox_args',
                    array(
                        'name' => 'user_id',
                        'id' => 'bookacti-booking-filter-customer',
                        'show_option_all' => esc_html__('All', 'booking-activities'),
                        'option_label' => array('first_name', ' ', 'last_name', ' (', 'user_login', ' / ', 'user_email', ')'),
                        'selected' => $selected_user,
                        'allow_clear' => 1,
                        'allow_tags' => 1,
                        'echo' => 1
                    )
                );
                bookacti_display_user_selectbox($args);
                ?>
                <button id="ba-plus-user-search-send">Ajouter</button>
            </div>
            <div class="ba-plus-info">
                
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Create an day column, with multiple events in it
 * @param array $events the events to display, agregated with user booked and waiting list
 * @return string HTML content
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

    $availability = bookacti_get_event_by_id($id)->availability;

    $is_recurring = $event['repeat_freq'] == "none" ? 0 : 1;
    ob_start();
    ?>
    <div class="ba-planning-event-box" data-event-id="<? echo $id; ?>" data-event-start="<? echo $start; ?>"
        data-event-end="<? echo $end; ?>" data-is-recurring="<? echo $is_recurring; ?>">
        <p class="quantity" data-count="<? echo count($event['booked'])?>" data-availability="<? echo $availability?>"><? echo  - count($event['booked']) + $availability; ?> dispo.</p> 
        <p><?php echo $pretty_start . "/" . $pretty_end; ?></p>
        <p><?php echo $event['title']; ?></p>
        <div class="ba-plus-action">
            <button class="ba-plus-add-btn ba-plus-btn">
                Ajouter un participant
            </button>
            <button class="ba-plus-edit-btn ba-plus-btn">
                Modifier le cours
            </button>
        </div>

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

/**
 * Remove <br> and <p> tags from shortcodes content
 */
function html_shorttag_filter($content)
{
    // Based on: https://wordpress.org/plugins/lct-temporary-wpautop-disable-shortcode/
    $new_content = '';
    $pieces = preg_split('/(\[html\].*?\[\/html\])/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    // don't interfere with plugin that disables wpautop on the entire page
    // see: https://plugins.svn.wordpress.org/toggle-wpautop/tags/1.2.2/toggle-wpautop.php
    $autop_disabled = get_post_meta(get_the_ID(), '_lp_disable_wpautop', true);
    foreach ($pieces as $piece) {
        if (preg_match('/\[html\](.*?)\[\/html\]/is', $piece, $matches)) {
            $new_content .= $matches[1];
        } else {
            $new_content .= $autop_disabled ? $piece : wpautop($piece);
        }
    }
    // remove the wpautop filter, but only do it if the other plugin won't do it for us
    if (!$autop_disabled) {
        remove_filter('the_content', 'wpautop');
        remove_filter('the_excerpt', 'wpautop');
    }
    return $new_content;
}
// idea to use 9 is from: https://plugins.svn.wordpress.org/wpautop-control/trunk/wpautop-control.php
add_filter('the_content', 'html_shorttag_filter', 9);
add_filter('the_excerpt', 'html_shorttag_filter', 9);


/**
 * Format the mail content, replacing %user% and %event% by the user and event informations
 * @param string $text the text to format
 * @param string $start_date the start date of the event
 * @param string $end_date the end date of the event
 * @param string $title the title of the event
 * @return string
 */
function ba_plus_format_mail($text, $start_date, $end_date, $title, $user){
    $start = ba_plus_get_full_date($start_date);
    $end = ba_plus_get_full_date($end_date);

    $pretty_day = $start['day'] . " " . $start['number'] . " " . $start['month'];
    $hour_range = $start['hour'] . " - " . $end['hour'];

    $text = str_replace("%user%", $user->display_name, $text);
    $text = str_replace("%event%", $title . " du " . $pretty_day . " de " . $hour_range, $text);
    return $text;
}

/**
 * Get the full date in french
 * @param string $date the date to format
 * @return array the date formatted
 */
function ba_plus_get_full_date($date){
    $date_ = datefmt_create(
        "fr-FR",
        IntlDateFormatter::FULL,
        IntlDateFormatter::FULL,
        'Europe/Paris',
        IntlDateFormatter::GREGORIAN
    );
    $str_date = datefmt_format($date_, strtotime($date));
    $pretty_hour = date('H:i', strtotime($date));
    $str_date = ucfirst($str_date);
    $date = explode(" ", $str_date);
    $pretty_hour = str_replace(":", "h", $pretty_hour);

    return array(
        "day" => $date[0],
        "number" => $date[1],
        "month" => $date[2],
        "year" => $date[3],
        "hour" => $pretty_hour,
    );
}