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
    <h1>Booking Activities Plus</h1>
    <form method="post" action="options.php">
        <!-- DIsplay texte area for the mail content (Title and body) for Certifiat expire, waiting list, annulation -->
        <h2>Contenu des mails</h2>

        <h3>Mail pour certificat expiré</h3>
        <label for="ba_plus_mail_certi_expire_title">Titre</label><br>
        <input type="text" name="ba_plus_mail_certi_expire_title" value="<?php echo get_option('ba_plus_mail_certi_expire_title', "Votre %doc% expire bientôt"); ?>"></input><br>
        <label for="ba_plus_mail_certi_expire_body">Corps</label><br>
        <textarea name="ba_plus_mail_certi_expire_body" rows="10" cols="50"><?php echo get_option('ba_plus_mail_certi_expire_body', "Bonjour %user%,\nVotre %doc% expire dans %expire_date% jours, pensez à le renouveler !\nA bientôt"); ?></textarea><br>

        <h3>Mail pour liste d'attente</h3>
        <label for="ba_plus_mail_waiting_list_title">Titre</label><br>
        <input type="text" name="ba_plus_mail_waiting_list_title" value="<?php echo get_option('ba_plus_mail_waiting_list_title', "Vous êtes toujours dans la file d'attente"); ?>"></input><br>
        <label for="ba_plus_mail_waiting_list_body">Corps</label><br>
        <textarea name="ba_plus_mail_waiting_list_body" rows="10" cols="50"><?php echo get_option('ba_plus_mail_waiting_list_body', "Bonjour %user%, \nCe mail à pour but des vous rappeler votre mise en file d'attente pour %event%\nA Bientôt"); ?></textarea><br>

        <h3>Mail pour annulation</h3>
        <label for="ba_plus_mail_cancel_title">Titre</label><br>
        <input type="text" name="ba_plus_mail_cancel_title" value="<?php echo get_option('ba_plus_mail_cancel_title', "Scéance annulée"); ?>"></input><br>
        <label for="ba_plus_mail_cancel_body">Corps</label><br>
        <textarea name="ba_plus_mail_cancel_body" rows="10" cols="50"><?php echo get_option('ba_plus_mail_cancel_body', "Bonjour %user%, \nL'évènement %event% à été annuler par manque de participant\nVeuillez nous excuser du dérangement"); ?></textarea><br>

        <!-- Display input for the delay before sending the mail -->
        <h2>Paramètres</h2>
        <label for="ba_plus_delay">Délai avant envoi du mail (en heures)</label>
        <input type="number" name="ba_plus_delay" value="<?php echo get_option('ba_plus_delay', 12); ?>">


        <?php
        settings_fields('ba_plus_settings');
        do_settings_sections('ba_plus_settings');
        submit_button();
        ?>
    </form>
    
    <?php
}
