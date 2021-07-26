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
import {get_strings as getStrings} from 'core/str';
import {addIconToContainerWithPromise} from 'core/loadingicon';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import * as Str from 'core/str';

const stringList = [
    'view_recording_yui_first',
    'view_recording_yui_prev',
    'view_recording_yui_next',
    'view_recording_yui_last',
    'view_recording_yui_page',
    'view_recording_yui_go',
    'view_recording_yui_rows',
    'view_recording_yui_show_all',
];

const getStringsForYui = () => {
    const stringMap = stringList.map(key => {
        return {
            key,
            component: 'bigbluebuttonbn',
        };
    });

    return getStrings(stringMap)
    .then(([first, prev, next, last, goToLabel, goToAction, perPage, showAll]) => {
        return {
            first,
            prev,
            next,
            last,
            goToLabel,
            goToAction,
            perPage,
            showAll,
        };
    })
    .catch();
};

const getYuiInstance = lang => new Promise(resolve => {
    // eslint-disable-next-line
    YUI({
        lang,
    }).use('intl', 'datatable', 'datatable-sort', 'datatable-paginator', 'datatype-number', Y => {
        resolve(Y);
    });
});

/**
 * Format the supplied date per the specified locale.
 *
 * @param   {string} locale
 * @param   {array} dateList
 * @returns {array}
 */
