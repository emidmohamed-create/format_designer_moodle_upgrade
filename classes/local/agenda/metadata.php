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
 * Storage and validation for Designer agenda metadata.
 *
 * @package   format_designer
 * @copyright 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_designer\local\agenda;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../lib.php');

/**
 * Agenda metadata value object and persistence service.
 */
class metadata {

    /** @var string Existing Designer options key. */
    public const OPTION_NAME = 'agenda';

    /** @var string[] Allowed metadata keys. */
    private const FIELDS = [
        'modality',
        'pedagogicaltype',
        'timestart',
        'timeend',
        'duration',
        'location',
        'meetingurl',
        'deliverable',
        'practicalinfo',
    ];

    /** @var string[] Allowed modalities. */
    private const MODALITIES = ['onsite', 'online_sync', 'online_async', 'hybrid'];

    /** @var string[] Allowed pedagogical types. */
    private const PEDAGOGICAL_TYPES = [
        'course',
        'workshop',
        'lab',
        'project',
        'assessment',
        'resource',
        'support',
    ];

    /** @var array<int, array> Page-level metadata cache. */
    private static $cache = [];

    /**
     * Get the supported metadata keys.
     *
     * @return string[]
     */
    public static function get_fields(): array {
        return self::FIELDS;
    }

    /**
     * Get the allowed modality values.
     *
     * @return string[]
     */
    public static function get_modalities(): array {
        return self::MODALITIES;
    }

    /**
     * Get the allowed pedagogical type values.
     *
     * @return string[]
     */
    public static function get_pedagogical_types(): array {
        return self::PEDAGOGICAL_TYPES;
    }

    /**
     * Return the normalized metadata for a course module.
     *
     * @param int $cmid Course module id.
     * @return array
     */
    public static function get_for_cmid(int $cmid): array {
        if (array_key_exists($cmid, self::$cache)) {
            return self::$cache[$cmid];
        }

        $value = \format_designer\options::get_option($cmid, self::OPTION_NAME);
        if ($value === null || $value === '') {
            self::$cache[$cmid] = [];
            return [];
        }

        $data = json_decode($value, true);
        if (!is_array($data)) {
            self::$cache[$cmid] = [];
            return [];
        }

        self::$cache[$cmid] = self::normalize($data);
        return self::$cache[$cmid];
    }

    /**
     * Preload activity metadata for a course page.
     *
     * @param int $courseid Course id.
     * @param int[] $cmids Course module ids.
     * @return void
     */
    public static function preload_for_course(int $courseid, array $cmids): void {
        global $DB;

        $cmids = array_values(array_unique(array_map('intval', $cmids)));
        if (empty($cmids)) {
            return;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED, 'agenda_cmid');
        $params = ['courseid' => $courseid, 'name' => self::OPTION_NAME] + $inparams;
        $records = $DB->get_records_sql(
            "SELECT cmid, value
               FROM {format_designer_options}
              WHERE courseid = :courseid AND name = :name AND cmid $insql",
            $params
        );

        foreach ($cmids as $cmid) {
            self::$cache[$cmid] = [];
        }
        foreach ($records as $record) {
            $data = json_decode($record->value, true);
            self::$cache[(int)$record->cmid] = is_array($data) ? self::normalize($data) : [];
        }
    }

    /**
     * Save metadata without touching other Designer options.
     *
     * @param int $cmid Course module id.
     * @param int $courseid Course id.
     * @param array $data Metadata to save.
     * @return void
     */
    public static function save(int $cmid, int $courseid, array $data): void {
        $cleaned = self::normalize($data);
        $errors = self::validate($data);
        if (!empty($errors)) {
            throw new \invalid_parameter_exception(reset($errors));
        }

        $value = json_encode($cleaned, JSON_UNESCAPED_UNICODE);
        if ($value === false) {
            throw new \coding_exception('Unable to encode Designer agenda metadata.');
        }

        \format_designer\options::insert_option($cmid, $courseid, self::OPTION_NAME, $value);
        self::$cache[$cmid] = $cleaned;
    }

    /**
     * Clean values and discard unknown fields.
     *
     * Empty values are deliberately retained in the returned shape only when
     * callers explicitly provide them; persistence removes them from JSON.
     *
     * @param array $data Raw values.
     * @return array
     */
    public static function normalize(array $data): array {
        $cleaned = [];
        foreach (self::FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $value = self::clean_value($field, $data[$field]);
            if ($value !== '') {
                $cleaned[$field] = $value;
            }
        }

        return $cleaned;
    }

    /**
     * Validate values after cleaning.
     *
     * @param array $data Raw values.
     * @return array Field errors, keyed by field name.
     */
    public static function validate(array $data): array {
        $errors = [];
        $cleaned = self::normalize($data);

        if (isset($cleaned['modality']) && !in_array($cleaned['modality'], self::MODALITIES, true)) {
            $errors['modality'] = get_string('invalidagendamodality', 'format_designer');
        }
        if (isset($cleaned['pedagogicaltype']) &&
                !in_array($cleaned['pedagogicaltype'], self::PEDAGOGICAL_TYPES, true)) {
            $errors['pedagogicaltype'] = get_string('invalidagendapedagogicaltype', 'format_designer');
        }

        foreach (['timestart', 'timeend'] as $field) {
            if (isset($cleaned[$field]) && !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $cleaned[$field])) {
                $errors[$field] = get_string('invalidagendatime', 'format_designer');
            }
        }

        if (!isset($errors['timestart'], $errors['timeend']) &&
                isset($cleaned['timestart'], $cleaned['timeend']) &&
                $cleaned['timeend'] < $cleaned['timestart']) {
            $errors['timeend'] = get_string('agendaendbeforestart', 'format_designer');
        }

        if (isset($cleaned['duration']) &&
                (!ctype_digit((string)$cleaned['duration']) || (int)$cleaned['duration'] > 1440)) {
            $errors['duration'] = get_string('invalidagendaduration', 'format_designer');
        }

        if (isset($cleaned['meetingurl'])) {
            $url = clean_param($cleaned['meetingurl'], PARAM_URL);
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if ($url === '' || !in_array(strtolower((string)$scheme), ['http', 'https'], true)) {
                $errors['meetingurl'] = get_string('invalidagendameetingurl', 'format_designer');
            }
        }

        return $errors;
    }

    /**
     * Clean one field according to its storage type.
     *
     * @param string $field Field name.
     * @param mixed $value Raw value.
     * @return string
     */
    private static function clean_value(string $field, $value): string {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        if ($field === 'meetingurl') {
            return trim(clean_param((string)$value, PARAM_URL));
        }
        if ($field === 'duration') {
            return trim(clean_param((string)$value, PARAM_TEXT));
        }

        return trim(clean_param((string)$value, PARAM_TEXT));
    }
}
