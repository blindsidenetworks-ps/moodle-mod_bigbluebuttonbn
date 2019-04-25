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

/**
 * Renderer.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */

namespace mod_bigbluebuttonbn\output;

use html_writer;
use html_table;
use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Class renderer
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Darko Miletic  (darko.miletic [at] gmail [dt] com)
 */
class renderer extends plugin_renderer_base {

    /**
     * Renderer for index.
     * @param  index $indexobj
     * @return string
     */
    protected function render_index(index $indexobj) {
        return html_writer::table($indexobj->table);
    }

    /**
     * Renderer for import_view.
     * @param  import_view $widget
     * @return string
     */
    protected function render_import_view(import_view $widget) {
        $context = $widget->export_for_template($this);
        return $this->render_from_template('mod_bigbluebuttonbn/import_view', $context);
    }

}