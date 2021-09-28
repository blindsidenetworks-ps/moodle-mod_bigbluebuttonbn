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
use context_user;
use core\chart_series;
use core\notification;
use core\chart_bar;
use core_user;
use Exception;
use mod_bigbluebuttonbn\locallib\bigbluebutton;
use mod_bigbluebuttonbn\server;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/message/lib.php');

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
     * View statistics action.
     */
    const ACTION_STATISTICS = 'statistics';

    /**
     *
     */
    const STATISTICS_DURATIONS = [
        'day',
        'week',
        'month',
        'trimester',
        'year'
    ];

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
            case self::ACTION_STATISTICS:
                $this->view_statistics(optional_param('duration', '',PARAM_ALPHA));
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
        $this->print_statistics_button();

        $records = server::get_records([], 'id');

        $table = new server_table();
        $table->display($records);

        $this->footer();
    }

    /**
     * Execute view statistics action.
     *
     * This will cal the create_stats_charts() function to generate the chart handling the statistics
     *
     * @param string|null $duration
     * @throws \dml_exception
     * @throws coding_exception
     */
    protected function view_statistics(string $duration = null) {
        global $OUTPUT;

        //set up chart
        $chart = $this->create_stats_chart($duration);

        //print out page
        $this->header(get_string('admin_external_page_bbbservers', 'bigbluebuttonbn') . ' ' .  get_string('seestatisticsrecent', 'bigbluebuttonbn'));
        $this->print_statistics_button();

        $this->print_statistics_duration_button();

        echo $OUTPUT->render($chart);

        $this->footer();
    }

    /**
     * This function is meant to be called by the view_statistics() function.
     *
     * It will fetch stats records from DB table mdl_bigbluebuttonbn_statistics according to duration.
     * If no duration is specified it'll fetch the last records of each server.
     *
     * Then it'll loop through the stats to set up the chart parameters and return the constructed chart object for the view to render.
     *
     * @param string|null $duration see const STATISTICS_DURATION array to check durations
     * @return chart_bar
     * @throws \dml_exception
     * @throws coding_exception
     */
    protected function create_stats_chart(string $duration)
    {
        global $DB;

        //fetch stats according to duration or fi duration not specified : last 3 records
        if ($duration == 'day') {
            $stats = $DB->get_records_sql("
                            SELECT id, serverid, servername, SUM(meetingscount) as meetingscount, SUM(attendeescount) as attendeescount 
                            FROM {bigbluebuttonbn_statistics} 
                            WHERE FROM_UNIXTIME(timecreated) > NOW() - INTERVAL 1 DAY
                            GROUP BY serverid");
        } else if ($duration == 'week') {
            $stats = $DB->get_records_sql("
                            SELECT id, serverid, servername, SUM(meetingscount) as meetingscount, SUM(attendeescount) as attendeescount 
                            FROM {bigbluebuttonbn_statistics} 
                            WHERE FROM_UNIXTIME(timecreated) > NOW() - INTERVAL 1 WEEK
                            GROUP BY serverid");
        } else if ($duration == 'month') {
            $stats = $DB->get_records_sql("
                            SELECT id, serverid, servername, SUM(meetingscount) as meetingscount, SUM(attendeescount) as attendeescount 
                            FROM {bigbluebuttonbn_statistics} 
                            WHERE FROM_UNIXTIME(timecreated) > NOW() - INTERVAL 1 MONTH
                            GROUP BY serverid");
        } else if ($duration == 'trimester') {
            $stats = $DB->get_records_sql("
                            SELECT id, serverid, servername, SUM(meetingscount) as meetingscount, SUM(attendeescount) as attendeescount 
                            FROM {bigbluebuttonbn_statistics} 
                            WHERE FROM_UNIXTIME(timecreated) > NOW() - INTERVAL 3 MONTH 
                            GROUP BY serverid");
        } else if ($duration == 'year') {
            $stats = $DB->get_records_sql("
                            SELECT id, serverid, servername, SUM(meetingscount) as meetingscount, SUM(attendeescount) as attendeescount 
                            FROM {bigbluebuttonbn_statistics} 
                            WHERE FROM_UNIXTIME(timecreated) > NOW() - INTERVAL 1 YEAR
                            GROUP BY serverid");
        } else {
            $stats = $DB->get_records_sql("SELECT * FROM {bigbluebuttonbn_statistics} WHERE id in (SELECT MAX(id) FROM {bigbluebuttonbn_statistics} GROUP BY serverid)");
        }

        // START setup charts
        $chart = new chart_bar();

        //add a new serie (column) for each element from stats
        foreach ($stats as $elem) {
            $serie = new chart_series($elem->servername, [$elem->meetingscount, $elem->attendeescount]);
            $chart->add_series($serie);
            if (isset($elem->timecreated)) {
                $chart->set_title(get_string('seestatisticslast' . $duration, 'bigbluebuttonbn') . date('d-m-Y G:i',$elem->timecreated));
            }
        }

        //if duration is set, inform duration in the title
        if ($duration != null) {
            $chart->set_title(get_string('seestatistics' . $duration, 'bigbluebuttonbn'));
        }

        //set labels = chart's bottom 'category' of data
        $chart->set_labels([
            get_string('seestatisticsnbrmeetings', 'bigbluebuttonbn'),
            get_string('seestatisticsnbrattendeess', 'bigbluebuttonbn')
        ]);
        //END setup charts

        return $chart;
    }

    /**
     * Execute edit action.
     *
     * if edition is setting a server to the 'crashed' status, it'll trigger end_all_meetings_from_server()
     *
     * @param string $action Could be edit or create.
     * @param null|int $id Id of the region or null if creating a new one.
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function edit($action, $id = null)
    {
        global $PAGE, $DB;

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
                $data->url = trim($data->url);
                if (empty($data->id)) {
                    //check if no other server uses that url
                    $twinservers = $DB->get_records('bigbluebuttonbn_servers', ['url' => $data->url]);
                    if (!empty($twinservers)) {
                        throw new Exception(get_string('server_already_exists', 'mod_bigbluebuttonbn'));
                    }

                    $persistent = $this->get_instance(0, $data);
                    $persistent->create();
                } else {
                    //check if no other server uses that url
                    $twinservers = $DB->get_records_sql("SELECT * from {bigbluebuttonbn_servers} WHERE id != :id AND url = :url", ['id' => $id, 'url' => $data->url]);
                    if (!empty($twinservers)) {
                        throw new Exception(get_string('server_already_exists', 'mod_bigbluebuttonbn'));
                    }

                    $instance->from_record($data);
                    //check if updated server record has now '3' enabled status = 'crashed'
                    if ($data->enabled === '3') {
                        //if so, end all meetings of this server
                        $this->end_all_meetings_from_server($data->id);
                    }
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
        // Delete relations between activities and server to force choice of a new server on next meeting launch
        global $DB;
        $DB->delete_records('bigbluebuttonbn_bn_server', ['serverid' => $id]);

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
     * Print out see statistics button.
     */
    protected function print_statistics_button()
    {
        echo $this->output->single_button(
            new \moodle_url(static::get_base_url(), ['action' => self::ACTION_STATISTICS]),
            get_string('seestatistics', 'bigbluebuttonbn')
        );
    }

    /**
     * Print out see statistics button for given durations
     *
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function print_statistics_duration_button()
    {
        foreach (self::STATISTICS_DURATIONS as $DURATION) {
            echo $this->output->single_button(
                new \moodle_url(static::get_base_url(), ['action' => self::ACTION_STATISTICS, 'duration' => $DURATION]),
                get_string('seestatistics' . $DURATION, 'bigbluebuttonbn')
            );
        }
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

    /**
     * This function will fetch all meetings from a given server.
     * The server ID is from the mdl_bigbluebuttonbn_servers table
     *
     * @param $servid
     * @return object|null
     * @throws \dml_exception
     */
    protected function get_meetings_from_server($servid)
    {
        global $DB;

        $server = $DB->get_record_sql("SELECT * FROM {bigbluebuttonbn_servers} WHERE id = $servid");
        bigbluebutton::$selected_server = new server(0, $server);
        $url = bigbluebutton::action_url('getMeetings');
        $servinfo = bigbluebuttonbn_wrap_xml_load_file($url);
        return $servinfo;
    }

    /**
     * This function will call get_meetings_from_server() the fetch all meetings linked to a server ID, then will loop through them and call bigbluebuttonbn_end_meeting() function to end each call.
     * Then will fetch all rooms hosting meetings related to the server and will call bigbluebuttonbn_end_selected_servers() function to kill the room
     *
     * @param $servid
     * @throws \dml_exception
     */
    protected function end_all_meetings_from_server($servid)
    {
        global $DB;

        //fetch meetings related to server
        $servinfo = $this->get_meetings_from_server($servid);

        //loop through meetings and end them than notify users
        if (!empty($servinfo->meetings)) {
            foreach ($servinfo->meetings->meeting as $meeting) {
                $meetingid = $meeting->meetingID->__toString();
                $modPW = $meeting->moderatorPW->__toString();

                bigbluebuttonbn_end_meeting($meetingid, $modPW);

                $this->notify_users_of_ended_meeting($meeting);
            }
        }

        //fetch all meeting rooms related to server than end them
        $meetingrooms = $DB->get_records_sql("SELECT * FROM {bigbluebuttonbn_bn_server} WHERE serverid = $servid");
        foreach ($meetingrooms as $room) {
            bigbluebuttonbn_end_selected_servers($room->bnid);
        }
    }

    /**
     * This function is triggered by the end_all_meetings_from_server() function. It'll set the course context from the meeting metadata then get all users related to this context.
     * Then a message is sent to that list of users. The message content is set by 'crashmsgcontent' in the lang file.
     *
     * @param $data
     * @throws coding_exception
     */
    protected function notify_users_of_ended_meeting($data)
    {
        // set course context and fetch related users
        $coursecontext = \context_course::instance((int) $data->metadata->{'bbb-context-id'}->__toString());
        $userslist = get_enrolled_users($coursecontext);

        //msg each user
        foreach ($userslist as $user) {
            $this->send_crashed_msg($user);
        }
    }

    /**
     * Sends a notifification to all users of a crashed course
     * The msg content is in lang/crashmsgcontent
     *
     * @param $user
     * @throws coding_exception
     */
    protected function send_crashed_msg($user)
    {
        $message = new \core\message\message();
        $message->component = 'mod_bigbluebuttonbn'; // Your plugin's name
        $message->name = 'crashed'; // Your notification name from message.php
        $message->userfrom = core_user::get_noreply_user(); // If the message is 'from' a specific user you can set them here
        $message->userto = $user;
        $message->subject = get_string('crashed', 'bigbluebuttonbn');;
        $message->fullmessage = get_string('crashmsgcontent', 'bigbluebuttonbn');
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessagehtml = get_string('crashmsgcontent', 'bigbluebuttonbn');;
        $message->smallmessage = get_string('crashmsgcontent', 'bigbluebuttonbn');;
        $message->notification = 1; // Because this is a notification generated from Moodle, not a user-to-user message
        $message->contexturl = (new \moodle_url('/course/'))->out(false); // A relevant URL for the notification
        $message->contexturlname = 'Course list'; // Link title explaining where users get to for the contexturl
        $content = ['*' => ['header' => '  ', 'footer' => '  ']]; // Extra content for specific processor
        $message->set_additional_content('email', $content);

// Actually send the message
        $messageid = message_send($message);
    }

}
