# Requirements Document

## Introduction

The AI Resume Extractor and Storage system enhances the existing HRMS recruitment module by integrating with an external Python-based resume parser and storing extracted structured data in the MySQL database. When candidates apply for jobs through the apply.php page, their uploaded resumes (PDF, DOC, DOCX) are sent to the Python parser, and extracted information including education history, skills, work experience, and certifications is stored in dedicated database tables for HR review and candidate management.

## Glossary

- **Python_Resume_Parser**: The external Python application that extracts structured data from resume documents
- **Candidate_Data_Store**: The MySQL database layer that persists extracted candidate information
- **PHP_Integration_Service**: The PHP service that coordinates calling the Python parser and storing results
- **HR_Review_Interface**: The candidates.php page where HR staff view extracted candidate information
- **Application_Processor**: The apply.php component that handles resume uploads and triggers extraction
- **Extraction_Status_Tracker**: The system component that tracks parsing status for each candidate

## Requirements

### Requirement 1: Database Schema for Candidate Information

**User Story:** As an HR administrator, I want a normalized database structure to store extracted candidate information, so that I can efficiently query and manage candidate data.

#### Acceptance Criteria

1. THE Candidate_Data_Store SHALL create a candidate_education table with fields for institution, degree, field_of_study, start_date, end_date, and grade
2. THE Candidate_Data_Store SHALL create a candidate_skills table with fields for skill_name, proficiency_level, and years_of_experience
3. THE Candidate_Data_Store SHALL create a candidate_work_experience table with fields for company, job_title, start_date, end_date, responsibilities, and achievements
4. THE Candidate_Data_Store SHALL create a candidate_certifications table with fields for certification_name, issuing_organization, issue_date, expiry_date, and credential_id
5. THE Candidate_Data_Store SHALL link all extracted data tables to the candidates table via candidate_id foreign key
6. THE Candidate_Data_Store SHALL include created_at and updated_at timestamp fields in all new tables

### Requirement 2: Python Parser Execution

**User Story:** As the PHP integration service, I want to execute the Python resume parser and receive structured JSON output, so that I can store the extracted data in the database.

#### Acceptance Criteria

1. WHEN a resume file path is provided, THE PHP_Integration_Service SHALL execute the Python parser via command line
2. THE PHP_Integration_Service SHALL pass the absolute file path as a command-line argument to the Python parser
3. WHEN the Python parser completes, THE PHP_Integration_Service SHALL capture the JSON output from stdout
4. IF the Python parser execution fails, THEN THE PHP_Integration_Service SHALL log the error and return a failure status
5. THE PHP_Integration_Service SHALL set a timeout of 30 seconds for Python parser execution
6. IF the parser times out, THEN THE PHP_Integration_Service SHALL terminate the process and log a timeout error

### Requirement 3: JSON Response Parsing

**User Story:** As the PHP integration service, I want to parse the JSON response from the Python parser, so that I can extract the structured data for database storage.

#### Acceptance Criteria

1. WHEN the Python parser returns JSON output, THE PHP_Integration_Service SHALL parse the JSON string into a PHP array
2. IF the JSON is invalid or malformed, THEN THE PHP_Integration_Service SHALL log the error and return a failure status
3. THE PHP_Integration_Service SHALL validate that required fields exist in the parsed JSON (success, data, metadata)
4. THE PHP_Integration_Service SHALL extract contact_info, work_experience, education, and skills from the data object
5. THE PHP_Integration_Service SHALL handle missing or null fields gracefully without throwing errors

### Requirement 4: Data Storage and Persistence

**User Story:** As the PHP integration service, I want to store extracted candidate information in the database, so that it persists for HR review.

#### Acceptance Criteria

1. WHEN extraction completes successfully, THE PHP_Integration_Service SHALL insert education records into candidate_education table
2. WHEN extraction completes successfully, THE PHP_Integration_Service SHALL insert skill records into candidate_skills table
3. WHEN extraction completes successfully, THE PHP_Integration_Service SHALL insert work experience records into candidate_work_experience table
4. WHEN extraction completes successfully, THE PHP_Integration_Service SHALL insert certification records into candidate_certifications table
5. IF a candidate reapplies with a new resume, THEN THE PHP_Integration_Service SHALL delete existing records and insert new ones
6. THE PHP_Integration_Service SHALL use database transactions to ensure all-or-nothing data insertion
7. IF database insertion fails, THEN THE PHP_Integration_Service SHALL roll back the transaction and log the error
8. THE PHP_Integration_Service SHALL store the complete JSON response in a candidate_parsed_resumes table for audit purposes

