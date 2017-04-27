#!/bin/bash

echo `date` '- updating courses'

/usr/bin/php smart_course_sync.php --verbose

echo `date` '- courses updated; creating overrides'

/usr/bin/php create_moodle_shell_overrides.php --verbose

echo `date` '- override creation finished'
