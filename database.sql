-- database.sql
-- MAYASE Database – Algerian Talent Mediation Platform
-- Version 1.0

SET FOREIGN_KEY_CHECKS=0;
DROP DATABASE IF EXISTS mayase_db;
CREATE DATABASE mayase_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mayase_db;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','agent','client','talent') NOT NULL DEFAULT 'talent',
    status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    first_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    wilaya_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    remember_token VARCHAR(100) DEFAULT NULL,
    agent_commission DECIMAL(5,2) DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: wilayas
-- --------------------------------------------------------
CREATE TABLE wilayas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

INSERT INTO wilayas (id, name) VALUES
(1, 'Adrar'),(2, 'Chlef'),(3, 'Laghouat'),(4, 'Oum El Bouaghi'),(5, 'Batna'),
(6, 'Béjaïa'),(7, 'Biskra'),(8, 'Béchar'),(9, 'Blida'),(10, 'Bouira'),
(11, 'Tamanrasset'),(12, 'Tébessa'),(13, 'Tlemcen'),(14, 'Tiaret'),(15, 'Tizi Ouzou'),
(16, 'Alger'),(17, 'Djelfa'),(18, 'Jijel'),(19, 'Sétif'),(20, 'Saïda'),
(21, 'Skikda'),(22, 'Sidi Bel Abbès'),(23, 'Annaba'),(24, 'Guelma'),(25, 'Constantine'),
(26, 'Médéa'),(27, 'Mostaganem'),(28, 'M''Sila'),(29, 'Mascara'),(30, 'Ouargla'),
(31, 'Oran'),(32, 'El Bayadh'),(33, 'Illizi'),(34, 'Bordj Bou Arréridj'),(35, 'Boumerdès'),
(36, 'El Tarf'),(37, 'Tindouf'),(38, 'Tissemsilt'),(39, 'El Oued'),(40, 'Khenchela'),
(41, 'Souk Ahras'),(42, 'Tipaza'),(43, 'Mila'),(44, 'Aïn Defla'),(45, 'Naâma'),
(46, 'Aïn Témouchent'),(47, 'Ghardaïa'),(48, 'Relizane'),(49, 'Timimoun'),(50, 'Bordj Badji Mokhtar'),
(51, 'Ouled Djellal'),(52, 'Béni Abbès'),(53, 'In Salah'),(54, 'In Guezzam'),(55, 'Touggourt'),
(56, 'Djanet'),(57, 'El Meghaier'),(58, 'El Meniaa');

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO categories (id, name) VALUES
(1, 'Actor'),(2, 'Screenwriter'),(3, 'Photographer'),(4, 'Video Editor'),
(5, 'Graphic Designer'),(6, 'Makeup Artist'),(7, 'Fashion Stylist');

-- --------------------------------------------------------
-- Table: talents (additional info for role=talent)
-- --------------------------------------------------------
CREATE TABLE talents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    talent_code VARCHAR(20) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    skills TEXT,
    experience TEXT,
    availability ENUM('available','busy','not_available') DEFAULT 'available',
    portfolio_visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: clients (additional info for role=client)
-- --------------------------------------------------------
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(255),
    description TEXT,
    subscription_status ENUM('trial','active','expired','cancelled') DEFAULT 'trial',
    trial_ends_at DATETIME DEFAULT NULL,
    subscription_ends_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: subscription_plans
-- --------------------------------------------------------
CREATE TABLE subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration_months INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO subscription_plans (name, price, duration_months) VALUES
('Mensuel', 5000.00, 1),
('Trimestriel', 13500.00, 3),
('Annuel', 48000.00, 12);

-- --------------------------------------------------------
-- Table: payments
-- --------------------------------------------------------
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    user_id INT NOT NULL COMMENT 'who made payment (admin/agent)',
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    subscription_start DATETIME,
    subscription_end DATETIME,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: projects
