#!/bin/bash

echo `date` '- updating users'

# Update users
/usr/bin/php smart_user_sync.php

echo `date` '- users updated; updating enrollments'

# Update enrollments
/usr/bin/php ../enrol/database/cli/sync.php -v

echo `date` '- enrollments updated'
