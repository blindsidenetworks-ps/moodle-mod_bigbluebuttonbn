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

$recordingsbn_locales = Array(
        'modulename' => 'Enregistrements BBB',
        'modulenameplural' => 'Enregistrements BBB',
        'modulename_help' => 'Utilisez le module enregistrements BBB pour voir les enregistrements de conférences BigBlueButton',
        'recordingsbnname' => 'Nom de la liste des enregistrements',
        'recordingsbnname_help' => 'Ce module enregistrements BBB permet de lister les conférences passées dans ce cours, qui ont été enregistrées, afin de les visionner après coup.',
        'recordingsbn' => 'Enregistrements BBB',
        'pluginadministration' => 'Administration enregistrements BBB',
        'pluginname' => 'Enregistrements BBB',
        'recordingsbn:view' => 'Voir les enregistrements BBB',
        'view_noguests' => 'Le module enregistrements conférences BBB n\'est pas ouvert aux visiteurs anonymes',
        );

foreach($recordingsbn_locales as $key => $value){
    $string[$key] = $value;
}

?>