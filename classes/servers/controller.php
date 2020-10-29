<?php
/**
 * This script is owned by CBlue SPRL, please contact CBlue regarding any licences issues.
 *
 * @date :       30/10/2020
 * @author:      rlemaire@cblue.be
 * @copyright:   CBlue SPRL, 2020
 */

namespace mod_bigbluebuttonbn\servers;

use bootstrap_renderer;
use coding_exception;
use core\notification;
use mod_bigbluebuttonbn\server;
use moodle_exception;
use stdClass;

class controller
{
    /**
     * View action.
     */
    const ACTION_VIEW = 'view';

    /**
     * Add action.
     */
    const ACTION_ADD = 'add';

    /**
     * Edit action.
     */
    const ACTION_EDIT = 'edit';

    /**
     * Delete action.
     */
    const ACTION_DELETE = 'delete';

    /**
     * @var $output bootstrap_renderer $OUTPUT object
     */
    protected $output;

    /**
     * controller constructor.
     */
    public function __construct()
    {
        global $OUTPUT;

        $this->output = $OUTPUT;
    }

    /**
     * Execute required action.
     *
     * @param string $action Action to execute.
     */
    public function execute($action)
    {
        $this->set_external_page();

        switch ($action) {
            case self::ACTION_ADD:
                $this->edit($action, null);
                break;
            case self::ACTION_EDIT:
                $this->edit($action, required_param('id', PARAM_INT));
                break;

            case self::ACTION_DELETE:
                $this->delete(required_param('id', PARAM_INT));
                break;
            case self::ACTION_VIEW:
            default:
                $this->view();
                break;
        }
    }

    /**
     * Returns base URL for the manager.
     * @return string
     */
    public static function get_base_url(): string
    {
        return '/mod/bigbluebuttonbn/servers.php';
    }

    /**
     * Set external page for the manager.
     */
    protected function set_external_page()
    {
        admin_externalpage_setup('bbbservers');
    }

    /**
     * Execute view action.
     */
    protected function view()
    {
        $this->header(get_string('admin_external_page_bbbservers', 'bigbluebuttonbn'));

        $this->print_add_button();

        $records = server::get_records([], 'id');

        $table = new server_table();
        $table->display($records);

        $this->footer();
    }

    /**
     * Execute edit action.
     *
     * @param string $action Could be edit or create.
     * @param null|int $id Id of the region or null if creating a new one.
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function edit($action, $id = null)
    {
        global $PAGE;

        $PAGE->set_url(new \moodle_url(static::get_base_url(), ['action' => $action, 'id' => $id]));
        $instance = null;

        if ($id) {
            $instance = $this->get_instance($id);
        }

        $form = $this->get_form($instance);

        if ($form->is_cancelled()) {
            redirect(new \moodle_url(static::get_base_url()));
        } elseif ($data = $form->get_data()) {
            unset($data->submitbutton);
            try {
                if (empty($data->id)) {
                    $persistent = $this->get_instance(0, $data);
                    $persistent->create();
                } else {
                    $instance->from_record($data);
                    $instance->update();
                }
                notification::success(get_string('changessaved'));
            } catch (\Exception $e) {
                notification::error($e->getMessage());
            }
            redirect(new \moodle_url(static::get_base_url()));
        } else {
            if (empty($instance)) {
                $this->header(get_string('server_new', 'bigbluebuttonbn'));
            } else {
                $this->header(get_string('server_edit', 'bigbluebuttonbn'));
            }
        }

        $form->display();
        $this->footer();
    }

    /**
     * Execute delete action.
     *
     * @param int $id ID of the region.
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function delete($id)
    {
        require_sesskey();
        $instance = $this->get_instance($id);

        $instance->delete();
        notification::success(get_string('deleted'));

        redirect(new \moodle_url(static::get_base_url()));
    }

    /**
     * Print out add button.
     */
    protected function print_add_button()
    {
        echo $this->output->single_button(
            new \moodle_url(static::get_base_url(), ['action' => self::ACTION_ADD]),
            get_string('addbbbserver', 'bigbluebuttonbn')
        );
    }

    /**
     * Returns form for the record.
     *
     * @param $instance
     * @return server_form
     * @throws coding_exception
     */
    protected function get_form($instance): server_form
    {
        global $PAGE;

        return new server_form($PAGE->url->out(false), ['persistent' => $instance]);
    }

    /**
     * Return record instance.
     *
     * @param int $id
     * @param stdClass|null $data
     * @return server
     */
    protected function get_instance($id = 0, stdClass $data = null)
    {
        return new server($id, $data);
    }

    /**
     * Print out page header.
     *
     * @param string $title Title to display.
     */
    protected function header($title)
    {
        echo $this->output->header();
        echo $this->output->heading($title);
    }

    /**
     * Print out the page footer.
     *
     * @return void
     */
    protected function footer()
    {
        echo $this->output->footer();
    }
}