### Requirement 5: Integration with Application Submission

**User Story:** As a job applicant, I want my resume to be automatically parsed when I submit my application, so that my information is immediately available to HR.

#### Acceptance Criteria

1. WHEN a candidate submits an application via apply.php, THE Application_Processor SHALL trigger resume extraction after successful file upload
2. THE Application_Processor SHALL pass the uploaded resume file path and candidate_id to the Extraction_Service
3. IF extraction fails, THEN THE Application_Processor SHALL still complete the application submission but log the extraction error
4. THE Application_Processor SHALL display a success message indicating that resume parsing is in progress
5. THE Application_Processor SHALL not block application submission waiting for extraction to complete

### Requirement 6: HR Review Interface Display

**User Story:** As an HR staff member, I want to view extracted candidate information on the candidates.php page, so that I can quickly review qualifications without opening resume files.

#### Acceptance Criteria

1. WHEN viewing a candidate's details, THE HR_Review_Interface SHALL display all extracted education records in chronological order
2. WHEN viewing a candidate's details, THE HR_Review_Interface SHALL display all extracted skills grouped by proficiency level
3. WHEN viewing a candidate's details, THE HR_Review_Interface SHALL display all extracted work experience in reverse chronological order
4. WHEN viewing a candidate's details, THE HR_Review_Interface SHALL display all extracted certifications with expiry status indicators
5. IF no data was extracted for a candidate, THEN THE HR_Review_Interface SHALL display a message indicating manual review is needed
6. THE HR_Review_Interface SHALL provide a link to download the original resume file

### Requirement 7: Error Handling and Logging

**User Story:** As a system administrator, I want comprehensive error logging for extraction failures, so that I can troubleshoot issues and improve the system.

#### Acceptance Criteria

1. WHEN Python parser execution fails, THE System SHALL log the error with candidate_id, file path, and error message
2. WHEN JSON parsing fails, THE System SHALL log the error with candidate_id and the raw output received
3. WHEN database insertion fails, THE System SHALL log the error with candidate_id and SQL error details
4. THE System SHALL write all extraction logs to a dedicated log file (logs/resume_extraction.log)
5. THE System SHALL include timestamps and severity levels in all log entries
6. IF extraction fails, THEN THE System SHALL update the extraction_status field to 'failed' with error details

### Requirement 8: Data Quality and Validation

**User Story:** As the PHP integration service, I want to validate extracted data before storage, so that the database contains clean and consistent information.

#### Acceptance Criteria

1. WHEN validating education records, THE PHP_Integration_Service SHALL ensure required fields (degree, institution) are not empty
2. WHEN validating work experience, THE PHP_Integration_Service SHALL ensure required fields (job_title, company) are not empty
3. WHEN validating skills, THE PHP_Integration_Service SHALL ensure skill_name is not empty
4. THE PHP_Integration_Service SHALL sanitize all text fields to prevent SQL injection using prepared statements
5. THE PHP_Integration_Service SHALL trim whitespace from all string fields before insertion
6. IF validation fails for a record, THEN THE PHP_Integration_Service SHALL skip that record and continue with others
7. THE PHP_Integration_Service SHALL log all skipped records with validation failure reasons

### Requirement 9: Python Parser Integration Configuration

**User Story:** As a system administrator, I want to configure the Python parser path and settings, so that the system can locate and execute the parser correctly.

#### Acceptance Criteria

1. THE System SHALL define a configuration constant for the Python executable path
2. THE System SHALL define a configuration constant for the resume parser script path
3. THE System SHALL validate that the Python executable exists before attempting to execute the parser
4. THE System SHALL validate that the parser script exists before attempting to execute it
5. IF the Python executable or parser script is not found, THEN THE System SHALL log an error and return a configuration failure status

### Requirement 10: Extraction Status Tracking

**User Story:** As an HR staff member, I want to know the extraction status for each candidate, so that I can identify which applications need manual review.

#### Acceptance Criteria

1. THE Candidate_Data_Store SHALL add an extraction_status field to the candidates table
2. THE Candidate_Data_Store SHALL add an extraction_attempted_at timestamp field to the candidates table
3. WHEN extraction starts, THE System SHALL set extraction_status to 'processing'
4. WHEN extraction succeeds, THE System SHALL set extraction_status to 'completed'
5. WHEN extraction fails, THE System SHALL set extraction_status to 'failed'
6. IF no extraction has been attempted, THEN THE extraction_status SHALL be 'pending'
