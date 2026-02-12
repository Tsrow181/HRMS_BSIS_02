# Implementation Plan: AI Resume Extraction and Storage

## Overview

This implementation plan breaks down the AI Resume Extraction and Storage feature into discrete coding tasks. The approach follows a bottom-up strategy: first establishing the database foundation, then building the core extraction service, integrating with the application flow, and finally adding the HR review interface. Each task builds incrementally on previous work, with checkpoints to validate functionality.

## Tasks

- [x] 1. Create database schema and migration scripts
  - Create SQL migration file for all new tables (candidate_education, candidate_skills, candidate_work_experience, candidate_certifications, candidate_parsed_resumes)
  - Add ALTER TABLE statements for candidates table (extraction_status, extraction_attempted_at, extraction_error)
  - Include indexes and foreign key constraints
  - Add rollback script for safe migration reversal
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 10.1, 10.2_

- [ ] 2. Implement DataValidator class
  - [x] 2.1 Create DataValidator.php with validation methods
    - Implement validateEducation() to check degree and institution are not empty
    - Implement validateWorkExperience() to check job_title and company are not empty
    - Implement validateSkill() to check skill_name is not empty
    - Implement validateCertification() to check certification_name and issuing_organization are not empty
    - Implement sanitize() method to trim whitespace
    - _Requirements: 8.1, 8.2, 8.3, 8.5_
  
  - [ ]* 2.2 Write property test for required field validation
    - **Property 16: Required Field Validation**
    - **Validates: Requirements 8.1, 8.2, 8.3, 8.6, 8.7**
  
  - [ ]* 2.3 Write unit tests for DataValidator edge cases
    - Test empty strings, whitespace-only strings, null values
    - Test very long text fields
    - Test special characters
    - _Requirements: 8.1, 8.2, 8.3_

- [ ] 3. Implement CandidateDataRepository class
  - [x] 3.1 Create CandidateDataRepository.php with database operations
    - Implement storeEducation() using prepared statements
    - Implement storeSkills() using prepared statements
    - Implement storeWorkExperience() using prepared statements
    - Implement storeCertifications() using prepared statements
    - Implement deleteExistingData() to remove old records
    - Implement storeParserResponse() for audit trail
    - Implement getCandidateData() to retrieve all extracted data
    - Use PDO transactions for atomicity
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.8_
  
  - [ ]* 3.2 Write property test for successful data insertion
    - **Property 5: Successful Data Insertion**
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.8**
  
  - [ ]* 3.3 Write property test for data replacement on re-extraction
    - **Property 6: Data Replacement on Re-extraction**
    - **Validates: Requirements 4.5**
  
  - [ ]* 3.4 Write property test for transaction atomicity
    - **Property 7: Transaction Atomicity**
    - **Validates: Requirements 4.6, 4.7**
  
  - [ ]* 3.5 Write property test for text field sanitization
    - **Property 17: Text Field Sanitization**
    - **Validates: Requirements 8.4, 8.5**
  
  - [ ]* 3.6 Write unit tests for database error handling
    - Test constraint violations
    - Test connection failures
    - Test transaction rollback
    - _Requirements: 4.7_

- [ ] 4. Checkpoint - Ensure database layer tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 5. Create Python resume parser stub
  - [x] 5.1 Create parsers/resume_parser.py with basic structure
    - Accept file path as command-line argument
    - Return JSON to stdout with success/error structure
    - Include metadata (parser_version, extraction_date, file_type)
    - Handle PDF, DOC, DOCX file types
    - Implement basic text extraction (can use libraries like PyPDF2, python-docx)
    - Return structured data for education, skills, work_experience, certifications
    - _Requirements: 2.1, 2.2, 2.3_
  
  - [ ]* 5.2 Write unit tests for Python parser
    - Test with sample PDF, DOC, DOCX files
    - Test with invalid file paths
    - Test with unsupported file formats
    - Verify JSON output structure
    - _Requirements: 2.1, 2.3_

