<?php
/**
 * Plugin Name: Booking Activities Plus
 * Plugin URI: https://github.com/Urbskali/booking-activities-plus
 * Description: A plugin to add a waiting list, among many other features, to the Booking Activities plugin
 * Version: 1.0.0
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
require_once ('model/model-passes.php');

// -- FONCTIONS -- //
require_once ('functions/functions-utils.php');
require_once ('functions/functions-booking.php');
require_once ('functions/functions-booking-system.php');
require_once ('functions/functions-passes.php');
require_once ('functions/functions-um.php');

// -- CONTROLLERS -- //
require_once ('controller/controller-admin.php');
require_once ('controller/controller-shortcodes.php');
require_once ('controller/controller-certificate.php');
require_once ('controller/controller-waiting-list.php');
require_once ('controller/controller-settings.php');

// -- VUES -- //
require_once ('view/view-settings.php');
require_once ('view/view-booking-list.php');

// -- CRON -- //
require_once ('cron/cron-waiting-list.php');
require_once ('cron/cron-certificate.php');
require_once ('cron/cron-cancel.php');



// # ---------------- JS SCRIPTS ---------------- # //
function ba_plus_enqueue_scripts()
{
    wp_enqueue_script('ba-wl-enable', plugins_url('js/enable-waiting-list.js', __FILE__), array('jquery'), BA_PLUS_VERSION, true);
    wp_enqueue_script('ba-wl-sort', plugins_url('js/sort-by-date.js', __FILE__), array('jquery'), BA_PLUS_VERSION, true);
    wp_enqueue_script('ba-wl-btn', plugins_url('js/send-cancel-wl.js', __FILE__), array('jquery'), BA_PLUS_VERSION, true);
    wp_enqueue_script('ba-wl-page-resa', plugins_url('js/planning.js', __FILE__), array('jquery'), BA_PLUS_VERSION, true);
    wp_register_script('ba-planning', plugins_url('js/admin-planning.js', __FILE__), array('jquery'), BA_PLUS_VERSION, true);
    wp_register_style('ba-planning-style', plugins_url('css/planning.css', __FILE__), BA_PLUS_VERSION, true);
}
add_action('wp_enqueue_scripts', 'ba_plus_enqueue_scripts');

function ba_plus_enqueue_admin_scripts()
{
    wp_enqueue_script('ba-wl-admin', plugins_url('js/admin-settings.js', __FILE__), array('jquery'), BA_PLUS_VERSION, true);
    wp_enqueue_script('ba-wl-cancel-admin', plugins_url('js/admin-cancel-wl.js', __FILE__), array('jquery'), BA_PLUS_VERSION, true);
    wp_enqueue_script('ba-wl-resa-admin', plugins_url('js/admin-resa.js', __FILE__), array('jquery'), BA_PLUS_VERSION, true);
    
}
add_action('admin_enqueue_scripts', 'ba_plus_enqueue_admin_scripts');

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
    add_submenu_page('booking-activities', 'Plus', 'Plus', 'bookacti_manage_booking_activities', 'ba-plus-settings', 'ba_plus_settings_page');
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
    add_option('ba_plus_refund_delay', 24);

    add_option('ba_plus_mail_cancel_title', 'Scéance annulée');
    add_option('ba_plus_mail_cancel_body', "Bonjour %user%, \nL'évènement %event% à été annulé par manque de participant\nVeuillez nous excuser du dérangement");

    add_option('ba_plus_mail_waiting_list_title', "Vous êtes toujours dans la file d'attente");
    add_option('ba_plus_mail_waiting_list_body', "Bonjour %user%, \nVous êtes toujours en alerte sur le cours %event%, si vous n\'êtes plus disponible, pensez à supprimer cette alerte, sinon vous risquez de ne plus pouvoir vous annuler sans frais.\nMerci de votre confiance.");
   
    add_option('ba_plus_mail_booked_title', "Vous avez été inscrit automatiquement à un cours");
    add_option('ba_plus_mail_booked_body', "Bonjour %user%, \nVous avez été inscit(e) sur le cours %event%, à la suite de votre alerte. Vous avez la possiblité de vous annuler sans frais à plus de 24 heures. \nMerci de votre confiance.");

    add_option('ba_plus_mail_certi_expire_title', 'Votre %doc% expire bientôt');
    add_option('ba_plus_mail_certi_expire_body', 'Bonjour %user%,\nVotre %doc% arrivera à échéance dans %expire_date% jours, pensez à le renouveler et à nous l\'envoyer scanné pour ne pas que votre copte soit bloqué. Les modèles de docuements à remplir sont dans le CGU. \nA bientôt');

    add_option('ba_plus_mail_tree_cancel_left_title', 'Plus que trois annulations');
    add_option('ba_plus_mail_tree_cancel_left_body', "Bonjour %user%, \nAttention, il ne vous reste plus que 3 annulations sans frais sur le quota attribué à votre forfait en cours.\nMerci de votre confiance.");
    

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
    delete_option('ba_plus_refund_delay');
    delete_option('ba_plus_mail_cancel_body');
    delete_option('ba_plus_mail_cancel_title');
    delete_option('ba_plus_mail_waiting_list_body');
    delete_option('ba_plus_mail_waiting_list_title');
    delete_option('ba_plus_mail_certi_expire_body');
    delete_option('ba_plus_mail_tree_cancel_left_title');
    delete_option('ba_plus_mail_tree_cancel_left_body');
    delete_option('ba_plus_mail_certi_expire_title');

    // Remove transients
    delete_transient('ba_plus_installing');

    // Remove rewrite rules
    flush_rewrite_rules();

    do_action('ba_plus_uninstall');
}
register_uninstall_hook(__FILE__, 'ba_plus_uninstall');

