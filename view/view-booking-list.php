<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Show a list of all waiting users
 */
function ba_plus_show_waiting_list() {
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
                        <td><a href="#" class="ba-plus-cancel-waiting-list" data-waiting-id="<?php echo $waiting->id; ?>" data-user-id="<?php echo $waiting->user_id; ?>">Supprimer</a></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table><br><br>
    </div>
    <?php
}
add_action( "bookacti_after_booking_list", "ba_plus_show_waiting_list" );