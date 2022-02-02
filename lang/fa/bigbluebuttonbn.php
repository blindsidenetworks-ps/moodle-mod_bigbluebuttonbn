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
 * Language File.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @author    Fred Dixon  (ffdixon [at] blindsidenetworks [dt] com)
 */
defined('MOODLE_INTERNAL') || die();

$string['error_format_json'] = "فرمت اطلاعات اشتباه است! لطفا با فرمت JSON اطلاعات را وارد کنید.";
$string['error_default_server_count_more_than_one'] = "بیش از یک سرور پیشفرض تعریف شده است.";
$string['error_no_default_server'] = "یک سرور باید به عنوان پیشفرض در نظر گرفته شود. به این صورت که یک ویژگی با کلید \"default\"و مقدار true باید برای سرور مورد نظر تعریف شود.";
$string['error_repetitive_servername'] = "نام سرور '%s'، بیش از یکبار (%d بار) تعریف شده.";
$string['error_field_required'] = "هر سرور باید ویژگی '%s' را داشته باشد!";
$string['error_forbidden_servername'] = "نام '%s' برای نام گزاری روی یک سرور مجاز نیست!";
$string['error_server_unavailable'] = "سرور '%s' در دسترس نیست!";

$string['server_unavailable'] = "سرور در دسترس نیست!";
$string['server_available'] = "سرور در دسترس است!<br>نسخه ی سرور: %s";
$string['selected_server'] = "سرور انتخاب شده:";


$string['config_servers'] = "سرور ها";
$string['config_servers_description'] = "لیست سرور ها را در فرمت JSON وارد کنید.
هر سرور باید ویژگی های زیر را داشته باشد.
<li><b>url:</b> آدرس سرور بیگ بلو باتن در فرمت URL</li>
<li><b>secret:</b> کد مخفی سرور بیگ بلو باتن</li>
<li><b>cap_sessions:</b> شمار نسبی نشست هایی که سرور میتواند میزبان باشد</li>
<li><b>cap_users:</b> شمار نسبی کاربرانی که سرور میتواند میزبان باشد </li>";

$string['config_server_selection_method'] = 'روش انتخاب سرور';
$string['config_server_selection_method_description'] =
    'یک روش برای انتخاب سرور برای نشست های جدید انتخاب کنید.';
$string['server_selection_method__users'] = 'شمار تعداد کاربران روی هر سرور';
$string['server_selection_method__sessions'] = 'شمار تعداد نشست ها روی هر سرور';