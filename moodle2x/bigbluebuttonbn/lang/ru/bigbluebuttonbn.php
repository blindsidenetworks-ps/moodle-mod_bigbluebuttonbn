<?php
/**
 * Language Converter for Russian, русский язык (ru)
 *
 * Authors:
 *      Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 *      Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010-2011 Blindside Networks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['bbbduetimeoverstartingtime'] = 'Время окончания встречи должно быть больше времени ее начала';
$string['bbbdurationwarning'] = 'Длительность встречи не более %duration%  минут(ы)';
$string['bbbfinished'] = 'Эта встреча завершена.';
$string['bbbinprocess'] = 'Эта встреча активна';
$string['bbbnorecordings'] = 'Записи еще не сформированы';
$string['bbbnotavailableyet'] = 'Извините, сессия еще недоступна';
$string['bbbrecordwarning'] = 'Эта сессия записывается';
$string['bbburl'] = 'Адрес сервера BBB должен заканчиваться строкой /bigbluebutton/.  (Адрес по умолчанию предоставлен Blindside Networks, который может использоваться для проверки.)';
$string['bigbluebuttonbn:join'] = 'Присоединиться';
$string['bigbluebuttonbn:moderate'] = 'Модерировать встречу';
$string['bigbluebuttonbn'] = 'BigBlueButton';
$string['bigbluebuttonbnfieldset'] = 'Не используется!';
$string['bigbluebuttonbnintro'] = 'Не используется!';
$string['bigbluebuttonbnSalt'] = 'Security Salt';
$string['bigbluebuttonbnUrl'] = 'Адрес сервера BigBlueButton';
$string['bigbluebuttonbnWait'] = 'Пользователь должен подождать';
$string['configsecuritysalt'] = 'Параметр security salt сервера BigBlueButton. (Значение по умолчанию сервера BigBlueButton, предоставленного Blindside Networks, который можно использовать для проверки.)';
$string['general_error_unable_connect'] = 'Невозможно подключиться. Пожалуйста проверьте правильность адреса сервера BigBlueButton и удостоверьтесь, что сервер работает.';
$string['index_confirm_end'] = 'Вы действительно хотите закрыть виртуальный класс?';
$string['index_disabled'] = 'отключено';
$string['index_enabled'] = 'включено';
$string['index_ending'] = 'Закрытие виртуального класса... Пожалуйста, ждите...';
$string['index_error_checksum'] = 'Ошибка контрольных сумм. Удостоверьтесь в правильности ввода security salt.';
$string['index_error_forciblyended'] = 'Невозможно присоединиться к встрече, т.к. она была завершена модератором.';
$string['index_error_unable_display'] = 'Невозможно отобразить. Пожалуйста проверьте правильность адреса сервера BigBlueButton и удостоверьтесь, что сервер работает.';
$string['index_heading_actions'] = 'Действия';
$string['index_heading_group'] = 'Группа';
$string['index_heading_moderator'] = 'Модераторы';
$string['index_heading_name'] = 'Комната';
$string['index_heading_recording'] = 'Записи';
$string['index_heading_users'] = 'Пользователи';
$string['index_heading_viewer'] = 'Просматривают';
$string['index_heading'] = 'Комнаты BigBlueButton';
$string['index_running'] = 'работает';
$string['index_warning_adding_meeting'] = 'Ошибка присвоения параметра meetingid.';
$string['mod_form_block_general'] = 'Основные настройки';
$string['mod_form_block_record'] = 'Настройки записи';
$string['mod_form_block_schedule'] = 'Расписание сессий';
$string['mod_form_field_availabledate'] = 'Вход разрешен';
$string['mod_form_field_description'] = 'Описание записанных сессий';
$string['mod_form_field_duedate'] = 'Вход закрыт';
$string['mod_form_field_duration_help'] = 'Установка длительности встречи обозначает максимальное время до момента, когда запись будет завершена';
$string['mod_form_field_duration'] = 'Длительность';
$string['mod_form_field_limitusers'] = 'Лимит пользователей';
$string['mod_form_field_limitusers_help'] = 'Максимальное количество пользователей, допустимое на встрече';
$string['mod_form_field_name'] = 'Название виртуального класса';
$string['mod_form_field_newwindow'] = 'Открывать BigBlueButton в новом окне';
$string['mod_form_field_record'] = 'Запись';
$string['mod_form_field_voicebridge_help'] = 'Номер голосовой конференции, который могут использовать участники для соединения с аудио-модулем.';
$string['mod_form_field_voicebridge'] = 'Аудио-мост';
$string['mod_form_field_wait'] = 'Учащийся должен дождаться входа модератора';
$string['mod_form_field_welcome_default'] = '<br>Добро пожаловать на встречу <b>%%CONFNAME%%</b>!<br><br>Для справки по BigBlueButton, просмотрите <a href="event:http://www.bigbluebutton.org/content/videos"><u>обучающие видео</u></a>.<br><br>Для присоединения к конференции, кликните по иконке с гарнитурой (в левом верхнем углу). <b>Пожалуйста, используйте аудио-гарнитуру, для того, чтобы избежать эффекта эхо</b>';
$string['mod_form_field_welcome_help'] = 'Заменяет стандартное сообщение сервера. Сообщение может содержать ключевые слова,(%%CONFNAME%%, %%DIALNUM%%, %%CONFNUM%%) которые преобразуются автоматически, а также, html-теги, такие, как <b>...</b> or <i></i> ';
$string['mod_form_field_welcome'] = 'Приветствие';
$string['modulename'] = 'BigBlueButtonBN';
$string['modulenameplural'] = 'BigBlueButtonBN';
$string['pluginadministration'] = 'BigBlueButton администрирование';
$string['pluginname'] = 'BigBlueButtonBN';
$string['serverhost'] = 'Имя сервера';
$string['view_error_unable_join_student'] = 'Ошибка подключения к серверу BigBlueButton. Пожалуйста, обратитесь к преподавателю или администратору.';
$string['view_error_unable_join_teacher'] = 'Ошибка подключения к серверу BigBlueButton. Пожалуйста, обратитесь к администратору.';
$string['view_error_unable_join'] = 'Невозможно присоединиться к встрече. Пожалуйста проверьте правильность адреса сервера BigBlueButton и удостоверьтесь, что сервер работает.';
$string['view_groups_selection_join'] = 'Присоединиться';
$string['view_groups_selection'] = 'Выберите группу, к которой хотите присоединиться и подтвердите действие';
$string['view_login_moderator'] = 'Вход модератора...';
$string['view_login_viewer'] = 'Вход слушателя...';
$string['view_noguests'] = 'BigBlueButtonBN недоступен для Гостей';
$string['view_nojoin'] = 'Вам не разрешено подключаться к этой сессии';
$string['view_recording_list_actionbar_delete'] = 'Удалить';
$string['view_recording_list_actionbar_hide'] = 'Скрыть';
$string['view_recording_list_actionbar_show'] = 'Показать';
$string['view_recording_list_actionbar'] = 'Панель инструментов';
$string['view_recording_list_activity'] = 'Активность';
$string['view_recording_list_course'] = 'Курс';
$string['view_recording_list_date'] = 'Дата';
$string['view_recording_list_description'] = 'Описание';
$string['view_recording_list_recording'] = 'Запись';
$string['view_wait'] = 'Виртуальный класс еще не открыт. Пожалуйста, ожидайте входа модератора...';

?>