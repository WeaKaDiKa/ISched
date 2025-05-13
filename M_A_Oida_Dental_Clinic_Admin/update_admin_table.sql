-- Add default profile columns to admin_logins table
ALTER TABLE admin_logins
ADD COLUMN IF NOT EXISTS first_name VARCHAR(50) DEFAULT 'Admin',
ADD COLUMN IF NOT EXISTS last_name VARCHAR(50) DEFAULT 'User',
ADD COLUMN IF NOT EXISTS name VARCHAR(100) DEFAULT 'Admin User',
ADD COLUMN IF NOT EXISTS age INT DEFAULT 25,
ADD COLUMN IF NOT EXISTS mobile VARCHAR(20) DEFAULT '+639123456789',
ADD COLUMN IF NOT EXISTS email VARCHAR(100) DEFAULT 'admin@example.com',
ADD COLUMN IF NOT EXISTS gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other',
ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT 'assets/photo/default_avatar.png';
