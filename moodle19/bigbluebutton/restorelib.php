<?php 

/**
 * Backup.
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *
 * @package   mod_bigbluebutton
 * @copyright 2010 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */


    //This php script contains all the stuff to backup/restore
    //bigbluebutton mods

    //This is the "graphical" structure of the bigbluebutton mod:   
    //
    //                       bigbluebutton 
    //                    (CL,pk->id)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------

    //This function executes all the restore procedure about this mod
    function bigbluebutton_restore_mods($mod,$restore) {

        global $CFG;

        $status = true;

        //Get record from backup_ids
        $data = backup_getid($restore->backup_unique_code,$mod->modtype,$mod->id);

        if ($data) {
            //Now get completed xmlized object
            $info = $data->info;
            //traverse_xmlize($info);                                                                     //Debug
            //print_object ($GLOBALS['traverse_array']);                                                  //Debug
            //$GLOBALS['traverse_array']="";                                                              //Debug
          
            //Now, build the LABEL record structure
            $bigbluebutton->course = $restore->course_id;
            $bigbluebutton->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $bigbluebutton->moderatorpass= backup_todb($info['MOD']['#']['MODERATORPASS']['0']['#']);
            $bigbluebutton->viewerpass= backup_todb($info['MOD']['#']['VIEWERPASS']['0']['#']);
            $bigbluebutton->wait= backup_todb($info['MOD']['#']['WAIT']['0']['#']);
            $bigbluebutton->meetingid= backup_todb($info['MOD']['#']['MEETINGID']['0']['#']);
            $bigbluebutton->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //The structure is equal to the db, so insert the bigbluebutton
            $newid = insert_record ("bigbluebutton",$bigbluebutton);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","bigbluebutton")." \"".format_string(stripslashes($bigbluebutton->name),true)."\"</li>";
            }
            backup_flush(300);

            if ($newid) {
                //We have the newid, update backup_ids
                backup_putid($restore->backup_unique_code,$mod->modtype,
                             $mod->id, $newid);
   
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }

    function bigbluebutton_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;

/**
        if ($bigbluebuttons = get_records_sql ("SELECT l.id, l.moderatorpass, l.viewerpass, l.wait, l.meetingid
                                   FROM {$CFG->prefix}bigbluebutton l
                                   WHERE l.course = $restore->course_id")) {
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($bigbluebuttons as $bigbluebutton) {
                //Increment counter
                $i++;
                $content = $bigbluebutton->content;
                $result = restore_decode_content_links_worker($content,$restore);

                if ($result != $content) {
                    //Update record
                    $bigbluebutton->content = addslashes($result);
                    $status = update_record("bigbluebutton", $bigbluebutton);
                    if (debugging()) {
                        if (!defined('RESTORE_SILENTLY')) {
                            echo '<br /><hr />'.s($content).'<br />changed to<br />'.s($result).'<hr /><br />';
                        }
                    }
                }
                //Do some output
                if (($i+1) % 5 == 0) {
                    if (!defined('RESTORE_SILENTLY')) {
                        echo ".";
                        if (($i+1) % 100 == 0) {
                            echo "<br />";
                        }
                    }
                    backup_flush(300);
                }
            }
        }
**/
        return $status;
    }

    //This function returns a log record with all the necessay transformations
    //done. It's used by restore_log_module() to restore modules log.
    function bigbluebutton_restore_logs($restore,$log) {
                    
        $status = false;
                    
        //Depending of the action, we recode different things
        switch ($log->action) {
        case "add":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        case "update":
            if ($log->cmid) {
                //Get the new_id of the module (to recode the info field)
                $mod = backup_getid($restore->backup_unique_code,$log->module,$log->info);
                if ($mod) {
                    $log->url = "view.php?id=".$log->cmid;
                    $log->info = $mod->new_id;
                    $status = true;
                }
            }
            break;
        default:
            if (!defined('RESTORE_SILENTLY')) {
                echo "action (".$log->module."-".$log->action.") unknown. Not restored<br />";                 //Debug
            }
            break;
        }

        if ($status) {
            $status = $log;
        }
        return $status;
    }
?>
