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

import './actions';
import * as repository from './repository';
import * as roomUpdater from './roomupdater';
import Config from 'core/config';
import {
    exception as displayException,
    fetchNotifications,
} from 'core/notification';
import {eventTypes} from './events';

export const init = (bigbluebuttonbnid) => {
    const completionElement = document.querySelector('a[href*=completion_validate]');
    if (completionElement) {
        completionElement.addEventListener("click", () => {
            repository.completionValidate(bigbluebuttonbnid).catch(displayException);
        });
    }
};

/**
 * Get the id of the current group.
 *
 * @param {HTMLElement} actionButton
 * @returns {number}
 * @private
 */
const getCurrentGroupId = actionButton => {
    const groupSelector = document.querySelector('.groupselector select[name="group"]');
    if (groupSelector) {
        return groupSelector.value;
    }

    return actionButton.dataset.groupId;
};

/**
 * Join a BBB conference in a new window.
 *
 * @param {string} joinUrl
 * @private
 */
const joinConference = joinUrl => {
    roomUpdater.poll();
    window.open(joinUrl);
};

/**
 * Init action button
 */
export const initActions = () => {
    const actionButtonRegion = document.getElementById('room_view_action_buttons');

    actionButtonRegion.addEventListener('click', e => {
        const actionButton = e.target.closest('.bbb-btn-action');
        if (!actionButton) {
            return;
        }

        const bbbId = actionButton.dataset.bbbId;

        if (actionButton.dataset.action === "join") {
            e.preventDefault();

            // TODO: This should be a link on the button itself.
            const joinURL = new URL(`${Config.wwwroot}/mod/bigbluebuttonbn/bbb_view.php`);

            joinURL.searchParams.append('action', 'join');
            joinURL.searchParams.append('id', actionButton.dataset.cmId);
            joinURL.searchParams.append('bn', bbbId);
            joinURL.searchParams.append('group', getCurrentGroupId(actionButton));

            joinConference(joinURL.toString());

            return;
        }
    });

    document.addEventListener(eventTypes.sessionEnded, () => {
        roomUpdater.stop();
        roomUpdater.updateRoom();
        fetchNotifications();
    });
};

/**
 * Auto close window ?
 * TODO Remove??
 */
export const setupWindowAutoClose = () => {
    // Not sure what this does here. Will need to have a closer look into the process.
    window.onunload = function() {
        opener.setTimeout(function() {
            roomUpdater.updateRoom();
        }, 5000);
        window.close();
    };
    window.close();
};
