<?php
// exit with an error code, hoping for the message to appear in the cron log
fwrite(STDERR, "Please remove all of Clientexec's services.php entries from your crontab and only leave one entry for cron.php, which should run every minute.");
exit(1);
