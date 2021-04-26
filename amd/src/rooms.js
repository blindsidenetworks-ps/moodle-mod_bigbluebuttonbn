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

import * as repository from './repository';
import {exception as displayException} from 'core/notification';
import Config from 'core/config';
import Templates from "core/templates";


export const init = (bigbluebuttonbnid) => {
    const completionElement = document.querySelector('a[href*=completion_validate]');
    if (completionElement) {
        completionElement.addEventListener("click", function () {
            repository.completionValidate(bigbluebuttonbnid).catch(displayException);
        });
    }
};
/**
 * Init action button
 */
export const initActions = () => {
    const actionButtons = document.querySelectorAll('.bbb-btn-action');
    actionButtons.forEach(
        (bt) => {
            bt.addEventListener('click', (e) => {
                const element = e.target;
                const id = element.id;
                const bbbid = element.dataset.bbbId;
                if (id === 'join_button_input') {
                    let group = 0;
                    const cmid = element.dataset.cmId;
                    let joinURL = new URL(Config.wwwroot + '/mod/bigbluebuttonbn/bbb_view.php');
                    const groupSelectors = document.querySelectorAll('.groupselector select[name="group"]');
                    if (groupSelectors.length) {
                        const selected = groupSelectors[0];
                        group = selected[selected.selectedIndex].value;
                    }
                    joinURL.searchParams.append('action', 'join');
                    joinURL.searchParams.append('id', cmid);
                    joinURL.searchParams.append('bn', bbbid);
                    if (group) {
                        joinURL.searchParams.append('group', group);
                    }
                    join(joinURL.toString());
                }
                if (id === 'end_button_input') {
                    const meetingId = element.dataset.meetingId;
                    repository.endMeeting({
                        'bigbluebuttonbnid': bbbid,
                        'meetingid': meetingId
                    }).then(
                        () => {
                            updateRoom();
                            autoUpdateRoom(true); // Stop autoupdating.
                        }
                    ).catch(displayException);
                }
            });
        }
    );
};

/**
 * Auto close window ?
 */
export const setupWindowAutoClose = () => {
    // Not sure what this does here. Will need to have a closer look into the process.
    window.onunload = function () {
        opener.setTimeout(function () {
            updateRoom();
        }, 5000);
        window.close();
    };
    window.close();
};

/**
 * Update room display.
 */
const updateRoom = (updatecache) => {
    const bbbRoomViewElement = document.getElementById('bbb-room-view');
    const bbbid = bbbRoomViewElement.dataset.bbbId;
    const meetingid = bbbRoomViewElement.dataset.meetingId;
    updatecache = (typeof updatecache === 'undefined') ? false : updatecache;
    // Meeting ID ?
    return repository.meetingInfo({
        bigbluebuttonbnid: bbbid,
        meetingid: meetingid,
        updatecache: updatecache
    }).then(
        (data) => {
            Templates.renderForPromise('mod_bigbluebuttonbn/room_view', data).then(
                (templatedata) => {
                    Templates.replaceNodeContents(bbbRoomViewElement, templatedata.html, templatedata.js);
                }
            ).catch(displayException);
            return data;
        }).catch(displayException);
};

/**
 * Join a BBB conference in a new window.
 * @param joinUrl
 */
const join = (joinUrl) => {
    autoUpdateRoom();
    window.open(joinUrl);
};

const CHECK_BBB_TIMEOUT = 1000;
const EXTEND_MAX_FACTOR = 10; // Extend timeout max factor.
/**
 * Function to start autoupdating and stop it if needed.
 *
 * @param stop
 */
function autoUpdateRoom(stop) {
    if (typeof autoUpdateRoom.timeoutint == 'undefined') {
        // It has not... perform the initialization
        autoUpdateRoom.timeoutint = 0;
    }
    if (typeof autoUpdateRoom.updatecount == 'undefined') {
        // It has not... perform the initialization
        autoUpdateRoom.updatecount = 0;
    }
    if (typeof autoUpdateRoom.currentfactor == 'undefined') {
        // It has not... perform the initialization
        autoUpdateRoom.currentfactor = 1;
    }
    if (stop) {
        if (autoUpdateRoom.timeoutint) {
            clearInterval(autoUpdateRoom.timeoutint);
            autoUpdateRoom.timeoutint = 0;
            autoUpdateRoom.updatecount = 0;
            autoUpdateRoom.currentfactor = 1;
        }
    } else {
        autoUpdateRoom.timeoutint = setInterval(function () {
            if ( (autoUpdateRoom.updatecount % autoUpdateRoom.currentfactor) === 0) {
                updateRoom(true);
                if (autoUpdateRoom.currentfactor >= EXTEND_MAX_FACTOR) {
                    autoUpdateRoom.currentfactor = 1;
                } else {
                    autoUpdateRoom.currentfactor++;
                }
            }
            autoUpdateRoom.updatecount++;
        }, CHECK_BBB_TIMEOUT);
    }
}