const formatDates = (locale, dateList) => dateList.map(row => {
    const date = new Date(row.date);
    row.date = date.toLocaleDateString(locale, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

    return row;
});

/**
 * Format response data for the table.
 *
 * @param   {string} response JSON-encoded table data
 * @returns {array}
 */
const getFormattedData = response => {
    const recordingData = response.tabledata;
    const rowData = JSON.parse(recordingData.data);

    return formatDates(recordingData.locale, rowData);
};

/**
 *
 * @param {String} tableId in which we will display the table
 * @returns {[(*|number), string, boolean]}
 */
const getTableInformations = (tableId) => {
    const tableElement = document.querySelector(tableId);
    const bbbid = tableElement.dataset.bbbid;
    const tools = tableElement.dataset.tools;
    const removeImportedId = tableElement.dataset.removeImportedId;
    return [bbbid, removeImportedId, tools];
};

/**
 *
 * @param {String} tableId in which we will display the table
 * @param {String} searchFormId The Id of the relate.
 * @param {Object} dataTable
 * @returns {{refreshTableData: refreshTableData, filterByText: filterByText, registerEventListeners: registerEventListeners}}
 */
const getDataTableFunctions = (tableId, searchFormId, dataTable) => {
    const updateTableFromResponse = response => {
        if (!response || !response.status) {
            // There was no output at all.
            return;
        }

        dataTable.get('data').reset(getFormattedData(response));
        dataTable.set(
            'currentData',
            dataTable.get('data')
        );

        const currentFilter = dataTable.get('currentFilter');
        if (currentFilter) {
            filterByText(currentFilter);
        }
    };

    const [bbbid, removeImportedId, tools] = getTableInformations(tableId);
    const refreshTableData = () => repository.fetchRecordings(bbbid, removeImportedId, tools).then(updateTableFromResponse);

    const filterByText = value => {
        const dataModel = dataTable.get('currentData');
        dataTable.set('currentFilter', value);

        const escapedRegex = value.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
        const rsearch = new RegExp(`<span>.*?${escapedRegex}.*?</span>`, 'i');

        dataTable.set('data', dataModel.filter({asList: true}, item => {
            const name = item.get('recording');
            if (name && rsearch.test(name)) {
                return true;
            }

            const description = item.get('description');
            if (description && rsearch.test(description)) {
                return true;
            }

            return false;
        }));
    };

    const requestAction = (element) => {
        const getDataFromAction = (element, dataType) => element.closest(`[data-${dataType}]`).dataset[dataType];

        const elementData = element.dataset;
        const payload = {
            bigbluebuttonbnid: bbbid,
            recordingid: getDataFromAction(element, 'recordingid'),
            additionaloptions: getDataFromAction(element, 'additionaloptions'),
            action: elementData.action,
        };
        // Slight change for import, the bigbluebuttonid is the bbb origin id.
        if (elementData.action === 'import') {
            payload.bigbluebuttonbnid = getDataFromAction(element, 'bboriginid');
        }
        if (element.dataset.requireConfirmation === "1") {
            // Create the confirmation dialogue.
            return new Promise((resolve) =>
                ModalFactory.create({
                    title: Str.get_string('confirm'),
                    body: recordingConfirmationMessage(payload),
                    type: ModalFactory.types.SAVE_CANCEL
                }).then(modal => {
                    modal.setSaveButtonText(Str.get_string('ok'));

                    // Handle save event.
                    modal.getRoot().on(ModalEvents.save, function() {
                        resolve(true);
                    });

                    // Handle hidden event.
                    modal.getRoot().on(ModalEvents.hidden, function() {
                        // Destroy when hidden.
                        modal.destroy();
                        resolve(false);
                    });

                    modal.show();

                    return modal;
                }).catch(Notification.exception)
            ).then((proceed) =>
                proceed ? repository.updateRecording(payload) : () => null
            );
        } else {
            return repository.updateRecording(payload);
        }
    };

    const recordingConfirmationMessage = async(data) => {
        let confirmation = await Str.get_string('view_recording_' + data.action + '_confirmation', 'bigbluebuttonbn');
        if (typeof confirmation === 'undefined') {
            return '';
        }
        let recordingType = await Str.get_string('view_recording', 'bigbluebuttonbn');
        const playbackElement = document.querySelector('#playbacks-' + data.recordingid);
        if (playbackElement.dataset.imported === 'true') {
            recordingType = await Str.get_string('view_recording_link', 'bigbluebuttonbn');
        }
        confirmation = confirmation.replace("{$a}", recordingType);
        if (data.action === 'import') {
            return confirmation;
        }
        // If it has associated links imported in a different course/activity, show that in confirmation dialog.
        const associatedLinks = document.querySelector(`a#recording-${data.action}-${data.recordingid}`);

        if (associatedLinks && associatedLinks.dataset && associatedLinks.dataset.links === 0) {
            return confirmation;
        }
        const numberAssociatedLinks = Number.parseInt(associatedLinks.dataset.links);
        let confirmationWarning = await Str.get_string('view_recording_' + data.action + '_confirmation_warning_p',
            'bigbluebuttonbn', numberAssociatedLinks);
        if (numberAssociatedLinks === 1) {
            confirmationWarning = await Str.get_string('view_recording_' + data.action + '_confirmation_warning_s',
                'bigbluebuttonbn');
        }
        confirmationWarning = confirmationWarning.replace("{$a}", numberAssociatedLinks) + '. ';
        return confirmationWarning + '\n\n' + confirmation;
    };

    /**
     * Process an action event.
     *
     * @param   {Event} e
     */
    const processAction = e => {
        const popoutLink = e.target.closest('a[data-href]');
        if (popoutLink) {
            e.preventDefault();

            const videoPlayer = window.open('', '_blank');
            videoPlayer.opener = null;
            videoPlayer.location.href = popoutLink.dataset.href;

            // TODO repository.viewRecording(args); .

            return;
        }

        // Fetch any clicked anchor.
        const clickedLink = e.target.closest('a[data-action]');
        if (clickedLink) {
            e.preventDefault();

            // Create a spinning icon on the table.
            const iconPromise = addIconToContainerWithPromise(dataTable.get('boundingBox').getDOMNode());

            requestAction(clickedLink)
                .then(refreshTableData)
                .catch(displayException)
                .then(iconPromise.resolve)
                .catch();
        }
    };

    const processSearchSubmission = e => {
        // Prevent the default action.
        e.preventDefault();
        const parentNode = e.target.closest('div[role=search]');
        const searchInput = parentNode.querySelector('input[name=search]');
        filterByText(searchInput.value);
    };

    const registerEventListeners = () => {
        // Add event listeners to the table boundingBox.
        const boundingBox = dataTable.get('boundingBox').getDOMNode();
        boundingBox.addEventListener('click', processAction);

        // Setup the search from handlers.
        const searchForm = document.querySelector(searchFormId);
        if (searchForm) {
            const searchButton = document.querySelector(searchFormId + ' button');
            searchButton.addEventListener('click', processSearchSubmission);
        }
    };

    return {
        filterByText,
        refreshTableData,
        registerEventListeners,
    };
};

/**
 * Setup the data table for the specified BBB instance.
 *
 * @param {String} tableId in which we will display the table
 * @param {String} searchFormId The Id of the relate.
 * @param   {object} response The response from the data request
 * @returns {Promise}
 */
const setupDatatable = (tableId, searchFormId, response) => {
    if (!response) {
        return Promise.resolve();
    }

    if (!response.status) {
        // Something failed. Continue to show the plain output.
        return Promise.resolve();
    }

    const recordingData = response.tabledata;

    let showRecordings = recordingData.profile_features.indexOf('all') !== -1;
    showRecordings = showRecordings || recordingData.profile_features.indexOf('showrecordings') !== -1;
    if (!showRecordings) {
        // TODO: This should be handled by the web service.
        // This user is not allowed to view recordings.
        return Promise.reject();
    }

    return Promise.all([getYuiInstance(recordingData.locale), getStringsForYui()])
    .then(([yuiInstance, strings]) => {
        // Add the fetched strings to the YUI Instance.
        yuiInstance.Intl.add('datatable-paginator', yuiInstance.config.lang, {...strings});

        return yuiInstance;
    })
    .then(yuiInstance => {
        const tableData = getFormattedData(response);

        const dataTable = new yuiInstance.DataTable({
            width: "1195px",
            columns: recordingData.columns,
            data: tableData,
            rowsPerPage: 3,
            paginatorLocation: ['header', 'footer']
        });
        dataTable.set('currentData', dataTable.get('data'));
        dataTable.set('currentFilter', '');

        return dataTable;
    })
    .then(dataTable => {
        dataTable.render(tableId);
        const {registerEventListeners} = getDataTableFunctions(
            tableId,
            searchFormId,
            dataTable);
        registerEventListeners();

        return dataTable;
    });
};

/**
 * Initialise recordings code.
 *
 * @method init
 * @param {String} tableId in which we will display the table
 * @param {String} searchFormId The Id of the relate.
 */
export const init = (tableId, searchFormId) => {
    const [bbbid, removeImportedId, tools] = getTableInformations(tableId);

    repository.fetchRecordings(bbbid, removeImportedId, tools)
        .then(response => setupDatatable(tableId, searchFormId, response))
        .catch(displayException);
};
