<?php
/**
 * Plugin Name: Booking Activities Plus
 * Plugin URI: https://github.com/Urbskali/booking-activities
 * Description: A plugin to add a waiting list, among many other features, to the Booking Activities plugin
 * Version: 1.0
 * Author: Urbskali
 * Author URI: https://github.com/urbskali
 * License: Mine :) (No touchy!)
 * Text Domain: booking-activities-plus
 */

if (!defined('ABSPATH')) {
    exit;
}

// Global variables 
if (!defined('BA_PLUS_VERSION')) {
    define('BA_PLUS_VERSION', '1.0');
}
if (!defined('BA_PLUS_PLUGIN_NAME')) {
    define('BA_PLUS_PLUGIN_NAME', 'Booking Activities Waiting List');
}
if (!defined('BA_PLUS_PATH')) {
    define('BA_PLUS_PATH', __DIR__);
}


// # ---------------- IMPORT ---------------- # //
// -- DATABASE -- //
require_once ('model/model-install.php');
require_once ('model/model-global.php');
require_once ('model/model-waiting-list.php');

// -- FONCTIONS -- //
require_once ('functions/functions-utils.php');
require_once ('functions/functions-booking.php');

// -- CONTROLLERS -- //
require_once ('controller/controller-shortcodes.php');
require_once ('controller/controller-certificate.php');
require_once ('controller/controller-waiting-list.php');

// -- VUES -- //
require_once ('view/view-settings.php');

// -- CRON -- //
require_once ('cron/cron-waiting-list.php');
require_once ('cron/cron-certificate.php');


// # ---------------- HOOKS ---------------- # //
add_filter("bookacti_validate_picked_event", "ba_plus_validate_picked_event", 5, 3);
add_filter("bookacti_validate_picked_events", "ba_plus_validate_picked_events", 5, 3);
add_filter( "bookacti_booking_can_be_cancelled", "ba_plus_can_cancel_event", 5, 4 );
add_action("bookacti_booking_form_before_booking", "ba_plus_add_user_to_waiting_list", 5, 3);




// # ---------------- JS SCRIPTS ---------------- # //
function ba_plus_enqueue_scripts()
{
    wp_enqueue_script('ba-wl-enable', plugins_url('js/enable-waiting-list.js', __FILE__), array('jquery'), BA_PLUS_VERSION, true);
    wp_enqueue_script('ba-wl-sort', plugins_url('js/sort-by-date.js', __FILE__), array('jquery'), BA_PLUS_VERSION, true);
    wp_enqueue_script('ba-wl-btn', plugins_url('js/send-cancel-wl.js', __FILE__), array('jquery'), BA_PLUS_VERSION, true);
}
add_action('wp_enqueue_scripts', 'ba_plus_enqueue_scripts');

// SEND AJAX REQUEST
function ba_plus_ajaxurl()
{
    ?>
    <script type="text/javascript">
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    </script>
    <?php
}
add_action('wp_head', 'ba_plus_ajaxurl');



// # ---------------- ADMIN PAGES ---------------- # //
/**
 * Add the waiting list page to the admin menu
 * @return void
 */
function ba_plus_create_menu()
{
    add_submenu_page('booking-activities', 'Plus', 'Plus', 'bookacti_manage_booking_activities', 'bookacti_waiting_list_settings', 'ba_plus_settings_page');
}
add_action('bookacti_admin_menu', 'ba_plus_create_menu', 20);



// # ---------------- MANAGE INSTALL STATE ---------------- # //

// activate the plugin
function ba_plus_activate()
{
    // Check if Booking Activities is installed
    if (!defined('BOOKACTI_TABLE_EVENTS')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires Booking Activities to be installed and active.');
    }

    // check if the version of Booking Activities is compatible
    if (version_compare(BOOKACTI_VERSION, '1.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires Booking Activities version 1.0 or higher.');
    }

    // Create tables in database
    ba_plus_create_tables();

    // Add options
    add_option('ba_plus_version', BA_PLUS_VERSION);
    add_option('ba_plus_install_date', time());

    // Add rewrite rules
    flush_rewrite_rules();


    do_action('ba_plus_activate');

}
register_activation_hook(__FILE__, 'ba_plus_activate');

function ba_plus_deactivate()
{
    // Remove rewrite rules
    flush_rewrite_rules();

    ba_plus_drop_table();

    do_action('ba_plus_deactivate');
}


// uninstall the plugin
function ba_plus_uninstall()
{
    // Drop tables in database
    ba_plus_drop_table();

    // Remove options
    delete_option('ba_plus_version');
    delete_option('ba_plus_install_date');

    // Remove transients
    delete_transient('ba_plus_installing');

    // Remove rewrite rules
    flush_rewrite_rules();

    do_action('ba_plus_uninstall');
}
register_uninstall_hook(__FILE__, 'ba_plus_uninstall');

