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

import Templates from "core/templates";
import {exception as displayException} from 'core/notification';
import {getMeetingInfo} from './repository';

const timeout = 1000;
const maxFactor = 10;

let updateCount = 0;
let updateFactor = 1;
let timerReference = null;

const resetValues = () => {
    updateCount = 0;
    updateFactor = 1;
};

export const stop = () => {
    if (timerReference) {
        clearInterval(timerReference);
        timerReference = null;
    }

    resetValues();
};

export const poll = () => {
    if ((updateCount % updateFactor) === 0) {
        updateRoom(true)
        .then(() => {
            if (updateFactor >= maxFactor) {
                updateFactor = 1;
            } else {
                updateFactor++;
            }

            return;

        })
        .catch()
        .then(() => {
            timerReference = setTimeout(() => poll(), timeout);
            return;
        })
        .catch();
    }
};

export const updateRoom = (updatecache = false) => {
    const bbbRoomViewElement = document.getElementById('bbb-room-view');
    const bbbId = bbbRoomViewElement.dataset.bbbId;
    const meetingId = bbbRoomViewElement.dataset.meetingId;

    return getMeetingInfo(bbbId, meetingId, updatecache)
        .then(data => Templates.renderForPromise('mod_bigbluebuttonbn/room_view', data))
        .then(({html, js}) => Templates.replaceNodeContents(bbbRoomViewElement, html, js))
        .catch(displayException);
};
