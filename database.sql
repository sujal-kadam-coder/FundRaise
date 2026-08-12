-- ============================================================
-- Crowdfunding Platform — Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS crowdfund_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crowdfund_platform;

-- NOTE: If you already ran this file before today's update, just run these
-- lines to add the new columns/tables without losing your existing data:
-- ALTER TABLE users ADD COLUMN role ENUM('Donor','Creator') NOT NULL DEFAULT 'Donor' AFTER phone;
-- ALTER TABLE campaigns ADD COLUMN cover_image VARCHAR(255) DEFAULT NULL AFTER cover_color;

-- ------------------------------------------------------------
-- Table: users  (Donors & Campaign Creators — same account type,
-- any logged-in user can both back and start campaigns)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    role ENUM('Donor','Creator') NOT NULL DEFAULT 'Donor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Table: admins  (Admin Panel login)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin -> username: admin | password: admin123
INSERT INTO admins (username, password) VALUES
('admin', '$2b$10$mgpcq6dkFSFP4FpsHlaYo.AN2oIeWwRj75YcHz40IEjQfSlYprA1W')
ON DUPLICATE KEY UPDATE username = username;

-- ------------------------------------------------------------
-- Table: categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(10) DEFAULT '💡'
);

INSERT INTO categories (name, icon) VALUES
('Medical', '🏥'),
('Education', '🎓'),
('Creative', '🎨'),
('Community', '🤝'),
('Startup', '🚀'),
('Emergency Relief', '🆘')
ON DUPLICATE KEY UPDATE name = name;

-- ------------------------------------------------------------
-- Table: campaigns
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    short_description VARCHAR(255) NOT NULL,
    story TEXT NOT NULL,
    goal_amount DECIMAL(12,2) NOT NULL,
    raised_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    deadline DATE NOT NULL,
    cover_color VARCHAR(20) DEFAULT 'teal',
    cover_image VARCHAR(255) DEFAULT NULL,
    status ENUM('Pending','Approved','Rejected','Closed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- ------------------------------------------------------------
-- Table: donations
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    message VARCHAR(255),
    is_anonymous TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Table: campaign_updates
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS campaign_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Table: campaign_comments  (Q&A on campaign pages)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS campaign_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- Table: contact_messages  (Contact Us form)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Sample data so the platform isn't empty on first run
-- ------------------------------------------------------------
INSERT INTO users (full_name, email, password, phone, role) VALUES
('Meera Joshi', 'meera.joshi@example.com', '$2b$10$mgpcq6dkFSFP4FpsHlaYo.AN2oIeWwRj75YcHz40IEjQfSlYprA1W', '9876500011', 'Creator'),
('Arjun Deshmukh', 'arjun.d@example.com', '$2b$10$mgpcq6dkFSFP4FpsHlaYo.AN2oIeWwRj75YcHz40IEjQfSlYprA1W', '9876500022', 'Donor');
-- Demo password for both sample users: admin123

INSERT INTO campaigns (user_id, category_id, title, short_description, story, goal_amount, raised_amount, deadline, cover_color, status) VALUES
(1, 1, 'Help Meera Complete Her Kidney Treatment', 'Supporting a 34-year-old teacher through a critical kidney transplant.', 'Meera has been battling kidney failure for the past year and urgently needs a transplant. Your support will help cover surgery, hospital stay, and post-operative medication costs. Every contribution brings her closer to a healthy life.', 500000, 318500, '2026-09-15', 'coral', 'Approved'),
(2, 4, 'Rebuild Our Village Community Hall', 'Restoring a flood-damaged community hall that serves 40 families.', 'Last monsoon\'s floods destroyed the roof and foundation of our village community hall — the only shared space for meetings, weddings, and school functions. We\'re raising funds to rebuild it before the next monsoon season.', 300000, 142000, '2026-10-01', 'teal', 'Approved'),
(1, 5, 'Launch EcoPack — Biodegradable Packaging Startup', 'A student-led startup building affordable biodegradable packaging for small vendors.', 'EcoPack is developing low-cost biodegradable packaging made from agricultural waste, aimed at small food vendors who currently rely on plastic. We need funds for our first production run and lab certification.', 800000, 96000, '2026-11-20', 'amber', 'Approved'),
(2, 2, 'Scholarship Fund for First-Generation College Students', 'Helping 10 first-generation students afford their first year of college.', 'We\'re raising funds to provide scholarships covering tuition and books for 10 first-generation college students from underserved communities in Maharashtra.', 400000, 400000, '2026-08-01', 'teal', 'Approved');

INSERT INTO donations (campaign_id, user_id, amount, message, is_anonymous) VALUES
(1, 2, 5000, 'Wishing you a speedy recovery, Meera!', 0),
(1, 1, 2000, '', 1),
(2, 1, 10000, 'Happy to help rebuild the hall.', 0),
(4, 2, 15000, 'Education changes everything. Good luck!', 0);

INSERT INTO campaign_updates (campaign_id, title, content) VALUES
(1, 'Surgery date confirmed', 'Thanks to your generosity, we have scheduled the transplant for next month. Thank you for your continued support!'),
(2, 'Foundation work has begun', 'Contractors started laying the new foundation this week. Photos to follow in the next update.');

INSERT INTO campaign_comments (campaign_id, user_id, comment) VALUES
(1, 2, 'Sending strength to you and your family. Is the hospital covering any part of the cost?'),
(2, 1, 'Will the new hall have a proper drainage system this time to avoid the same damage?');
