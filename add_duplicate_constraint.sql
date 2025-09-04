-- Remove the UNIQUE constraint on email in candidates table
ALTER TABLE candidates DROP INDEX email;

-- Add composite unique constraint to prevent duplicate applications for same job
-- This allows same email to apply for different jobs but prevents duplicate applications to same job
ALTER TABLE job_applications ADD CONSTRAINT unique_application_per_job 
UNIQUE (job_opening_id, candidate_id);

-- Create a view to check for duplicate applications by email and job
CREATE VIEW duplicate_application_check AS
SELECT 
    c.email,
    ja.job_opening_id,
    COUNT(*) as application_count
FROM candidates c
JOIN job_applications ja ON c.candidate_id = ja.candidate_id
GROUP BY c.email, ja.job_opening_id
HAVING COUNT(*) > 1;