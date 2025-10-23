# Setting up Daily Employee Status Update

## Cron Job Setup

To automatically update employee statuses daily, add this line to your crontab:

```bash
# Run daily at 6:00 AM to update employee statuses
0 6 * * * /usr/bin/php /path/to/your/project/daily_employee_status_update.php
```

## Windows Task Scheduler Setup

1. Open Task Scheduler
2. Create Basic Task
3. Name: "Daily Employee Status Update"
4. Trigger: Daily at 6:00 AM
5. Action: Start a program
   - Program: `php.exe`
   - Arguments: `C:\Program Files\xampp\htdocs\HRMS_BSIS_02\daily_employee_status_update.php`
   - Start in: `C:\Program Files\xampp\htdocs\HRMS_BSIS_02`

## Manual Execution

You can also run the script manually:

```bash
php daily_employee_status_update.php
```

## Logs

The script logs all activities to the error log. Check your PHP error log for details about the update process.
