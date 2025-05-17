DROP TABLE IF EXISTS admin_logins;
CREATE TABLE admin_logins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    age INT,
    mobile VARCHAR(20),
    gender ENUM('Male', 'Female'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admin_logins (admin_id, email, password, name, first_name, last_name, age, mobile, gender)
VALUES ('ADM-001', 'marcgermineganan05@gmail.com', '$2y$10$YQvJXkGq8h4qKzj.2X9Ziu0Q9.8tG3/.4NF3ZvwwKvtqMhMP.JVTW', 'Marc Germine Ganan', 'Marc', 'Ganan', 25, '1234567890', 'Male'); 