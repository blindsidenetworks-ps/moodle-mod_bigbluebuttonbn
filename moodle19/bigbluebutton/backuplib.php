<?php 

/*
   Copyright 2010 Blindside Networks Inc.

   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

  Initial version:
        Fred Dixon (ffdixon [at] blindsidenetworks [dt] org)
 */

    //This php script contains all the stuff to backup/restore
    //bigbluebutton mods

    //This is the "graphical" structure of the BigBlueButton mod:
    //
    //                       bigbluebutton
    //                     (CL,pk->id)
    //
    // Meaning: pk->primary key field of the table
    //          fk->foreign key to link with parent
    //          nt->nested field (recursive data)
    //          CL->course level info
    //          UL->user level info
    //          files->table may have files)
    //
    //-----------------------------------------------------------

    //This function executes all the backup procedure about this mod
    function bigbluebutton_backup_mods($bf,$preferences) {
        global $CFG;

        $status = true; 

        ////Iterate over bigbluebutton table
        if ($bigbluebuttons = get_records ("bigbluebutton","course", $preferences->backup_course,"id")) {
            foreach ($bigbluebuttons as $bigbluebutton) {
                if (backup_mod_selected($preferences,'bigbluebutton',$bigbluebutton->id)) {
                    $status = bigbluebutton_backup_one_mod($bf,$preferences,$bigbluebutton);
                }
            }
        }
        return $status;
    }
   
    function bigbluebutton_backup_one_mod($bf,$preferences,$bigbluebutton) {

        global $CFG;
    
        if (is_numeric($bigbluebutton)) {
            $bigbluebutton = get_record('bigbluebutton','id',$bigbluebutton);
        }
    
        $status = true;

        //Start mod
        fwrite ($bf,start_tag("MOD",3,true));
        //Print assignment data
        fwrite ($bf,full_tag("ID",4,false,$bigbluebutton->id));
        fwrite ($bf,full_tag("MODTYPE",4,false,"bigbluebutton"));
        fwrite ($bf,full_tag("NAME",4,false,$bigbluebutton->name));
        fwrite ($bf,full_tag("MODERATORPASS",4,false,$bigbluebutton->moderatorpass));
        fwrite ($bf,full_tag("VIEWERPASS",4,false,$bigbluebutton->viewerpass));
        fwrite ($bf,full_tag("WAIT",4,false,$bigbluebutton->wait));
        fwrite ($bf,full_tag("MEETINGID",4,false,$bigbluebutton->meetingid));
        fwrite ($bf,full_tag("TIMEMODIFIED",4,false,$bigbluebutton->timemodified));
        //End mod
        $status = fwrite ($bf,end_tag("MOD",3,true));

        return $status;
    }

    ////Return an array of info (name,value)
    function bigbluebutton_check_backup_mods($course,$user_data=false,$backup_unique_code,$instances=null) {
        if (!empty($instances) && is_array($instances) && count($instances)) {
            $info = array();
            foreach ($instances as $id => $instance) {
                $info += bigbluebutton_check_backup_mods_instances($instance,$backup_unique_code);
            }
            return $info;
        }
        
         //First the course data
         $info[0][0] = get_string("modulenameplural","bigbluebutton");
         $info[0][1] = count_records("bigbluebutton", "course", "$course");
         return $info;
    } 

    ////Return an array of info (name,value)
    function bigbluebutton_check_backup_mods_instances($instance,$backup_unique_code) {
         //First the course data
        $info[$instance->id.'0'][0] = '<b>'.$instance->name.'</b>';
        $info[$instance->id.'0'][1] = '';
        return $info;
    }

?>
