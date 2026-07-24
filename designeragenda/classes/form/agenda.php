<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Form for editing Designer agenda metadata.
 *
 * @package   format_designeragenda
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designeragenda\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use format_designeragenda\local\agenda\metadata;

/**
 * Moodle form for activity agenda metadata.
 */
class agenda extends \moodleform {

    /**
     * Define the form.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $cm = $this->_customdata['cm'];
        $current = metadata::get_for_cmid((int)$cm->id);

        $mform->addElement('header', 'agendaheader', get_string('agendainformation', 'format_designeragenda'));

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->setDefault('cmid', $cm->id);

        $modalities = ['' => get_string('choosedots')];
        foreach (metadata::get_modalities() as $value) {
            $modalities[$value] = get_string('agendamodality_' . $value, 'format_designeragenda');
        }
        $mform->addElement('select', 'modality', get_string('agendamodality', 'format_designeragenda'), $modalities);
        $mform->setType('modality', PARAM_ALPHANUMEXT);
        $mform->setDefault('modality', $current['modality'] ?? '');

        $pedagogicaltypes = ['' => get_string('choosedots')];
        foreach (metadata::get_pedagogical_types() as $value) {
            $pedagogicaltypes[$value] = get_string('agendapedagogicaltype_' . $value, 'format_designeragenda');
        }
        $mform->addElement('select', 'pedagogicaltype', get_string('agendapedagogicaltype', 'format_designeragenda'),
            $pedagogicaltypes);
        $mform->setType('pedagogicaltype', PARAM_ALPHANUMEXT);
        $mform->setDefault('pedagogicaltype', $current['pedagogicaltype'] ?? '');

        $mform->addElement('text', 'timestart', get_string('agendatimestart', 'format_designeragenda'),
            ['placeholder' => get_string('agendatimestartplaceholder', 'format_designeragenda'), 'maxlength' => 5]);
        $mform->setType('timestart', PARAM_TEXT);
        $mform->setDefault('timestart', $current['timestart'] ?? '');

        $mform->addElement('text', 'timeend', get_string('agendatimeend', 'format_designeragenda'),
            ['placeholder' => get_string('agendatimeendplaceholder', 'format_designeragenda'), 'maxlength' => 5]);
        $mform->setType('timeend', PARAM_TEXT);
        $mform->setDefault('timeend', $current['timeend'] ?? '');

        $mform->addElement('text', 'duration', get_string('agendaduration', 'format_designeragenda'),
            ['placeholder' => get_string('agendadurationplaceholder', 'format_designeragenda'), 'maxlength' => 4]);
        $mform->setType('duration', PARAM_TEXT);
        $mform->setDefault('duration', $current['duration'] ?? '');

        $mform->addElement('text', 'location', get_string('agendalocation', 'format_designeragenda'),
            ['maxlength' => 255]);
        $mform->setType('location', PARAM_TEXT);
        $mform->setDefault('location', $current['location'] ?? '');

        $mform->addElement('url', 'meetingurl', get_string('agendameetingurl', 'format_designeragenda'),
            ['maxlength' => 2048]);
        $mform->setType('meetingurl', PARAM_URL);
        $mform->setDefault('meetingurl', $current['meetingurl'] ?? '');

        $mform->addElement('textarea', 'deliverable', get_string('agendadeliverable', 'format_designeragenda'),
            ['rows' => 3, 'maxlength' => 2048]);
        $mform->setType('deliverable', PARAM_TEXT);
        $mform->setDefault('deliverable', $current['deliverable'] ?? '');

        $mform->addElement('textarea', 'practicalinfo', get_string('agendapracticalinfo', 'format_designeragenda'),
            ['rows' => 3, 'maxlength' => 2048]);
        $mform->setType('practicalinfo', PARAM_TEXT);
        $mform->setDefault('practicalinfo', $current['practicalinfo'] ?? '');

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Validate submitted values.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $cm = $this->_customdata['cm'];
        if ((int)($data['cmid'] ?? 0) !== (int)$cm->id) {
            $errors['cmid'] = get_string('invalidcoursemodule', 'error');
        }

        foreach (metadata::validate($data) as $field => $error) {
            $errors[$field] = $error;
        }

        return $errors;
    }
}
