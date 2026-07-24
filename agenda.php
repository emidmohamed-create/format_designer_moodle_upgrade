<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Edit Designer agenda metadata for one course module.
 *
 * @package   format_designer
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/classes/form/agenda.php');
require_once(__DIR__ . '/classes/local/agenda/metadata.php');

$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$context = context_course::instance($course->id);

require_login($course, false, $cm);
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/course/format/designer/agenda.php', ['cmid' => $cmid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('editagendainformation', 'format_designer'));
$PAGE->set_heading($course->fullname);

$form = new format_designer\form\agenda(null, ['cm' => $cm, 'course' => $course]);
$returnurl = new moodle_url('/course/view.php', ['id' => $course->id], 'module-' . $cmid);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    format_designer\local\agenda\metadata::save($cmid, $course->id, (array)$data);
    redirect($returnurl, get_string('agendaupdated', 'format_designer'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editagendainformation', 'format_designer'));
$form->display();
echo $OUTPUT->footer();
