<?php 

/**
 * Backup.
 *
 * Authors:
 *      Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 *      Jesus Federico (jesus [at] blindsidenetworks [dt] org)
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */


    //This php script contains all the stuff to backup/restore
    //bigbluebuttonbn mods

    //This is the "graphical" structure of the bigbluebuttonbn mod:   
    //
    //                       bigbluebuttonbn 
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
    function bigbluebuttonbn_restore_mods($mod,$restore) {

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
            $bigbluebuttonbn->course = $restore->course_id;
            $bigbluebuttonbn->name = backup_todb($info['MOD']['#']['NAME']['0']['#']);
            $bigbluebuttonbn->moderatorpass= backup_todb($info['MOD']['#']['MODERATORPASS']['0']['#']);
            $bigbluebuttonbn->viewerpass= backup_todb($info['MOD']['#']['VIEWERPASS']['0']['#']);
            $bigbluebuttonbn->wait= backup_todb($info['MOD']['#']['WAIT']['0']['#']);
            $bigbluebuttonbn->meetingid= backup_todb($info['MOD']['#']['MEETINGID']['0']['#']);
            $bigbluebuttonbn->timemodified = backup_todb($info['MOD']['#']['TIMEMODIFIED']['0']['#']);

            //The structure is equal to the db, so insert the bigbluebuttonbn
            $newid = insert_record ("bigbluebuttonbn",$bigbluebuttonbn);

            //Do some output     
            if (!defined('RESTORE_SILENTLY')) {
                echo "<li>".get_string("modulename","bigbluebuttonbn")." \"".format_string(stripslashes($bigbluebuttonbn->name),true)."\"</li>";
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

    function bigbluebuttonbn_decode_content_links_caller($restore) {
        global $CFG;
        $status = true;

/**
        if ($bigbluebuttonbns = get_records_sql ("SELECT l.id, l.moderatorpass, l.viewerpass, l.wait, l.meetingid
                                   FROM {$CFG->prefix}bigbluebuttonbn l
                                   WHERE l.course = $restore->course_id")) {
            $i = 0;   //Counter to send some output to the browser to avoid timeouts
            foreach ($bigbluebuttonbns as $bigbluebuttonbn) {
                //Increment counter
                $i++;
                $content = $bigbluebuttonbn->content;
                $result = restore_decode_content_links_worker($content,$restore);

                if ($result != $content) {
                    //Update record
                    $bigbluebuttonbn->content = addslashes($result);
                    $status = update_record("bigbluebuttonbn", $bigbluebuttonbn);
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
    function bigbluebuttonbn_restore_logs($restore,$log) {
                    
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
