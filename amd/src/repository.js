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

import {call as fetchMany} from 'core/ajax';

/**
 * Request for recording
 *
 * @param   {Number} bigbluebuttonbnid The instance ID
 * @param   {Boolean} removeimported Remove already imported record
 * @param   {String} tools the set of tools to display
 * @returns {Promise}
 */

const getListTableRequest = (bigbluebuttonbnid, removeimportedid, tools)  => {
    return {
        methodname: 'mod_bigbluebutton_recording_list_table',
        args: {
            bigbluebuttonbnid,
            removeimportedid,
            tools
        }
    };
};

/**
 * Fetch the list of recordings from the server.
 *
 * @param   {Number} bigbluebuttonbnid The instance ID
 * @param   {Boolean} removeimported Remove already imported record
 * @param   {String} tools the set of tools to display
 * @returns {Promise}
 */
export const fetchRecordings = (bigbluebuttonbnid, removeImportedId, tools) =>
    fetchMany([getListTableRequest(bigbluebuttonbnid, removeImportedId, tools)])[0];

/**
 * Perform an update on a single recording.
 *
 * @param   {object} args The instance ID
 * @returns {Promise}
 */
export const updateRecording = args => fetchMany([
    {
        methodname: 'mod_bigbluebutton_recording_update_recording',
        args,
    }
])[0];

/**
 * end Meeting
 * @param args
 * @returns {*}
 */
export const endMeeting = args => fetchMany([
    {
        methodname: 'mod_bigbluebutton_meeting_end',
        args,
    }
])[0];

/**
 * completionValidate
 * @param args
 * @returns {*}
 */
export const completionValidate = args => fetchMany([
    {
        methodname: 'mod_bigbluebutton_completion_validate',
        args,
    }
])[0];


/**
 * MeetingInfo
 * @param args
 * @returns {*}
 */
export const meetingInfo = args => fetchMany([
    {
        methodname: 'mod_bigbluebutton_meeting_info',
        args,
    }
])[0];

/**
 * Request for Opencast recording
 *
 * @param   {Number} bigbluebuttonbnid The instance ID
 * @param   {String} tools the set of tools to display
 * @returns {Promise}
 */

 const getOpencastListTableRequest = (bigbluebuttonbnid, tools)  => {
    return {
        methodname: 'mod_bigbluebutton_opencast_recording_list_table',
        args: {
            bigbluebuttonbnid,
            tools
        }
    };
};

/**
 * Fetch the list of Opencast recordings from the server.
 *
 * @param   {Number} bigbluebuttonbnid The instance ID
 * @param   {String} tools the set of tools to display
 * @returns {Promise}
 */
 export const fetchOpencastRecordings = (bigbluebuttonbnid, tools) =>
    fetchMany([getOpencastListTableRequest(bigbluebuttonbnid, tools)])[0];