- [ ] 6. Implement ResumeExtractionService class
  - [x] 6.1 Create ResumeExtractionService.php with core extraction logic
    - Define configuration constants (PYTHON_EXECUTABLE, RESUME_PARSER_SCRIPT, PARSER_TIMEOUT)
    - Implement executePythonParser() to run parser via shell_exec with timeout
    - Implement parseParserOutput() to parse JSON response
    - Implement updateExtractionStatus() to update candidate record
    - Implement extractAndStore() to orchestrate the full workflow
    - Integrate DataValidator for validation
    - Integrate CandidateDataRepository for storage
    - Add comprehensive error logging to logs/resume_extraction.log
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 3.1, 3.2, 3.3, 3.4, 3.5, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 9.1, 9.2, 10.3, 10.4, 10.5_
  
  - [ ]* 6.2 Write property test for parser execution with file path
    - **Property 1: Parser Execution with File Path**
    - **Validates: Requirements 2.1, 2.2, 2.3**
  
  - [ ]* 6.3 Write property test for parser execution error handling
    - **Property 2: Parser Execution Error Handling**
    - **Validates: Requirements 2.4, 2.6**
  
  - [ ]* 6.4 Write property test for JSON parsing and validation
    - **Property 3: JSON Parsing and Validation**
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.5**
  
  - [ ]* 6.5 Write property test for data extraction from JSON
    - **Property 4: Data Extraction from JSON**
    - **Validates: Requirements 3.4**
  
  - [ ]* 6.6 Write property test for configuration validation
    - **Property 18: Configuration Validation**
    - **Validates: Requirements 9.3, 9.4, 9.5**
  
  - [ ]* 6.7 Write property test for extraction status lifecycle
    - **Property 19: Extraction Status Lifecycle**
    - **Validates: Requirements 10.3, 10.4, 10.5**
  
  - [ ]* 6.8 Write property test for comprehensive error logging
    - **Property 14: Comprehensive Error Logging**
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5**
  
  - [ ]* 6.9 Write property test for extraction status tracking
    - **Property 15: Extraction Status Tracking**
    - **Validates: Requirements 7.6**
  
  - [ ]* 6.10 Write unit tests for timeout handling
    - Test parser timeout scenario
    - Test process termination
    - _Requirements: 2.5, 2.6_

- [ ] 7. Checkpoint - Ensure extraction service tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 8. Integrate extraction service with apply.php
  - [x] 8.1 Modify apply.php to trigger extraction after resume upload
    - Add require_once for ResumeExtractionService.php
    - After successful resume upload and candidate creation, call extractAndStore()
    - Pass candidate_id and resume file path to extraction service
    - Wrap extraction call in try-catch to prevent blocking application submission
    - Log extraction errors but continue with application submission
    - Update success message to indicate resume parsing is in progress
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_
  
  - [ ]* 8.2 Write property test for application submission integration
    - **Property 8: Application Submission Integration**
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.5**
  
  - [ ]* 8.3 Write integration test for apply.php flow
    - Test complete application submission with resume upload
    - Verify extraction is triggered
    - Verify application succeeds even if extraction fails
    - _Requirements: 5.1, 5.3, 5.5_

- [ ] 9. Implement HR review interface in candidates.php
  - [x] 9.1 Add extracted data display sections to candidate detail view
    - Create function to retrieve candidate extracted data using CandidateDataRepository
    - Add Education History section with chronological ordering
    - Add Skills Matrix section grouped by proficiency level
    - Add Work Experience Timeline section with reverse chronological ordering
    - Add Certifications Panel with expiry status indicators (active/expired/no expiry)
    - Add extraction status badge (completed/failed/pending/processing)
    - Add download link for original resume file
    - Display "Manual review needed" message when no data extracted
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_
  
  - [ ]* 9.2 Write property test for education display ordering
    - **Property 9: Education Display Ordering**
    - **Validates: Requirements 6.1**
  
  - [ ]* 9.3 Write property test for skills grouping by proficiency
    - **Property 10: Skills Grouping by Proficiency**
    - **Validates: Requirements 6.2**
  
  - [ ]* 9.4 Write property test for work experience display ordering
    - **Property 11: Work Experience Display Ordering**
    - **Validates: Requirements 6.3**
  
  - [ ]* 9.5 Write property test for certification expiry status
    - **Property 12: Certification Expiry Status**
    - **Validates: Requirements 6.4**
  
  - [ ]* 9.6 Write property test for resume download link
    - **Property 13: Resume Download Link**
    - **Validates: Requirements 6.6**
  
  - [ ]* 9.7 Write unit tests for UI edge cases
    - Test display when no education records exist
    - Test display when no skills exist
    - Test display when no work experience exists
    - Test display when no certifications exist
    - Test extraction status badge for all statuses
    - _Requirements: 6.5_

- [ ] 10. Add manual re-extraction trigger
  - [ ] 10.1 Add "Re-extract Resume" button to candidates.php
    - Create AJAX endpoint to trigger re-extraction
    - Call ResumeExtractionService.extractAndStore() with existing resume path
    - Return extraction status and updated data
    - Update UI to show extraction in progress
    - _Requirements: 5.1, 5.2_
  
  - [ ]* 10.2 Write integration test for manual re-extraction
    - Test re-extraction replaces old data
    - Test UI updates after re-extraction
    - _Requirements: 4.5_

- [ ] 11. Create database migration runner script
  - [ ] 11.1 Create run_migration.php script
    - Read and execute SQL migration file
    - Check if tables already exist before creating
    - Log migration success/failure
    - Provide rollback option
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 10.1, 10.2_

- [ ] 12. Final checkpoint - End-to-end testing
  - Run complete application flow: upload resume → extraction → HR review
  - Verify all database tables are populated correctly
  - Verify error handling for various failure scenarios
  - Verify logging is working correctly
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties (minimum 100 iterations each)
- Unit tests validate specific examples and edge cases
- The Python parser can start as a simple stub and be enhanced later with AI integration
- All database operations use prepared statements for security
- Extraction failures do not block application submission (fault tolerance)
