-- ============================================================
-- Event Registration Manager - Database Schema
-- Drupal 10 Custom Module
-- ============================================================
-- This SQL file creates the custom database tables required
-- by the Event Registration Manager module.
-- 
-- Note: In a standard Drupal installation, these tables are
-- created automatically when the module is enabled via the
-- hook_schema() implementation in the .install file.
-- ============================================================

-- Drop tables if they exist (for clean reinstall)
DROP TABLE IF EXISTS `event_registration`;
DROP TABLE IF EXISTS `event_config`;

-- ============================================================
-- Table: event_config
-- Description: Stores event configuration data
-- ============================================================
CREATE TABLE `event_config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key: Unique event ID.',
  `event_name` VARCHAR(255) NOT NULL COMMENT 'Name of the event.',
  `category` VARCHAR(100) NOT NULL COMMENT 'Category of the event.',
  `event_date` VARCHAR(20) NOT NULL COMMENT 'Date of the event (YYYY-MM-DD).',
  `registration_start_date` VARCHAR(20) NOT NULL COMMENT 'Registration start date (YYYY-MM-DD).',
  `registration_end_date` VARCHAR(20) NOT NULL COMMENT 'Registration end date (YYYY-MM-DD).',
  `created` INT NOT NULL DEFAULT 0 COMMENT 'Unix timestamp when the event was created.',
  `changed` INT NOT NULL DEFAULT 0 COMMENT 'Unix timestamp when the event was last modified.',
  PRIMARY KEY (`id`),
  INDEX `idx_category` (`category`),
  INDEX `idx_event_date` (`event_date`),
  INDEX `idx_registration_dates` (`registration_start_date`, `registration_end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores event configuration data.';

-- ============================================================
-- Table: event_registration
-- Description: Stores event registration data
-- ============================================================
CREATE TABLE `event_registration` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key: Unique registration ID.',
  `full_name` VARCHAR(255) NOT NULL COMMENT 'Full name of the registrant.',
  `email` VARCHAR(255) NOT NULL COMMENT 'Email address of the registrant.',
  `college_name` VARCHAR(255) NOT NULL COMMENT 'College name of the registrant.',
  `department` VARCHAR(255) NOT NULL COMMENT 'Department of the registrant.',
  `event_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to event_config table.',
  `category` VARCHAR(100) NOT NULL COMMENT 'Category of the event.',
  `event_date` VARCHAR(20) NOT NULL COMMENT 'Date of the event (YYYY-MM-DD).',
  `event_name` VARCHAR(255) NOT NULL COMMENT 'Name of the event.',
  `created` INT NOT NULL DEFAULT 0 COMMENT 'Unix timestamp when registration was created.',
  PRIMARY KEY (`id`),
  INDEX `idx_event_id` (`event_id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_event_date` (`event_date`),
  INDEX `idx_email_event_date` (`email`, `event_date`),
  CONSTRAINT `fk_event_config` FOREIGN KEY (`event_id`) 
    REFERENCES `event_config` (`id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores event registration data.';

-- ============================================================
-- Sample Data (Optional - for testing purposes)
-- ============================================================

-- Insert sample events
INSERT INTO `event_config` 
  (`event_name`, `category`, `event_date`, `registration_start_date`, `registration_end_date`, `created`, `changed`) 
VALUES
  ('Introduction to Web Development', 'online_workshop', '2026-03-15', '2026-02-01', '2026-03-10', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('Spring Hackathon 2026', 'hackathon', '2026-04-20', '2026-02-01', '2026-04-15', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('Tech Conference 2026', 'conference', '2026-05-10', '2026-02-01', '2026-05-05', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('Python Bootcamp', 'one_day_workshop', '2026-03-25', '2026-02-01', '2026-03-20', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('Advanced JavaScript Workshop', 'online_workshop', '2026-03-15', '2026-02-01', '2026-03-10', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('AI & ML Hackathon', 'hackathon', '2026-04-20', '2026-02-01', '2026-04-15', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Insert sample registrations
INSERT INTO `event_registration`
  (`full_name`, `email`, `college_name`, `department`, `event_id`, `category`, `event_date`, `event_name`, `created`)
VALUES
  ('John Doe', 'john.doe@example.com', 'City University', 'Computer Science', 1, 'online_workshop', '2026-03-15', 'Introduction to Web Development', UNIX_TIMESTAMP()),
  ('Jane Smith', 'jane.smith@example.com', 'State College', 'Information Technology', 2, 'hackathon', '2026-04-20', 'Spring Hackathon 2026', UNIX_TIMESTAMP()),
  ('Bob Johnson', 'bob.johnson@example.com', 'Tech Institute', 'Software Engineering', 3, 'conference', '2026-05-10', 'Tech Conference 2026', UNIX_TIMESTAMP());

-- ============================================================
-- Verification Queries
-- ============================================================

-- Verify tables were created
-- SELECT TABLE_NAME, TABLE_COMMENT FROM INFORMATION_SCHEMA.TABLES 
-- WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'event_%';

-- Count events
-- SELECT COUNT(*) as event_count FROM event_config;

-- Count registrations
-- SELECT COUNT(*) as registration_count FROM event_registration;
