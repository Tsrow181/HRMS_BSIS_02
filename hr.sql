-- ===============================
-- CORE USER AND EMPLOYEE TABLES
-- ===============================

-- Create user table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'manager', 'hr', 'employee') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create personal_information table
CREATE TABLE personal_information (
    personal_info_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Non-binary', 'Prefer not to say') NOT NULL,
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed') NOT NULL,
    nationality VARCHAR(50) NOT NULL,
    tax_id VARCHAR(20),
    social_security_number VARCHAR(20),
    phone_number VARCHAR(20) NOT NULL,
    emergency_contact_name VARCHAR(100),
    emergency_contact_relationship VARCHAR(50),
    emergency_contact_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create departments table
CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,
    description TEXT,
    manager_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create job_roles table
CREATE TABLE job_roles (
    job_role_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    department_id INT NOT NULL,
    min_salary DECIMAL(10,2),
    max_salary DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

-- Create employee_profiles table (connects users, personal info, and current job role)
CREATE TABLE employee_profiles (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    personal_info_id INT UNIQUE,
    job_role_id INT,
    manager_id INT,
    employee_number VARCHAR(20) NOT NULL UNIQUE,
    hire_date DATE NOT NULL,
    employment_status ENUM('Full-time', 'Part-time', 'Contract', 'Intern', 'Terminated') NOT NULL,
    current_salary DECIMAL(10,2) NOT NULL,
    work_email VARCHAR(100) UNIQUE,
    work_phone VARCHAR(20),
    location VARCHAR(100),
    remote_work BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (personal_info_id) REFERENCES personal_information(personal_info_id) ON DELETE SET NULL,
    FOREIGN KEY (job_role_id) REFERENCES job_roles(job_role_id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Add foreign key for department manager after employee_profiles table is created
ALTER TABLE departments
ADD CONSTRAINT fk_department_manager
FOREIGN KEY (manager_id) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL;

-- Create employment_history table
CREATE TABLE employment_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    job_role_id INT,
    start_date DATE NOT NULL,
    end_date DATE,
    salary DECIMAL(10,2) NOT NULL,
    manager_id INT,
    reason_for_change VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (job_role_id) REFERENCES job_roles(job_role_id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create document_management table
CREATE TABLE document_management (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    document_type ENUM('Contract', 'ID', 'Resume', 'Certificate', 'Performance Review', 'Other') NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATE,
    document_status ENUM('Active', 'Expired', 'Pending Review') DEFAULT 'Active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- ===============================
-- PAYROLL MANAGEMENT
-- ===============================

-- Create salary structure table
CREATE TABLE salary_structures (
    salary_structure_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    basic_salary DECIMAL(10, 2) NOT NULL,
    allowances DECIMAL(10, 2) DEFAULT 0,
    deductions DECIMAL(10, 2) DEFAULT 0,
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create payroll cycles table
CREATE TABLE payroll_cycles (
    payroll_cycle_id INT AUTO_INCREMENT PRIMARY KEY,
    cycle_name VARCHAR(50) NOT NULL,
    pay_period_start DATE NOT NULL,
    pay_period_end DATE NOT NULL,
    pay_date DATE NOT NULL,
    status ENUM('Pending', 'Processing', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create tax deductions table
CREATE TABLE tax_deductions (
    tax_deduction_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    tax_type VARCHAR(50) NOT NULL,
    tax_percentage DECIMAL(5, 2),
    tax_amount DECIMAL(10, 2),
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create statutory deductions table
CREATE TABLE statutory_deductions (
    statutory_deduction_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    deduction_type VARCHAR(50) NOT NULL,
    deduction_amount DECIMAL(10, 2) NOT NULL,
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create benefits plans table
CREATE TABLE benefits_plans (
    benefit_plan_id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(100) NOT NULL,
    plan_type VARCHAR(50) NOT NULL,
    description TEXT,
    eligibility_criteria TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create employee benefits table
CREATE TABLE employee_benefits (
    benefit_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    benefit_plan_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    benefit_amount DECIMAL(10, 2),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (benefit_plan_id) REFERENCES benefits_plans(benefit_plan_id) ON DELETE CASCADE
);

-- Create bonus payments table
CREATE TABLE bonus_payments (
    bonus_payment_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    bonus_type VARCHAR(50) NOT NULL,
    bonus_amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    payroll_cycle_id INT,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (payroll_cycle_id) REFERENCES payroll_cycles(payroll_cycle_id) ON DELETE SET NULL
);

-- Create compensation packages table
CREATE TABLE compensation_packages (
    compensation_package_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    package_name VARCHAR(100) NOT NULL,
    base_salary DECIMAL(10, 2) NOT NULL,
    variable_pay DECIMAL(10, 2) DEFAULT 0,
    benefits_summary TEXT,
    total_compensation DECIMAL(10, 2) NOT NULL,
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create payroll transactions table
CREATE TABLE payroll_transactions (
    payroll_transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    payroll_cycle_id INT NOT NULL,
    gross_pay DECIMAL(10, 2) NOT NULL,
    tax_deductions DECIMAL(10, 2) DEFAULT 0,
    statutory_deductions DECIMAL(10, 2) DEFAULT 0,
    other_deductions DECIMAL(10, 2) DEFAULT 0,
    net_pay DECIMAL(10, 2) NOT NULL,
    processed_date DATETIME NOT NULL,
    status ENUM('Pending', 'Processed', 'Paid', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (payroll_cycle_id) REFERENCES payroll_cycles(payroll_cycle_id) ON DELETE CASCADE
);

-- Create payment disbursements table
CREATE TABLE payment_disbursements (
    payment_disbursement_id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_transaction_id INT NOT NULL,
    employee_id INT NOT NULL,
    payment_method ENUM('Bank Transfer', 'Check', 'Cash', 'Other') NOT NULL,
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    payment_amount DECIMAL(10, 2) NOT NULL,
    disbursement_date DATETIME NOT NULL,
    status ENUM('Pending', 'Processed', 'Failed') DEFAULT 'Pending',
    reference_number VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_transaction_id) REFERENCES payroll_transactions(payroll_transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create payslips table
CREATE TABLE payslips (
    payslip_id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_transaction_id INT NOT NULL,
    employee_id INT NOT NULL,
    payslip_url VARCHAR(255),
    generated_date DATETIME NOT NULL,
    status ENUM('Generated', 'Sent', 'Viewed') DEFAULT 'Generated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_transaction_id) REFERENCES payroll_transactions(payroll_transaction_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- ===============================
-- PERFORMANCE MANAGEMENT
-- ===============================

-- Create competencies table
CREATE TABLE competencies (
    competency_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create employee competencies table
CREATE TABLE employee_competencies (
    employee_id INT NOT NULL,
    competency_id INT NOT NULL,
    rating INT NOT NULL,
    assessment_date DATE NOT NULL,
    assessed_by INT,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (employee_id, competency_id, assessment_date),
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (competency_id) REFERENCES competencies(competency_id) ON DELETE CASCADE,
    FOREIGN KEY (assessed_by) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create performance review cycles table
CREATE TABLE performance_review_cycles (
    cycle_id INT AUTO_INCREMENT PRIMARY KEY,
    cycle_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Upcoming', 'In Progress', 'Completed') DEFAULT 'Upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create performance reviews table
CREATE TABLE performance_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    cycle_id INT NOT NULL,
    review_date DATE NOT NULL,
    overall_rating DECIMAL(3,2) NOT NULL,
    strengths TEXT,
    areas_of_improvement TEXT,
    comments TEXT,
    status ENUM('Draft', 'Submitted', 'Acknowledged', 'Finalized') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (cycle_id) REFERENCES performance_review_cycles(cycle_id) ON DELETE CASCADE
);

-- Create feedback 360 table
CREATE TABLE feedback_360 (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    reviewed_employee_id INT NOT NULL,
    feedback_provider_id INT NOT NULL,
    cycle_id INT NOT NULL,
    feedback_date DATE NOT NULL,
    rating DECIMAL(3,2),
    comments TEXT,
    is_anonymous BOOLEAN DEFAULT FALSE,
    status ENUM('Pending', 'Submitted', 'Included') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewed_employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (feedback_provider_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (cycle_id) REFERENCES performance_review_cycles(cycle_id) ON DELETE CASCADE
);

-- Create goals table
CREATE TABLE goals (
    goal_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Not Started', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Not Started',
    progress DECIMAL(5,2) DEFAULT 0,
    weight DECIMAL(5,2) DEFAULT 100.00,
    supervisor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (supervisor_id) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create goal updates table
CREATE TABLE goal_updates (
    update_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    update_date DATE NOT NULL,
    progress DECIMAL(5,2) NOT NULL,
    comments TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES goals(goal_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create performance metrics table
CREATE TABLE performance_metrics (
    metric_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,2) NOT NULL,
    recorded_date DATE NOT NULL,
    recorded_by INT,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create development plans table
CREATE TABLE development_plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    coach_id INT,
    plan_name VARCHAR(100) NOT NULL,
    plan_description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Draft', 'Active', 'Completed', 'Cancelled') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (coach_id) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create development activities table
CREATE TABLE development_activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    activity_name VARCHAR(100) NOT NULL,
    activity_type ENUM('Training', 'Mentoring', 'Project', 'Education', 'Other') NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('Not Started', 'In Progress', 'Completed') DEFAULT 'Not Started',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES development_plans(plan_id) ON DELETE CASCADE
);

-- ===============================
-- LEAVE AND ATTENDANCE MANAGEMENT
-- ===============================

-- Create public holidays table
CREATE TABLE public_holidays (
    holiday_id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE UNIQUE NOT NULL,
    holiday_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create leave types table
CREATE TABLE leave_types (
    leave_type_id INT AUTO_INCREMENT PRIMARY KEY,
    leave_type_name VARCHAR(50) NOT NULL,
    description TEXT,
    paid BOOLEAN DEFAULT TRUE,
    default_days DECIMAL(5,2) DEFAULT 0,
    carry_forward BOOLEAN DEFAULT FALSE,
    max_carry_forward_days DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create leave balances table
CREATE TABLE leave_balances (
    balance_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    year YEAR NOT NULL,
    total_leaves DECIMAL(5,2) NOT NULL,
    leaves_taken DECIMAL(5,2) DEFAULT 0,
    leaves_pending DECIMAL(5,2) DEFAULT 0,
    leaves_remaining DECIMAL(5,2),
    last_updated DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(leave_type_id) ON DELETE CASCADE
);

-- Create leave requests table
CREATE TABLE leave_requests (
    leave_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days DECIMAL(5,2) NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    applied_on DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_by INT,
    approved_on DATETIME,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(leave_type_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create shifts table
CREATE TABLE shifts (
    shift_id INT AUTO_INCREMENT PRIMARY KEY,
    shift_name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create employee shifts table
CREATE TABLE employee_shifts (
    employee_shift_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    shift_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    is_overtime BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES shifts(shift_id) ON DELETE CASCADE
);

-- Create attendance table
CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    clock_in DATETIME,
    clock_out DATETIME,
    status ENUM('Present', 'Absent', 'Late', 'Half Day', 'On Leave') NOT NULL,
    working_hours DECIMAL(5,2),
    overtime_hours DECIMAL(5,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create attendance_summary table
CREATE TABLE attendance_summary (
    summary_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    month INT NOT NULL,
    year YEAR NOT NULL,
    total_present INT DEFAULT 0,
    total_absent INT DEFAULT 0,
    total_late INT DEFAULT 0,
    total_leave INT DEFAULT 0,
    total_working_hours DECIMAL(7,2) DEFAULT 0,
    total_overtime_hours DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    UNIQUE KEY (employee_id, month, year)
);

-- ===============================
-- EXIT MANAGEMENT
-- ===============================

-- Create exits table
CREATE TABLE exits (
    exit_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    exit_type ENUM('Resignation', 'Termination', 'Retirement', 'End of Contract', 'Other') NOT NULL,
    exit_reason TEXT,
    notice_date DATE NOT NULL,
    exit_date DATE NOT NULL,
    status ENUM('Pending', 'Processing', 'Completed', 'Cancelled') DEFAULT 'Pending',
    initiated_by INT NOT NULL,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (initiated_by) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create exit checklist table
CREATE TABLE exit_checklist (
    checklist_id INT AUTO_INCREMENT PRIMARY KEY,
    exit_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    responsible_department VARCHAR(50) NOT NULL,
    status ENUM('Pending', 'Completed', 'Not Applicable') DEFAULT 'Pending',
    completed_date DATE,
    completed_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exit_id) REFERENCES exits(exit_id) ON DELETE CASCADE,
    FOREIGN KEY (completed_by) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create exit interviews table
CREATE TABLE exit_interviews (
    interview_id INT AUTO_INCREMENT PRIMARY KEY,
    exit_id INT NOT NULL,
    employee_id INT NOT NULL,
    interviewer_id INT NOT NULL,
    interview_date DATE NOT NULL,
    feedback TEXT,
    improvement_suggestions TEXT,
    reason_for_leaving TEXT,
    would_recommend BOOLEAN,
    status ENUM('Scheduled', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exit_id) REFERENCES exits(exit_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (interviewer_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create knowledge transfers table
CREATE TABLE knowledge_transfers (
    transfer_id INT AUTO_INCREMENT PRIMARY KEY,
    exit_id INT NOT NULL,
    employee_id INT NOT NULL,
    successor_id INT,
    handover_details TEXT,
    start_date DATE,
    completion_date DATE,
    status ENUM('Not Started', 'In Progress', 'Completed', 'N/A') DEFAULT 'Not Started',
    completed_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exit_id) REFERENCES exits(exit_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (successor_id) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create settlements table
CREATE TABLE settlements (
    settlement_id INT AUTO_INCREMENT PRIMARY KEY,
    exit_id INT NOT NULL,
    employee_id INT NOT NULL,
    last_working_day DATE NOT NULL,
    final_salary DECIMAL(10,2) NOT NULL,
    severance_pay DECIMAL(10,2) DEFAULT 0,
    unused_leave_payout DECIMAL(10,2) DEFAULT 0,
    deductions DECIMAL(10,2) DEFAULT 0,
    final_settlement_amount DECIMAL(10,2) NOT NULL,
    payment_date DATE,
    payment_method VARCHAR(50),
    status ENUM('Pending', 'Processing', 'Completed') DEFAULT 'Pending',
    processed_by INT,
    processed_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exit_id) REFERENCES exits(exit_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create exit documents table
CREATE TABLE exit_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    exit_id INT NOT NULL,
    employee_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    document_url VARCHAR(255) NOT NULL,
    uploaded_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exit_id) REFERENCES exits(exit_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create post exit surveys table
CREATE TABLE post_exit_surveys (
    survey_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    exit_id INT NOT NULL,
    survey_date DATE NOT NULL,
    survey_response TEXT,
    satisfaction_rating INT,
    submitted_date TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (exit_id) REFERENCES exits(exit_id) ON DELETE CASCADE
);
-- ===============================
-- RECRUITMENT MANAGEMENT
-- ===============================

-- Create job_openings table
CREATE TABLE job_openings (
    job_opening_id INT AUTO_INCREMENT PRIMARY KEY,
    job_role_id INT NOT NULL,
    department_id INT NOT NULL,
    hiring_manager_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT NOT NULL,
    responsibilities TEXT NOT NULL,
    location VARCHAR(100),
    employment_type ENUM('Full-time', 'Part-time', 'Contract', 'Temporary', 'Internship') NOT NULL,
    experience_level VARCHAR(50),
    education_requirements TEXT,
    salary_range_min DECIMAL(10,2),
    salary_range_max DECIMAL(10,2),
    vacancy_count INT DEFAULT 1,
    posting_date DATE NOT NULL,
    closing_date DATE,
    status ENUM('Draft', 'Open', 'On Hold', 'Closed', 'Cancelled') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_role_id) REFERENCES job_roles(job_role_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE CASCADE,
    FOREIGN KEY (hiring_manager_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create candidates table
CREATE TABLE candidates (
    candidate_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    resume_url VARCHAR(255),
    cover_letter_url VARCHAR(255),
    source VARCHAR(100),
    current_position VARCHAR(100),
    current_company VARCHAR(100),
    notice_period VARCHAR(50),
    expected_salary DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create job_applications table
CREATE TABLE job_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    job_opening_id INT NOT NULL,
    candidate_id INT NOT NULL,
    application_date DATETIME NOT NULL,
    status ENUM('Applied', 'Screening', 'Interview', 'Assessment', 'Reference Check', 'Offer', 'Hired', 'Rejected', 'Withdrawn') DEFAULT 'Applied',
    notes TEXT,
    assessment_scores JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_opening_id) REFERENCES job_openings(job_opening_id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id) ON DELETE CASCADE
);

-- Create interview_stages table
CREATE TABLE interview_stages (
    stage_id INT AUTO_INCREMENT PRIMARY KEY,
    job_opening_id INT NOT NULL,
    stage_name VARCHAR(100) NOT NULL,
    stage_order INT NOT NULL,
    description TEXT,
    is_mandatory BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_opening_id) REFERENCES job_openings(job_opening_id) ON DELETE CASCADE
);

-- Create interviews table
CREATE TABLE interviews (
    interview_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    stage_id INT NOT NULL,
    interviewer_id INT NOT NULL,
    schedule_date DATETIME NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    location VARCHAR(255),
    interview_type ENUM('In-person', 'Phone', 'Video Call', 'Technical Assessment') NOT NULL,
    status ENUM('Scheduled', 'Completed', 'Rescheduled', 'Cancelled') DEFAULT 'Scheduled',
    feedback TEXT,
    rating DECIMAL(3,2),
    recommendation ENUM('Strong Yes', 'Yes', 'Maybe', 'No', 'Strong No'),
    completed_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES job_applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (stage_id) REFERENCES interview_stages(stage_id) ON DELETE CASCADE,
    FOREIGN KEY (interviewer_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create job_offers table
CREATE TABLE job_offers (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    job_opening_id INT NOT NULL,
    candidate_id INT NOT NULL,
    offered_salary DECIMAL(10,2) NOT NULL,
    offered_benefits TEXT,
    start_date DATE,
    expiration_date DATE NOT NULL,
    approval_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    offer_status ENUM('Draft', 'Sent', 'Accepted', 'Negotiating', 'Declined', 'Expired') DEFAULT 'Draft',
    offer_letter_url VARCHAR(255),
    notes TEXT,
    approved_by INT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES job_applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (job_opening_id) REFERENCES job_openings(job_opening_id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(candidate_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create recruitment_analytics table
CREATE TABLE recruitment_analytics (
    analytics_id INT AUTO_INCREMENT PRIMARY KEY,
    job_opening_id INT NOT NULL,
    total_applications INT DEFAULT 0,
    applications_per_day DECIMAL(5,2) DEFAULT 0,
    average_processing_time INT DEFAULT 0 COMMENT 'In days',
    average_time_to_hire INT DEFAULT 0 COMMENT 'In days',
    offer_acceptance_rate DECIMAL(5,2) DEFAULT 0,
    recruitment_source_breakdown JSON,
    cost_per_hire DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_opening_id) REFERENCES job_openings(job_opening_id) ON DELETE CASCADE
);

-- Create onboarding_tasks table
CREATE TABLE onboarding_tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    task_name VARCHAR(255) NOT NULL,
    description TEXT,
    department_id INT,
    task_type ENUM('Administrative', 'Equipment', 'Training', 'Introduction', 'Documentation', 'Other') NOT NULL,
    is_mandatory BOOLEAN DEFAULT TRUE,
    default_due_days INT DEFAULT 7 COMMENT 'Days after joining date',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
);

-- Create employee_onboarding table
CREATE TABLE employee_onboarding (
    onboarding_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    hiring_manager_id INT NOT NULL,
    start_date DATE NOT NULL,
    expected_completion_date DATE NOT NULL,
    status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (hiring_manager_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create employee_onboarding_tasks table
CREATE TABLE employee_onboarding_tasks (
    employee_task_id INT AUTO_INCREMENT PRIMARY KEY,
    onboarding_id INT NOT NULL,
    task_id INT NOT NULL,
    due_date DATE NOT NULL,
    assigned_to INT,
    status ENUM('Not Started', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Not Started',
    completion_date DATE,
    completed_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (onboarding_id) REFERENCES employee_onboarding(onboarding_id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES onboarding_tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- ===============================
-- TRAINING AND DEVELOPMENT
-- ===============================

-- Create training_courses table
CREATE TABLE training_courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    delivery_method ENUM('Online', 'Classroom', 'Workshop', 'Self-paced', 'Hybrid') NOT NULL,
    duration INT COMMENT 'Duration in hours',
    max_participants INT,
    prerequisites TEXT,
    materials_url VARCHAR(255),
    status ENUM('Active', 'Inactive', 'In Development') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create trainers table
CREATE TABLE trainers (
    trainer_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    specialization VARCHAR(255),
    bio TEXT,
    is_internal BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create training_sessions table
CREATE TABLE training_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    trainer_id INT NOT NULL,
    session_name VARCHAR(255) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    location VARCHAR(255),
    capacity INT NOT NULL,
    cost_per_participant DECIMAL(10,2),
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES training_courses(course_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE
);

-- Create training_enrollments table
CREATE TABLE training_enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    employee_id INT NOT NULL,
    enrollment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Enrolled', 'Completed', 'Dropped', 'Failed', 'Waitlisted') DEFAULT 'Enrolled',
    completion_date DATE,
    score DECIMAL(5,2),
    feedback TEXT,
    certificate_url VARCHAR(255),
    nominated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES training_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (nominated_by) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create learning_resources table
CREATE TABLE learning_resources (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    resource_name VARCHAR(255) NOT NULL,
    resource_type ENUM('Book', 'Online Course', 'Video', 'Article', 'Webinar', 'Podcast', 'Other') NOT NULL,
    description TEXT,
    resource_url VARCHAR(255),
    author VARCHAR(100),
    publication_date DATE,
    duration VARCHAR(50),
    tags VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create employee_resources table (learning resources assigned to employees)
CREATE TABLE employee_resources (
    employee_resource_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    resource_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    assigned_by INT,
    due_date DATE,
    completed_date DATE,
    status ENUM('Assigned', 'In Progress', 'Completed', 'Overdue') DEFAULT 'Assigned',
    rating INT,
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES learning_resources(resource_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create skill_matrix table
CREATE TABLE skill_matrix (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create employee_skills table
CREATE TABLE employee_skills (
    employee_skill_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    skill_id INT NOT NULL,
    proficiency_level ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') NOT NULL,
    assessed_date DATE NOT NULL,
    assessed_by INT,
    certification_url VARCHAR(255),
    expiry_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skill_matrix(skill_id) ON DELETE CASCADE,
    FOREIGN KEY (assessed_by) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- Create training_needs_assessment table
CREATE TABLE training_needs_assessment (
    assessment_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    assessor_id INT NOT NULL,
    assessment_date DATE NOT NULL,
    skills_gap TEXT,
    recommended_trainings TEXT,
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    status ENUM('Identified', 'In Progress', 'Completed') DEFAULT 'Identified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (assessor_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create career_paths table
CREATE TABLE career_paths (
    path_id INT AUTO_INCREMENT PRIMARY KEY,
    path_name VARCHAR(100) NOT NULL,
    description TEXT,
    department_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL
);

-- Create career_path_stages table
CREATE TABLE career_path_stages (
    stage_id INT AUTO_INCREMENT PRIMARY KEY,
    path_id INT NOT NULL,
    job_role_id INT NOT NULL,
    stage_order INT NOT NULL,
    minimum_time_in_role INT COMMENT 'In months',
    required_skills TEXT,
    required_experience TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (path_id) REFERENCES career_paths(path_id) ON DELETE CASCADE,
    FOREIGN KEY (job_role_id) REFERENCES job_roles(job_role_id) ON DELETE CASCADE
);

-- Create employee_career_paths table
CREATE TABLE employee_career_paths (
    employee_path_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    path_id INT NOT NULL,
    current_stage_id INT NOT NULL,
    start_date DATE NOT NULL,
    target_completion_date DATE,
    status ENUM('Active', 'Completed', 'On Hold', 'Abandoned') DEFAULT 'Active',
    mentor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (path_id) REFERENCES career_paths(path_id) ON DELETE CASCADE,
    FOREIGN KEY (current_stage_id) REFERENCES career_path_stages(stage_id) ON DELETE CASCADE,
    FOREIGN KEY (mentor_id) REFERENCES employee_profiles(employee_id) ON DELETE SET NULL
);

-- ===============================
-- REPORTING AND ANALYTICS
-- ===============================

-- Create report_definitions table
CREATE TABLE report_definitions (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(100) NOT NULL,
    description TEXT,
    report_type ENUM('HR', 'Payroll', 'Performance', 'Recruitment', 'Training', 'Attendance', 'Custom') NOT NULL,
    query_definition TEXT,
    parameters JSON,
    created_by INT NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create report_schedules table
CREATE TABLE report_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    schedule_name VARCHAR(100) NOT NULL,
    frequency ENUM('Daily', 'Weekly', 'Monthly', 'Quarterly', 'Yearly', 'One-time') NOT NULL,
    next_run_date DATETIME,
    recipients TEXT,
    active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES report_definitions(report_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create report_executions table
CREATE TABLE report_executions (
    execution_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    schedule_id INT,
    executed_by INT NOT NULL,
    execution_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    parameters JSON,
    status ENUM('Running', 'Completed', 'Failed') DEFAULT 'Running',
    result_file_path VARCHAR(255),
    error_message TEXT,
    execution_time INT COMMENT 'In seconds',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES report_definitions(report_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES report_schedules(schedule_id) ON DELETE SET NULL,
    FOREIGN KEY (executed_by) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create dashboards table
CREATE TABLE dashboards (
    dashboard_id INT AUTO_INCREMENT PRIMARY KEY,
    dashboard_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES employee_profiles(employee_id) ON DELETE CASCADE
);

-- Create dashboard_widgets table
CREATE TABLE dashboard_widgets (
    widget_id INT AUTO_INCREMENT PRIMARY KEY,
    dashboard_id INT NOT NULL,
    widget_name VARCHAR(100) NOT NULL,
    widget_type ENUM('Chart', 'Table', 'KPI', 'Custom') NOT NULL,
    data_source VARCHAR(255) NOT NULL,
    configuration JSON,
    position_x INT NOT NULL,
    position_y INT NOT NULL,
    width INT NOT NULL,
    height INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dashboard_id) REFERENCES dashboards(dashboard_id) ON DELETE CASCADE
);

-- Create hr_metrics table
CREATE TABLE hr_metrics (
    metric_id INT AUTO_INCREMENT PRIMARY KEY,
    metric_date DATE NOT NULL,
    total_employees INT DEFAULT 0,
    new_hires INT DEFAULT 0,
    terminations INT DEFAULT 0,
    open_positions INT DEFAULT 0,
    average_tenure DECIMAL(5,2) DEFAULT 0 COMMENT 'In years',
    turnover_rate DECIMAL(5,2) DEFAULT 0,
    average_time_to_hire INT DEFAULT 0 COMMENT 'In days',
    training_completion_rate DECIMAL(5,2) DEFAULT 0,
    average_performance_score DECIMAL(3,2) DEFAULT 0,
    employee_satisfaction_score DECIMAL(3,2) DEFAULT 0,
    diversity_metrics JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create department_metrics table
CREATE TABLE department_metrics (
    department_metric_id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    metric_date DATE NOT NULL,
    headcount INT DEFAULT 0,
    budget_utilization DECIMAL(5,2) DEFAULT 0,
    average_performance_score DECIMAL(3,2) DEFAULT 0,
    turnover_rate DECIMAL(5,2) DEFAULT 0,
    training_hours_avg DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE CASCADE
);

-- ===============================
-- AUDIT AND SYSTEM LOGGING
-- ===============================

-- Create audit_logs table
CREATE TABLE audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id VARCHAR(50) NOT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(50),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Create system_logs table
CREATE TABLE system_logs (
    system_log_id INT AUTO_INCREMENT PRIMARY KEY,
    log_type ENUM('Info', 'Warning', 'Error', 'Critical') NOT NULL,
    log_source VARCHAR(100) NOT NULL,
    log_message TEXT NOT NULL,
    stack_trace TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create login_history table
CREATE TABLE login_history (
    login_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    logout_time DATETIME,
    ip_address VARCHAR(50),
    user_agent VARCHAR(255),
    login_status ENUM('Success', 'Failed') NOT NULL,
    failure_reason VARCHAR(255),
    session_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);