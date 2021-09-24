<?php
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

namespace mod_bigbluebuttonbn\task;

use core\task\adhoc_task;
use mod_bigbluebuttonbn\recording;
use cache;
use moodle_exception;

/**
 * Class containing the scheduled task for converting recordings for the BigBlueButton version 2.5 in Moodle 4.0.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2021 Jesus Federico, Blindside Networks Inc <jesus at blindsidenetworks dot com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reset_recordings extends adhoc_task {

    /** @var int Chunk size to use when resetting recordings */
    protected static $chunksize = 50;

    /**
     * Run the migration task.
     */
    public function execute() {
        $classname = static::class;

        mtrace("Executing {$classname}...");

        $this->process_reset_serverinfo_cache();
        $this->process_reset_recordings_cache();
        $this->process_reset_recordings();
    }

    /**
     * Process reset key serverinfo from cache.
     */
    protected function process_reset_serverinfo_cache() {
        // Reset serverinfo cache.
        mtrace("Reset serverinfo key from cache...");
        $cache = cache::make('mod_bigbluebuttonbn', 'serverinfo');
        $cache->purge();
    }

    /**
     * Process reset key recordings from cache.
     */
    protected function process_reset_recordings_cache() {
        // Reset recording cache.
        mtrace("Reset recordings key from cache...");
        $cache = cache::make('mod_bigbluebuttonbn', 'recordings');
        $cache->purge();
    }

    /**
     * Process all bigbluebuttonbn_recordings looking for entries which should be reset to be fetched again.
     */
    protected function process_reset_recordings() {
        global $DB;

        // Reset status of all the recordings.
        mtrace("Reset status of all the recordings...");
        $sql = "UPDATE {bigbluebuttonbn_recordings}
                SET status = ?
                WHERE status = ? OR status = ?";
        mtrace($sql);
        $DB->execute($sql,
            [recording::RECORDING_STATUS_RESET, recording::RECORDING_STATUS_PROCESSED, recording::RECORDING_STATUS_NOTIFIED]
        );
    }
}
