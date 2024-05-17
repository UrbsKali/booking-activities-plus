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

    $waiting_list = ba_plus_get_user_waiting_list(get_current_user_id());


    foreach ($waiting_list as $key => $value) {
        if ($value->event_id == 0)
            continue; // Skip if event id is 0 (no event id)

        $event = bookacti_get_event_by_id($value->event_id);
        $waiting_list[$key]->event_name = $event->title;
        $waiting_list[$key]->event_start = $event->start;
        $waiting_list[$key]->event_end = $event->end;
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
 * @return html
 */
function bookacti_get_waiting_list_actions_html($waiting_item)
{
    ob_start();
    ?>
    <div class='bookacti-booking-actions'>
        <?php
        // cancel waiting list
        echo "<a href='#' class='bookacti-cancel-waiting-list' data-waiting-id='" . $waiting_item->id . "'>Annuler</a>";
        ?>
    </div>
    <?php
    return ob_get_clean();
}
