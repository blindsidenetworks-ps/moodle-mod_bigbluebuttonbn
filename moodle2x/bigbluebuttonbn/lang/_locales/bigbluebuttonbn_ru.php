<?php
/**
 * Language File
 *
 * Authors:
 *    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 *    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 *    
 * Translation files available at 
 *     http://www.getlocalization.com/bigbluebutton_moodle2x
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2012 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
defined('MOODLE_INTERNAL') || die();

$bigbluebuttonbn_locales = Array(
        'bbbduetimeoverstartingtime' => 'Время окончания встречи должно быть больше времени ее начала',
        'bbbdurationwarning' => 'Длительность встречи не более %duration%  минут(ы)',
        'bbbfinished' => 'Эта встреча завершена.',
        'bbbinprocess' => 'Эта встреча активна',
        'bbbnorecordings' => 'Записи еще не сформированы',
        'bbbnotavailableyet' => 'Извините, сессия еще недоступна',
        'bbbrecordwarning' => 'Эта сессия записывается',
        'bbburl' => 'Адрес сервера BBB должен заканчиваться строкой /bigbluebutton/.  (Адрес по умолчанию предоставлен Blindside Networks, который может использоваться для проверки.)',
        'bigbluebuttonbn:join' => 'Присоединиться',
        'bigbluebuttonbn:moderate' => 'Модерировать встречу',
        'bigbluebuttonbn' => 'BigBlueButton',
        'bigbluebuttonbnfieldset' => 'Не используется!',
        'bigbluebuttonbnintro' => 'Не используется!',
        'bigbluebuttonbnSalt' => 'Security Salt',
        'bigbluebuttonbnUrl' => 'Адрес сервера BigBlueButton',
        'bigbluebuttonbnWait' => 'Пользователь должен подождать',
        'configsecuritysalt' => 'Параметр security salt сервера BigBlueButton. (Значение по умолчанию сервера BigBlueButton, предоставленного Blindside Networks, который можно использовать для проверки.)',
        'general_error_unable_connect' => 'Невозможно подключиться. Пожалуйста проверьте правильность адреса сервера BigBlueButton и удостоверьтесь, что сервер работает.',
        'index_confirm_end' => 'Вы действительно хотите закрыть виртуальный класс?',
        'index_disabled' => 'отключено',
        'index_enabled' => 'включено',
        'index_ending' => 'Закрытие виртуального класса... Пожалуйста, ждите...',
        'index_error_checksum' => 'Ошибка контрольных сумм. Удостоверьтесь в правильности ввода security salt.',
        'index_error_forciblyended' => 'Невозможно присоединиться к встрече, т.к. она была завершена модератором.',
        'index_error_unable_display' => 'Невозможно отобразить. Пожалуйста проверьте правильность адреса сервера BigBlueButton и удостоверьтесь, что сервер работает.',
        'index_heading_actions' => 'Действия',
        'index_heading_group' => 'Группа',
        'index_heading_moderator' => 'Модераторы',
        'index_heading_name' => 'Комната',
        'index_heading_recording' => 'Записи',
        'index_heading_users' => 'Пользователи',
        'index_heading_viewer' => 'Просматривают',
        'index_heading' => 'Комнаты BigBlueButton',
        'index_running' => 'работает',
        'index_warning_adding_meeting' => 'Ошибка присвоения параметра meetingid.',
        'mod_form_block_general' => 'Основные настройки',
        'mod_form_block_record' => 'Настройки записи',
        'mod_form_block_schedule' => 'Расписание сессий',
        'mod_form_field_availabledate' => 'Вход разрешен',
        'mod_form_field_description' => 'Описание записанных сессий',
        'mod_form_field_duedate' => 'Вход закрыт',
        'mod_form_field_duration_help' => 'Установка длительности встречи обозначает максимальное время до момента, когда запись будет завершена',
        'mod_form_field_duration' => 'Длительность',
        'mod_form_field_limitusers' => 'Лимит пользователей',
        'mod_form_field_limitusers_help' => 'Максимальное количество пользователей, допустимое на встрече',
        'mod_form_field_name' => 'Название виртуального класса',
        'mod_form_field_newwindow' => 'Открывать BigBlueButton в новом окне',
        'mod_form_field_record' => 'Запись',
        'mod_form_field_voicebridge_help' => 'Номер голосовой конференции, который могут использовать участники для соединения с аудио-модулем.',
        'mod_form_field_voicebridge' => 'Аудио-мост',
        'mod_form_field_wait' => 'Учащийся должен дождаться входа модератора',
        'mod_form_field_welcome_default' => '<br>Добро пожаловать на встречу <b>%%CONFNAME%%</b>!<br><br>Для справки по BigBlueButton, просмотрите <a href="event:http://www.bigbluebutton.org/content/videos"><u>обучающие видео</u></a>.<br><br>Для присоединения к конференции, кликните по иконке с гарнитурой (в левом верхнем углу). <b>Пожалуйста, используйте аудио-гарнитуру, для того, чтобы избежать эффекта эхо</b>',
        'mod_form_field_welcome_help' => 'Заменяет стандартное сообщение сервера. Сообщение может содержать ключевые слова,(%%CONFNAME%%, %%DIALNUM%%, %%CONFNUM%%) которые преобразуются автоматически, а также, html-теги, такие, как <b>...</b> or <i></i> ',
        'mod_form_field_welcome' => 'Приветствие',
        'modulename' => 'BigBlueButtonBN',
        'modulenameplural' => 'BigBlueButtonBN',
        'pluginadministration' => 'BigBlueButton администрирование',
        'pluginname' => 'BigBlueButtonBN',
        'serverhost' => 'Имя сервера',
        'view_error_unable_join_student' => 'Ошибка подключения к серверу BigBlueButton. Пожалуйста, обратитесь к преподавателю или администратору.',
        'view_error_unable_join_teacher' => 'Ошибка подключения к серверу BigBlueButton. Пожалуйста, обратитесь к администратору.',
        'view_error_unable_join' => 'Невозможно присоединиться к встрече. Пожалуйста проверьте правильность адреса сервера BigBlueButton и удостоверьтесь, что сервер работает.',
        'view_groups_selection_join' => 'Присоединиться',
        'view_groups_selection' => 'Выберите группу, к которой хотите присоединиться и подтвердите действие',
        'view_login_moderator' => 'Вход модератора...',
        'view_login_viewer' => 'Вход слушателя...',
        'view_noguests' => 'BigBlueButtonBN недоступен для Гостей',
        'view_nojoin' => 'Вам не разрешено подключаться к этой сессии',
        'view_recording_list_actionbar_delete' => 'Удалить',
        'view_recording_list_actionbar_hide' => 'Скрыть',
        'view_recording_list_actionbar_show' => 'Показать',
        'view_recording_list_actionbar' => 'Панель инструментов',
        'view_recording_list_activity' => 'Активность',
        'view_recording_list_course' => 'Курс',
        'view_recording_list_date' => 'Дата',
        'view_recording_list_description' => 'Описание',
        'view_recording_list_recording' => 'Запись',
        'view_wait' => 'Виртуальный класс еще не открыт. Пожалуйста, ожидайте входа модератора...',
        );

foreach($bigbluebuttonbn_locales as $key => $value){
    $string[$key] = $value;
}

?>