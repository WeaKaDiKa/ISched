CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    patient_name VARCHAR(255) NOT NULL,
    rating INT NOT NULL,
    comment TEXT,
    services VARCHAR(255),
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_seen TINYINT(1) DEFAULT 0,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);
