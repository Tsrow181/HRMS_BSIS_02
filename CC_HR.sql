-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 19, 2025 at 10:08 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cc_hr`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `clock_in` datetime DEFAULT NULL,
  `clock_out` datetime DEFAULT NULL,
  `status` enum('Present','Absent','Late','Half Day','On Leave') NOT NULL,
  `working_hours` decimal(5,2) DEFAULT NULL,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_summary`
--

CREATE TABLE `attendance_summary` (
  `summary_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `total_present` int(11) DEFAULT 0,
  `total_absent` int(11) DEFAULT 0,
  `total_late` int(11) DEFAULT 0,
  `total_leave` int(11) DEFAULT 0,
  `total_working_hours` decimal(7,2) DEFAULT 0.00,
  `total_overtime_hours` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `benefits_plans`
--

CREATE TABLE `benefits_plans` (
  `benefit_plan_id` int(11) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `plan_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `eligibility_criteria` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bonus_payments`
--

CREATE TABLE `bonus_payments` (
  `bonus_payment_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `bonus_type` varchar(50) NOT NULL,
  `bonus_amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payroll_cycle_id` int(11) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `candidate_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `resume_url` varchar(255) DEFAULT NULL,
  `cover_letter_url` varchar(255) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `current_position` varchar(100) DEFAULT NULL,
  `current_company` varchar(100) DEFAULT NULL,
  `notice_period` varchar(50) DEFAULT NULL,
  `expected_salary` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `career_paths`
--

CREATE TABLE `career_paths` (
  `path_id` int(11) NOT NULL,
  `path_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `career_path_stages`
--

CREATE TABLE `career_path_stages` (
  `stage_id` int(11) NOT NULL,
  `path_id` int(11) NOT NULL,
  `job_role_id` int(11) NOT NULL,
  `stage_order` int(11) NOT NULL,
  `minimum_time_in_role` int(11) DEFAULT NULL COMMENT 'In months',
  `required_skills` text DEFAULT NULL,
  `required_experience` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compensation_packages`
--

CREATE TABLE `compensation_packages` (
  `compensation_package_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `base_salary` decimal(10,2) NOT NULL,
  `variable_pay` decimal(10,2) DEFAULT 0.00,
  `benefits_summary` text DEFAULT NULL,
  `total_compensation` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `competencies`
--

CREATE TABLE `competencies` (
  `competency_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `job_role_id` int(11) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `competencies`
--

INSERT INTO `competencies` (`competency_id`, `name`, `description`, `job_role_id`, `category`, `created_at`, `updated_at`) VALUES
(4, 'Leadership', 'Ability to guide and inspire the community toward shared goals and progress.', 1, NULL, '2025-08-19 07:11:01', '2025-08-19 07:11:01'),
(5, 'Public Speaking', 'Effective communication with the public, media, and government bodies.', 1, NULL, '2025-08-19 07:11:01', '2025-08-19 07:11:01'),
(6, 'Decision-Making', 'Capacity to make informed and timely decisions that impact the city.', 1, NULL, '2025-08-19 07:11:01', '2025-08-19 07:11:01'),
(7, 'Policy Development', 'Skill in drafting, reviewing, and implementing policies that improve governance.', 1, NULL, '2025-08-19 07:11:01', '2025-08-19 07:11:01'),
(8, 'Crisis Management', 'Ability to manage emergencies and unforeseen situations effectively.', 1, NULL, '2025-08-19 07:11:01', '2025-08-19 07:11:01'),
(9, 'Community Engagement', 'Maintaining strong relationships with citizens and stakeholders.', 1, NULL, '2025-08-19 07:11:01', '2025-08-19 07:11:01'),
(10, 'Financial Oversight', 'Understanding and monitoring of city budgets and financial resources.', 1, NULL, '2025-08-19 07:11:01', '2025-08-19 07:11:01'),
(11, 'Legislative Support', 'Assists in drafting, reviewing, and passing ordinances and resolutions.', 2, NULL, '2025-08-19 07:11:16', '2025-08-19 07:11:16'),
(12, 'Leadership', 'Provides leadership in the absence of the mayor and supports executive initiatives.', 2, NULL, '2025-08-19 07:11:16', '2025-08-19 07:11:16'),
(13, 'Conflict Resolution', 'Mediates disputes and promotes harmony among council members and stakeholders.', 2, NULL, '2025-08-19 07:11:16', '2025-08-19 07:11:16'),
(14, 'Public Representation', 'Represents the city and the mayor in official functions and community events.', 2, NULL, '2025-08-19 07:11:16', '2025-08-19 07:11:16'),
(15, 'Policy Analysis', 'Reviews policies and ensures they align with community needs and development goals.', 2, NULL, '2025-08-19 07:11:16', '2025-08-19 07:11:16'),
(16, 'Community Engagement', 'Maintains close connections with constituents to address concerns and suggestions.', 2, NULL, '2025-08-19 07:11:16', '2025-08-19 07:11:16'),
(17, 'Budget Oversight', 'Works with the city council to review and approve budgets for efficient resource use.', 2, NULL, '2025-08-19 07:11:16', '2025-08-19 07:11:16'),
(18, 'Legislative Function', 'Drafts, reviews, and enacts ordinances and resolutions for the welfare of constituents.', 3, NULL, '2025-08-19 07:11:32', '2025-08-19 07:11:32'),
(19, 'Constituency Service', 'Addresses the needs and concerns of residents through responsive governance.', 3, NULL, '2025-08-19 07:11:32', '2025-08-19 07:11:32'),
(20, 'Policy Advocacy', 'Promotes policies and programs that support local development and social equity.', 3, NULL, '2025-08-19 07:11:32', '2025-08-19 07:11:32'),
(21, 'Community Engagement', 'Conducts public hearings, consultations, and dialogues with stakeholders.', 3, NULL, '2025-08-19 07:11:32', '2025-08-19 07:11:32'),
(22, 'Budget Review', 'Participates in deliberating and approving the city/municipal budget.', 3, NULL, '2025-08-19 07:11:32', '2025-08-19 07:11:32'),
(23, 'Transparency & Accountability', 'Ensures decisions and actions are guided by fairness, honesty, and integrity.', 3, NULL, '2025-08-19 07:11:32', '2025-08-19 07:11:32'),
(24, 'Collaboration', 'Works closely with other council members, barangays, and committees to achieve goals.', 3, NULL, '2025-08-19 07:11:32', '2025-08-19 07:11:32');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `description`, `location`, `created_at`, `updated_at`) VALUES
(1, 'Office of the Mayor', 'Executive office responsible for municipal governance and administration', 'City Hall - 2nd Floor', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(2, 'Sangguniang Bayan', 'Municipal legislative body responsible for enacting local ordinances', 'City Hall - Session Hall', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(3, 'Municipal Treasurer\'s Office', 'Handles municipal revenue collection, treasury operations, and financial management', 'City Hall - 1st Floor', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(4, 'Municipal Budget Office', 'Responsible for budget preparation, monitoring, and financial planning', 'City Hall - 1st Floor', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(5, 'Municipal Accountant\'s Office', 'Manages municipal accounting, bookkeeping, and financial reporting', 'City Hall - 1st Floor', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(6, 'Municipal Planning & Development Office', 'Handles municipal planning, development programs, and project management', 'City Hall - 3rd Floor', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(7, 'Municipal Engineer\'s Office', 'Oversees infrastructure projects, public works, and engineering services', 'Engineering Building', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(8, 'Municipal Civil Registrar\'s Office', 'Manages civil registration services and vital statistics', 'City Hall - Ground Floor', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(9, 'Municipal Health Office', 'Provides public health services and healthcare programs', 'Health Center Building', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(10, 'Municipal Social Welfare & Development Office', 'Administers social services and community development programs', 'Social Services Building', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(11, 'Municipal Agriculture Office', 'Supports agricultural development and provides farming assistance', 'Agriculture Extension Office', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(12, 'Municipal Assessor\'s Office', 'Conducts property assessment and real property taxation', 'City Hall - Ground Floor', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(13, 'Municipal Human Resource & Administrative Office', 'Manages personnel administration and human resources', 'City Hall - 2nd Floor', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(14, 'Municipal Disaster Risk Reduction & Management Office', 'Coordinates disaster preparedness and emergency response', 'Emergency Operations Center', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(15, 'General Services Office', 'Provides general administrative support and facility management', 'City Hall - Basement', '2025-08-19 07:00:02', '2025-08-19 07:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `development_activities`
--

CREATE TABLE `development_activities` (
  `activity_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `activity_name` varchar(100) NOT NULL,
  `activity_type` enum('Training','Mentoring','Project','Education','Other') NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed') DEFAULT 'Not Started',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `development_plans`
--

CREATE TABLE `development_plans` (
  `plan_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `plan_description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Draft','Active','Completed','Cancelled') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_management`
--

CREATE TABLE `document_management` (
  `document_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `document_type` enum('Contract','ID','Resume','Certificate','Performance Review','Other') NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL,
  `document_status` enum('Active','Expired','Pending Review') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_management`
--

INSERT INTO `document_management` (`document_id`, `employee_id`, `document_type`, `document_name`, `file_path`, `upload_date`, `expiry_date`, `document_status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, '', 'Appointment Order - Municipal Treasurer', '/documents/appointments/maria_santos_appointment.pdf', '2025-08-19 07:00:02', NULL, 'Active', 'Appointed by Mayor per Civil Service guidelines', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(2, 1, 'Contract', 'Employment Contract - Municipal Treasurer', '/documents/contracts/maria_santos_contract.pdf', '2025-08-19 07:00:02', '2025-07-01', 'Active', 'Department head contract', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(3, 1, 'Resume', 'Resume - Maria Santos', '/documents/resumes/maria_santos_resume.pdf', '2025-08-19 07:00:02', NULL, 'Active', 'CPA with municipal finance experience', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(4, 2, '', 'Appointment Order - Municipal Engineer', '/documents/appointments/roberto_cruz_appointment.pdf', '2025-08-19 07:00:02', NULL, 'Active', 'Licensed Civil Engineer appointment', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(5, 2, 'Certificate', 'Professional Engineer License', '/documents/licenses/roberto_cruz_pe_license.pdf', '2025-08-19 07:00:02', '2025-12-31', 'Active', 'Updated PRC license', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(6, 2, 'Contract', 'Employment Contract - Municipal Engineer', '/documents/contracts/roberto_cruz_contract.pdf', '2025-08-19 07:00:02', '2024-06-15', 'Active', 'Engineering department head', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(7, 3, 'Contract', 'Employment Contract - Nurse', '/documents/contracts/jennifer_reyes_contract.pdf', '2025-08-19 07:00:02', '2025-01-20', 'Active', 'Municipal health office nurse', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(8, 3, 'Certificate', 'Nursing License', '/documents/licenses/jennifer_reyes_rn_license.pdf', '2025-08-19 07:00:02', '2025-08-31', 'Active', 'Updated PRC nursing license', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(9, 3, 'Certificate', 'Basic Life Support Training', '/documents/certificates/jennifer_reyes_bls_cert.pdf', '2025-08-19 07:00:02', '2024-12-31', 'Active', 'Required medical certification', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(10, 4, 'Contract', 'Employment Contract - CAD Operator', '/documents/contracts/antonio_garcia_contract.pdf', '2025-08-19 07:00:02', '2024-03-10', 'Active', 'Engineering support staff', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(11, 4, 'Certificate', 'AutoCAD Certification', '/documents/certificates/antonio_garcia_autocad_cert.pdf', '2025-08-19 07:00:02', '2025-06-30', 'Active', 'Professional CAD certification', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(12, 5, 'Contract', 'Employment Contract - Social Worker', '/documents/contracts/lisa_mendoza_contract.pdf', '2025-08-19 07:00:02', '2024-09-05', 'Active', 'MSWDO social worker', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(13, 5, 'Certificate', 'Social Work License', '/documents/licenses/lisa_mendoza_sw_license.pdf', '2025-08-19 07:00:02', '2025-10-31', 'Active', 'Updated PRC social work license', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(14, 6, 'Contract', 'Employment Contract - Accounting Staff', '/documents/contracts/michael_torres_contract.pdf', '2025-08-19 07:00:02', '2025-11-12', 'Active', 'Municipal accountant office staff', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(15, 6, 'Certificate', 'Bookkeeping Certification', '/documents/certificates/michael_torres_bookkeeping_cert.pdf', '2025-08-19 07:00:02', '2024-12-31', 'Active', 'Professional bookkeeping certification', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(16, 7, 'Contract', 'Employment Contract - Clerk', '/documents/contracts/carmen_delacruz_contract.pdf', '2025-08-19 07:00:02', '2025-02-28', 'Active', 'Civil registrar office clerk', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(17, 7, '', 'Civil Registration Training', '/documents/training/carmen_delacruz_civil_reg_training.pdf', '2025-08-19 07:00:02', NULL, 'Active', 'Specialized civil registration procedures', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(18, 8, 'Contract', 'Employment Contract - Maintenance Worker', '/documents/contracts/ricardo_villanueva_contract.pdf', '2025-08-19 07:00:02', '2024-05-18', 'Active', 'General services maintenance', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(19, 8, 'Certificate', 'Electrical Safety Training', '/documents/certificates/ricardo_villanueva_electrical_safety.pdf', '2025-08-19 07:00:02', '2024-12-31', 'Active', 'Safety certification for maintenance work', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(20, 9, 'Contract', 'Employment Contract - Cashier', '/documents/contracts/sandra_pascual_contract.pdf', '2025-08-19 07:00:02', '2025-09-10', 'Active', 'Treasury office cashier', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(21, 9, '', 'Financial Management Training', '/documents/training/sandra_pascual_finance_training.pdf', '2025-08-19 07:00:02', NULL, 'Active', 'Municipal financial procedures training', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(22, 10, 'Contract', 'Employment Contract - Collection Officer', '/documents/contracts/jose_ramos_contract.pdf', '2025-08-19 07:00:02', '2024-12-01', 'Active', 'Revenue collection specialist', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(23, 10, '', 'Revenue Collection Procedures', '/documents/training/jose_ramos_collection_training.pdf', '2025-08-19 07:00:02', NULL, 'Active', 'Specialized revenue collection training', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(24, 11, 'Contract', 'Employment Contract - Administrative Aide', '/documents/contracts/ana_morales_contract.pdf', '2025-08-19 07:00:02', '2025-04-15', 'Active', 'HR office administrative support', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(25, 12, 'Contract', 'Employment Contract - Agricultural Technician', '/documents/contracts/pablo_fernandez_contract.pdf', '2025-08-19 07:00:02', '2024-08-20', 'Active', 'Agriculture office technical staff', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(26, 12, 'Certificate', 'Agricultural Extension Training', '/documents/certificates/pablo_fernandez_agri_ext_cert.pdf', '2025-08-19 07:00:02', '2025-07-31', 'Active', 'Agricultural extension certification', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(27, 13, 'Contract', 'Employment Contract - Midwife', '/documents/contracts/grace_lopez_contract.pdf', '2025-08-19 07:00:02', '2025-06-30', 'Active', 'Municipal health office midwife', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(28, 13, 'Certificate', 'Midwifery License', '/documents/licenses/grace_lopez_midwife_license.pdf', '2025-08-19 07:00:02', '2025-09-30', 'Active', 'Updated PRC midwifery license', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(29, 14, 'Contract', 'Employment Contract - Driver', '/documents/contracts/eduardo_hernandez_contract.pdf', '2025-08-19 07:00:02', '2025-01-10', 'Active', 'Municipal vehicle operator', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(30, 14, 'Certificate', 'Professional Driver License', '/documents/licenses/eduardo_hernandez_driver_license.pdf', '2025-08-19 07:00:02', '2025-12-31', 'Active', 'Professional driver\'s license', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(31, 15, 'Contract', 'Employment Contract - Security Personnel', '/documents/contracts/rosario_gonzales_contract.pdf', '2025-08-19 07:00:02', '2024-11-05', 'Active', 'Municipal facility security', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(32, 15, 'Certificate', 'Security Guard License', '/documents/licenses/rosario_gonzales_security_license.pdf', '2025-08-19 07:00:02', '2025-08-31', 'Active', 'SOSIA security guard license', '2025-08-19 07:00:02', '2025-08-19 07:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `employee_benefits`
--

CREATE TABLE `employee_benefits` (
  `benefit_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `benefit_plan_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `benefit_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_career_paths`
--

CREATE TABLE `employee_career_paths` (
  `employee_path_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `path_id` int(11) NOT NULL,
  `current_stage_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `target_completion_date` date DEFAULT NULL,
  `status` enum('Active','Completed','On Hold','Abandoned') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_competencies`
--

CREATE TABLE `employee_competencies` (
  `employee_id` int(11) NOT NULL,
  `competency_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `assessment_date` date NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_onboarding`
--

CREATE TABLE `employee_onboarding` (
  `onboarding_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `expected_completion_date` date NOT NULL,
  `status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_onboarding_tasks`
--

CREATE TABLE `employee_onboarding_tasks` (
  `employee_task_id` int(11) NOT NULL,
  `onboarding_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Not Started','In Progress','Completed','Cancelled') DEFAULT 'Not Started',
  `completion_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_profiles`
--

CREATE TABLE `employee_profiles` (
  `employee_id` int(11) NOT NULL,
  `personal_info_id` int(11) DEFAULT NULL,
  `job_role_id` int(11) DEFAULT NULL,
  `employee_number` varchar(20) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `hire_date` date NOT NULL,
  `employment_status` enum('Full-time','Part-time','Contract','Intern','Terminated') NOT NULL,
  `current_salary` decimal(10,2) NOT NULL,
  `work_email` varchar(100) DEFAULT NULL,
  `work_phone` varchar(20) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `remote_work` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_profiles`
--

INSERT INTO `employee_profiles` (`employee_id`, `personal_info_id`, `job_role_id`, `employee_number`, `employee_name`, `hire_date`, `employment_status`, `current_salary`, `work_email`, `work_phone`, `location`, `remote_work`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 'MUN001', 'Maria Santos', '2019-07-01', 'Full-time', 65000.00, 'maria.santos@municipality.gov.ph', '034-123-0001', 'City Hall - 1st Floor', 0, '2025-08-19 07:00:02', '2025-08-19 07:50:05'),
(2, 2, 8, 'MUN002', 'Roberto Cruz', '2018-06-15', 'Full-time', 75000.00, 'roberto.cruz@municipality.gov.ph', '034-123-0002', 'Engineering Building', 0, '2025-08-19 07:00:02', '2025-08-19 07:50:44'),
(3, 3, 17, 'MUN003', 'Jennifer Reyes', '2020-01-20', 'Full-time', 42000.00, 'jennifer.reyes@municipality.gov.ph', '034-123-0003', 'Municipal Health Office', 0, '2025-08-19 07:00:02', '2025-08-19 07:51:25'),
(4, 4, 21, 'MUN004', 'Antonio Garcia', '2019-03-10', 'Full-time', 38000.00, 'antonio.garcia@municipality.gov.ph', '034-123-0004', 'Municipal Engineer\'s Office', 0, '2025-08-19 07:00:02', '2025-08-19 07:52:26'),
(5, 5, 20, 'MUN005', 'Lisa Mendoza', '2021-09-05', 'Full-time', 45000.00, 'lisa.mendoza@municipality.gov.ph', '034-123-0005', 'Municipal Social Welfare & Development Office', 0, '2025-08-19 07:00:02', '2025-08-19 07:52:53'),
(6, 6, 25, 'MUN006', 'Michael Torres', '2020-11-12', 'Full-time', 28000.00, 'michael.torres@municipality.gov.ph', '034-123-0006', 'Municipal Accountant\'s Office', 0, '2025-08-19 07:00:02', '2025-08-19 07:53:31'),
(7, 7, 27, 'MUN007', 'Carmen Dela Cruz', '2022-02-28', 'Full-time', 30000.00, 'carmen.delacruz@municipality.gov.ph', '034-123-0007', 'Municipal Civil Registrar\'s Office', 0, '2025-08-19 07:00:02', '2025-08-19 07:54:41'),
(8, 8, 32, 'MUN008', 'Ricardo Villanueva', '2021-05-18', 'Full-time', 22000.00, 'ricardo.villanueva@municipality.gov.ph', '034-123-0008', 'General Services Office', 0, '2025-08-19 07:00:02', '2025-08-19 07:55:56'),
(9, 9, 28, 'MUN009', 'Sandra Pascual', '2020-09-10', 'Full-time', 32000.00, 'sandra.pascual@municipality.gov.ph', '034-123-0009', 'Municipal Treasurer\'s Office', 0, '2025-08-19 07:00:02', '2025-08-19 07:56:44'),
(10, 10, 29, 'MUN010', 'Jose Ramos', '2019-12-01', 'Full-time', 35000.00, 'jose.ramos@municipality.gov.ph', '034-123-0010', 'Municipal Treasurer\'s Office', 0, '2025-08-19 07:00:02', '2025-08-19 07:57:24'),
(11, 11, 26, 'MUN011', 'Ana Morales', '2022-04-15', 'Full-time', 28000.00, 'ana.morales@municipality.gov.ph', '034-123-0011', 'Municipal Human Resource & Administrative Office', 0, '2025-08-19 07:00:02', '2025-08-19 07:58:27'),
(12, 12, 19, 'MUN012', 'Pablo Fernandez', '2021-08-20', 'Full-time', 40000.00, 'pablo.fernandez@municipality.gov.ph', '034-123-0012', 'Municipal Agriculture Office', 0, '2025-08-19 07:00:02', '2025-08-19 08:01:06'),
(13, 13, 18, 'MUN013', 'Grace Lopez', '2020-06-30', 'Full-time', 42000.00, 'grace.lopez@municipality.gov.ph', '034-123-0013', 'Municipal Health Office', 0, '2025-08-19 07:00:02', '2025-08-19 08:01:22'),
(14, 14, 31, 'MUN014', 'Eduardo Hernandez', '2022-01-10', 'Full-time', 25000.00, 'eduardo.hernandez@municipality.gov.ph', '034-123-0014', 'General Services Office', 0, '2025-08-19 07:00:02', '2025-08-19 08:01:47'),
(15, 15, 33, 'MUN015', 'Rosario Gonzales', '2021-11-05', 'Full-time', 24000.00, 'rosario.gonzales@municipality.gov.ph', '034-123-0015', 'General Services Office', 0, '2025-08-19 07:00:02', '2025-08-19 08:02:12');

-- --------------------------------------------------------

--
-- Table structure for table `employee_resources`
--

CREATE TABLE `employee_resources` (
  `employee_resource_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `status` enum('Assigned','In Progress','Completed','Overdue') DEFAULT 'Assigned',
  `rating` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_shifts`
--

CREATE TABLE `employee_shifts` (
  `employee_shift_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `is_overtime` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_skills`
--

CREATE TABLE `employee_skills` (
  `employee_skill_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `proficiency_level` enum('Beginner','Intermediate','Advanced','Expert') NOT NULL,
  `assessed_date` date NOT NULL,
  `certification_url` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_history`
--

CREATE TABLE `employment_history` (
  `history_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `job_role_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `salary` decimal(10,2) NOT NULL,
  `reason_for_change` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employment_history`
--

INSERT INTO `employment_history` (`history_id`, `employee_id`, `job_role_id`, `start_date`, `end_date`, `salary`, `reason_for_change`, `created_at`, `updated_at`) VALUES
(1, 1, 4, '2019-07-01', NULL, 65000.00, 'Appointed as Municipal Treasurer', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(2, 2, 8, '2018-06-15', NULL, 75000.00, 'Appointed as Municipal Engineer', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(3, 3, 17, '2020-01-20', NULL, 42000.00, 'Hired as Nurse', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(4, 4, 21, '2019-03-10', NULL, 38000.00, 'Hired as CAD Operator', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(5, 5, 20, '2021-09-05', NULL, 45000.00, 'Hired as Social Worker', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(6, 6, 25, '2020-11-12', NULL, 28000.00, 'Hired as Accounting Staff', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(7, 7, 27, '2022-02-28', NULL, 30000.00, 'Hired as Clerk', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(8, 8, 32, '2021-05-18', NULL, 22000.00, 'Hired as Maintenance Worker', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(9, 9, 28, '2020-09-10', NULL, 32000.00, 'Hired as Cashier', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(10, 10, 29, '2019-12-01', NULL, 35000.00, 'Hired as Collection Officer', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(11, 11, 26, '2022-04-15', NULL, 28000.00, 'Hired as Administrative Aide', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(12, 12, 19, '2021-08-20', NULL, 40000.00, 'Hired as Agricultural Technician', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(13, 13, 18, '2020-06-30', NULL, 42000.00, 'Hired as Midwife', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(14, 14, 31, '2022-01-10', NULL, 25000.00, 'Hired as Driver', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(15, 15, 33, '2021-11-05', NULL, 24000.00, 'Hired as Security Personnel', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(16, 1, 26, '2017-03-01', '2019-06-30', 25000.00, 'Started as Administrative Aide', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(17, 2, 21, '2015-08-01', '2018-06-14', 32000.00, 'Started as CAD Operator', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(18, 5, 26, '2019-01-15', '2021-09-04', 25000.00, 'Started as Administrative Aide', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(19, 9, 27, '2018-05-01', '2020-09-09', 22000.00, 'Started as Clerk', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(20, 10, 27, '2017-10-01', '2019-11-30', 20000.00, 'Started as Clerk', '2025-08-19 07:00:02', '2025-08-19 07:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `exits`
--

CREATE TABLE `exits` (
  `exit_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `exit_type` enum('Resignation','Termination','Retirement','End of Contract','Other') NOT NULL,
  `exit_reason` text DEFAULT NULL,
  `notice_date` date NOT NULL,
  `exit_date` date NOT NULL,
  `status` enum('Pending','Processing','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_checklist`
--

CREATE TABLE `exit_checklist` (
  `checklist_id` int(11) NOT NULL,
  `exit_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `responsible_department` varchar(50) NOT NULL,
  `status` enum('Pending','Completed','Not Applicable') DEFAULT 'Pending',
  `completed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_documents`
--

CREATE TABLE `exit_documents` (
  `document_id` int(11) NOT NULL,
  `exit_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_url` varchar(255) NOT NULL,
  `uploaded_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exit_interviews`
--

CREATE TABLE `exit_interviews` (
  `interview_id` int(11) NOT NULL,
  `exit_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `interview_date` date NOT NULL,
  `feedback` text DEFAULT NULL,
  `improvement_suggestions` text DEFAULT NULL,
  `reason_for_leaving` text DEFAULT NULL,
  `would_recommend` tinyint(1) DEFAULT NULL,
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goals`
--

CREATE TABLE `goals` (
  `goal_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Not Started','In Progress','Completed','Cancelled') DEFAULT 'Not Started',
  `progress` decimal(5,2) DEFAULT 0.00,
  `weight` decimal(5,2) DEFAULT 100.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goal_updates`
--

CREATE TABLE `goal_updates` (
  `update_id` int(11) NOT NULL,
  `goal_id` int(11) NOT NULL,
  `update_date` date NOT NULL,
  `progress` decimal(5,2) NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

CREATE TABLE `interviews` (
  `interview_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `stage_id` int(11) NOT NULL,
  `schedule_date` datetime NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `location` varchar(255) DEFAULT NULL,
  `interview_type` enum('In-person','Phone','Video Call','Technical Assessment') NOT NULL,
  `status` enum('Scheduled','Completed','Rescheduled','Cancelled') DEFAULT 'Scheduled',
  `feedback` text DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `recommendation` enum('Strong Yes','Yes','Maybe','No','Strong No') DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interview_stages`
--

CREATE TABLE `interview_stages` (
  `stage_id` int(11) NOT NULL,
  `job_opening_id` int(11) NOT NULL,
  `stage_name` varchar(100) NOT NULL,
  `stage_order` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `application_id` int(11) NOT NULL,
  `job_opening_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `application_date` datetime NOT NULL,
  `status` enum('Applied','Screening','Interview','Assessment','Reference Check','Offer','Hired','Rejected','Withdrawn') DEFAULT 'Applied',
  `notes` text DEFAULT NULL,
  `assessment_scores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`assessment_scores`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_offers`
--

CREATE TABLE `job_offers` (
  `offer_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `job_opening_id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `offered_salary` decimal(10,2) NOT NULL,
  `offered_benefits` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `expiration_date` date NOT NULL,
  `approval_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `offer_status` enum('Draft','Sent','Accepted','Negotiating','Declined','Expired') DEFAULT 'Draft',
  `offer_letter_url` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_openings`
--

CREATE TABLE `job_openings` (
  `job_opening_id` int(11) NOT NULL,
  `job_role_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `responsibilities` text NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `employment_type` enum('Full-time','Part-time','Contract','Temporary','Internship') NOT NULL,
  `experience_level` varchar(50) DEFAULT NULL,
  `education_requirements` text DEFAULT NULL,
  `salary_range_min` decimal(10,2) DEFAULT NULL,
  `salary_range_max` decimal(10,2) DEFAULT NULL,
  `vacancy_count` int(11) DEFAULT 1,
  `posting_date` date NOT NULL,
  `closing_date` date DEFAULT NULL,
  `status` enum('Draft','Open','On Hold','Closed','Cancelled') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_roles`
--

CREATE TABLE `job_roles` (
  `job_role_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `department` varchar(50) NOT NULL,
  `min_salary` decimal(10,2) DEFAULT NULL,
  `max_salary` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_roles`
--

INSERT INTO `job_roles` (`job_role_id`, `title`, `description`, `department`, `min_salary`, `max_salary`, `created_at`, `updated_at`) VALUES
(1, 'Mayor', 'Chief executive of the municipality responsible for overall governance', 'Office of the Mayor', 80000.00, 120000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(2, 'Vice Mayor', 'Presiding officer of Sangguniang Bayan and assistant to the Mayor', 'Sangguniang Bayan', 70000.00, 100000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(3, 'Councilor', 'Member of the municipal legislative body', 'Sangguniang Bayan', 60000.00, 85000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(4, 'Municipal Treasurer', 'Head of treasury operations and revenue collection', 'Municipal Treasurer\'s Office', 55000.00, 75000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(5, 'Municipal Budget Officer', 'Responsible for municipal budget preparation and monitoring', 'Municipal Budget Office', 50000.00, 70000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(6, 'Municipal Accountant', 'Chief accountant responsible for municipal financial records', 'Municipal Accountant\'s Office', 50000.00, 70000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(7, 'Municipal Planning & Development Coordinator', 'Head of municipal planning and development programs', 'Municipal Planning & Development Office', 55000.00, 75000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(8, 'Municipal Engineer', 'Chief engineer overseeing infrastructure and public works', 'Municipal Engineer\'s Office', 60000.00, 85000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(9, 'Municipal Civil Registrar', 'Head of civil registration services', 'Municipal Civil Registrar\'s Office', 45000.00, 65000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(10, 'Municipal Health Officer', 'Chief medical officer and head of health services', 'Municipal Health Office', 70000.00, 95000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(11, 'Municipal Social Welfare Officer', 'Head of social welfare and development programs', 'Municipal Social Welfare & Development Office', 50000.00, 70000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(12, 'Municipal Agriculturist', 'Agricultural development officer and extension coordinator', 'Municipal Agriculture Office', 50000.00, 70000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(13, 'Municipal Assessor', 'Head of property assessment and real property taxation', 'Municipal Assessor\'s Office', 50000.00, 70000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(14, 'Municipal HR Officer', 'Head of human resources and personnel administration', 'Municipal Human Resource & Administrative Office', 50000.00, 70000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(15, 'MDRRM Officer', 'Disaster risk reduction and management coordinator', 'Municipal Disaster Risk Reduction & Management Off', 45000.00, 65000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(16, 'General Services Officer', 'Head of general services and facility management', 'General Services Office', 40000.00, 60000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(17, 'Nurse', 'Provides nursing services and healthcare support', 'Municipal Health Office', 35000.00, 50000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(18, 'Midwife', 'Provides maternal and child health services', 'Municipal Health Office', 30000.00, 45000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(19, 'Sanitary Inspector', 'Conducts health and sanitation inspections', 'Municipal Health Office', 28000.00, 40000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(20, 'Social Worker', 'Provides social services and community assistance', 'Municipal Social Welfare & Development Office', 35000.00, 50000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(21, 'Agricultural Technician', 'Provides technical support for agricultural programs', 'Municipal Agriculture Office', 28000.00, 40000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(22, 'Civil Engineer', 'Designs and supervises infrastructure projects', 'Municipal Engineer\'s Office', 45000.00, 65000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(23, 'CAD Operator', 'Creates technical drawings and engineering plans', 'Municipal Engineer\'s Office', 30000.00, 45000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(24, 'Building Inspector', 'Inspects construction projects for code compliance', 'Municipal Engineer\'s Office', 35000.00, 50000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(25, 'Budget Analyst', 'Analyzes budget data and prepares financial reports', 'Municipal Budget Office', 35000.00, 50000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(26, 'Accounting Staff', 'Handles bookkeeping and accounting transactions', 'Municipal Accountant\'s Office', 25000.00, 38000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(27, 'Planning Staff', 'Assists in municipal planning and development activities', 'Municipal Planning & Development Office', 30000.00, 45000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(28, 'Administrative Aide', 'Provides administrative support to various departments', 'Municipal Human Resource & Administrative Office', 22000.00, 35000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(29, 'Clerk', 'Handles clerical work and document processing', 'Municipal Civil Registrar\'s Office', 20000.00, 32000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(30, 'Cashier', 'Processes payments and financial transactions', 'Municipal Treasurer\'s Office', 22000.00, 35000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(31, 'Collection Officer', 'Collects municipal revenues and taxes', 'Municipal Treasurer\'s Office', 25000.00, 38000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(32, 'Property Custodian', 'Manages and maintains municipal property and assets', 'General Services Office', 22000.00, 35000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(33, 'Maintenance Worker', 'Performs maintenance and repair work on municipal facilities', 'General Services Office', 18000.00, 28000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(34, 'Utility Worker', 'Provides general utility and janitorial services', 'General Services Office', 16000.00, 25000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(35, 'Driver', 'Operates municipal vehicles and provides transportation services', 'General Services Office', 20000.00, 32000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(36, 'Security Personnel', 'Provides security services for municipal facilities', 'General Services Office', 18000.00, 28000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(37, 'Legislative Staff', 'Provides secretarial support to Sangguniang Bayan', 'Sangguniang Bayan', 25000.00, 38000.00, '2025-08-19 07:00:02', '2025-08-19 07:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `knowledge_transfers`
--

CREATE TABLE `knowledge_transfers` (
  `transfer_id` int(11) NOT NULL,
  `exit_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `handover_details` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed','N/A') DEFAULT 'Not Started',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_resources`
--

CREATE TABLE `learning_resources` (
  `resource_id` int(11) NOT NULL,
  `resource_name` varchar(255) NOT NULL,
  `resource_type` enum('Book','Online Course','Video','Article','Webinar','Podcast','Other') NOT NULL,
  `description` text DEFAULT NULL,
  `resource_url` varchar(255) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `publication_date` date DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

CREATE TABLE `leave_balances` (
  `balance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `total_leaves` decimal(5,2) NOT NULL,
  `leaves_taken` decimal(5,2) DEFAULT 0.00,
  `leaves_pending` decimal(5,2) DEFAULT 0.00,
  `leaves_remaining` decimal(5,2) DEFAULT NULL,
  `last_updated` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `leave_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` decimal(5,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  `applied_on` datetime DEFAULT current_timestamp(),
  `approved_on` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `leave_type_id` int(11) NOT NULL,
  `leave_type_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `paid` tinyint(1) DEFAULT 1,
  `default_days` decimal(5,2) DEFAULT 0.00,
  `carry_forward` tinyint(1) DEFAULT 0,
  `max_carry_forward_days` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onboarding_tasks`
--

CREATE TABLE `onboarding_tasks` (
  `task_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `task_type` enum('Administrative','Equipment','Training','Introduction','Documentation','Other') NOT NULL,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `default_due_days` int(11) DEFAULT 7 COMMENT 'Days after joining date',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_disbursements`
--

CREATE TABLE `payment_disbursements` (
  `payment_disbursement_id` int(11) NOT NULL,
  `payroll_transaction_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payment_method` enum('Bank Transfer','Check','Cash','Other') NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `disbursement_date` datetime NOT NULL,
  `status` enum('Pending','Processed','Failed') DEFAULT 'Pending',
  `reference_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_cycles`
--

CREATE TABLE `payroll_cycles` (
  `payroll_cycle_id` int(11) NOT NULL,
  `cycle_name` varchar(50) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `pay_date` date NOT NULL,
  `status` enum('Pending','Processing','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_transactions`
--

CREATE TABLE `payroll_transactions` (
  `payroll_transaction_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payroll_cycle_id` int(11) NOT NULL,
  `gross_pay` decimal(10,2) NOT NULL,
  `tax_deductions` decimal(10,2) DEFAULT 0.00,
  `statutory_deductions` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `net_pay` decimal(10,2) NOT NULL,
  `processed_date` datetime NOT NULL,
  `status` enum('Pending','Processed','Paid','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `payslip_id` int(11) NOT NULL,
  `payroll_transaction_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payslip_url` varchar(255) DEFAULT NULL,
  `generated_date` datetime NOT NULL,
  `status` enum('Generated','Sent','Viewed') DEFAULT 'Generated',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_metrics`
--

CREATE TABLE `performance_metrics` (
  `metric_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `metric_name` varchar(100) NOT NULL,
  `metric_value` decimal(10,2) NOT NULL,
  `recorded_date` date NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_reviews`
--

CREATE TABLE `performance_reviews` (
  `review_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `cycle_id` int(11) NOT NULL,
  `review_date` date NOT NULL,
  `overall_rating` decimal(3,2) NOT NULL,
  `strengths` text DEFAULT NULL,
  `areas_of_improvement` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `status` enum('Draft','Submitted','Acknowledged','Finalized') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_review_cycles`
--

CREATE TABLE `performance_review_cycles` (
  `cycle_id` int(11) NOT NULL,
  `cycle_name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Upcoming','In Progress','Completed') DEFAULT 'Upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_information`
--

CREATE TABLE `personal_information` (
  `personal_info_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Non-binary','Prefer not to say') NOT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') NOT NULL,
  `nationality` varchar(50) NOT NULL,
  `tax_id` varchar(20) DEFAULT NULL,
  `social_security_number` varchar(20) DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal_information`
--

INSERT INTO `personal_information` (`personal_info_id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `marital_status`, `nationality`, `tax_id`, `social_security_number`, `phone_number`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone`, `created_at`, `updated_at`) VALUES
(1, 'Maria', 'Santos', '1985-03-12', 'Female', 'Married', 'Filipino', '123-45-6789', '123456789', '0917-123-4567', 'Carlos Santos', 'Spouse', '0917-567-8901', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(2, 'Roberto', 'Cruz', '1978-07-20', 'Male', 'Married', 'Filipino', '234-56-7890', '234567890', '0917-234-5678', 'Elena Cruz', 'Spouse', '0917-678-9012', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(3, 'Jennifer', 'Reyes', '1988-11-08', 'Female', 'Single', 'Filipino', '345-67-8901', '345678901', '0917-345-6789', 'Mark Reyes', 'Brother', '0917-789-0123', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(4, 'Antonio', 'Garcia', '1975-01-25', 'Male', 'Married', 'Filipino', '456-78-9012', '456789012', '0917-456-7890', 'Rosa Garcia', 'Spouse', '0917-890-1234', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(5, 'Lisa', 'Mendoza', '1982-09-14', 'Female', 'Divorced', 'Filipino', '567-89-0123', '567890123', '0917-567-8901', 'John Mendoza', 'Father', '0917-901-2345', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(6, 'Michael', 'Torres', '1980-06-03', 'Male', 'Married', 'Filipino', '678-90-1234', '678901234', '0917-678-9012', 'Anna Torres', 'Spouse', '0917-012-3456', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(7, 'Carmen', 'Dela Cruz', '1987-12-18', 'Female', 'Single', 'Filipino', '789-01-2345', '789012345', '0917-789-0123', 'Pedro Dela Cruz', 'Father', '0917-123-4567', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(8, 'Ricardo', 'Villanueva', '1970-04-07', 'Male', 'Married', 'Filipino', '890-12-3456', '890123456', '0917-890-1234', 'Diana Villanueva', 'Spouse', '0917-234-5678', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(9, 'Sandra', 'Pascual', '1984-08-29', 'Female', 'Married', 'Filipino', '901-23-4567', '901234567', '0917-901-2345', 'Luis Pascual', 'Spouse', '0917-345-6789', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(10, 'Jose', 'Ramos', '1972-05-15', 'Male', 'Married', 'Filipino', '012-34-5678', '012345678', '0917-012-3456', 'Teresa Ramos', 'Spouse', '0917-456-7890', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(11, 'Ana', 'Morales', '1986-10-30', 'Female', 'Single', 'Filipino', '123-56-7890', '123567890', '0917-135-7890', 'Maria Morales', 'Mother', '0917-579-0123', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(12, 'Pablo', 'Fernandez', '1979-02-22', 'Male', 'Married', 'Filipino', '234-67-8901', '234678901', '0917-246-7890', 'Carmen Fernandez', 'Spouse', '0917-680-1234', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(13, 'Grace', 'Lopez', '1983-09-07', 'Female', 'Married', 'Filipino', '345-78-9012', '345789012', '0917-357-8901', 'David Lopez', 'Spouse', '0917-791-2345', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(14, 'Eduardo', 'Hernandez', '1977-12-03', 'Male', 'Married', 'Filipino', '456-89-0123', '456890123', '0917-468-9012', 'Sofia Hernandez', 'Spouse', '0917-802-3456', '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(15, 'Rosario', 'Gonzales', '1989-06-28', 'Female', 'Single', 'Filipino', '567-90-1234', '567901234', '0917-579-0123', 'Miguel Gonzales', 'Father', '0917-913-4567', '2025-08-19 07:00:02', '2025-08-19 07:00:02');

-- --------------------------------------------------------

--
-- Table structure for table `post_exit_surveys`
--

CREATE TABLE `post_exit_surveys` (
  `survey_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `exit_id` int(11) NOT NULL,
  `survey_date` date NOT NULL,
  `survey_response` text DEFAULT NULL,
  `satisfaction_rating` int(11) DEFAULT NULL,
  `submitted_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `public_holidays`
--

CREATE TABLE `public_holidays` (
  `holiday_id` int(11) NOT NULL,
  `holiday_date` date NOT NULL,
  `holiday_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recruitment_analytics`
--

CREATE TABLE `recruitment_analytics` (
  `analytics_id` int(11) NOT NULL,
  `job_opening_id` int(11) NOT NULL,
  `total_applications` int(11) DEFAULT 0,
  `applications_per_day` decimal(5,2) DEFAULT 0.00,
  `average_processing_time` int(11) DEFAULT 0 COMMENT 'In days',
  `average_time_to_hire` int(11) DEFAULT 0 COMMENT 'In days',
  `offer_acceptance_rate` decimal(5,2) DEFAULT 0.00,
  `recruitment_source_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recruitment_source_breakdown`)),
  `cost_per_hire` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salary_structures`
--

CREATE TABLE `salary_structures` (
  `salary_structure_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `effective_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settlements`
--

CREATE TABLE `settlements` (
  `settlement_id` int(11) NOT NULL,
  `exit_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `last_working_day` date NOT NULL,
  `final_salary` decimal(10,2) NOT NULL,
  `severance_pay` decimal(10,2) DEFAULT 0.00,
  `unused_leave_payout` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `final_settlement_amount` decimal(10,2) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('Pending','Processing','Completed') DEFAULT 'Pending',
  `processed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shifts`
--

CREATE TABLE `shifts` (
  `shift_id` int(11) NOT NULL,
  `shift_name` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skill_matrix`
--

CREATE TABLE `skill_matrix` (
  `skill_id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `statutory_deductions`
--

CREATE TABLE `statutory_deductions` (
  `statutory_deduction_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `deduction_type` varchar(50) NOT NULL,
  `deduction_amount` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tax_deductions`
--

CREATE TABLE `tax_deductions` (
  `tax_deduction_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `tax_type` varchar(50) NOT NULL,
  `tax_percentage` decimal(5,2) DEFAULT NULL,
  `tax_amount` decimal(10,2) DEFAULT NULL,
  `effective_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `trainer_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `is_internal` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_courses`
--

CREATE TABLE `training_courses` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `delivery_method` enum('Online','Classroom','Workshop','Self-paced','Hybrid') NOT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Duration in hours',
  `max_participants` int(11) DEFAULT NULL,
  `prerequisites` text DEFAULT NULL,
  `materials_url` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive','In Development') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_enrollments`
--

CREATE TABLE `training_enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `enrollment_date` datetime DEFAULT current_timestamp(),
  `status` enum('Enrolled','Completed','Dropped','Failed','Waitlisted') DEFAULT 'Enrolled',
  `completion_date` date DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `certificate_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_needs_assessment`
--

CREATE TABLE `training_needs_assessment` (
  `assessment_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `assessment_date` date NOT NULL,
  `skills_gap` text DEFAULT NULL,
  `recommended_trainings` text DEFAULT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `status` enum('Identified','In Progress','Completed') DEFAULT 'Identified',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_sessions`
--

CREATE TABLE `training_sessions` (
  `session_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `session_name` varchar(255) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `capacity` int(11) NOT NULL,
  `cost_per_participant` decimal(10,2) DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','hr') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin123', 'admin@company.com', 'admin', 1, NULL, '2025-08-19 07:00:02', '2025-08-19 07:00:02'),
(2, 'hr_manager', 'hr123', 'hr@company.com', 'hr', 1, NULL, '2025-08-19 07:00:02', '2025-08-19 07:00:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `attendance_summary`
--
ALTER TABLE `attendance_summary`
  ADD PRIMARY KEY (`summary_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`,`month`,`year`);

--
-- Indexes for table `benefits_plans`
--
ALTER TABLE `benefits_plans`
  ADD PRIMARY KEY (`benefit_plan_id`);

--
-- Indexes for table `bonus_payments`
--
ALTER TABLE `bonus_payments`
  ADD PRIMARY KEY (`bonus_payment_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `payroll_cycle_id` (`payroll_cycle_id`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`candidate_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `career_paths`
--
ALTER TABLE `career_paths`
  ADD PRIMARY KEY (`path_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `career_path_stages`
--
ALTER TABLE `career_path_stages`
  ADD PRIMARY KEY (`stage_id`),
  ADD KEY `path_id` (`path_id`),
  ADD KEY `job_role_id` (`job_role_id`);

--
-- Indexes for table `compensation_packages`
--
ALTER TABLE `compensation_packages`
  ADD PRIMARY KEY (`compensation_package_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `competencies`
--
ALTER TABLE `competencies`
  ADD PRIMARY KEY (`competency_id`),
  ADD KEY `fk_job_role` (`job_role_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `development_activities`
--
ALTER TABLE `development_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `development_plans`
--
ALTER TABLE `development_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `document_management`
--
ALTER TABLE `document_management`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_benefits`
--
ALTER TABLE `employee_benefits`
  ADD PRIMARY KEY (`benefit_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `benefit_plan_id` (`benefit_plan_id`);

--
-- Indexes for table `employee_career_paths`
--
ALTER TABLE `employee_career_paths`
  ADD PRIMARY KEY (`employee_path_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `path_id` (`path_id`),
  ADD KEY `current_stage_id` (`current_stage_id`);

--
-- Indexes for table `employee_competencies`
--
ALTER TABLE `employee_competencies`
  ADD PRIMARY KEY (`employee_id`,`competency_id`,`assessment_date`),
  ADD KEY `competency_id` (`competency_id`);

--
-- Indexes for table `employee_onboarding`
--
ALTER TABLE `employee_onboarding`
  ADD PRIMARY KEY (`onboarding_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_onboarding_tasks`
--
ALTER TABLE `employee_onboarding_tasks`
  ADD PRIMARY KEY (`employee_task_id`),
  ADD KEY `onboarding_id` (`onboarding_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `employee_profiles`
--
ALTER TABLE `employee_profiles`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD UNIQUE KEY `personal_info_id` (`personal_info_id`),
  ADD UNIQUE KEY `work_email` (`work_email`),
  ADD UNIQUE KEY `employee_id` (`employee_id`,`personal_info_id`,`job_role_id`,`employee_number`),
  ADD KEY `job_role_id` (`job_role_id`);

--
-- Indexes for table `employee_resources`
--
ALTER TABLE `employee_resources`
  ADD PRIMARY KEY (`employee_resource_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `resource_id` (`resource_id`);

--
-- Indexes for table `employee_shifts`
--
ALTER TABLE `employee_shifts`
  ADD PRIMARY KEY (`employee_shift_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `shift_id` (`shift_id`);

--
-- Indexes for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD PRIMARY KEY (`employee_skill_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `employment_history`
--
ALTER TABLE `employment_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `job_role_id` (`job_role_id`);

--
-- Indexes for table `exits`
--
ALTER TABLE `exits`
  ADD PRIMARY KEY (`exit_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `exit_checklist`
--
ALTER TABLE `exit_checklist`
  ADD PRIMARY KEY (`checklist_id`),
  ADD KEY `exit_id` (`exit_id`);

--
-- Indexes for table `exit_documents`
--
ALTER TABLE `exit_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `exit_id` (`exit_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `exit_interviews`
--
ALTER TABLE `exit_interviews`
  ADD PRIMARY KEY (`interview_id`),
  ADD KEY `exit_id` (`exit_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `goals`
--
ALTER TABLE `goals`
  ADD PRIMARY KEY (`goal_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `goal_updates`
--
ALTER TABLE `goal_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `goal_id` (`goal_id`);

--
-- Indexes for table `interviews`
--
ALTER TABLE `interviews`
  ADD PRIMARY KEY (`interview_id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `stage_id` (`stage_id`);

--
-- Indexes for table `interview_stages`
--
ALTER TABLE `interview_stages`
  ADD PRIMARY KEY (`stage_id`),
  ADD KEY `job_opening_id` (`job_opening_id`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `job_opening_id` (`job_opening_id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indexes for table `job_offers`
--
ALTER TABLE `job_offers`
  ADD PRIMARY KEY (`offer_id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `job_opening_id` (`job_opening_id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indexes for table `job_openings`
--
ALTER TABLE `job_openings`
  ADD PRIMARY KEY (`job_opening_id`),
  ADD KEY `job_role_id` (`job_role_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `job_roles`
--
ALTER TABLE `job_roles`
  ADD PRIMARY KEY (`job_role_id`);

--
-- Indexes for table `knowledge_transfers`
--
ALTER TABLE `knowledge_transfers`
  ADD PRIMARY KEY (`transfer_id`),
  ADD KEY `exit_id` (`exit_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `learning_resources`
--
ALTER TABLE `learning_resources`
  ADD PRIMARY KEY (`resource_id`);

--
-- Indexes for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD PRIMARY KEY (`balance_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `leave_type_id` (`leave_type_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `leave_type_id` (`leave_type_id`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`leave_type_id`);

--
-- Indexes for table `onboarding_tasks`
--
ALTER TABLE `onboarding_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `payment_disbursements`
--
ALTER TABLE `payment_disbursements`
  ADD PRIMARY KEY (`payment_disbursement_id`),
  ADD KEY `payroll_transaction_id` (`payroll_transaction_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `payroll_cycles`
--
ALTER TABLE `payroll_cycles`
  ADD PRIMARY KEY (`payroll_cycle_id`);

--
-- Indexes for table `payroll_transactions`
--
ALTER TABLE `payroll_transactions`
  ADD PRIMARY KEY (`payroll_transaction_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `payroll_cycle_id` (`payroll_cycle_id`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`payslip_id`),
  ADD KEY `payroll_transaction_id` (`payroll_transaction_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `performance_metrics`
--
ALTER TABLE `performance_metrics`
  ADD PRIMARY KEY (`metric_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `cycle_id` (`cycle_id`);

--
-- Indexes for table `performance_review_cycles`
--
ALTER TABLE `performance_review_cycles`
  ADD PRIMARY KEY (`cycle_id`);

--
-- Indexes for table `personal_information`
--
ALTER TABLE `personal_information`
  ADD PRIMARY KEY (`personal_info_id`);

--
-- Indexes for table `post_exit_surveys`
--
ALTER TABLE `post_exit_surveys`
  ADD PRIMARY KEY (`survey_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `exit_id` (`exit_id`);

--
-- Indexes for table `public_holidays`
--
ALTER TABLE `public_holidays`
  ADD PRIMARY KEY (`holiday_id`),
  ADD UNIQUE KEY `holiday_date` (`holiday_date`);

--
-- Indexes for table `recruitment_analytics`
--
ALTER TABLE `recruitment_analytics`
  ADD PRIMARY KEY (`analytics_id`),
  ADD KEY `job_opening_id` (`job_opening_id`);

--
-- Indexes for table `salary_structures`
--
ALTER TABLE `salary_structures`
  ADD PRIMARY KEY (`salary_structure_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `settlements`
--
ALTER TABLE `settlements`
  ADD PRIMARY KEY (`settlement_id`),
  ADD KEY `exit_id` (`exit_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `shifts`
--
ALTER TABLE `shifts`
  ADD PRIMARY KEY (`shift_id`);

--
-- Indexes for table `skill_matrix`
--
ALTER TABLE `skill_matrix`
  ADD PRIMARY KEY (`skill_id`);

--
-- Indexes for table `statutory_deductions`
--
ALTER TABLE `statutory_deductions`
  ADD PRIMARY KEY (`statutory_deduction_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `tax_deductions`
--
ALTER TABLE `tax_deductions`
  ADD PRIMARY KEY (`tax_deduction_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`trainer_id`);

--
-- Indexes for table `training_courses`
--
ALTER TABLE `training_courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `training_enrollments`
--
ALTER TABLE `training_enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `training_needs_assessment`
--
ALTER TABLE `training_needs_assessment`
  ADD PRIMARY KEY (`assessment_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `training_sessions`
--
ALTER TABLE `training_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_summary`
--
ALTER TABLE `attendance_summary`
  MODIFY `summary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `benefits_plans`
--
ALTER TABLE `benefits_plans`
  MODIFY `benefit_plan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bonus_payments`
--
ALTER TABLE `bonus_payments`
  MODIFY `bonus_payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `candidate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `career_paths`
--
ALTER TABLE `career_paths`
  MODIFY `path_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `career_path_stages`
--
ALTER TABLE `career_path_stages`
  MODIFY `stage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compensation_packages`
--
ALTER TABLE `compensation_packages`
  MODIFY `compensation_package_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `competencies`
--
ALTER TABLE `competencies`
  MODIFY `competency_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `development_activities`
--
ALTER TABLE `development_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `development_plans`
--
ALTER TABLE `development_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_management`
--
ALTER TABLE `document_management`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `employee_benefits`
--
ALTER TABLE `employee_benefits`
  MODIFY `benefit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_career_paths`
--
ALTER TABLE `employee_career_paths`
  MODIFY `employee_path_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_onboarding`
--
ALTER TABLE `employee_onboarding`
  MODIFY `onboarding_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_onboarding_tasks`
--
ALTER TABLE `employee_onboarding_tasks`
  MODIFY `employee_task_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_profiles`
--
ALTER TABLE `employee_profiles`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `employee_resources`
--
ALTER TABLE `employee_resources`
  MODIFY `employee_resource_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_shifts`
--
ALTER TABLE `employee_shifts`
  MODIFY `employee_shift_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_skills`
--
ALTER TABLE `employee_skills`
  MODIFY `employee_skill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_history`
--
ALTER TABLE `employment_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `exits`
--
ALTER TABLE `exits`
  MODIFY `exit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_checklist`
--
ALTER TABLE `exit_checklist`
  MODIFY `checklist_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_documents`
--
ALTER TABLE `exit_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exit_interviews`
--
ALTER TABLE `exit_interviews`
  MODIFY `interview_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goals`
--
ALTER TABLE `goals`
  MODIFY `goal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goal_updates`
--
ALTER TABLE `goal_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interviews`
--
ALTER TABLE `interviews`
  MODIFY `interview_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interview_stages`
--
ALTER TABLE `interview_stages`
  MODIFY `stage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_offers`
--
ALTER TABLE `job_offers`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_openings`
--
ALTER TABLE `job_openings`
  MODIFY `job_opening_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_roles`
--
ALTER TABLE `job_roles`
  MODIFY `job_role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `knowledge_transfers`
--
ALTER TABLE `knowledge_transfers`
  MODIFY `transfer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learning_resources`
--
ALTER TABLE `learning_resources`
  MODIFY `resource_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_balances`
--
ALTER TABLE `leave_balances`
  MODIFY `balance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `leave_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `onboarding_tasks`
--
ALTER TABLE `onboarding_tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_disbursements`
--
ALTER TABLE `payment_disbursements`
  MODIFY `payment_disbursement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_cycles`
--
ALTER TABLE `payroll_cycles`
  MODIFY `payroll_cycle_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_transactions`
--
ALTER TABLE `payroll_transactions`
  MODIFY `payroll_transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `payslip_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_metrics`
--
ALTER TABLE `performance_metrics`
  MODIFY `metric_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_review_cycles`
--
ALTER TABLE `performance_review_cycles`
  MODIFY `cycle_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_information`
--
ALTER TABLE `personal_information`
  MODIFY `personal_info_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `post_exit_surveys`
--
ALTER TABLE `post_exit_surveys`
  MODIFY `survey_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `public_holidays`
--
ALTER TABLE `public_holidays`
  MODIFY `holiday_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recruitment_analytics`
--
ALTER TABLE `recruitment_analytics`
  MODIFY `analytics_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salary_structures`
--
ALTER TABLE `salary_structures`
  MODIFY `salary_structure_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settlements`
--
ALTER TABLE `settlements`
  MODIFY `settlement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shifts`
--
ALTER TABLE `shifts`
  MODIFY `shift_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skill_matrix`
--
ALTER TABLE `skill_matrix`
  MODIFY `skill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `statutory_deductions`
--
ALTER TABLE `statutory_deductions`
  MODIFY `statutory_deduction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tax_deductions`
--
ALTER TABLE `tax_deductions`
  MODIFY `tax_deduction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `trainer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_courses`
--
ALTER TABLE `training_courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_enrollments`
--
ALTER TABLE `training_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_needs_assessment`
--
ALTER TABLE `training_needs_assessment`
  MODIFY `assessment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_sessions`
--
ALTER TABLE `training_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_summary`
--
ALTER TABLE `attendance_summary`
  ADD CONSTRAINT `attendance_summary_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `bonus_payments`
--
ALTER TABLE `bonus_payments`
  ADD CONSTRAINT `bonus_payments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bonus_payments_ibfk_2` FOREIGN KEY (`payroll_cycle_id`) REFERENCES `payroll_cycles` (`payroll_cycle_id`) ON DELETE SET NULL;

--
-- Constraints for table `career_paths`
--
ALTER TABLE `career_paths`
  ADD CONSTRAINT `career_paths_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL;

--
-- Constraints for table `career_path_stages`
--
ALTER TABLE `career_path_stages`
  ADD CONSTRAINT `career_path_stages_ibfk_1` FOREIGN KEY (`path_id`) REFERENCES `career_paths` (`path_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `career_path_stages_ibfk_2` FOREIGN KEY (`job_role_id`) REFERENCES `job_roles` (`job_role_id`) ON DELETE CASCADE;

--
-- Constraints for table `compensation_packages`
--
ALTER TABLE `compensation_packages`
  ADD CONSTRAINT `compensation_packages_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `competencies`
--
ALTER TABLE `competencies`
  ADD CONSTRAINT `fk_job_role` FOREIGN KEY (`job_role_id`) REFERENCES `job_roles` (`job_role_id`);

--
-- Constraints for table `development_activities`
--
ALTER TABLE `development_activities`
  ADD CONSTRAINT `development_activities_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `development_plans` (`plan_id`) ON DELETE CASCADE;

--
-- Constraints for table `development_plans`
--
ALTER TABLE `development_plans`
  ADD CONSTRAINT `development_plans_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `document_management`
--
ALTER TABLE `document_management`
  ADD CONSTRAINT `document_management_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_benefits`
--
ALTER TABLE `employee_benefits`
  ADD CONSTRAINT `employee_benefits_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_benefits_ibfk_2` FOREIGN KEY (`benefit_plan_id`) REFERENCES `benefits_plans` (`benefit_plan_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_career_paths`
--
ALTER TABLE `employee_career_paths`
  ADD CONSTRAINT `employee_career_paths_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_career_paths_ibfk_2` FOREIGN KEY (`path_id`) REFERENCES `career_paths` (`path_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_career_paths_ibfk_3` FOREIGN KEY (`current_stage_id`) REFERENCES `career_path_stages` (`stage_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_competencies`
--
ALTER TABLE `employee_competencies`
  ADD CONSTRAINT `employee_competencies_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_competencies_ibfk_2` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`competency_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_onboarding`
--
ALTER TABLE `employee_onboarding`
  ADD CONSTRAINT `employee_onboarding_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_onboarding_tasks`
--
ALTER TABLE `employee_onboarding_tasks`
  ADD CONSTRAINT `employee_onboarding_tasks_ibfk_1` FOREIGN KEY (`onboarding_id`) REFERENCES `employee_onboarding` (`onboarding_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_onboarding_tasks_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `onboarding_tasks` (`task_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_profiles`
--
ALTER TABLE `employee_profiles`
  ADD CONSTRAINT `employee_profiles_ibfk_1` FOREIGN KEY (`personal_info_id`) REFERENCES `personal_information` (`personal_info_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_profiles_ibfk_2` FOREIGN KEY (`job_role_id`) REFERENCES `job_roles` (`job_role_id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_resources`
--
ALTER TABLE `employee_resources`
  ADD CONSTRAINT `employee_resources_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_resources_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `learning_resources` (`resource_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_shifts`
--
ALTER TABLE `employee_shifts`
  ADD CONSTRAINT `employee_shifts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_shifts_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`shift_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_skills`
--
ALTER TABLE `employee_skills`
  ADD CONSTRAINT `employee_skills_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skill_matrix` (`skill_id`) ON DELETE CASCADE;

--
-- Constraints for table `employment_history`
--
ALTER TABLE `employment_history`
  ADD CONSTRAINT `employment_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employment_history_ibfk_2` FOREIGN KEY (`job_role_id`) REFERENCES `job_roles` (`job_role_id`) ON DELETE SET NULL;

--
-- Constraints for table `exits`
--
ALTER TABLE `exits`
  ADD CONSTRAINT `exits_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `exit_checklist`
--
ALTER TABLE `exit_checklist`
  ADD CONSTRAINT `exit_checklist_ibfk_1` FOREIGN KEY (`exit_id`) REFERENCES `exits` (`exit_id`) ON DELETE CASCADE;

--
-- Constraints for table `exit_documents`
--
ALTER TABLE `exit_documents`
  ADD CONSTRAINT `exit_documents_ibfk_1` FOREIGN KEY (`exit_id`) REFERENCES `exits` (`exit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exit_documents_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `exit_interviews`
--
ALTER TABLE `exit_interviews`
  ADD CONSTRAINT `exit_interviews_ibfk_1` FOREIGN KEY (`exit_id`) REFERENCES `exits` (`exit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exit_interviews_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `goals`
--
ALTER TABLE `goals`
  ADD CONSTRAINT `goals_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `goal_updates`
--
ALTER TABLE `goal_updates`
  ADD CONSTRAINT `goal_updates_ibfk_1` FOREIGN KEY (`goal_id`) REFERENCES `goals` (`goal_id`) ON DELETE CASCADE;

--
-- Constraints for table `interviews`
--
ALTER TABLE `interviews`
  ADD CONSTRAINT `interviews_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interviews_ibfk_2` FOREIGN KEY (`stage_id`) REFERENCES `interview_stages` (`stage_id`) ON DELETE CASCADE;

--
-- Constraints for table `interview_stages`
--
ALTER TABLE `interview_stages`
  ADD CONSTRAINT `interview_stages_ibfk_1` FOREIGN KEY (`job_opening_id`) REFERENCES `job_openings` (`job_opening_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_opening_id`) REFERENCES `job_openings` (`job_opening_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`candidate_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_offers`
--
ALTER TABLE `job_offers`
  ADD CONSTRAINT `job_offers_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `job_applications` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_offers_ibfk_2` FOREIGN KEY (`job_opening_id`) REFERENCES `job_openings` (`job_opening_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_offers_ibfk_3` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`candidate_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_openings`
--
ALTER TABLE `job_openings`
  ADD CONSTRAINT `job_openings_ibfk_1` FOREIGN KEY (`job_role_id`) REFERENCES `job_roles` (`job_role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_openings_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE;

--
-- Constraints for table `knowledge_transfers`
--
ALTER TABLE `knowledge_transfers`
  ADD CONSTRAINT `knowledge_transfers_ibfk_1` FOREIGN KEY (`exit_id`) REFERENCES `exits` (`exit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `knowledge_transfers_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD CONSTRAINT `leave_balances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_balances_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`leave_type_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`leave_type_id`) ON DELETE CASCADE;

--
-- Constraints for table `onboarding_tasks`
--
ALTER TABLE `onboarding_tasks`
  ADD CONSTRAINT `onboarding_tasks_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_disbursements`
--
ALTER TABLE `payment_disbursements`
  ADD CONSTRAINT `payment_disbursements_ibfk_1` FOREIGN KEY (`payroll_transaction_id`) REFERENCES `payroll_transactions` (`payroll_transaction_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_disbursements_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_transactions`
--
ALTER TABLE `payroll_transactions`
  ADD CONSTRAINT `payroll_transactions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_transactions_ibfk_2` FOREIGN KEY (`payroll_cycle_id`) REFERENCES `payroll_cycles` (`payroll_cycle_id`) ON DELETE CASCADE;

--
-- Constraints for table `payslips`
--
ALTER TABLE `payslips`
  ADD CONSTRAINT `payslips_ibfk_1` FOREIGN KEY (`payroll_transaction_id`) REFERENCES `payroll_transactions` (`payroll_transaction_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payslips_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `performance_metrics`
--
ALTER TABLE `performance_metrics`
  ADD CONSTRAINT `performance_metrics_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  ADD CONSTRAINT `performance_reviews_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `performance_reviews_ibfk_2` FOREIGN KEY (`cycle_id`) REFERENCES `performance_review_cycles` (`cycle_id`) ON DELETE CASCADE;

--
-- Constraints for table `post_exit_surveys`
--
ALTER TABLE `post_exit_surveys`
  ADD CONSTRAINT `post_exit_surveys_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_exit_surveys_ibfk_2` FOREIGN KEY (`exit_id`) REFERENCES `exits` (`exit_id`) ON DELETE CASCADE;

--
-- Constraints for table `recruitment_analytics`
--
ALTER TABLE `recruitment_analytics`
  ADD CONSTRAINT `recruitment_analytics_ibfk_1` FOREIGN KEY (`job_opening_id`) REFERENCES `job_openings` (`job_opening_id`) ON DELETE CASCADE;

--
-- Constraints for table `salary_structures`
--
ALTER TABLE `salary_structures`
  ADD CONSTRAINT `salary_structures_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `settlements`
--
ALTER TABLE `settlements`
  ADD CONSTRAINT `settlements_ibfk_1` FOREIGN KEY (`exit_id`) REFERENCES `exits` (`exit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `settlements_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `statutory_deductions`
--
ALTER TABLE `statutory_deductions`
  ADD CONSTRAINT `statutory_deductions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `tax_deductions`
--
ALTER TABLE `tax_deductions`
  ADD CONSTRAINT `tax_deductions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `training_enrollments`
--
ALTER TABLE `training_enrollments`
  ADD CONSTRAINT `training_enrollments_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `training_sessions` (`session_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `training_enrollments_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `training_needs_assessment`
--
ALTER TABLE `training_needs_assessment`
  ADD CONSTRAINT `training_needs_assessment_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee_profiles` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `training_sessions`
--
ALTER TABLE `training_sessions`
  ADD CONSTRAINT `training_sessions_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `training_courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `training_sessions_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
