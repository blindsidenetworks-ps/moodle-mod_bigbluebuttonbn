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

/**
 * Initiate the YUI langauge strings with appropriate values for the sortable list from Moodle.
 *
 * @param   {YUI} Y
 * @returns {Promise}
 */
const initYuiLanguage = Y => {
    const stringList = [
        'view_recording_yui_first',
        'view_recording_yui_prev',
        'view_recording_yui_next',
        'view_recording_yui_last',
        'view_recording_yui_page',
        'view_recording_yui_go',
        'view_recording_yui_rows',
        'view_recording_yui_show_all',
    ].map(key => {
        return {
            key,
            component: 'bigbluebuttonbn',
        };
    });

    return getStrings(stringList)
        .then(([first, prev, next, last, goToLabel, goToAction, perPage, showAll]) => {
            Y.Intl.add('datatable-paginator', Y.config.lang, {
                first,
                prev,
                next,
                last,
                goToLabel,
                goToAction,
                perPage,
                showAll,
            });
        })
        .catch();
};

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
        day: 'numeric'
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
    let rowData = JSON.parse(recordingData.data);

    rowData = formatDates(recordingData.locale, rowData);

    return rowData;
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
    return [bbbid, tools];
};

/**
 * Setup the data table for the specified BBB instance.
 *
 * @param {String} tableId in which we will display the table
 * @param   {object} response The response from the data request
 * @returns {Promise}
 */
const setupDatatable = (tableId, response) => {
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
        return Promise.resolve();
    }

    return new Promise(function (resolve) {
        // eslint-disable-next-line
        YUI({
            lang: recordingData.locale,
        }).use('intl', 'datatable', 'datatable-sort', 'datatable-paginator', 'datatype-number', Y => {
            initYuiLanguage(Y)
                .then(() => {
                    const tableData = getFormattedData(response);

                    const dataTable = new Y.DataTable({
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
                .then(resolve)
                .catch();
        });
    })
    .then(dataTable => {
        dataTable.render(tableId);
        return dataTable;
    });
};

/**
 * Initialise opencast recordings code.
 *
 * @method init
 * @param {String} tableId in which we will display the table
 */
export const init = (tableId) => {
    const [bbbid, tools] = getTableInformations(tableId);
    repository.fetchOpencastRecordings(bbbid, tools)
        .then(response => setupDatatable(tableId, response))
        .catch(displayException);
};
