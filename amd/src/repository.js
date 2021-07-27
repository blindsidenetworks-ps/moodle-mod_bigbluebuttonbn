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
 * Fetch the list of recordings from the server.
 *
 * @param   {Number} bigbluebuttonbnid The instance ID
 * @param   {number} groupid
 * @param   {Boolean} removeimportedid Remove already imported record
 * @param   {String} tools the set of tools to display
 * @returns {Promise}
 */
export const fetchRecordings = (bigbluebuttonbnid, groupid, removeimportedid, tools) => {
    const args = {
        bigbluebuttonbnid,
        removeimportedid,
        tools,
    };

    if (groupid) {
        args.groupid = groupid;
    }

    return fetchMany([{methodname: 'mod_bigbluebutton_recording_list_table', args}])[0];
};

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
 * End the Meeting
 *
 * @param {number} bigbluebuttonbnid
 * @param {string} meetingid
 * @returns {Promise}
 */
export const endMeeting = (bigbluebuttonbnid, meetingid) => fetchMany([
    {
        methodname: 'mod_bigbluebutton_meeting_end',
        args: {
            bigbluebuttonbnid,
            meetingid,
        },
    }
])[0];

/**
 * Validate completion.
 *
 * @param {object} args
 * @returns {Promise}
 */
export const completionValidate = args => fetchMany([
    {
        methodname: 'mod_bigbluebutton_completion_validate',
        args,
    }
])[0];


/**
 * Fetch meeting info for the specified meeting.
 *
 * @param {number} bigbluebuttonbnid
 * @param {string} meetingid
 * @param {boolean} [updatecache=false]
 * @returns {Promise}
 */
export const getMeetingInfo = (bigbluebuttonbnid, meetingid, updatecache = false) => fetchMany([
    {
        methodname: 'mod_bigbluebutton_meeting_info',
        args: {
            bigbluebuttonbnid,
            meetingid,
            updatecache,
        },
    }
])[0];
