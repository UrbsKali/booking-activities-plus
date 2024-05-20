<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Show a list of all waiting users
 */
function ba_plus_show_waiting_list()
{
    $waiting_list = ba_plus_get_all_waiting_list();
    ?>
    <div class="ba-plus-waiting-list">
        <h2>Liste d'attente</h2>
        <table class="wp-list-table widefat fixed striped table-view-list bookings bookacti-list-table">
            <thead>
                <tr>
                    <th class="">Utilisateur</th>
                    <th>Événement</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($waiting_list as $waiting) {
                    $user = get_userdata($waiting->user_id);
                    $user_name = $user->display_name;
                    ?>
                    <tr>
                        <td><?php echo $user_name; ?></td>
                        <td><?php echo $waiting->title; ?></td>
                        <td><?php echo $waiting->start; ?></td>
                        <td><a href="#" class="ba-plus-cancel-waiting-list" data-waiting-id="<?php echo $waiting->id; ?>"
                                data-user-id="<?php echo $waiting->user_id; ?>">Supprimer</a></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table><br><br>
    </div>
    <?php
}
add_action("bookacti_after_booking_list", "ba_plus_show_waiting_list");


function ba_plus_admin_book()
{
    ?>
    <br><br> <button id="ba-plus-add-resa" class="button button-primary"
        onclick="document.querySelector('#ba-plus-add-resa-popup').classList.toggle('open');">Faire une réservation</button>
    <div id="ba-plus-add-resa-popup">
        <form id="ba-plus-add-resa-form">
            <!-- close btn -->
            <button type="button" class="button"
                onclick="document.querySelector('#ba-plus-add-resa-popup').classList.toggle('open');"
                style="position: absolute; top: 10px; right: 10px;">X</button>
            <h2>Ajouter une réservation</h2>
            <label for="ba-plus-add-resa-user">Utilisateur</label>
            <?php
            $selected_user = isset($_REQUEST['user_id']) ? esc_attr($_REQUEST['user_id']) : '';
            $args = apply_filters('bookacti_booking_list_user_selectbox_args', array(
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
            <label for="ba-plus-add-resa-event">Événement</label>
            <select name="event_id" id="ba-plus-add-resa-event">
                <option value="">Sélectionner un événement</option>
                <?php
                $filters = array(
                    'status' => 'active',
                    'start' => date('Y-m-d H:i:s'),
                    'end' => date('Y-m-d H:i:s', strtotime('+1 month')),
                    'limit' => -1
                );
                $filters = bookacti_format_booking_filters($filters);
                $events = bookacti_fetch_events($filters);
                foreach ($events['data'] as $event) {
                    echo '<option value="' . $event['id'] . '" data-start-date="' . $event["start"] . '" data-end-date="' . $event["endx"] . '">' . $event['title'] . " - " . $event['start'] . '</option>';
                }
                ?>
            </select>
            <button type="submit" class="button button-primary ba-plus-add-resa-btn">Ajouter</button>
        </form>
    </div>
    <style>
        #ba-plus-add-resa-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            transition: all 0.3s ease-in-out;
        }

        #ba-plus-add-resa-popup label {
            display: block;
            margin-bottom: 5px;
        }

        #ba-plus-add-resa-popup select {
            width: 100%;
            padding: 5px;
            margin-bottom: 10px;
        }

        #ba-plus-add-resa-popup .select2 {
            width: 100%;
            padding: 5px;
            margin-bottom: 10px;
        }

        #ba-plus-add-resa-popup button {
            padding: 5px 10px;
        }

        #ba-plus-add-resa-popup.open {
            display: block;
        }

        body:has(#ba-plus-add-resa-popup.open) {
            overflow: hidden;
        }
    </style>
    <?php
    return;
}
add_action("bookacti_after_booking_filters", "ba_plus_admin_book");