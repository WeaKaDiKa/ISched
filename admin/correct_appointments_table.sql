-- This is the correct SQL for the appointments table

-- First, backup your existing table if you have one
-- CREATE TABLE appointments_backup AS SELECT * FROM appointments;

-- Drop the existing table if needed
-- DROP TABLE IF EXISTS appointments;

-- Create the appointments table with the correct structure
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `services` varchar(255) DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `reference_number` varchar(50) DEFAULT NULL,
  `clinic_branch` varchar(100) DEFAULT 'Maligaya Park Branch',
  `consent` tinyint(1) DEFAULT 1,
  `blood_type` varchar(10) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add this index if you want to search by reference number quickly
ALTER TABLE `appointments` ADD INDEX `idx_reference_number` (`reference_number`);

-- Add this index if you want to filter by status quickly
ALTER TABLE `appointments` ADD INDEX `idx_status` (`status`);

-- If you need to add the foreign key constraint (only if patients table exists)
-- ALTER TABLE `appointments` ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL;

-- Create a stored procedure to generate reference numbers (safer than triggers)
DELIMITER $$
CREATE PROCEDURE `generate_appointment_reference`(IN appointment_id INT)
BEGIN
    UPDATE appointments 
    SET reference_number = CONCAT('APT-', YEAR(CURRENT_DATE()), '-', LPAD(appointment_id, 5, '0'))
    WHERE id = appointment_id AND (reference_number IS NULL OR reference_number = '');
END$$
DELIMITER ;

-- Example of how to use the procedure after inserting a new appointment:
-- INSERT INTO appointments (patient_id, services, appointment_date, appointment_time) VALUES (1, 'Dental Check-up', '2025-06-01', '09:00:00');
-- CALL generate_appointment_reference(LAST_INSERT_ID());
