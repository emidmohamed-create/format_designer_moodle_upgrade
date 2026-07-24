<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Activity agenda metadata output data.
 *
 * @package   format_designeragenda
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designeragenda\output;

defined('MOODLE_INTERNAL') || die();

use cm_info;
use format_designeragenda\local\agenda\metadata;
use renderer_base;

/**
 * Prepare agenda metadata for Mustache templates.
 */
class agenda_metadata {

    /** @var cm_info Course module. */
    private $cm;

    /**
     * Constructor.
     *
     * @param cm_info $cm Course module.
     */
    public function __construct(cm_info $cm) {
        $this->cm = $cm;
    }

    /**
     * Export data for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return array
     */
    public function export_for_template(renderer_base $output): array {

        $values = metadata::get_for_cmid((int)$this->cm->id);
        if (empty($values)) {
            return [];
        }
        $data = [
            'hasagenda' => !empty($values),
            'hasschedule' => false,
            'hasduration' => false,
            'hasmodality' => false,
            'haspedagogicaltype' => false,
            'haslocation' => false,
            'hasmeetingurl' => false,
            'hasdeliverable' => false,
            'haspracticalinfo' => false,
        ];

        if (isset($values['timestart']) || isset($values['timeend'])) {
            $times = array_filter([$values['timestart'] ?? '', $values['timeend'] ?? '']);
            $data['hasschedule'] = !empty($times);
            $data['schedule'] = implode(' – ', $times);
            $data['schedulelabel'] = get_string('agendaschedule', 'format_designeragenda');
        }
        if (isset($values['duration'])) {
            $data['hasduration'] = true;
            $data['duration'] = get_string('agendadurationminutes', 'format_designeragenda', (int)$values['duration']);
        }
        if (isset($values['modality'])) {
            $data['hasmodality'] = true;
            $data['modality'] = get_string('agendamodality_' . $values['modality'], 'format_designeragenda');
            $data['modalityicon'] = [
                'onsite' => 'fa-building',
                'online_sync' => 'fa-video-camera',
                'online_async' => 'fa-laptop',
                'hybrid' => 'fa-random',
            ][$values['modality']] ?? 'fa-info-circle';
            $data['modalitylabel'] = get_string('agendamodality', 'format_designeragenda');
        }
        if (isset($values['pedagogicaltype'])) {
            $data['haspedagogicaltype'] = true;
            $data['pedagogicaltype'] = get_string('agendapedagogicaltype_' . $values['pedagogicaltype'],
                'format_designeragenda');
            $data['pedagogicaltypelabel'] = get_string('agendapedagogicaltype', 'format_designeragenda');
        }
        if (isset($values['location'])) {
            $data['haslocation'] = true;
            $data['location'] = $values['location'];
            $data['locationlabel'] = get_string('agendalocation', 'format_designeragenda');
        }
        if (isset($values['meetingurl'])) {
            $data['hasmeetingurl'] = true;
            $data['meetingurl'] = $values['meetingurl'];
            $data['meetingurllabel'] = get_string('agendameetingurl', 'format_designeragenda');
        }
        if (isset($values['deliverable'])) {
            $data['hasdeliverable'] = true;
            $data['deliverable'] = $values['deliverable'];
            $data['deliverablelabel'] = get_string('agendadeliverable', 'format_designeragenda');
        }
        if (isset($values['practicalinfo'])) {
            $data['haspracticalinfo'] = true;
            $data['practicalinfo'] = $values['practicalinfo'];
            $data['practicalinfolabel'] = get_string('agendapracticalinfo', 'format_designeragenda');
        }

        return $data;
    }
}
