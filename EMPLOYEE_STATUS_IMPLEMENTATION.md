# Employee Status Management Implementation

## Overview

This implementation adds automatic employee status management based on leave status. When an employee is on approved leave, their status is automatically set to "On Leave", and when they return, it's set back to "Active".

## Features Implemented

### 1. Database Changes
- Added `status` column to `employee_profiles` table with values: 'Active', 'Inactive', 'On Leave'
- Default status is 'Active' for existing employees

### 2. Core Functions (`employee_status_functions.php`)
- `isEmployeeOnLeave($employee_id, $date)` - Checks if employee is currently on approved leave
- `updateEmployeeStatusBasedOnLeave($employee_id, $date)` - Updates individual employee status
- `updateAllEmployeesStatusBasedOnLeave($date)` - Updates all employees' statuses
- `getEmployeeStatusInfo($employee_id)` - Gets employee status with leave information
- `handleLeaveStatusChange($employee_id, $leave_status)` - Handles status changes when leave is approved/rejected

### 3. Integration Points
- **Leave Approval/Rejection**: Status automatically updates when leave requests are approved or rejected
- **Daily Updates**: Scheduled task runs daily to check and update all employee statuses
- **Employee Profile Display**: Shows current status with visual indicators

### 4. Visual Indicators
- ✅ **Active**: Green badge - Employee is working normally
- 🏖️ **On Leave**: Yellow badge - Employee is on approved leave
- ⏸️ **Inactive**: Red badge - Employee is inactive (not on leave but not active)

## Files Created/Modified

### New Files
1. `add_employee_status_column.sql` - Database migration script
2. `employee_status_functions.php` - Core status management functions
3. `daily_employee_status_update.php` - Daily status update script
4. `run_employee_status_migration.php` - Migration runner script
5. `setup_cron_job.md` - Instructions for setting up automated updates

### Modified Files
1. `leave_requests.php` - Added status update logic to approval/rejection process
2. `employee_profile.php` - Added status column to employee listing with visual indicators

## Setup Instructions

### 1. Run Database Migration
```bash
php run_employee_status_migration.php
```

### 2. Set Up Daily Automation
Choose one of the following:

#### Option A: Cron Job (Linux/Mac)
```bash
# Add to crontab
0 6 * * * /usr/bin/php /path/to/your/project/daily_employee_status_update.php
```

#### Option B: Windows Task Scheduler
1. Open Task Scheduler
2. Create Basic Task: "Daily Employee Status Update"
3. Trigger: Daily at 6:00 AM
4. Action: Start a program
   - Program: `php.exe`
   - Arguments: `C:\Program Files\xampp\htdocs\HRMS_BSIS_02\daily_employee_status_update.php`

### 3. Test the Implementation
1. Go to Employee Profile page - you should see the new "Current Status" column
2. Submit a leave request and approve it - employee status should change to "On Leave"
3. Reject a leave request - employee status should remain "Active"
4. Run the daily update script manually to test: `php daily_employee_status_update.php`

## How It Works

### Automatic Status Updates
1. **When Leave is Approved**: Employee status immediately changes to "On Leave"
2. **When Leave is Rejected**: Employee status remains "Active"
3. **Daily Check**: Script runs daily to ensure all statuses are current
4. **Leave End**: When leave period ends, status automatically returns to "Active"

### Status Logic
- **Active**: Employee is not on any approved leave
- **On Leave**: Employee has an approved leave request that covers the current date
- **Inactive**: Employee is not active (can be set manually for other reasons)

### Visual Display
The employee listing now shows:
- **Employment Status**: Full-time, Part-time, Contract, etc. (existing field)
- **Current Status**: Active, On Leave, Inactive (new field with visual indicators)

## Benefits

1. **Real-time Status Tracking**: HR can immediately see who is on leave
2. **Automated Management**: No manual intervention needed for status updates
3. **Visual Clarity**: Clear indicators show employee availability
4. **Audit Trail**: All status changes are logged for compliance
5. **Integration**: Works seamlessly with existing leave management system

## Troubleshooting

### Common Issues
1. **Status not updating**: Check if the daily script is running
2. **Database errors**: Ensure the migration script ran successfully
3. **Visual issues**: Check that CSS styles are loading properly

### Manual Status Update
If needed, you can manually update statuses:
```php
require_once 'employee_status_functions.php';
updateAllEmployeesStatusBasedOnLeave();
```

### Logs
Check the error log for status update activities:
- Daily updates are logged with timestamps
- Individual status changes are logged with employee IDs
- Errors are logged with detailed messages

## Future Enhancements

1. **Email Notifications**: Send notifications when status changes
2. **Manager Dashboard**: Show team status overview
3. **Status History**: Track status changes over time
4. **Custom Statuses**: Add more status types (Sick, Vacation, etc.)
5. **Mobile App Integration**: Push notifications for status changes
