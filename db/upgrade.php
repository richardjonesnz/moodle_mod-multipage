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
 * This file keeps track of upgrades to the multipage module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 ** @package    mod_multipage
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 *
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute multipage upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_multipage_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    /**
     * Upgrade script.
     * Two fields were added to install.xml on 2018/04/18
     */

    if ($oldversion < 2018041800) {

        // Define text field togglename to be added to multipage_pages.
        // Follows field pagecontentsformat.
        $table = new xmldb_table('multipage_pages');
        $field = new xmldb_field('togglename', XMLDB_TYPE_CHAR, '60', null, null, null, null, 'pagecontentsformat');

        // Add field togglename
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field toggletext to be added to multipage_pages.
        $table = new xmldb_table('multipage_pages');
        $field = new xmldb_field('toggletext', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'togglename');

        // Add field toggletext
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018041800, 'multipage');
    }
   
    // Add the max_attempts field to multipage table
    if ($oldversion < 2018050300) {

        // Define field to hold max attempts
        $table = new xmldb_table('multipage');
        $field = new xmldb_field('max_attempts', 
                XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, 
                XMLDB_NOTNULL, null, '0','grade');

        // Add field
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018050300, 'multipage');
    }    

    return true;
}
