<?php
/**
 * Language File
 *
 * Authors:
 *    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 *
 * Translation files available at 
 *     http://www.getlocalization.com/bigbluebutton_moodle2x
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
defined('MOODLE_INTERNAL') || die();

$bigbluebuttonbn_locales = Array(
        'bbbduetimeoverstartingtime'=> "La date de fin doit être supérieure à la date de début.",
        'bbbdurationwarning' => 'La durée maximale de cette session est de %duration% minutes.',
        'bbbfinished' => 'Cette activité est terminée.',
        'bbbinprocess' => 'Cette activité est en cours.',
        'bbbnorecordings' => 'Il n\'y a pas d\'enregistrement actuellement. Merci de revenir plus tard.',
        'bbbnotavailableyet' => 'Désolé, cette session n\'est pas encore disponible.',
        'bbbrecordwarning' => 'Cette session est enregistrée.',
        'bbburl' => 'L\'URL de votre serveur BigBlueButton doit se terminer par /bigbluebutton/. (l\'URL par défaut pointe vers un serveur BigBlueButton fourni par Blindside Networks, que vous pouvez utiliser pour des tests)',
        'bigbluebuttonbn:join' => 'Joindre une conférence',
        'bigbluebuttonbn:moderate' => 'Modérer une conférence',
        'bigbluebuttonbn' => 'BigBlueButton',
        'bigbluebuttonbnfieldset' => 'Exemple de champ personnalisé',
        'bigbluebuttonbnintro' => 'Introduction BigBlueButton',
        'bigbluebuttonbnSalt' => 'Sel de sécurité',
        'bigbluebuttonbnUrl' => 'URL du serveur BigBlueButton',
        'bigbluebuttonbnWait' => 'L\'utilisateur doit patienter',
        'configsecuritysalt' => 'Le sel de sécurité de votre serveur BigBlueButton. (Le sel par défaut est pour un serveur BigBlueButton fourni par Blindside Networks, que vous pouvez utiliser pour des tests)',
        'general_error_unable_connect' => 'Impossible de se connecter. Vérifiez l\'URL du serveur BigBlueButton ET vérifiez que le serveur est actif.',
        'index_confirm_end' => 'Souhaitez-vous terminer la conférence ?',
        'index_disabled' => 'désactivé',
        'index_enabled' => 'activé',
        'index_ending' => 'Fermeture de la conférence... merci de patienter',
        'index_error_checksum' => 'Une erreur de vérification est apparue. Vérifiez votre sel de sécurité.',
        'index_error_forciblyended' => 'Impossible de joindre cette conférence, car elle a été manuellement fermée.',
        'index_error_unable_display' => 'Impossible d\'afficher les conférences. Vérifiez l\'URL du serveur BigBlueButton ET vérifiez que le serveur est actif.',
        'index_heading_actions' => 'Actions',
        'index_heading_group' => 'Groupe',
        'index_heading_moderator' => 'Modérateurs',
        'index_heading_name' => 'Salon',
        'index_heading_recording' => 'Enregistrement',
        'index_heading_users' => 'Utilisateurs',
        'index_heading_viewer' => 'Participants',
        'index_heading' => 'Salons BigBlueButton',
        'index_running' => 'en cours',
        'index_warning_adding_meeting' => 'Impossible d\'attribuer un nouveau meetingid.',
        'mod_form_block_general' => 'Paramètres généraux',
        'mod_form_block_record' => 'Paramètres d\'enregistrement',
        'mod_form_block_schedule' => 'Planification de session',
        'mod_form_field_availabledate' => 'Connexion à partir de',
        'mod_form_field_description' => 'Description de la session enregistrée',
        'mod_form_field_duedate' => 'Connexion jusqu\'à',
        'mod_form_field_duration_help' => 'Définir la durée d\'une conférence établira la durée maximale d\'une conférence, avant que l\'enregistrement ne se termine.',
        'mod_form_field_duration' => 'Durée',
        'mod_form_field_limitusers' => 'Limiter les participants',
        'mod_form_field_limitusers_help' => 'Nombre maximum de participants par conférence',
        'mod_form_field_name' => 'Nom de la conférence',
        'mod_form_field_newwindow' => 'Ouvrir BigBlueButton dans une nouvelle fenêtre',
        'mod_form_field_record' => 'Enregistrer',
        'mod_form_field_voicebridge_help' => 'Numéro de conférence vocale que les participants entrent pour rejoindre la conférence vocale.',
        'mod_form_field_voicebridge' => 'Connexion orale',
        'mod_form_field_wait' => 'Les étudiants doivent attendre qu\'un modérateur soit présent',
        'mod_form_field_welcome_default' => '<br>Bienvenue sur <b>%%CONFNAME%%</b>!<br><br>Pour comprendre comment fonctionne BigBlueButton, veuillez voir nos <a href="event:http://www.bigbluebutton.org/content/videos"><u>tutoriels vidéos</u></a>.<br><br>Pour joindre la communication audio, cliquez sur l\'icône du casque (en haut à gauche). <b>Merci d\'utiliser un casque, pour éviter l\'écho.</b>',
        'mod_form_field_welcome_help' => 'Remplace le message de bienvenue par défaut défini pour le serveur BigBlueButton. Le message peut inclure des mots clés (%%CONFNAME%%, %%DIALNUM%%, %%CONFNUM%%) qui seront substitués automatiquement, ainsi que des balises HTML comme <b>...</b> ou <i></i>',
        'mod_form_field_welcome' => 'Message d\'accueil',
        'modulename' => 'BigBlueButtonBN',
        'modulenameplural' => 'BigBlueButtonBN',
        'pluginadministration' => 'Administration BigBlueButton',
        'pluginname' => 'BigBlueButtonBN',
        'serverhost' => 'Nom du serveur',
        'view_error_no_group_student' => 'You have not been erolled in a group. Please contact your Teacher or the Administrator.',
        'view_error_no_group_teacher' => 'There are no groups configured yet. Please set up groups or contact the Administrator.',
        'view_error_no_group' => 'There are no groups configured yet. Please set up groups before trying to join the meeting.',
        'view_error_unable_join_student' => 'Impossible de se connecter au serveur BigBlueButton. Veuillez contacter votre enseignant ou l\'administrateur.',
        'view_error_unable_join_teacher' => 'Impossible de se connecter au serveur BigBlueButton. Veuillez contacter l\'administrateur.',
        'view_error_unable_join' => 'Impossible de rejoindre la conférence. Vérifiez l\'URL du serveur BigBlueButton ET vérifiez que le serveur est actif.',
        'view_groups_selection_join' => 'Joindre',
        'view_groups_selection' => 'Sélectionnez le groupe que vous souhaitez joindre, et confirmez l\'action',
        'view_login_moderator' => 'Connexion comme modérateur...',
        'view_login_viewer' => 'Connexion comme participant...',
        'view_noguests' => 'BigBlueButtonBN n\'est pas ouvert aux visiteurs anonymes',
        'view_nojoin' => 'Votre rôle ne vous permet pas de joindre cette session.',
        'view_recording_list_actionbar_delete' => 'Supprimer',
        'view_recording_list_actionbar_hide' => 'Cacher',
        'view_recording_list_actionbar_show' => 'Montrer',
        'view_recording_list_actionbar' => 'Outils',
        'view_recording_list_activity' => 'Activité',
        'view_recording_list_course' => 'Cours',
        'view_recording_list_date' => 'Date',
        'view_recording_list_description' => 'Description',
        'view_recording_list_recording' => 'Enregistrement',
        'view_wait' => 'La conférence n\'a pas encore démarré. En attente de connexion d\'un modérateur...',
        );

foreach($bigbluebuttonbn_locales as $key => $value){
    $string[$key] = $value;
}

?>