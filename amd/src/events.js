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

// TODO For master this can be changed to `core/event_dispatcher` and the local copy removed.
// For 3.11 and earlier this line should stay.
import {dispatchEvent} from './event_dispatcher';

export const eventTypes = {
    /**
     * Fired when a session has been ended.
     * @event mod_bigbluebuttonbn/sessionEnded
     * @type CustomEvent
     * @property {object} detail
     * @property {string} detail.bbbId
     * @property {string} detail.meetingId
     */
    sessionEnded: 'mod_bigbluebuttonbn/sessionEnded',
};

/**
 * Trigger the sessionEnded event.
 *
 * @param {string} bbbId
 * @param {string} meetingId
 * @returns {CustomEvent}
 * @fires event:mod_bigbluebuttonbn/sessionEnded
 */
export const notifySessionEnded = (bbbId, meetingId) => dispatchEvent(eventTypes.sessionEnded, {
    bbbId,
    meetingId,
});
