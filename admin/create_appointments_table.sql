-- Drop the existing table if needed (be careful with this in production)
-- DROP TABLE IF EXISTS appointments;

-- Create the appointments table with proper structure
CREATE TABLE appointments (
  id int(11) NOT NULL AUTO_INCREMENT,
  patient_id int(11) DEFAULT NULL,
  services varchar(255) DEFAULT NULL,
  appointment_date date DEFAULT NULL,
  appointment_time time DEFAULT NULL,
  status varchar(50) DEFAULT 'pending',
  reference_number varchar(50) DEFAULT NULL,
  clinic_branch varchar(100) DEFAULT 'Maligaya Park Branch',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  parent_appointment_id int(11) DEFAULT NULL,
  notes text DEFAULT NULL,
  PRIMARY KEY (id),
  KEY patient_id (patient_id),
  CONSTRAINT appointments_ibfk_1 FOREIGN KEY (patient_id) REFERENCES patients (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create a trigger to automatically generate reference numbers
-- Note: You'll need to run this separately in phpMyAdmin
/*
DELIMITER //
CREATE TRIGGER before_appointment_insert
BEFORE INSERT ON appointments
FOR EACH ROW
BEGIN
  IF NEW.reference_number IS NULL THEN
    SET NEW.reference_number = CONCAT('APT-', YEAR(CURRENT_DATE()), '-', LPAD(LAST_INSERT_ID() + 1, 5, '0'));
  END IF;
END//
DELIMITER ;
*/
