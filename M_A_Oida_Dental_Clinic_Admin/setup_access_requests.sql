-- Create access_requests table
CREATE TABLE IF NOT EXISTS access_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    role_requested VARCHAR(50) NOT NULL,
    status ENUM('Pending', 'Approved', 'Denied') DEFAULT 'Pending',
    submitted_on DATETIME DEFAULT CURRENT_TIMESTAMP,
    submitted_by INT DEFAULT NULL
);
