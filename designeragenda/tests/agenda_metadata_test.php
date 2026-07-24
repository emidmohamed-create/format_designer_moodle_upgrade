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
 * Tests for Designer agenda metadata.
 *
 * @package   format_designeragenda
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designeragenda\local\agenda;

defined('MOODLE_INTERNAL') || die();

/**
 * Agenda metadata tests.
 */
final class metadata_test extends \advanced_testcase {

    /**
     * Test unknown fields are removed and values are cleaned.
     *
     * @return void
     */
    public function test_normalize(): void {
        $data = metadata::normalize([
            'modality' => 'onsite',
            'location' => "  Room <script>alert('x')</script>  ",
            'duration' => '30',
            'unknown' => 'must not be stored',
        ]);

        $this->assertSame('onsite', $data['modality']);
        $this->assertStringNotContainsString('<script>', $data['location']);
        $this->assertSame('30', $data['duration']);
        $this->assertArrayNotHasKey('unknown', $data);
    }

    /**
     * Test validation of enumerations, time, duration and URL.
     *
     * @return void
     */
    public function test_validate(): void {
        $errors = metadata::validate([
            'modality' => 'invalid',
            'pedagogicaltype' => 'invalid',
            'timestart' => '9:00',
            'timeend' => '25:00',
            'duration' => '1441',
            'meetingurl' => 'javascript:alert(1)',
        ]);

        $this->assertArrayHasKey('modality', $errors);
        $this->assertArrayHasKey('pedagogicaltype', $errors);
        $this->assertArrayHasKey('timestart', $errors);
        $this->assertArrayHasKey('timeend', $errors);
        $this->assertArrayHasKey('duration', $errors);
        $this->assertArrayHasKey('meetingurl', $errors);

        $errors = metadata::validate([
            'timestart' => '10:00',
            'timeend' => '09:00',
        ]);
        $this->assertArrayHasKey('timeend', $errors);
    }
    /**
     * Test persistence in the dedicated Designer Agenda options table.
     *
     * @return void
     */
    public function test_save_and_get_for_cmid_preserves_other_options(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(['format' => 'designeragenda']);
        $module = $this->getDataGenerator()->create_module('label', ['course' => $course->id]);

        \format_designeragenda\options::insert_option($module->cmid, $course->id, 'existingoption', 'keep me');
        metadata::save($module->cmid, $course->id, [
            'modality' => 'hybrid',
            'timestart' => '09:00',
            'duration' => '60',
            'location' => 'Room 1',
        ]);

        $result = metadata::get_for_cmid($module->cmid);
        $this->assertSame('hybrid', $result['modality']);
        $this->assertSame('09:00', $result['timestart']);
        $this->assertSame('60', $result['duration']);
        $this->assertSame('Room 1', $result['location']);
        $this->assertSame('keep me', $DB->get_field('format_designeragenda_options', 'value', [
            'cmid' => $module->cmid,
            'name' => 'existingoption',
        ]));
    }

    /**
     * Test clearing optional fields does not delete unrelated options.
     *
     * @return void
     */
    public function test_save_empty_data(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(['format' => 'designeragenda']);
        $module = $this->getDataGenerator()->create_module('label', ['course' => $course->id]);

        metadata::save($module->cmid, $course->id, ['location' => 'Room 1']);
        metadata::save($module->cmid, $course->id, ['location' => '']);

        $this->assertSame([], metadata::get_for_cmid($module->cmid));
        $this->assertTrue($DB->record_exists('format_designeragenda_options', [
            'cmid' => $module->cmid,
            'name' => metadata::OPTION_NAME,
        ]));
    }
}
