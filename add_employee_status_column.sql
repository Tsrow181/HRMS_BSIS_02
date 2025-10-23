-- Add a new status field to employee_profiles table to track active/inactive status
ALTER TABLE employee_profiles 
ADD COLUMN status ENUM('Active', 'Inactive', 'On Leave') DEFAULT 'Active' AFTER employment_status;

-- Update existing records to have 'Active' status
UPDATE employee_profiles SET status = 'Active' WHERE status IS NULL;
