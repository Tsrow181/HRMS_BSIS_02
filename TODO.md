# TODO: Fix Shifts.php - Remove Duplicates and Correct Duration Computation

## Tasks
- [x] Update calculateDuration function in shifts.php to handle overnight shifts (add 24 hours if end_time < start_time)
- [x] Modify getShifts function in dp.php to use SELECT DISTINCT to remove duplicate rows
