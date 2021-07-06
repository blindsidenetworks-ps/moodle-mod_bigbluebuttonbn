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
     * Render the index table.
     *
     * @param  index $index
     * @return string
     */
    protected function render_index(index $index): string {
        $this->page->requires->js_call_amd('mod_bigbluebuttonbn/index', 'init');

        return html_writer::table($index->get_table($this));
    }

    /**
     * Renders the general warning button.
     *
     * @param string $href
     * @param string $text
     * @param string $class
     * @param string $title
     *
     * @return string
     */
    public function render_warning_button($href, $text = '', $class = '', $title = '') {
        if ($text == '') {
            $text = get_string('ok', 'moodle');
        }
        if ($title == '') {
            $title = $text;
        }
        if ($class == '') {
            $class = 'btn btn-secondary';
        }
        $output = '  <form method="post" action="' . $href . '" class="form-inline">' . "\n";
        $output .= '      <button type="submit" class="' . $class . '"' . "\n";
        $output .= '          title="' . $title . '"' . "\n";
        $output .= '          >' . $text . '</button>' . "\n";
        $output .= '  </form>' . "\n";
        return $output;
    }

    /**
     * Renders the general warning message.
     *
     * @param string $message
     * @param string $type
     * @param string $href
     * @param string $text
     * @param string $class
     *
     * @return string
     */
    public function render_warning($message, $type = 'info', $href = '', $text = '', $class = '') {
        $output = "\n";
        // Evaluates if config_warning is enabled.
        if (empty($message)) {
            return $output;
        }
        $output .= $this->output->box_start(
                'box boxalignleft adminerror alert alert-' . $type . ' alert-block fade in',
                'bigbluebuttonbn_view_general_warning'
            ) . "\n";
        $output .= '    ' . $message . "\n";
        $output .= '  <div class="singlebutton pull-right">' . "\n";
        if (!empty($href)) {
            $output .= $this->render_warning_button($href, $text, $class);
        }
        $output .= '  </div>' . "\n";
        $output .= $this->output->box_end() . "\n";
        return $output;
    }

}