-- --------------------------------------------------------
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    wilaya_id INT DEFAULT NULL,
    budget DECIMAL(10,2) DEFAULT NULL,
    deadline DATE DEFAULT NULL,
    status ENUM('open','closed','filled') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (wilaya_id) REFERENCES wilayas(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: project_requirements
-- --------------------------------------------------------
CREATE TABLE project_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    category_id INT NOT NULL,
    quantity_needed INT DEFAULT 1,
    quantity_filled INT DEFAULT 0,
    assigned_talent_id INT DEFAULT NULL,
    status ENUM('open','filled','closed') DEFAULT 'open',
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_talent_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: applications
-- --------------------------------------------------------
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    talent_id INT NOT NULL,
    project_id INT NOT NULL,
    requirement_id INT DEFAULT NULL,
    message TEXT,
    status ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (talent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (requirement_id) REFERENCES project_requirements(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: requests (client <-> talent mediation)
-- --------------------------------------------------------
CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    talent_id INT NOT NULL,
    project_id INT DEFAULT NULL,
    message TEXT,
    status ENUM('pending','approved','rejected','accepted_by_talent','completed','cancelled') DEFAULT 'pending',
    admin_approved_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (talent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: request_messages (internal chat)
-- --------------------------------------------------------
CREATE TABLE request_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: portfolio_items
-- --------------------------------------------------------
CREATE TABLE portfolio_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    talent_id INT NOT NULL,
    title VARCHAR(255),
    type ENUM('image','video') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (talent_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50),
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Table: settings
-- --------------------------------------------------------
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) UNIQUE NOT NULL,
    value TEXT
) ENGINE=InnoDB;

INSERT INTO settings (`key`, value) VALUES
('site_name', 'MAYASE'),
('default_agent_commission', '10'),
('contact_email', 'contact@mayase.dz');

-- --------------------------------------------------------
-- Table: activity_logs
-- --------------------------------------------------------
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- SEED DATA
-- --------------------------------------------------------
-- Password for all test users: 'password123' (bcrypt hash)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO users (id, email, password, role, status, first_name, last_name, phone, wilaya_id, agent_commission) VALUES
(1, 'admin@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active', 'Admin', 'Mayase', '0550000001', 16, NULL),
(2, 'agent1@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent', 'active', 'Agent', 'One', '0550000002', 31, 12.00),
(3, 'agent2@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'agent', 'active', 'Agent', 'Two', '0550000003', 16, 15.00);

-- Talents (IDs 4..53) and Clients (IDs 54..73)
-- We'll create 50 talents and 20 clients with realistic data.
-- For brevity, only first few rows are fully spelled; the rest follow the same pattern.
INSERT INTO users (id, email, password, role, status, first_name, last_name, phone, wilaya_id, profile_photo, bio) VALUES
(4, 'lensdz@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Amine', 'Bouzid', '0551000001', 16, 'profile4.jpg', 'Photographer with 5 years experience'),
(5, 'tlemcenframes@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Sarah', 'Taleb', '0551000002', 13, 'profile5.jpg', 'Cinematographer from Tlemcen'),
(6, 'creativeoran@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Karim', 'Meddah', '0551000003', 31, 'profile6.jpg', 'Graphic designer & illustrator'),
(7, 'cinemadz@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Lamia', 'Khelifi', '0551000004', 16, NULL, 'Actor and screenwriter'),
(8, 'atlasdesigner@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Sofiane', 'Djebbar', '0551000005', 9, NULL, 'Graphic designer specialized in branding'),
(9, 'dzstorymaker@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Nadia', 'Hamza', '0551000006', 25, NULL, 'Screenwriter for films and ads'),
(10, 'visualbyamine@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Amine', 'Saidi', '0551000007', 19, NULL, 'Video editor and motion designer'),
(11, 'setifeditor@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Rania', 'Boutaleb', '0551000008', 19, NULL, 'Film editor with Adobe Premiere'),
(12, 'orancreator@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Yacine', 'Belhadj', '0551000009', 31, NULL, 'Makeup artist for cinema'),
(13, 'algeract@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Meriem', 'Ait', '0551000010', 16, NULL, 'Actor with theater background'),
(14, 'batnaphoto@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Hichem', 'Ziani', '0551000011', 5, NULL, 'Photographer for events and portraits'),
(15, 'blidastylist@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Selma', 'Cherif', '0551000012', 9, NULL, 'Fashion stylist'),
(16, 'tiziouzouactor@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Idir', 'Amirouche', '0551000013', 15, NULL, 'Actor and voice over artist'),
(17, 'mostaganemvideo@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Nassim', 'Bouaziz', '0551000014', 27, NULL, 'Videographer and editor'),
(18, 'djanetphoto@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Aicha', 'Mourad', '0551000015', 56, NULL, 'Nature photographer'),
(19, 'adrardesign@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Mokhtar', 'Bekri', '0551000016', 1, NULL, 'Graphic designer'),
(20, 'skikdawriter@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Leila', 'Sahraoui', '0551000017', 21, NULL, 'Screenwriter for documentaries'),
(21, 'annabastyle@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Ines', 'Boussaid', '0551000018', 23, NULL, 'Fashion stylist & costume designer'),
(22, 'bejaiamakeup@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Sabrina', 'Ighil', '0551000019', 6, NULL, 'Makeup artist for weddings & cinema'),
(23, 'chlefeditor@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Mohamed', 'Larbi', '0551000020', 2, NULL, 'Video editor'),
(24, 'laghouatphoto@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Fatima', 'Zohra', '0551000021', 3, NULL, 'Photographer'),
(25, 'oumscreen@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Ali', 'Guessoum', '0551000022', 4, NULL, 'Screenwriter'),
(26, 'tissemsiltactor@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Hanane', 'Khaldi', '0551000023', 38, NULL, 'Actor'),
(27, 'relizanedesigner@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Walid', 'Fekir', '0551000024', 48, NULL, 'Graphic designer'),
(28, 'naamastyle@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Zineb', 'Boukhari', '0551000025', 45, NULL, 'Fashion stylist'),
(29, 'tipazaphoto@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Mehdi', 'Saadi', '0551000026', 42, NULL, 'Photographer'),
(30, 'bouiraedit@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Nabil', 'Touati', '0551000027', 10, NULL, 'Video editor'),
(31, 'medeaactor@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Sana', 'Boumediene', '0551000028', 26, NULL, 'Actor'),
(32, 'msilawriter@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Adel', 'Berbache', '0551000029', 28, NULL, 'Screenwriter'),
(33, 'mascarade@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Khadidja', 'Dali', '0551000030', 29, NULL, 'Graphic designer'),
(34, 'bayadhdirector@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Redouane', 'Kaddour', '0551000031', 32, NULL, 'Photographer'),
(35, 'illizistylist@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Nesrine', 'Akli', '0551000032', 33, NULL, 'Fashion stylist'),
(36, 'tindoufmakeup@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Djamila', 'Senouci', '0551000033', 37, NULL, 'Makeup artist'),
(37, 'elouedactor@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Fouad', 'Bensalem', '0551000034', 39, NULL, 'Actor'),
(38, 'soukahrasphoto@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Amina', 'Bouras', '0551000035', 41, NULL, 'Photographer'),
(39, 'miladesigner@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Samir', 'Mansouri', '0551000036', 43, NULL, 'Graphic designer'),
(40, 'aindeflastyle@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Rym', 'Boudjema', '0551000037', 44, NULL, 'Fashion stylist'),
(41, 'ghardaiavideo@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Ismail', 'Bouabdellah', '0551000038', 47, NULL, 'Video editor'),
(42, 'relizanewriter@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Yasmine', 'Chaib', '0551000039', 48, NULL, 'Screenwriter'),
(43, 'ouarglaphoto@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Abdellah', 'Slimani', '0551000040', 30, NULL, 'Photographer'),
(44, 'annabaact@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Nadia', 'Bouziane', '0551000041', 23, NULL, 'Actor'),
(45, 'setifmakeup@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Lilia', 'Ferhat', '0551000042', 19, NULL, 'Makeup artist'),
(46, 'batnaeditor@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Mounir', 'Bouchareb', '0551000043', 5, NULL, 'Video editor'),
(47, 'djelfawriter@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Soumeya', 'Hadj', '0551000044', 17, NULL, 'Screenwriter'),
(48, 'oranphoto2@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Zakaria', 'Bouteldja', '0551000045', 31, NULL, 'Photographer'),
(49, 'algergraphic@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Imene', 'Ould', '0551000046', 16, NULL, 'Graphic designer'),
(50, 'tlemcenstyle@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Aymen', 'Bensaada', '0551000047', 13, NULL, 'Fashion stylist'),
(51, 'bejaiadirector@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Rachid', 'Aouadi', '0551000048', 6, NULL, 'Actor'),
(52, 'mostaact@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Sara', 'Benslimane', '0551000049', 27, NULL, 'Actor'),
(53, 'adrarmakeup@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'talent', 'active', 'Ahmed', 'Toufik', '0551000050', 1, NULL, 'Makeup artist');

-- Clients (IDs 54..73)
INSERT INTO users (id, email, password, role, status, first_name, last_name, phone, wilaya_id, profile_photo) VALUES
(54, 'saharammedia@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Sahara', 'Media', '0552000001', 16, NULL),
(55, 'numidiaagency@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Numidia', 'Agency', '0552000002', 25, NULL),
(56, 'atlascreative@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Atlas', 'Creative', '0552000003', 16, NULL),
(57, 'oranproductions@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Oran', 'Productions', '0552000004', 31, NULL),
(58, 'tlemcenstudio@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Tlemcen', 'Studio', '0552000005', 13, NULL),
(59, 'auresfilms@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Aurès', 'Films', '0552000006', 5, NULL),
(60, 'algervision@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Alger', 'Vision', '0552000007', 16, NULL),
(61, 'dzmarketing@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'DZ', 'Marketing', '0552000008', 31, NULL),
(62, 'blidamedia@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Blida', 'Media', '0552000009', 9, NULL),
(63, 'bejaiacreative@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Bejaia', 'Creative', '0552000010', 6, NULL),
(64, 'setifstudio@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Setif', 'Studio', '0552000011', 19, NULL),
(65, 'annabaprod@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Annaba', 'Prod', '0552000012', 23, NULL),
(66, 'constantinefilms@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Constantine', 'Films', '0552000013', 25, NULL),
(67, 'tiziouzoudesign@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Tizi Ouzou', 'Design', '0552000014', 15, NULL),
(68, 'mostaevents@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Mosta', 'Events', '0552000015', 27, NULL),
(69, 'tipazavideo@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Tipaza', 'Video', '0552000016', 42, NULL),
(70, 'soukahrasmedia@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Souk Ahras', 'Media', '0552000017', 41, NULL),
(71, 'ghardaiaprod@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Ghardaïa', 'Prod', '0552000018', 47, NULL),
(72, 'ouarglastudio@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Ouargla', 'Studio', '0552000019', 30, NULL),
(73, 'adrarvideo@mayase.dz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', 'Adrar', 'Video', '0552000020', 1, NULL);

-- Populate talents table (IDs linked to users 4..53)
INSERT INTO talents (id, user_id, talent_code, category_id, skills, experience, availability) VALUES
(1, 4, 'PHO-1001', 3, 'Portrait, Event, Adobe Lightroom', '5 ans', 'available'),
(2, 5, 'PHO-1002', 3, 'Cinematography, Drone', '3 ans', 'available'),
(3, 6, 'DES-2001', 5, 'Illustration, Branding', '4 ans', 'available'),
(4, 7, 'ACT-3001', 1, 'Theatre, Dubbing', '6 ans', 'available'),
(5, 8, 'DES-2002', 5, 'Logo design, Print', '7 ans', 'available'),
(6, 9, 'SCR-4001', 2, 'Fiction, Advertising', '5 ans', 'available'),
(7, 10, 'EDI-5001', 4, 'Premiere Pro, After Effects', '4 ans', 'available'),
(8, 11, 'EDI-5002', 4, 'Color grading, Sound design', '3 ans', 'available'),
(9, 12, 'MUA-6001', 6, 'Cinema makeup, SFX', '8 ans', 'available'),
(10, 13, 'ACT-3002', 1, 'Improvisation, Drama', '2 ans', 'available'),
(11, 14, 'PHO-1003', 3, 'Wedding, Studio', '6 ans', 'available'),
(12, 15, 'STY-7001', 7, 'Editorial, Costume design', '3 ans', 'available'),
(13, 16, 'ACT-3003', 1, 'Voice over, Narration', '10 ans', 'available'),
(14, 17, 'EDI-5003', 4, 'Documentary editing', '5 ans', 'available'),
(15, 18, 'PHO-1004', 3, 'Landscape, Travel', '4 ans', 'available'),
(16, 19, 'DES-2003', 5, 'Web design, UI', '2 ans', 'available'),
(17, 20, 'SCR-4002', 2, 'Documentary scripts', '7 ans', 'available'),
(18, 21, 'STY-7002', 7, 'Fashion shows, TV', '5 ans', 'available'),
(19, 22, 'MUA-6002', 6, 'Bridal, Special effects', '4 ans', 'available'),
(20, 23, 'EDI-5004', 4, 'Corporate videos', '3 ans', 'available'),
(21, 24, 'PHO-1005', 3, 'Product photography', '6 ans', 'available'),
(22, 25, 'SCR-4003', 2, 'TV series', '8 ans', 'available'),
(23, 26, 'ACT-3004', 1, 'Comedy, Drama', '1 an', 'available'),
(24, 27, 'DES-2004', 5, 'Packaging, Branding', '5 ans', 'available'),
(25, 28, 'STY-7003', 7, 'Personal shopping', '3 ans', 'available'),
(26, 29, 'PHO-1006', 3, 'Architecture', '4 ans', 'available'),
(27, 30, 'EDI-5005', 4, 'Music videos', '6 ans', 'available'),
(28, 31, 'ACT-3005', 1, 'Voice imitation', '2 ans', 'available'),
(29, 32, 'SCR-4004', 2, 'Short films', '4 ans', 'available'),
(30, 33, 'DES-2005', 5, 'Social media graphics', '3 ans', 'available'),
(31, 34, 'PHO-1007', 3, 'Fashion photography', '5 ans', 'available'),
(32, 35, 'STY-7004', 7, 'Costume design', '4 ans', 'available'),
(33, 36, 'MUA-6003', 6, 'Beauty, Bridal', '9 ans', 'available'),
(34, 37, 'ACT-3006', 1, 'Theatre, Film', '7 ans', 'available'),
(35, 38, 'PHO-1008', 3, 'Sports', '2 ans', 'available'),
(36, 39, 'DES-2006', 5, 'Illustration', '6 ans', 'available'),
(37, 40, 'STY-7005', 7, 'Wardrobe management', '3 ans', 'available'),
(38, 41, 'EDI-5006', 4, 'Motion graphics', '5 ans', 'available'),
(39, 42, 'SCR-4005', 2, 'Advertising copy', '4 ans', 'available'),
(40, 43, 'PHO-1009', 3, 'Aerial photography', '3 ans', 'available'),
(41, 44, 'ACT-3007', 1, 'Dubbing, Voice over', '8 ans', 'available'),
(42, 45, 'MUA-6004', 6, 'TV makeup', '5 ans', 'available'),
(43, 46, 'EDI-5007', 4, 'YouTube content', '2 ans', 'available'),
(44, 47, 'SCR-4006', 2, 'Film analysis', '3 ans', 'available'),
(45, 48, 'PHO-1010', 3, 'Event coverage', '6 ans', 'available'),
(46, 49, 'DES-2007', 5, 'Brand identity', '4 ans', 'available'),
(47, 50, 'STY-7006', 7, 'Runway styling', '5 ans', 'available'),
(48, 51, 'ACT-3008', 1, 'Action, Stunts', '4 ans', 'available'),
(49, 52, 'ACT-3009', 1, 'Newcomer', '1 an', 'available'),
(50, 53, 'MUA-6005', 6, 'Film makeup', '7 ans', 'available');

-- Populate clients table
INSERT INTO clients (id, user_id, company_name, description, subscription_status, trial_ends_at) VALUES
(1, 54, 'Sahara Media', 'Production audiovisuelle à Alger', 'active', '2026-07-17 00:00:00'),
(2, 55, 'Numidia Agency', 'Agence de communication et marketing', 'trial', '2026-07-17 00:00:00'),
(3, 56, 'Atlas Creative', 'Studio de design et branding', 'active', '2026-07-17 00:00:00'),
(4, 57, 'Oran Productions', 'Production cinématographique', 'trial', '2026-07-17 00:00:00'),
(5, 58, 'Tlemcen Studio', 'Photographie et vidéo', 'active', '2026-07-17 00:00:00'),
(6, 59, 'Aurès Films', 'Maison de production à Batna', 'trial', '2026-07-17 00:00:00'),
(7, 60, 'Alger Vision', 'Publicité et événementiel', 'active', '2026-07-17 00:00:00'),
(8, 61, 'DZ Marketing', 'Marketing digital', 'trial', '2026-07-17 00:00:00'),
(9, 62, 'Blida Media', 'Création de contenu', 'active', '2026-07-17 00:00:00'),
(10, 63, 'Bejaia Creative', 'Agence de publicité', 'trial', '2026-07-17 00:00:00'),
(11, 64, 'Setif Studio', 'Studio photo/vidéo', 'active', '2026-07-17 00:00:00'),
(12, 65, 'Annaba Prod', 'Production cinéma', 'trial', '2026-07-17 00:00:00'),
(13, 66, 'Constantine Films', 'Films institutionnels', 'active', '2026-07-17 00:00:00'),
(14, 67, 'Tizi Ouzou Design', 'Design graphique', 'trial', '2026-07-17 00:00:00'),
(15, 68, 'Mosta Events', 'Organisation événements', 'active', '2026-07-17 00:00:00'),
(16, 69, 'Tipaza Video', 'Captation mariage', 'trial', '2026-07-17 00:00:00'),
(17, 70, 'Souk Ahras Media', 'Journalisme vidéo', 'active', '2026-07-17 00:00:00'),
(18, 71, 'Ghardaïa Prod', 'Production documentaire', 'trial', '2026-07-17 00:00:00'),
(19, 72, 'Ouargla Studio', 'Studio créatif', 'active', '2026-07-17 00:00:00'),
(20, 73, 'Adrar Video', 'Vidéos publicitaires', 'trial', '2026-07-17 00:00:00');

-- Projects (30)
INSERT INTO projects (id, client_id, title, description, wilaya_id, budget, deadline, status) VALUES
(1, 54, 'TV Commercial', '30-second ad for mobile operator', 16, 150000.00, '2026-08-01', 'open'),
(2, 54, 'Documentary Production', '52-min documentary about Casbah', 16, 500000.00, '2026-09-15', 'open'),
(3, 55, 'Brand Video Campaign', 'Social media clips for new startup', 25, 80000.00, '2026-07-30', 'open'),
(4, 56, 'Fashion Shoot', 'Editorial for Algerian designer', 16, 60000.00, '2026-07-25', 'open'),
(5, 57, 'Short Film Casting', 'Seeking actors for a 15-min drama', 31, 120000.00, '2026-08-20', 'open'),
(6, 57, 'Wedding Photography', 'Full-day coverage in Oran', 31, 40000.00, '2026-08-10', 'open'),
(7, 58, 'YouTube Documentary', 'History of Tlemcen landmarks', 13, 90000.00, '2026-09-01', 'open'),
(8, 59, 'Social Media Campaign', 'Content creation for Instagram', 5, 25000.00, '2026-07-28', 'open'),
(9, 60, 'Corporate Video', 'Internal training video', 16, 70000.00, '2026-08-05', 'open'),
(10, 60, 'Event Photography', 'Coverage of tech conference', 16, 30000.00, '2026-07-20', 'open'),
(11, 61, 'Product Shoot', 'E-commerce photos for new line', 31, 20000.00, '2026-07-22', 'open'),
(12, 62, 'Cooking Show Pilot', 'Pilot episode for YouTube', 9, 100000.00, '2026-08-30', 'open'),
(13, 63, 'Travel Vlog Series', '5 episodes around Kabylie', 6, 150000.00, '2026-09-10', 'open'),
(14, 64, 'Music Video', 'Clip for emerging raï artist', 19, 80000.00, '2026-08-15', 'open'),
(15, 65, 'Real Estate Virtual Tours', '360° videos for agency', 23, 60000.00, '2026-07-31', 'open'),
(16, 66, 'Historical Documentary', 'Ancient Roman sites in Constantine', 25, 300000.00, '2026-10-01', 'open'),
(17, 67, 'Packaging Design', 'Visual identity for olive oil brand', 15, 45000.00, '2026-08-05', 'open'),
(18, 68, 'Event Aftermovie', 'Highlight video for festival', 27, 35000.00, '2026-07-25', 'open'),
(19, 69, 'Wedding Trailer', 'Cinematic trailer for couple', 42, 50000.00, '2026-08-18', 'open'),
(20, 70, 'News Reportage', 'Short documentary on local artisans', 41, 75000.00, '2026-09-05', 'open'),
(21, 71, 'Architectural Photography', 'Shooting new mosque in Ghardaïa', 47, 40000.00, '2026-08-12', 'open'),
(22, 72, 'Animation Explainer Video', '2D animation for startup pitch', 30, 110000.00, '2026-09-20', 'open'),
(23, 73, 'Desert Expedition Film', 'Documenting a camel caravan', 1, 200000.00, '2026-11-01', 'open'),
(24, 54, 'Testimonial Videos', 'Client testimonials for website', 16, 45000.00, '2026-07-29', 'open'),
(25, 55, 'Print Ad Design', 'Magazine ad for tourist office', 25, 15000.00, '2026-07-23', 'open'),
(26, 56, 'Logo & Branding', 'Rebrand for a restaurant chain', 16, 60000.00, '2026-08-10', 'open'),
(27, 57, 'Casting Call for Feature', 'Main roles for upcoming film', 31, 250000.00, '2026-10-01', 'open'),
(28, 58, 'Photo Book', 'Coffee table book about Tlemcen', 13, 80000.00, '2026-09-15', 'open'),
(29, 59, 'TV Series Trailer', 'Promo for Ramadan series', 5, 130000.00, '2026-09-01', 'open'),
(30, 60, 'Virtual Event Production', 'Live streaming of awards ceremony', 16, 90000.00, '2026-08-25', 'open');

-- Project requirements (each project needs at least one role)
INSERT INTO project_requirements (project_id, category_id, quantity_needed) VALUES
(1,1,2),(1,4,1),(2,4,1),(2,2,1),(3,3,1),(3,5,1),(4,3,1),(4,7,1),(5,1,3),(5,6,1),
(6,3,2),(7,4,1),(7,2,1),(8,5,1),(9,4,1),(9,3,1),(10,3,2),(11,3,1),(12,1,2),(12,6,1),
(13,4,1),(13,3,1),(14,1,1),(14,4,1),(15,3,2),(16,2,1),(16,4,1),(17,5,2),(18,4,1),(18,3,1),
(19,4,1),(19,3,1),(20,4,1),(20,2,1),(21,3,2),(22,5,1),(22,4,1),(23,4,1),(23,3,1),(24,4,1),
(25,5,1),(26,5,2),(27,1,5),(27,6,2),(28,3,1),(28,5,1),(29,4,1),(29,1,1),(30,4,1),(30,3,1);

SET FOREIGN_KEY_CHECKS=1;