#!/bin/bash

echo `date` '- updating courses'

/usr/bin/php smart_course_sync.php --verbose

# echo `date` '- courses updated; creating overrides'

# Not needed for Moodle Production
# /usr/bin/php create_moodle_shell_overrides.php --verbose

# echo `date` '- override creation finished; resetting blocks'
echo `date` '- courses updated; resetting blocks'

/usr/bin/php reset_blocks.php

echo `date` '- blocks reset'
