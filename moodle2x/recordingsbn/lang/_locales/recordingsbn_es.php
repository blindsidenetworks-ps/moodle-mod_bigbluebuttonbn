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
        'modulename'=> "RecordingsBN",
        'modulenameplural'=> "RecordingsBN",
        'modulename_help'=> "Utilce el modulo recordingsBN como recurso en un curso para tener acceso a las grabaciones relacionadas con este.",
        'recordingsbnname'=> "Nombre de grabacion",
        'recordingsbnname_help'=> "RecordingsBN proporciona una lista de grabaciones alojadas en un Servidor BigBlueButton ofreciendo acceso directo a ellas.",
        'recordingsbn'=> "RecordingsBN",
        'pluginadministration'=> "Administracion de recordingsbn",
        'pluginname'=> "RecordingsBN",
        'recordingsbn:view'=> "Ver las grabaciones almacenadas en un Servidor BigBlueButton",
        'view_noguests'=> "El modulo RecordingsBN no se encuentra disponible para visitantes",
        );

foreach($recordingsbn_locales as $key => $value){
    $string[$key] = $value;
}

?>