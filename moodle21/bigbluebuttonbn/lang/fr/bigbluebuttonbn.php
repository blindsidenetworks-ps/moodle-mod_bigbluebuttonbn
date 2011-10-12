<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local language pack from http://fcv2a.univ-tlse1.fr
 *
 * @package    mod
 * @subpackage bigbluebuttonbn
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['bbbduetimeoverstartingtime'] = 'La date de fin doit être supérieure à la date de début.';
$string['bbbdurationwarning'] = 'La durée maximale de cette session est de %duration% minutes.';
$string['bbbfinished'] = 'Cette activité est terminée.';
$string['bbbinprocess'] = 'Cette activité est en cours.';
$string['bbbnorecordings'] = 'Il n\'y a pas d\'enregistrement actuellement. Merci de revenir plus tard.';
$string['bbbnotavailableyet'] = 'Désolé, cette session n\'est pas encore disponible.';
$string['bbbrecordwarning'] = 'Cette session est enregistrée.';
$string['bbburl'] = 'L\'URL de votre serveur BigBlueButton doit se terminer par /bigbluebutton/. (l\'URL par défaut pointe vers un serveur BigBlueButton fourni par Blindside Networks, que vous pouvez utiliser pour des tests)';
$string['bigbluebuttonbn:join'] = 'Joindre une conférence';
$string['bigbluebuttonbn:moderate'] = 'Modérer une conférence';
$string['bigbluebuttonbn'] = 'BigBlueButton';
$string['bigbluebuttonbnfieldset'] = 'Exemple de champ personnalisé';
$string['bigbluebuttonbnintro'] = 'Introduction BigBlueButton';
$string['bigbluebuttonbnSalt'] = 'Sel de sécurité';
$string['bigbluebuttonbnUrl'] = 'URL du serveur BigBlueButton';
$string['bigbluebuttonbnWait'] = 'L\'utilisateur doit patienter';
$string['configsecuritysalt'] = 'Le sel de sécurité de votre serveur BigBlueButton. (Le sel par défaut est pour vers un serveur BigBlueButton fourni par Blindside Networks, que vous pouvez utiliser pour des tests)';
$string['general_error_unable_connect'] = 'Impossible de se connecter. Vérifiez l\'URL du serveur BigBlueButton ET vérifiez que le serveur est actif.';
$string['index_confirm_end'] = 'Souhaitez-vous terminer la conférence ?';
$string['index_disabled'] = 'désactivé';
$string['index_enabled'] = 'activé';
$string['index_ending'] = 'Fermeture de la conférence... merci de patienter';
$string['index_error_checksum'] = 'Une erreur de vérification est apparue. Vérifiez votre sel de sécurité.';
$string['index_error_forciblyended'] = 'Unable to join this meeting because it has been manualy ended.';
$string['index_error_unable_display'] = 'Impossible d\'afficher les conférences. Vérifiez l\'URL du serveur BigBlueButton ET vérifiez que le serveur est actif.';
$string['index_heading_actions'] = 'Actions';
$string['index_heading_group'] = 'Group';
$string['index_heading_moderator'] = 'Modérateurs';
$string['index_heading_name'] = 'Salon';
$string['index_heading_recording'] = 'Enregistrement';
$string['index_heading_users'] = 'Utilisateurs';
$string['index_heading_viewer'] = 'Participants';
$string['index_heading'] = 'Salons BigBlueButton';
$string['index_running'] = 'en cours';
$string['index_warning_adding_meeting'] = 'Impossible d\'attribuer un nouveau meetingid.';
$string['mod_form_block_general'] = 'Paramètres généraux';
$string['mod_form_block_record'] = 'Paramètres d\'enregistrement';
$string['mod_form_block_schedule'] = 'Planification de session';
$string['mod_form_field_availabledate'] = 'Connexion à partir de';
$string['mod_form_field_description'] = 'Description de la session enregistrée';
$string['mod_form_field_duedate'] = 'Connexion jusqu\'à';
$string['mod_form_field_duration_help'] = 'Définir la durée d\'une conférence établira la durée maximale d\'une conférence, avant que l\'enregistrement ne se termine.';
$string['mod_form_field_duration'] = 'Durée';
$string['mod_form_field_name'] = 'Nom de la conférence';
$string['mod_form_field_newwindow'] = 'Ouvrir BigBlueButton dans une nouvelle fenêtre';
$string['mod_form_field_record'] = 'Enregistrer';
$string['mod_form_field_voicebridge_help'] = 'Numéro de conférence vocale que les participants entrent pour rejoindre la conférence vocale.';
$string['mod_form_field_voicebridge'] = 'Connexion orale';
$string['mod_form_field_wait'] = 'Les étudiants doivent attendre qu\'un modérateur soit présent';
$string['mod_form_field_welcome_default'] = '<br>Bienvenue sur <b>%%CONFNAME%%</b>!<br><br>Pour comprendre comment fonctionne BigBlueButton, veuillez voir nos <a href="event:http://www.bigbluebutton.org/content/videos"><u>tutoriels vidéos</u></a>.<br><br>Pour joindre la communication audio, cliquez sur l\'icône du casque (en haut à gauche). <b>Merci d\'utiliser un casque, pour éviter l\'écho.</b>';
$string['mod_form_field_welcome_help'] = 'Remplace le message de bienvenue par défaut défini pour le serveur BigBlueButton. Le message peut inclure des mots clés (%%CONFNAME%%, %%DIALNUM%%, %%CONFNUM%%) qui seront substitués automatiquement, ainsi que des balises HTML comme <b>...</b> ou <i></i>';
$string['mod_form_field_welcome'] = 'Message d\'accueil';
$string['modulename'] = 'BigBlueButtonBN';
$string['modulenameplural'] = 'BigBlueButtonBN';
$string['pluginadministration'] = 'Administration BigBlueButton';
$string['pluginname'] = 'BigBlueButtonBN';
$string['serverhost'] = 'Nom du serveur';
$string['view_error_unable_join_student'] = 'Impossible de se connecter au serveur BigBlueButton. Veuillez contacter votre enseignant ou l\'administrateur.';
$string['view_error_unable_join_teacher'] = 'Impossible de se connecter au serveur BigBlueButton. Veuillez contacter l\'administrateur.';
$string['view_error_unable_join'] = 'Impossible de rejoindre la conférence. Vérifiez l\'URL du serveur BigBlueButton ET vérifiez que le serveur est actif.';
$string['view_groups_selection_join'] = 'Join'; 
$string['view_groups_selection'] = 'Select the group you want to join and confirm the action'; 
$string['view_login_moderator'] = 'Connexion comme modérateur...';
$string['view_login_viewer'] = 'Connexion comme participant...';
$string['view_noguests'] = 'The BigBlueButtonBN is not open to guests';
$string['view_nojoin'] = 'You are not in a role allowed to join this session.';
$string['view_recording_list_actionbar_delete'] = 'Supprimer';
$string['view_recording_list_actionbar_hide'] = 'Cacher';
$string['view_recording_list_actionbar_show'] = 'Montrer';
$string['view_recording_list_actionbar'] = 'Barre d\'outils';
$string['view_recording_list_activity'] = 'Activité';
$string['view_recording_list_course'] = 'Cours';
$string['view_recording_list_date'] = 'Date';
$string['view_recording_list_description'] = 'Description';
$string['view_recording_list_recording'] = 'Enregistrement';
$string['view_wait'] = 'La conférence n\'a pas encore démarré. En attente de connexion d\'un modérateur...';
