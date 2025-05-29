-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender_id INT,
    sender_name VARCHAR(100),
    sender_photo VARCHAR(255),
    message VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    reference_id INT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES patients(id) ON DELETE CASCADE
);
