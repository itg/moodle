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

function xmldb_block_quickmail_upgrade($oldversion) {
    global $DB;

    $result = true;

    $dbman = $DB->get_manager();

    if ($oldversion < 2011021812) {
        // Compatibility for transition to LSU quickmail.

        // Modify block_quickmail_log.
        $table = new xmldb_table('block_quickmail_log');
        $field = new xmldb_field('attachment', XMLDB_TYPE_TEXT, 'small', XMLDB_NOTNULL, null, null);
        $dbman->change_field_type($table, $field);

        $field = new xmldb_field('timesent', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'time');
        }

        // Add block_quickmail_signatures.
        $table = new xmldb_table('block_quickmail_signatures');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, 11, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('title', XMLDB_TYPE_CHAR, 125, null, null, null, null);
        $table->add_field('signature', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('default_flag', XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add block_quickmail_drafts.
        $table = new xmldb_table('block_quickmail_drafts');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, 11, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, 11, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('mailto', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('subject', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('attachment', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null);
        $table->add_field('format', XMLDB_TYPE_INTEGER, 3, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');
        $table->add_field('time', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add block_quickmail_config.
        $table = new xmldb_table('block_quickmail_config');
        $table->add_field('id', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('coursesid', XMLDB_TYPE_INTEGER, 11, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, 25, null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_CHAR, 125, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2011021812, 'quickmail');
    }

    if ($oldversion < 2012021014) {
        $table = new xmldb_table('block_quickmail_alternate');

        $field = new xmldb_field('id');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
            XMLDB_NOTNULL, true, null, null);

        $table->addField($field);

        $field = new xmldb_field('courseid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
            XMLDB_NOTNULL, false, null, 'id');

        $table->addField($field);

        $field = new xmldb_field('address');
        $field->set_attributes(XMLDB_TYPE_CHAR, '100', null,
            XMLDB_NOTNULL, false, null, 'courseid');

        $table->addField($field);

        $field = new xmldb_field('valid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED,
            XMLDB_NOTNULL, false, '0', 'address');

        $table->addField($field);

        $key = new xmldb_key('PRIMARY');
        $key->set_attributes(XMLDB_KEY_PRIMARY, array('id'));

        $table->addKey($key);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        foreach (array('log', 'drafts') as $table) {
            // Define field alternateid to be added to block_quickmail_log.
            $table = new xmldb_table('block_quickmail_' . $table);
            $field = new xmldb_field('alternateid', XMLDB_TYPE_INTEGER, '10',
                XMLDB_UNSIGNED, null, null, null, 'userid');

            // Conditionally launch add field alternateid.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Quickmail savepoint reached.
        upgrade_block_savepoint($result, 2012021014, 'quickmail');
    }

    if ($oldversion < 2012061112) {
        // Restructure database references to the new filearea locations.
        foreach (array('log', 'drafts') as $type) {
            $params = array(
                'component' => 'block_quickmail_' . $type,
                'filearea' => 'attachment'
            );

            $attachments = $DB->get_records('files', $params);

            foreach ($attachments as $attachment) {
                $attachment->filearea = 'attachment_' . $type;
                $attachment->component = 'block_quickmail';

                $result = $result && $DB->update_record('files', $attachment);
            }
        }

        upgrade_block_savepoint($result, 2012061112, 'quickmail');
    }

    if ($oldversion < 2014010700) {
        // If new and old present; disable adding for the old one.
        $context = context_system::instance();
        list($roleids) = get_roles_with_cap_in_context($context, 'block/quickmail:addinstance');
        foreach ($roleids as $roleid) {
            assign_capability('block/quickmail:addinstance', CAP_PREVENT, $roleid, $context, true);
        }
        upgrade_block_savepoint(true, 2014010700, 'quickmail');
    }

    return $result;
}
