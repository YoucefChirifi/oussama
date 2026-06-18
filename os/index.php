<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
date_default_timezone_set('Africa/Algiers');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const APP_NAME = 'MAYASE';
const APP_VERSION = '1.0.0';
const DB_HOST = 'localhost';
const DB_NAME = 'mayase_db';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';
const MAX_UPLOAD_SIZE = 25 * 1024 * 1024; // 25MB
const REMEMBER_COOKIE = 'mayase_remember';

// --------------------------------------------------------------------
// Embedded SQL schema + seed data (entire script from the question)
// --------------------------------------------------------------------
const SQL_INSTALL = <<<'SQL'
-- MAYASE database seed
-- Default seed password for all demo accounts: Mayase123!
CREATE DATABASE IF NOT EXISTS `mayase_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mayase_db`;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `remember_tokens`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `subscriptions`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `portfolio_items`;
DROP TABLE IF EXISTS `applications`;
DROP TABLE IF EXISTS `requests`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `talents`;
DROP TABLE IF EXISTS `clients`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `settings`;
SET FOREIGN_KEY_CHECKS=1;


CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(80) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_categories_name` (`name`),
  UNIQUE KEY `uniq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(120) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` ENUM('super_admin','agent','project_owner','talent') NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar_path` VARCHAR(255) DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`),
  UNIQUE KEY `uniq_users_phone` (`phone`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `talents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `talent_code` VARCHAR(20) NOT NULL,
  `nickname` VARCHAR(120) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `wilaya` VARCHAR(60) NOT NULL,
  `bio` TEXT NOT NULL,
  `skills` TEXT NOT NULL,
  `experience_years` INT UNSIGNED NOT NULL DEFAULT 0,
  `availability` VARCHAR(120) NOT NULL DEFAULT 'Available',
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_talents_user_id` (`user_id`),
  UNIQUE KEY `uniq_talents_code` (`talent_code`),
  UNIQUE KEY `uniq_talents_nickname` (`nickname`),
  KEY `idx_talents_category` (`category_id`),
  KEY `idx_talents_wilaya` (`wilaya`),
  KEY `idx_talents_status` (`status`),
  CONSTRAINT `fk_talents_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_talents_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `clients` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `company_name` VARCHAR(190) NOT NULL,
  `wilaya` VARCHAR(60) NOT NULL,
  `company_bio` TEXT DEFAULT NULL,
  `subscription_status` ENUM('trial','active','expired') NOT NULL DEFAULT 'trial',
  `trial_ends_at` DATE DEFAULT NULL,
  `subscription_ends_at` DATE DEFAULT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_clients_user_id` (`user_id`),
  UNIQUE KEY `uniq_clients_company_name` (`company_name`),
  KEY `idx_clients_wilaya` (`wilaya`),
  KEY `idx_clients_subscription_status` (`subscription_status`),
  CONSTRAINT `fk_clients_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(190) NOT NULL,
  `description` TEXT NOT NULL,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `required_roles` VARCHAR(255) DEFAULT NULL,
  `wilaya` VARCHAR(60) NOT NULL,
  `budget` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `deadline` DATE DEFAULT NULL,
  `status` ENUM('open','closed','in_progress','completed') NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_projects_client` (`client_id`),
  KEY `idx_projects_category` (`category_id`),
  KEY `idx_projects_status` (`status`),
  KEY `idx_projects_wilaya` (`wilaya`),
  CONSTRAINT `fk_projects_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_projects_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `applications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `talent_id` INT UNSIGNED NOT NULL,
  `cover_letter` TEXT DEFAULT NULL,
  `status` ENUM('pending','approved','rejected','in_progress','completed') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_app_project_talent` (`project_id`,`talent_id`),
  KEY `idx_applications_project` (`project_id`),
  KEY `idx_applications_talent` (`talent_id`),
  KEY `idx_applications_status` (`status`),
  CONSTRAINT `fk_applications_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_applications_talent` FOREIGN KEY (`talent_id`) REFERENCES `talents`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` INT UNSIGNED NOT NULL,
  `talent_id` INT UNSIGNED NOT NULL,
  `project_id` INT UNSIGNED DEFAULT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('pending','approved','rejected','in_progress','completed') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_requests_client` (`client_id`),
  KEY `idx_requests_talent` (`talent_id`),
  KEY `idx_requests_project` (`project_id`),
  KEY `idx_requests_status` (`status`),
  CONSTRAINT `fk_requests_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_requests_talent` FOREIGN KEY (`talent_id`) REFERENCES `talents`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_requests_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `portfolio_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `talent_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(190) NOT NULL,
  `media_type` ENUM('image','video') NOT NULL DEFAULT 'image',
  `file_path` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_portfolio_talent` (`talent_id`),
  KEY `idx_portfolio_featured` (`is_featured`),
  CONSTRAINT `fk_portfolio_talent` FOREIGN KEY (`talent_id`) REFERENCES `talents`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(60) NOT NULL,
  `title` VARCHAR(190) NOT NULL,
  `body` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`),
  KEY `idx_notifications_read` (`is_read`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `activity_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(120) NOT NULL,
  `context` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_user` (`user_id`),
  KEY `idx_activity_action` (`action`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `subscriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` INT UNSIGNED NOT NULL,
  `plan_name` VARCHAR(120) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `starts_at` DATE NOT NULL,
  `ends_at` DATE NOT NULL,
  `status` ENUM('trial','active','expired','cancelled') NOT NULL DEFAULT 'trial',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_subscriptions_client` (`client_id`),
  KEY `idx_subscriptions_status` (`status`),
  CONSTRAINT `fk_subscriptions_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` INT UNSIGNED DEFAULT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `purpose` VARCHAR(190) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'paid',
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_client` (`client_id`),
  KEY `idx_payments_user` (`user_id`),
  KEY `idx_payments_status` (`status`),
  CONSTRAINT `fk_payments_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `remember_tokens` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` VARCHAR(64) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_selector` (`selector`),
  KEY `idx_remember_user` (`user_id`),
  CONSTRAINT `fk_remember_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reset_user` (`user_id`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`name`,`slug`) VALUES
('Actor','actor'),
('Screenwriter','screenwriter'),
('Photographer','photographer'),
('Video Editor','video-editor'),
('Graphic Designer','graphic-designer'),
('Makeup Artist','makeup-artist'),
('Fashion Stylist','fashion-stylist');
INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('site_name','MAYASE'),
('site_tagline','Discover Talent. Create Opportunities.'),
('currency','DZD'),
('monthly_subscription_fee','12000'),
('special_monthly_offer','9000'),
('agent_monthly_pay','45000'),
('free_trial_days','30'),
('platform_commission_percent','10'),
('support_email','support@mayase.dz');
INSERT INTO `users` (`role`,`full_name`,`email`,`phone`,`password_hash`,`avatar_path`,`bio`,`is_active`,`is_approved`,`last_login_at`) VALUES
('super_admin','Yacine Bensalem','superadmin@mayase.dz','0550000001','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Platform account',1,1,NULL),
('agent','Nadia Bouzid','agent@mayase.dz','0550000002','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Platform account',1,1,NULL),
('talent','Amine Len','lensdz@mayase.dz','0510000002','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Alger',1,1,NULL),
('talent','Yacine Tle','tlemcenframes@mayase.dz','0510000003','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Constantine',1,1,NULL),
('talent','Nadia Cre','creativeoran@mayase.dz','0510000004','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Béjaïa',1,1,NULL),
('talent','Sara Cin','cinemadz@mayase.dz','0510000005','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Mostaganem',1,1,NULL),
('talent','Ilyes Atl','atlasdesigner@mayase.dz','0510000006','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Adrar',1,1,NULL),
('talent','Meriem DzS','dzstorymaker@mayase.dz','0510000007','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Tlemcen',1,1,NULL),
('talent','Bilal Vis','visualbyamine@mayase.dz','0510000008','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Sétif',1,1,NULL),
('talent','Sofiane Set','setifeditor@mayase.dz','0510000009','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Blida',1,1,NULL),
('talent','Ines Ora','orancreator@mayase.dz','0510000010','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Ouargla',1,1,NULL),
('talent','Mounir Sah','saharalens@mayase.dz','0510000011','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Oran',1,1,NULL),
('talent','Lina Aur','aurèsstyle@mayase.dz','0510000012','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Annaba',1,1,NULL),
('talent','Anis Num','numidiamakeup@mayase.dz','0510000013','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Batna',1,1,NULL),
('talent','Salma Bli','blidamotion@mayase.dz','0510000014','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Tizi Ouzou',1,1,NULL),
('talent','Riad Tiz','tiziscript@mayase.dz','0510000015','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Alger',1,1,NULL),
('talent','Kenza Adr','adrarfocus@mayase.dz','0510000016','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Constantine',1,1,NULL),
('talent','Walid Alg','algercanvas@mayase.dz','0510000017','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Béjaïa',1,1,NULL),
('talent','Hafsa Mza','mzabbeauty@mayase.dz','0510000018','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Mostaganem',1,1,NULL),
('talent','Mehdi Med','medeaframe@mayase.dz','0510000019','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Adrar',1,1,NULL),
('talent','Rania Kab','kabyliestyle@mayase.dz','0510000020','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Tlemcen',1,1,NULL),
('talent','Farid Con','constantineact@mayase.dz','0510000021','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Sétif',1,1,NULL),
('talent','Amina Pul','pulsephoto@mayase.dz','0510000022','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Blida',1,1,NULL),
('talent','Karim Nad','nadiacut@mayase.dz','0510000023','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Ouargla',1,1,NULL),
('talent','Aya Stu','studiosafi@mayase.dz','0510000024','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Oran',1,1,NULL),
('talent','Houda Ora','oranpixel@mayase.dz','0510000025','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Annaba',1,1,NULL),
('talent','Tarek Son','soniawardrobe@mayase.dz','0510000026','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Batna',1,1,NULL),
('talent','Mina Ray','rayanscene@mayase.dz','0510000027','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Tizi Ouzou',1,1,NULL),
('talent','Nabil Han','hanaframes@mayase.dz','0510000028','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Alger',1,1,NULL),
('talent','Dounia Yas','yassinemotion@mayase.dz','0510000029','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Constantine',1,1,NULL),
('talent','Samir DzM','dzmakeuppro@mayase.dz','0510000030','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Béjaïa',1,1,NULL),
('talent','Lamia Bor','bordjlens@mayase.dz','0510000031','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Mostaganem',1,1,NULL),
('talent','Nassim Chl','chloestyledz@mayase.dz','0510000032','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Adrar',1,1,NULL),
('talent','Imane Ade','adelvision@mayase.dz','0510000033','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Tlemcen',1,1,NULL),
('talent','Khaled Mil','milascript@mayase.dz','0510000034','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Sétif',1,1,NULL),
('talent','Yasmine Sid','sidifilm@mayase.dz','0510000035','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Blida',1,1,NULL),
('talent','Adel Ran','raniaedit@mayase.dz','0510000036','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Ouargla',1,1,NULL),
('talent','Sonia Bis','biskraflash@mayase.dz','0510000037','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Oran',1,1,NULL),
('talent','Ramy Yan','yanisbrand@mayase.dz','0510000038','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Annaba',1,1,NULL),
('talent','Meryem Ami','aminashoot@mayase.dz','0510000039','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Batna',1,1,NULL),
('talent','Hichem Sah','saharastylist@mayase.dz','0510000040','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Tizi Ouzou',1,1,NULL),
('talent','Ahlam Oma','omardialogue@mayase.dz','0510000041','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Alger',1,1,NULL),
('talent','Noureddine ElK','elkalashot@mayase.dz','0510000042','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Constantine',1,1,NULL),
('talent','Malak Mou','mounapalette@mayase.dz','0510000043','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Béjaïa',1,1,NULL),
('talent','Zinedine Hic','hichemreel@mayase.dz','0510000044','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Mostaganem',1,1,NULL),
('talent','Lydia Lyd','lydiaframe@mayase.dz','0510000045','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Adrar',1,1,NULL),
('talent','Said Zin','zinedinestory@mayase.dz','0510000046','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Tlemcen',1,1,NULL),
('talent','Widad Wid','widadstyle@mayase.dz','0510000047','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Sétif',1,1,NULL),
('talent','Nadjet Nas','nassimpixels@mayase.dz','0510000048','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Blida',1,1,NULL),
('talent','Yanis Sam','samialens@mayase.dz','0510000049','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Ouargla',1,1,NULL),
('talent','Selma Dja','djazaircut@mayase.dz','0510000050','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Oran',1,1,NULL),
('talent','Ayman Ima','imanescene@mayase.dz','0510000051','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Creative talent from Annaba',1,1,NULL),
('project_owner','Lina B','saharamedia@mayase.dz','0610000052','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Sahara Media creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Anis C','numidiaagency@mayase.dz','0610000053','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Numidia Agency creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Salma D','atlascreative@mayase.dz','0610000054','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Atlas Creative creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Riad E','oranproductions@mayase.dz','0610000055','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Oran Productions creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Kenza F','tlemcenstudio@mayase.dz','0610000056','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Tlemcen Studio creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Walid G','aurèsfilms@mayase.dz','0610000057','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Aurès Films creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Hafsa H','algervision@mayase.dz','0610000058','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Alger Vision creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Mehdi I','dzmarketing@mayase.dz','0610000059','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Dz Marketing creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Rania J','maghrebhouse@mayase.dz','0610000060','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Maghreb House creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Farid K','bayastudio@mayase.dz','0610000061','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Baya Studio creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Amina B','casbahmedia@mayase.dz','0610000062','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Casbah Media creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Karim C','meknescreative@mayase.dz','0610000063','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Meknes Creative creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Aya D','dzmotion@mayase.dz','0610000064','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Dz Motion creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Houda E','annababrandlab@mayase.dz','0610000065','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Annaba Brand Lab creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Tarek F','setifculture@mayase.dz','0610000066','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Setif Culture creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Mina G','bejaiadream@mayase.dz','0610000067','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Bejaia Dream creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Nabil H','batnamedia@mayase.dz','0610000068','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Batna Media creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Dounia I','blidaframe@mayase.dz','0610000069','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Blida Frame creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Samir J','mostacontent@mayase.dz','0610000070','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Mosta Content creates campaigns, photo, and video productions across Algeria.',1,1,NULL),
('project_owner','Lamia K','ouedstudio@mayase.dz','0610000071','$2y$12$BZD8fZH2vvQJPYzIqVnIrOVtawKqbawSlsw48snmZ/x0VR6gSyHnG',NULL,'Oued Studio creates campaigns, photo, and video productions across Algeria.',1,1,NULL);
INSERT INTO `talents` (`user_id`,`talent_code`,`nickname`,`category_id`,`wilaya`,`bio`,`skills`,`experience_years`,`availability`,`profile_photo`,`status`) VALUES
(3,'ACT-1023','LensDz',1,'Alger','LensDz is a actor based in Alger with a strong Algerian visual identity and commercial storytelling style.','Screen presence, casting, improvisation, commercial acting',2,'Available',NULL,'approved'),
(4,'SCR-1040','TlemcenFrames',2,'Constantine','TlemcenFrames is a screenwriter based in Constantine with a strong Algerian visual identity and commercial storytelling style.','Script structure, dialogue, adaptation, pitching',3,'Busy this week',NULL,'approved'),
(5,'PHO-1057','CreativeOran',3,'Béjaïa','CreativeOran is a photographer based in Béjaïa with a strong Algerian visual identity and commercial storytelling style.','Portraits, events, commercial shots, color grading',4,'Available for remote',NULL,'approved'),
(6,'EDI-1074','CinemaDz',4,'Mostaganem','CinemaDz is a video editor based in Mostaganem with a strong Algerian visual identity and commercial storytelling style.','Narrative editing, pacing, color correction, motion graphics',5,'Available evenings',NULL,'pending'),
(7,'DES-1091','AtlasDesigner',5,'Adrar','AtlasDesigner is a graphic designer based in Adrar with a strong Algerian visual identity and commercial storytelling style.','Branding, posters, social media, typography',6,'Available weekends',NULL,'approved'),
(8,'MUA-1108','DzStoryMaker',6,'Tlemcen','DzStoryMaker is a makeup artist based in Tlemcen with a strong Algerian visual identity and commercial storytelling style.','Beauty looks, cinematic makeup, bridal and event work',7,'Available',NULL,'approved'),
(9,'STY-1125','VisualByAmine',7,'Sétif','VisualByAmine is a fashion stylist based in Sétif with a strong Algerian visual identity and commercial storytelling style.','Wardrobe curation, shoot styling, trend direction',8,'Busy this week',NULL,'approved'),
(10,'ACT-1142','SetifEditor',1,'Blida','SetifEditor is a actor based in Blida with a strong Algerian visual identity and commercial storytelling style.','Screen presence, casting, improvisation, commercial acting',9,'Available for remote',NULL,'pending'),
(11,'SCR-1159','OranCreator',2,'Ouargla','OranCreator is a screenwriter based in Ouargla with a strong Algerian visual identity and commercial storytelling style.','Script structure, dialogue, adaptation, pitching',10,'Available evenings',NULL,'approved'),
(12,'PHO-1176','SaharaLens',3,'Oran','SaharaLens is a photographer based in Oran with a strong Algerian visual identity and commercial storytelling style.','Portraits, events, commercial shots, color grading',2,'Available weekends',NULL,'approved'),
(13,'EDI-1193','AurèsStyle',4,'Annaba','AurèsStyle is a video editor based in Annaba with a strong Algerian visual identity and commercial storytelling style.','Narrative editing, pacing, color correction, motion graphics',3,'Available',NULL,'approved'),
(14,'DES-1210','NumidiaMakeup',5,'Batna','NumidiaMakeup is a graphic designer based in Batna with a strong Algerian visual identity and commercial storytelling style.','Branding, posters, social media, typography',4,'Busy this week',NULL,'pending'),
(15,'MUA-1227','BlidaMotion',6,'Tizi Ouzou','BlidaMotion is a makeup artist based in Tizi Ouzou with a strong Algerian visual identity and commercial storytelling style.','Beauty looks, cinematic makeup, bridal and event work',5,'Available for remote',NULL,'approved'),
(16,'STY-1244','TiziScript',7,'Alger','TiziScript is a fashion stylist based in Alger with a strong Algerian visual identity and commercial storytelling style.','Wardrobe curation, shoot styling, trend direction',6,'Available evenings',NULL,'approved'),
(17,'ACT-1261','AdrarFocus',1,'Constantine','AdrarFocus is a actor based in Constantine with a strong Algerian visual identity and commercial storytelling style.','Screen presence, casting, improvisation, commercial acting',7,'Available weekends',NULL,'approved'),
(18,'SCR-1278','AlgerCanvas',2,'Béjaïa','AlgerCanvas is a screenwriter based in Béjaïa with a strong Algerian visual identity and commercial storytelling style.','Script structure, dialogue, adaptation, pitching',8,'Available',NULL,'pending'),
(19,'PHO-1295','MzabBeauty',3,'Mostaganem','MzabBeauty is a photographer based in Mostaganem with a strong Algerian visual identity and commercial storytelling style.','Portraits, events, commercial shots, color grading',9,'Busy this week',NULL,'approved'),
(20,'EDI-1312','MedeaFrame',4,'Adrar','MedeaFrame is a video editor based in Adrar with a strong Algerian visual identity and commercial storytelling style.','Narrative editing, pacing, color correction, motion graphics',10,'Available for remote',NULL,'approved'),
(21,'DES-1329','KabylieStyle',5,'Tlemcen','KabylieStyle is a graphic designer based in Tlemcen with a strong Algerian visual identity and commercial storytelling style.','Branding, posters, social media, typography',2,'Available evenings',NULL,'approved'),
(22,'MUA-1346','ConstantineAct',6,'Sétif','ConstantineAct is a makeup artist based in Sétif with a strong Algerian visual identity and commercial storytelling style.','Beauty looks, cinematic makeup, bridal and event work',3,'Available weekends',NULL,'pending'),
(23,'STY-1363','PulsePhoto',7,'Blida','PulsePhoto is a fashion stylist based in Blida with a strong Algerian visual identity and commercial storytelling style.','Wardrobe curation, shoot styling, trend direction',4,'Available',NULL,'approved'),
(24,'ACT-1380','NadiaCut',1,'Ouargla','NadiaCut is a actor based in Ouargla with a strong Algerian visual identity and commercial storytelling style.','Screen presence, casting, improvisation, commercial acting',5,'Busy this week',NULL,'approved'),
(25,'SCR-1397','StudioSafi',2,'Oran','StudioSafi is a screenwriter based in Oran with a strong Algerian visual identity and commercial storytelling style.','Script structure, dialogue, adaptation, pitching',6,'Available for remote',NULL,'approved'),
(26,'PHO-1414','OranPixel',3,'Annaba','OranPixel is a photographer based in Annaba with a strong Algerian visual identity and commercial storytelling style.','Portraits, events, commercial shots, color grading',7,'Available evenings',NULL,'pending'),
(27,'EDI-1431','SoniaWardrobe',4,'Batna','SoniaWardrobe is a video editor based in Batna with a strong Algerian visual identity and commercial storytelling style.','Narrative editing, pacing, color correction, motion graphics',8,'Available weekends',NULL,'approved'),
(28,'DES-1448','RayanScene',5,'Tizi Ouzou','RayanScene is a graphic designer based in Tizi Ouzou with a strong Algerian visual identity and commercial storytelling style.','Branding, posters, social media, typography',9,'Available',NULL,'approved'),
(29,'MUA-1465','HanaFrames',6,'Alger','HanaFrames is a makeup artist based in Alger with a strong Algerian visual identity and commercial storytelling style.','Beauty looks, cinematic makeup, bridal and event work',10,'Busy this week',NULL,'approved'),
(30,'STY-1482','YassineMotion',7,'Constantine','YassineMotion is a fashion stylist based in Constantine with a strong Algerian visual identity and commercial storytelling style.','Wardrobe curation, shoot styling, trend direction',2,'Available for remote',NULL,'pending'),
(31,'ACT-1499','DzMakeupPro',1,'Béjaïa','DzMakeupPro is a actor based in Béjaïa with a strong Algerian visual identity and commercial storytelling style.','Screen presence, casting, improvisation, commercial acting',3,'Available evenings',NULL,'approved'),
(32,'SCR-1516','BordjLens',2,'Mostaganem','BordjLens is a screenwriter based in Mostaganem with a strong Algerian visual identity and commercial storytelling style.','Script structure, dialogue, adaptation, pitching',4,'Available weekends',NULL,'approved'),
(33,'PHO-1533','ChloéStyleDz',3,'Adrar','ChloéStyleDz is a photographer based in Adrar with a strong Algerian visual identity and commercial storytelling style.','Portraits, events, commercial shots, color grading',5,'Available',NULL,'approved'),
(34,'EDI-1550','AdelVision',4,'Tlemcen','AdelVision is a video editor based in Tlemcen with a strong Algerian visual identity and commercial storytelling style.','Narrative editing, pacing, color correction, motion graphics',6,'Busy this week',NULL,'pending'),
(35,'DES-1567','MilaScript',5,'Sétif','MilaScript is a graphic designer based in Sétif with a strong Algerian visual identity and commercial storytelling style.','Branding, posters, social media, typography',7,'Available for remote',NULL,'approved'),
(36,'MUA-1584','SidiFilm',6,'Blida','SidiFilm is a makeup artist based in Blida with a strong Algerian visual identity and commercial storytelling style.','Beauty looks, cinematic makeup, bridal and event work',8,'Available evenings',NULL,'approved'),
(37,'STY-1601','RaniaEdit',7,'Ouargla','RaniaEdit is a fashion stylist based in Ouargla with a strong Algerian visual identity and commercial storytelling style.','Wardrobe curation, shoot styling, trend direction',9,'Available weekends',NULL,'approved'),
(38,'ACT-1618','BiskraFlash',1,'Oran','BiskraFlash is a actor based in Oran with a strong Algerian visual identity and commercial storytelling style.','Screen presence, casting, improvisation, commercial acting',10,'Available',NULL,'pending'),
(39,'SCR-1635','YanisBrand',2,'Annaba','YanisBrand is a screenwriter based in Annaba with a strong Algerian visual identity and commercial storytelling style.','Script structure, dialogue, adaptation, pitching',2,'Busy this week',NULL,'approved'),
(40,'PHO-1652','AminaShoot',3,'Batna','AminaShoot is a photographer based in Batna with a strong Algerian visual identity and commercial storytelling style.','Portraits, events, commercial shots, color grading',3,'Available for remote',NULL,'approved'),
(41,'EDI-1669','SaharaStylist',4,'Tizi Ouzou','SaharaStylist is a video editor based in Tizi Ouzou with a strong Algerian visual identity and commercial storytelling style.','Narrative editing, pacing, color correction, motion graphics',4,'Available evenings',NULL,'approved'),
(42,'DES-1686','OmarDialogue',5,'Alger','OmarDialogue is a graphic designer based in Alger with a strong Algerian visual identity and commercial storytelling style.','Branding, posters, social media, typography',5,'Available weekends',NULL,'pending'),
(43,'MUA-1703','ElKalaShot',6,'Constantine','ElKalaShot is a makeup artist based in Constantine with a strong Algerian visual identity and commercial storytelling style.','Beauty looks, cinematic makeup, bridal and event work',6,'Available',NULL,'approved'),
(44,'STY-1720','MounaPalette',7,'Béjaïa','MounaPalette is a fashion stylist based in Béjaïa with a strong Algerian visual identity and commercial storytelling style.','Wardrobe curation, shoot styling, trend direction',7,'Busy this week',NULL,'approved'),
(45,'ACT-1737','HichemReel',1,'Mostaganem','HichemReel is a actor based in Mostaganem with a strong Algerian visual identity and commercial storytelling style.','Screen presence, casting, improvisation, commercial acting',8,'Available for remote',NULL,'approved'),
(46,'SCR-1754','LydiaFrame',2,'Adrar','LydiaFrame is a screenwriter based in Adrar with a strong Algerian visual identity and commercial storytelling style.','Script structure, dialogue, adaptation, pitching',9,'Available evenings',NULL,'pending'),
(47,'PHO-1771','ZinedineStory',3,'Tlemcen','ZinedineStory is a photographer based in Tlemcen with a strong Algerian visual identity and commercial storytelling style.','Portraits, events, commercial shots, color grading',10,'Available weekends',NULL,'approved'),
(48,'EDI-1788','WidadStyle',4,'Sétif','WidadStyle is a video editor based in Sétif with a strong Algerian visual identity and commercial storytelling style.','Narrative editing, pacing, color correction, motion graphics',2,'Available',NULL,'approved'),
(49,'DES-1805','NassimPixels',5,'Blida','NassimPixels is a graphic designer based in Blida with a strong Algerian visual identity and commercial storytelling style.','Branding, posters, social media, typography',3,'Busy this week',NULL,'approved'),
(50,'MUA-1822','SamiaLens',6,'Ouargla','SamiaLens is a makeup artist based in Ouargla with a strong Algerian visual identity and commercial storytelling style.','Beauty looks, cinematic makeup, bridal and event work',4,'Available for remote',NULL,'pending'),
(51,'STY-1839','DjazairCut',7,'Oran','DjazairCut is a fashion stylist based in Oran with a strong Algerian visual identity and commercial storytelling style.','Wardrobe curation, shoot styling, trend direction',5,'Available evenings',NULL,'approved'),
(52,'ACT-1856','ImaneScene',1,'Annaba','ImaneScene is a actor based in Annaba with a strong Algerian visual identity and commercial storytelling style.','Screen presence, casting, improvisation, commercial acting',6,'Available weekends',NULL,'approved');
INSERT INTO `clients` (`user_id`,`company_name`,`wilaya`,`company_bio`,`subscription_status`,`trial_ends_at`,`subscription_ends_at`,`status`) VALUES
(53,'Sahara Media','Alger','Sahara Media creates campaigns, photo, and video productions across Algeria.','trial',DATE_ADD(CURDATE(), INTERVAL 30 DAY),NULL,'approved'),
(54,'Numidia Agency','Oran','Numidia Agency creates campaigns, photo, and video productions across Algeria.','active',DATE_ADD(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 60 DAY),'approved'),
(55,'Atlas Creative','Tlemcen','Atlas Creative creates campaigns, photo, and video productions across Algeria.','trial',DATE_ADD(CURDATE(), INTERVAL 30 DAY),NULL,'approved'),
(56,'Oran Productions','Constantine','Oran Productions creates campaigns, photo, and video productions across Algeria.','active',DATE_ADD(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 60 DAY),'approved'),
(57,'Tlemcen Studio','Annaba','Tlemcen Studio creates campaigns, photo, and video productions across Algeria.','trial',DATE_ADD(CURDATE(), INTERVAL 30 DAY),NULL,'approved'),
(58,'Aurès Films','Sétif','Aurès Films creates campaigns, photo, and video productions across Algeria.','active',DATE_ADD(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 60 DAY),'approved'),
(59,'Alger Vision','Béjaïa','Alger Vision creates campaigns, photo, and video productions across Algeria.','trial',DATE_ADD(CURDATE(), INTERVAL 30 DAY),NULL,'approved'),
(60,'Dz Marketing','Batna','Dz Marketing creates campaigns, photo, and video productions across Algeria.','active',DATE_ADD(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 60 DAY),'approved'),
(61,'Maghreb House','Blida','Maghreb House creates campaigns, photo, and video productions across Algeria.','trial',DATE_ADD(CURDATE(), INTERVAL 30 DAY),NULL,'approved'),
(62,'Baya Studio','Mostaganem','Baya Studio creates campaigns, photo, and video productions across Algeria.','active',DATE_ADD(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 60 DAY),'approved'),
(63,'Casbah Media','Tizi Ouzou','Casbah Media creates campaigns, photo, and video productions across Algeria.','trial',DATE_ADD(CURDATE(), INTERVAL 30 DAY),NULL,'approved'),
(64,'Meknes Creative','Ouargla','Meknes Creative creates campaigns, photo, and video productions across Algeria.','active',DATE_ADD(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 60 DAY),'approved'),
(65,'Dz Motion','Adrar','Dz Motion creates campaigns, photo, and video productions across Algeria.','trial',DATE_ADD(CURDATE(), INTERVAL 30 DAY),NULL,'approved'),
(66,'Annaba Brand Lab','Alger','Annaba Brand Lab creates campaigns, photo, and video productions across Algeria.','active',DATE_ADD(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 60 DAY),'approved'),
(67,'Setif Culture','Oran','Setif Culture creates campaigns, photo, and video productions across Algeria.','trial',DATE_ADD(CURDATE(), INTERVAL 30 DAY),NULL,'approved'),
(68,'Bejaia Dream','Tlemcen','Bejaia Dream creates campaigns, photo, and video productions across Algeria.','active',DATE_ADD(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 60 DAY),'approved'),
(69,'Batna Media','Constantine','Batna Media creates campaigns, photo, and video productions across Algeria.','trial',DATE_ADD(CURDATE(), INTERVAL 30 DAY),NULL,'approved'),
(70,'Blida Frame','Annaba','Blida Frame creates campaigns, photo, and video productions across Algeria.','active',DATE_ADD(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 60 DAY),'approved'),
(71,'Mosta Content','Sétif','Mosta Content creates campaigns, photo, and video productions across Algeria.','trial',DATE_ADD(CURDATE(), INTERVAL 30 DAY),NULL,'approved'),
(72,'Oued Studio','Béjaïa','Oued Studio creates campaigns, photo, and video productions across Algeria.','active',DATE_ADD(CURDATE(), INTERVAL 30 DAY),DATE_ADD(CURDATE(), INTERVAL 60 DAY),'approved');
INSERT INTO `subscriptions` (`client_id`,`plan_name`,`amount`,`starts_at`,`ends_at`,`status`) VALUES
(1,'Trial',0,DATE_SUB(CURDATE(), INTERVAL 1 DAY),DATE_ADD(CURDATE(), INTERVAL 29 DAY),'trial'),
(2,'Professional',12200,DATE_SUB(CURDATE(), INTERVAL 2 DAY),DATE_ADD(CURDATE(), INTERVAL 32 DAY),'active'),
(3,'Trial',0,DATE_SUB(CURDATE(), INTERVAL 3 DAY),DATE_ADD(CURDATE(), INTERVAL 27 DAY),'trial'),
(4,'Professional',12400,DATE_SUB(CURDATE(), INTERVAL 4 DAY),DATE_ADD(CURDATE(), INTERVAL 34 DAY),'active'),
(5,'Trial',0,DATE_SUB(CURDATE(), INTERVAL 5 DAY),DATE_ADD(CURDATE(), INTERVAL 25 DAY),'trial'),
(6,'Professional',12600,DATE_SUB(CURDATE(), INTERVAL 6 DAY),DATE_ADD(CURDATE(), INTERVAL 36 DAY),'active'),
(7,'Trial',0,DATE_SUB(CURDATE(), INTERVAL 7 DAY),DATE_ADD(CURDATE(), INTERVAL 23 DAY),'trial'),
(8,'Professional',12800,DATE_SUB(CURDATE(), INTERVAL 8 DAY),DATE_ADD(CURDATE(), INTERVAL 38 DAY),'active'),
(9,'Trial',0,DATE_SUB(CURDATE(), INTERVAL 9 DAY),DATE_ADD(CURDATE(), INTERVAL 21 DAY),'trial'),
(10,'Professional',13000,DATE_SUB(CURDATE(), INTERVAL 10 DAY),DATE_ADD(CURDATE(), INTERVAL 40 DAY),'active'),
(11,'Trial',0,DATE_SUB(CURDATE(), INTERVAL 11 DAY),DATE_ADD(CURDATE(), INTERVAL 19 DAY),'trial'),
(12,'Professional',13200,DATE_SUB(CURDATE(), INTERVAL 12 DAY),DATE_ADD(CURDATE(), INTERVAL 42 DAY),'active'),
(13,'Trial',0,DATE_SUB(CURDATE(), INTERVAL 13 DAY),DATE_ADD(CURDATE(), INTERVAL 17 DAY),'trial'),
(14,'Professional',13400,DATE_SUB(CURDATE(), INTERVAL 14 DAY),DATE_ADD(CURDATE(), INTERVAL 44 DAY),'active'),
(15,'Trial',0,DATE_SUB(CURDATE(), INTERVAL 15 DAY),DATE_ADD(CURDATE(), INTERVAL 15 DAY),'trial'),
(16,'Professional',13600,DATE_SUB(CURDATE(), INTERVAL 16 DAY),DATE_ADD(CURDATE(), INTERVAL 46 DAY),'active'),
(17,'Trial',0,DATE_SUB(CURDATE(), INTERVAL 17 DAY),DATE_ADD(CURDATE(), INTERVAL 13 DAY),'trial'),
(18,'Professional',13800,DATE_SUB(CURDATE(), INTERVAL 18 DAY),DATE_ADD(CURDATE(), INTERVAL 48 DAY),'active'),
(19,'Trial',0,DATE_SUB(CURDATE(), INTERVAL 19 DAY),DATE_ADD(CURDATE(), INTERVAL 11 DAY),'trial'),
(20,'Professional',14000,DATE_SUB(CURDATE(), INTERVAL 20 DAY),DATE_ADD(CURDATE(), INTERVAL 50 DAY),'active');
INSERT INTO `payments` (`client_id`,`user_id`,`purpose`,`amount`,`status`,`paid_at`) VALUES
(2,NULL,'Subscription payment',12200,'paid',NOW()),
(4,NULL,'Subscription payment',12400,'paid',NOW()),
(6,NULL,'Subscription payment',12600,'paid',NOW()),
(8,NULL,'Subscription payment',12800,'paid',NOW()),
(10,NULL,'Subscription payment',13000,'paid',NOW()),
(12,NULL,'Subscription payment',13200,'paid',NOW()),
(14,NULL,'Subscription payment',13400,'paid',NOW()),
(16,NULL,'Subscription payment',13600,'paid',NOW()),
(18,NULL,'Subscription payment',13800,'paid',NOW()),
(20,NULL,'Subscription payment',14000,'paid',NOW());
INSERT INTO `projects` (`client_id`,`title`,`description`,`category_id`,`required_roles`,`wilaya`,`budget`,`deadline`,`status`) VALUES
(1,'TV Commercial','TV Commercial for Sahara Media with a focus on Algerian creative production and strong visual identity.',1,'Photographer,Graphic Designer','Tlemcen',53500,DATE_ADD(CURDATE(), INTERVAL 1 DAY),'closed'),
(2,'Documentary Production','Documentary Production for Numidia Agency with a focus on Algerian creative production and strong visual identity.',2,'Screenwriter,Actor','Annaba',57000,DATE_ADD(CURDATE(), INTERVAL 2 DAY),'in_progress'),
(3,'Brand Video Campaign','Brand Video Campaign for Atlas Creative with a focus on Algerian creative production and strong visual identity.',3,'Makeup Artist,Fashion Stylist','Béjaïa',60500,DATE_ADD(CURDATE(), INTERVAL 3 DAY),'completed'),
(4,'Fashion Shoot','Fashion Shoot for Oran Productions with a focus on Algerian creative production and strong visual identity.',4,'Actor,Video Editor','Blida',64000,DATE_ADD(CURDATE(), INTERVAL 4 DAY),'open'),
(5,'Wedding Photography','Wedding Photography for Tlemcen Studio with a focus on Algerian creative production and strong visual identity.',5,'Photographer,Graphic Designer','Tizi Ouzou',67500,DATE_ADD(CURDATE(), INTERVAL 5 DAY),'closed'),
(6,'YouTube Documentary','YouTube Documentary for Aurès Films with a focus on Algerian creative production and strong visual identity.',6,'Screenwriter,Actor','Adrar',71000,DATE_ADD(CURDATE(), INTERVAL 6 DAY),'in_progress'),
(7,'Social Media Campaign','Social Media Campaign for Alger Vision with a focus on Algerian creative production and strong visual identity.',7,'Makeup Artist,Fashion Stylist','Oran',74500,DATE_ADD(CURDATE(), INTERVAL 7 DAY),'completed'),
(8,'Short Film Casting','Short Film Casting for Dz Marketing with a focus on Algerian creative production and strong visual identity.',1,'Actor,Video Editor','Constantine',78000,DATE_ADD(CURDATE(), INTERVAL 8 DAY),'open'),
(9,'Corporate Event Coverage','Corporate Event Coverage for Maghreb House with a focus on Algerian creative production and strong visual identity.',2,'Photographer,Graphic Designer','Sétif',81500,DATE_ADD(CURDATE(), INTERVAL 9 DAY),'closed'),
(10,'Music Video','Music Video for Baya Studio with a focus on Algerian creative production and strong visual identity.',3,'Screenwriter,Actor','Batna',85000,DATE_ADD(CURDATE(), INTERVAL 10 DAY),'in_progress'),
(11,'Festival Promo','Festival Promo for Casbah Media with a focus on Algerian creative production and strong visual identity.',4,'Makeup Artist,Fashion Stylist','Mostaganem',88500,DATE_ADD(CURDATE(), INTERVAL 11 DAY),'completed'),
(12,'Product Launch Video','Product Launch Video for Meknes Creative with a focus on Algerian creative production and strong visual identity.',5,'Actor,Video Editor','Ouargla',92000,DATE_ADD(CURDATE(), INTERVAL 12 DAY),'open'),
(13,'Food Photography','Food Photography for Dz Motion with a focus on Algerian creative production and strong visual identity.',6,'Photographer,Graphic Designer','Alger',95500,DATE_ADD(CURDATE(), INTERVAL 13 DAY),'closed'),
(14,'Real Estate Reel','Real Estate Reel for Annaba Brand Lab with a focus on Algerian creative production and strong visual identity.',7,'Screenwriter,Actor','Tlemcen',99000,DATE_ADD(CURDATE(), INTERVAL 14 DAY),'in_progress'),
(15,'Podcast Visuals','Podcast Visuals for Setif Culture with a focus on Algerian creative production and strong visual identity.',1,'Makeup Artist,Fashion Stylist','Annaba',102500,DATE_ADD(CURDATE(), INTERVAL 15 DAY),'completed'),
(16,'Cinema Trailer','Cinema Trailer for Bejaia Dream with a focus on Algerian creative production and strong visual identity.',2,'Actor,Video Editor','Béjaïa',106000,DATE_ADD(CURDATE(), INTERVAL 16 DAY),'open'),
(17,'Makeup Campaign','Makeup Campaign for Batna Media with a focus on Algerian creative production and strong visual identity.',3,'Photographer,Graphic Designer','Blida',109500,DATE_ADD(CURDATE(), INTERVAL 17 DAY),'closed'),
(18,'Streetwear Lookbook','Streetwear Lookbook for Blida Frame with a focus on Algerian creative production and strong visual identity.',4,'Screenwriter,Actor','Tizi Ouzou',113000,DATE_ADD(CURDATE(), INTERVAL 18 DAY),'in_progress'),
(19,'Startup Pitch Video','Startup Pitch Video for Mosta Content with a focus on Algerian creative production and strong visual identity.',5,'Makeup Artist,Fashion Stylist','Adrar',116500,DATE_ADD(CURDATE(), INTERVAL 19 DAY),'completed'),
(20,'Tourism Ad','Tourism Ad for Oued Studio with a focus on Algerian creative production and strong visual identity.',6,'Actor,Video Editor','Oran',120000,DATE_ADD(CURDATE(), INTERVAL 20 DAY),'open'),
(1,'Brand Identity Refresh','Brand Identity Refresh for Sahara Media with a focus on Algerian creative production and strong visual identity.',7,'Photographer,Graphic Designer','Constantine',123500,DATE_ADD(CURDATE(), INTERVAL 21 DAY),'closed'),
(2,'Recruitment Video','Recruitment Video for Numidia Agency with a focus on Algerian creative production and strong visual identity.',1,'Screenwriter,Actor','Sétif',127000,DATE_ADD(CURDATE(), INTERVAL 22 DAY),'in_progress'),
(3,'Restaurant Campaign','Restaurant Campaign for Atlas Creative with a focus on Algerian creative production and strong visual identity.',2,'Makeup Artist,Fashion Stylist','Batna',130500,DATE_ADD(CURDATE(), INTERVAL 23 DAY),'completed'),
(4,'Sports Highlight Reel','Sports Highlight Reel for Oran Productions with a focus on Algerian creative production and strong visual identity.',3,'Actor,Video Editor','Mostaganem',134000,DATE_ADD(CURDATE(), INTERVAL 24 DAY),'open'),
(5,'TV Series Casting','TV Series Casting for Tlemcen Studio with a focus on Algerian creative production and strong visual identity.',4,'Photographer,Graphic Designer','Ouargla',137500,DATE_ADD(CURDATE(), INTERVAL 25 DAY),'closed'),
(6,'Influencer Content Pack','Influencer Content Pack for Aurès Films with a focus on Algerian creative production and strong visual identity.',5,'Screenwriter,Actor','Alger',141000,DATE_ADD(CURDATE(), INTERVAL 26 DAY),'in_progress'),
(7,'E-commerce Product Shoot','E-commerce Product Shoot for Alger Vision with a focus on Algerian creative production and strong visual identity.',6,'Makeup Artist,Fashion Stylist','Tlemcen',144500,DATE_ADD(CURDATE(), INTERVAL 27 DAY),'completed'),
(8,'Cultural Event Coverage','Cultural Event Coverage for Dz Marketing with a focus on Algerian creative production and strong visual identity.',7,'Actor,Video Editor','Annaba',148000,DATE_ADD(CURDATE(), INTERVAL 28 DAY),'open'),
(9,'Public Service Announcement','Public Service Announcement for Maghreb House with a focus on Algerian creative production and strong visual identity.',1,'Photographer,Graphic Designer','Béjaïa',151500,DATE_ADD(CURDATE(), INTERVAL 29 DAY),'closed'),
(10,'Fashion Week Coverage','Fashion Week Coverage for Baya Studio with a focus on Algerian creative production and strong visual identity.',2,'Screenwriter,Actor','Blida',155000,DATE_ADD(CURDATE(), INTERVAL 30 DAY),'in_progress');
INSERT INTO `portfolio_items` (`talent_id`,`title`,`media_type`,`file_path`,`description`,`is_featured`) VALUES
(1,'LensDz Portfolio 1','image',NULL,'Selected work from LensDz.',1),
(2,'TlemcenFrames Portfolio 2','image',NULL,'Selected work from TlemcenFrames.',1),
(3,'CreativeOran Portfolio 3','video',NULL,'Selected work from CreativeOran.',1),
(4,'CinemaDz Portfolio 4','image',NULL,'Selected work from CinemaDz.',1),
(5,'AtlasDesigner Portfolio 5','image',NULL,'Selected work from AtlasDesigner.',1),
(6,'DzStoryMaker Portfolio 6','video',NULL,'Selected work from DzStoryMaker.',1),
(7,'VisualByAmine Portfolio 7','image',NULL,'Selected work from VisualByAmine.',1),
(8,'SetifEditor Portfolio 8','image',NULL,'Selected work from SetifEditor.',1),
(9,'OranCreator Portfolio 9','video',NULL,'Selected work from OranCreator.',0),
(10,'SaharaLens Portfolio 10','image',NULL,'Selected work from SaharaLens.',0),
(11,'AurèsStyle Portfolio 11','image',NULL,'Selected work from AurèsStyle.',0),
(12,'NumidiaMakeup Portfolio 12','video',NULL,'Selected work from NumidiaMakeup.',0),
(13,'BlidaMotion Portfolio 13','image',NULL,'Selected work from BlidaMotion.',0),
(14,'TiziScript Portfolio 14','image',NULL,'Selected work from TiziScript.',0),
(15,'AdrarFocus Portfolio 15','video',NULL,'Selected work from AdrarFocus.',0);
INSERT INTO `applications` (`project_id`,`talent_id`,`cover_letter`,`status`) VALUES
(1,12,'Interested in collaborating on this project with a strong visual delivery.','approved'),
(2,13,'Interested in collaborating on this project with a strong visual delivery.','rejected'),
(3,14,'Interested in collaborating on this project with a strong visual delivery.','in_progress'),
(4,15,'Interested in collaborating on this project with a strong visual delivery.','completed'),
(5,16,'Interested in collaborating on this project with a strong visual delivery.','pending'),
(6,17,'Interested in collaborating on this project with a strong visual delivery.','approved'),
(7,18,'Interested in collaborating on this project with a strong visual delivery.','rejected'),
(8,19,'Interested in collaborating on this project with a strong visual delivery.','in_progress'),
(9,20,'Interested in collaborating on this project with a strong visual delivery.','completed'),
(10,21,'Interested in collaborating on this project with a strong visual delivery.','pending');
INSERT INTO `requests` (`client_id`,`talent_id`,`project_id`,`message`,`status`) VALUES
(5,22,1,'We would like to discuss availability and project scope for this role.','approved'),
(6,23,2,'We would like to discuss availability and project scope for this role.','rejected'),
(7,24,3,'We would like to discuss availability and project scope for this role.','in_progress'),
(8,25,4,'We would like to discuss availability and project scope for this role.','completed'),
(9,26,5,'We would like to discuss availability and project scope for this role.','pending'),
(10,27,6,'We would like to discuss availability and project scope for this role.','approved'),
(11,28,7,'We would like to discuss availability and project scope for this role.','rejected'),
(12,29,8,'We would like to discuss availability and project scope for this role.','in_progress'),
(13,30,9,'We would like to discuss availability and project scope for this role.','completed'),
(14,31,10,'We would like to discuss availability and project scope for this role.','pending');
INSERT INTO `notifications` (`user_id`,`type`,`title`,`body`,`is_read`) VALUES
(3,'New Request','New mediation request received','A client has requested your talent for a new project.',0),
(4,'New Request','New mediation request received','A client has requested your talent for a new project.',0),
(5,'New Request','New mediation request received','A client has requested your talent for a new project.',0),
(6,'New Request','New mediation request received','A client has requested your talent for a new project.',0),
(7,'New Request','New mediation request received','A client has requested your talent for a new project.',0),
(8,'New Request','New mediation request received','A client has requested your talent for a new project.',0),
(9,'New Request','New mediation request received','A client has requested your talent for a new project.',0),
(10,'New Request','New mediation request received','A client has requested your talent for a new project.',0),
(11,'New Request','New mediation request received','A client has requested your talent for a new project.',0),
(12,'New Request','New mediation request received','A client has requested your talent for a new project.',0);
INSERT INTO `activity_logs` (`user_id`,`action`,`context`,`ip_address`) VALUES
(3,'seed_import','Initial Algerian dataset imported','127.0.0.1'),
(4,'seed_import','Initial Algerian dataset imported','127.0.0.1'),
(5,'seed_import','Initial Algerian dataset imported','127.0.0.1'),
(6,'seed_import','Initial Algerian dataset imported','127.0.0.1'),
(7,'seed_import','Initial Algerian dataset imported','127.0.0.1'),
(8,'seed_import','Initial Algerian dataset imported','127.0.0.1'),
(9,'seed_import','Initial Algerian dataset imported','127.0.0.1'),
(10,'seed_import','Initial Algerian dataset imported','127.0.0.1'),
(11,'seed_import','Initial Algerian dataset imported','127.0.0.1'),
(12,'seed_import','Initial Algerian dataset imported','127.0.0.1');
SET FOREIGN_KEY_CHECKS=1;
SQL;

@mkdir(__DIR__ . '/logs', 0775, true);
@mkdir(__DIR__ . '/uploads/profile_photos', 0775, true);
@mkdir(__DIR__ . '/uploads/portfolio_images', 0775, true);
@mkdir(__DIR__ . '/uploads/portfolio_videos', 0775, true);

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    log_error_message("PHP ERROR [$severity] $message in $file:$line");
    if (error_reporting() === 0) {
        return false;
    }
    return true;
});

set_exception_handler(function (Throwable $e): void {
    log_error_message("UNCAUGHT EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<script src="https://cdn.tailwindcss.com"></script><title>Unexpected Error</title></head><body class="bg-white text-black">';
    echo '<div class="min-h-screen flex items-center justify-center p-6"><div class="max-w-xl w-full rounded-3xl border p-8 shadow-sm">';
    echo '<h1 class="text-2xl font-bold mb-3">Something went wrong</h1>';
    echo '<p class="text-slate-600 mb-4">A technical error was logged. Please try again or check the database configuration.</p>';
    echo '<pre class="text-xs bg-slate-100 rounded-2xl p-4 overflow-auto">' . e($e->getMessage()) . '</pre>';
    echo '</div></div></body></html>';
    exit;
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
        log_error_message("FATAL: {$error['message']} in {$error['file']}:{$error['line']}");
        if (!headers_sent()) {
            http_response_code(500);
        }
    }
});

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self' https://cdn.tailwindcss.com https://fonts.googleapis.com https://fonts.gstatic.com; img-src 'self' data: blob:; media-src 'self' data: blob:; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com;");

function log_error_message(string $message): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents(__DIR__ . '/logs/error.log', $line, FILE_APPEND);
}

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function url(string $page, array $params = []): string {
    $query = array_merge(['page' => $page], $params);
    return '?' . http_build_query($query);
}

function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function token_int(int $len = 32): string {
    return bin2hex(random_bytes($len));
}

function app_now(): string {
    return date('Y-m-d H:i:s');
}

function app_date(): string {
    return date('Y-m-d');
}

function is_post(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function storage_paths(): array {
    return [
        'logs' => __DIR__ . '/logs',
        'profile' => __DIR__ . '/uploads/profile_photos',
        'images' => __DIR__ . '/uploads/portfolio_images',
        'videos' => __DIR__ . '/uploads/portfolio_videos',
    ];
}

class SessionManager {
    public static function start(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION['started_at'])) {
            $_SESSION['started_at'] = time();
        }
    }

    public static function regenerate(): void {
        session_regenerate_id(true);
    }

    public static function destroy(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function connect(): PDO {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return self::$instance;
    }

    public static function pdo(): PDO {
        return self::connect();
    }
}

class Security {
    public static function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = token_int(16);
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . e(self::csrfToken()) . '">';
    }

    public static function verifyCsrf(): void {
        if (!is_post()) {
            return;
        }
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            throw new RuntimeException('Invalid CSRF token.');
        }
    }

    public static function clientIp(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public static function sanitizeText(?string $value): string {
        return trim(preg_replace('/\s+/', ' ', (string)$value));
    }

    public static function canSeePrivateContacts(?array $viewer): bool {
        return $viewer && in_array($viewer['role'], ['super_admin', 'agent'], true);
    }

    public static function validateUpload(array $file, array $allowedExtensions, int $maxBytes): array {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'No file uploaded.'];
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed.'];
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            return ['ok' => false, 'error' => 'File is too large.'];
        }
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            return ['ok' => false, 'error' => 'Unsupported file extension.'];
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
        $allowedMime = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
            'mp4' => ['video/mp4'],
        ];
        $valid = $allowedMime[$ext] ?? [];
        if (!in_array($mime, $valid, true)) {
            return ['ok' => false, 'error' => 'Invalid file MIME type.'];
        }
        return ['ok' => true, 'ext' => $ext, 'mime' => $mime];
    }

    public static function randomSelector(int $bytes = 16): string {
        return bin2hex(random_bytes($bytes));
    }
}

class Validator {
    public static function required(array $data, array $fields): array {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        return $errors;
    }

    public static function email(string $value): bool {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function minLen(string $value, int $min): bool {
        return mb_strlen(trim($value)) >= $min;
    }

    public static function maxLen(string $value, int $max): bool {
        return mb_strlen(trim($value)) <= $max;
    }

    public static function numeric($value): bool {
        return is_numeric($value);
    }

    public static function date(string $value): bool {
        $d = DateTime::createFromFormat('Y-m-d', $value);
        return $d && $d->format('Y-m-d') === $value;
    }
}

class Settings {
    private static array $cache = [];

    public static function all(): array {
        if (self::$cache) {
            return self::$cache;
        }
        $stmt = Database::pdo()->query("SELECT setting_key, setting_value FROM settings");
        self::$cache = [];
        foreach ($stmt->fetchAll() as $row) {
            self::$cache[$row['setting_key']] = $row['setting_value'];
        }
        return self::$cache;
    }

    public static function get(string $key, $default = null) {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, string $value): void {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $value]);
        self::$cache[$key] = $value;
    }
}

class NotificationManager {
    public static function create(int $userId, string $type, string $title, string $body): void {
        $stmt = Database::pdo()->prepare("INSERT INTO notifications (user_id, type, title, body) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $type, $title, $body]);
    }

    public static function createForRoles(array $roles, string $type, string $title, string $body): void {
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $stmt = Database::pdo()->prepare("SELECT id FROM users WHERE role IN ($placeholders) AND is_active = 1");
        $stmt->execute($roles);
        foreach ($stmt->fetchAll() as $row) {
            self::create((int)$row['id'], $type, $title, $body);
        }
    }

    public static function unreadCount(int $userId): int {
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function latest(int $userId, int $limit = 10): array {
        $stmt = Database::pdo()->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT $limit");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function markRead(int $id, int $userId): void {
        $stmt = Database::pdo()->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }
}

class User {
    public static function findById(int $id): ?array {
        $stmt = Database::pdo()->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array {
        $stmt = Database::pdo()->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(array $data): int {
        $stmt = Database::pdo()->prepare("INSERT INTO users (role, full_name, email, phone, password_hash, avatar_path, bio, is_active, is_approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['role'],
            $data['full_name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['password_hash'],
            $data['avatar_path'] ?? null,
            $data['bio'] ?? null,
            $data['is_active'] ?? 1,
            $data['is_approved'] ?? 1,
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function updateLastLogin(int $id): void {
        $stmt = Database::pdo()->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function countByRole(string $role): int {
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM users WHERE role = ?");
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }

    public static function allByRole(?string $role = null): array {
        if ($role) {
            $stmt = Database::pdo()->prepare("SELECT * FROM users WHERE role = ? ORDER BY id DESC");
            $stmt->execute([$role]);
            return $stmt->fetchAll();
        }
        return Database::pdo()->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
    }

    public static function revenue(): float {
        $stmt = Database::pdo()->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'paid'");
        return (float)$stmt->fetchColumn();
    }
}

class Talent {
    public static function profileByUserId(int $userId): ?array {
        $stmt = Database::pdo()->prepare("SELECT t.*, c.name AS category_name, c.slug AS category_slug, u.full_name, u.email, u.phone, u.avatar_path, u.bio AS user_bio, u.is_approved AS user_approved
            FROM talents t
            JOIN categories c ON c.id = t.category_id
            JOIN users u ON u.id = t.user_id
            WHERE t.user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function profileById(int $id): ?array {
        $stmt = Database::pdo()->prepare("SELECT t.*, c.name AS category_name, c.slug AS category_slug, u.full_name, u.email, u.phone, u.avatar_path, u.bio AS user_bio
            FROM talents t
            JOIN categories c ON c.id = t.category_id
            JOIN users u ON u.id = t.user_id
            WHERE t.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, array $data): int {
        $stmt = Database::pdo()->prepare("INSERT INTO talents (user_id, talent_code, nickname, category_id, wilaya, bio, skills, experience_years, availability, profile_photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $data['talent_code'],
            $data['nickname'],
            $data['category_id'],
            $data['wilaya'],
            $data['bio'],
            $data['skills'],
            $data['experience_years'],
            $data['availability'],
            $data['profile_photo'] ?? null,
            $data['status'] ?? 'pending',
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void {
        $stmt = Database::pdo()->prepare("UPDATE talents SET nickname=?, category_id=?, wilaya=?, bio=?, skills=?, experience_years=?, availability=?, profile_photo=?, status=? WHERE id=?");
        $stmt->execute([
            $data['nickname'],
            $data['category_id'],
            $data['wilaya'],
            $data['bio'],
            $data['skills'],
            $data['experience_years'],
            $data['availability'],
            $data['profile_photo'] ?? null,
            $data['status'] ?? 'pending',
            $id,
        ]);
    }

    public static function search(array $filters = [], int $limit = 24): array {
        $sql = "SELECT t.*, c.name AS category_name, c.slug AS category_slug, u.full_name, u.avatar_path
                FROM talents t
                JOIN categories c ON c.id = t.category_id
                JOIN users u ON u.id = t.user_id
                WHERE t.status = 'approved'";
        $params = [];
        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }
        if (!empty($filters['wilaya'])) {
            $sql .= " AND t.wilaya = ?";
            $params[] = $filters['wilaya'];
        }
        if (!empty($filters['q'])) {
            $sql .= " AND (t.nickname LIKE ? OR t.bio LIKE ? OR t.skills LIKE ? OR t.talent_code LIKE ?)";
            $q = '%' . $filters['q'] . '%';
            array_push($params, $q, $q, $q, $q);
        }
        $sql .= " ORDER BY t.id DESC LIMIT " . (int)$limit;
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function featured(int $limit = 6): array {
        $stmt = Database::pdo()->prepare("SELECT t.*, c.name AS category_name, u.avatar_path FROM talents t JOIN categories c ON c.id = t.category_id JOIN users u ON u.id = t.user_id WHERE t.status = 'approved' ORDER BY t.id DESC LIMIT $limit");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countApproved(): int {
        return (int)Database::pdo()->query("SELECT COUNT(*) FROM talents WHERE status = 'approved'")->fetchColumn();
    }

    public static function countPending(): int {
        return (int)Database::pdo()->query("SELECT COUNT(*) FROM talents WHERE status = 'pending'")->fetchColumn();
    }

    public static function all(): array {
        return Database::pdo()->query("SELECT * FROM talents ORDER BY id DESC")->fetchAll();
    }

    public static function portfolio(int $talentId): array {
        $stmt = Database::pdo()->prepare("SELECT * FROM portfolio_items WHERE talent_id = ? ORDER BY is_featured DESC, id DESC");
        $stmt->execute([$talentId]);
        return $stmt->fetchAll();
    }

    public static function portfolioCount(int $talentId): int {
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM portfolio_items WHERE talent_id = ?");
        $stmt->execute([$talentId]);
        return (int)$stmt->fetchColumn();
    }

    public static function completion(int $talentId): int {
        $talent = self::profileById($talentId);
        if (!$talent) return 0;
        $fields = ['nickname', 'category_id', 'wilaya', 'bio', 'skills', 'experience_years', 'availability'];
        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($talent[$field])) $filled++;
        }
        $profile = (int)round(($filled / count($fields)) * 100);
        $portfolio = self::portfolioCount($talentId) > 0 ? 20 : 0;
        $photo = !empty($talent['profile_photo']) ? 10 : 0;
        return min(100, $profile + $portfolio + $photo);
    }
}

class Client {
    public static function profileByUserId(int $userId): ?array {
        $stmt = Database::pdo()->prepare("SELECT c.*, u.full_name, u.email, u.phone, u.avatar_path, u.bio AS user_bio FROM clients c JOIN users u ON u.id = c.user_id WHERE c.user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function profileById(int $id): ?array {
        $stmt = Database::pdo()->prepare("SELECT c.*, u.full_name, u.email, u.phone, u.avatar_path, u.bio AS user_bio FROM clients c JOIN users u ON u.id = c.user_id WHERE c.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, array $data): int {
        $stmt = Database::pdo()->prepare("INSERT INTO clients (user_id, company_name, wilaya, company_bio, subscription_status, trial_ends_at, subscription_ends_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $data['company_name'],
            $data['wilaya'],
            $data['company_bio'] ?? null,
            $data['subscription_status'] ?? 'trial',
            $data['trial_ends_at'] ?? null,
            $data['subscription_ends_at'] ?? null,
            $data['status'] ?? 'approved',
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function count(): int {
        return (int)Database::pdo()->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    }

    public static function all(): array {
        return Database::pdo()->query("SELECT * FROM clients ORDER BY id DESC")->fetchAll();
    }

    public static function dashboardStats(int $clientId): array {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $projects = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $requests = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN projects p ON p.id = a.project_id WHERE p.client_id = ?");
        $stmt->execute([$clientId]);
        $applications = (int)$stmt->fetchColumn();

        return compact('projects', 'requests', 'applications');
    }
}

class Project {
    public static function create(int $clientId, array $data): int {
        $stmt = Database::pdo()->prepare("INSERT INTO projects (client_id, title, description, category_id, required_roles, wilaya, budget, deadline, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $clientId,
            $data['title'],
            $data['description'],
            $data['category_id'] ?: null,
            $data['required_roles'] ?? null,
            $data['wilaya'],
            $data['budget'],
            $data['deadline'] ?? null,
            $data['status'] ?? 'open',
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void {
        $stmt = Database::pdo()->prepare("UPDATE projects SET title=?, description=?, category_id=?, required_roles=?, wilaya=?, budget=?, deadline=?, status=? WHERE id=?");
        $stmt->execute([
            $data['title'],
            $data['description'],
            $data['category_id'] ?: null,
            $data['required_roles'] ?? null,
            $data['wilaya'],
            $data['budget'],
            $data['deadline'] ?? null,
            $data['status'],
            $id,
        ]);
    }

    public static function close(int $id): void {
        $stmt = Database::pdo()->prepare("UPDATE projects SET status = 'closed' WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function reopen(int $id): void {
        $stmt = Database::pdo()->prepare("UPDATE projects SET status = 'open' WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function find(int $id): ?array {
        $stmt = Database::pdo()->prepare("SELECT p.*, c.company_name, c.wilaya AS client_wilaya, c.user_id AS client_user_id, u.full_name, u.email, u.phone
            FROM projects p
            JOIN clients c ON c.id = p.client_id
            JOIN users u ON u.id = c.user_id
            WHERE p.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function search(array $filters = [], int $limit = 24): array {
        $sql = "SELECT p.*, c.company_name, c.wilaya AS client_wilaya, cat.name AS category_name
                FROM projects p
                JOIN clients c ON c.id = p.client_id
                LEFT JOIN categories cat ON cat.id = p.category_id
                WHERE 1=1";
        $params = [];
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['wilaya'])) {
            $sql .= " AND p.wilaya = ?";
            $params[] = $filters['wilaya'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }
        if (!empty($filters['q'])) {
            $sql .= " AND (p.title LIKE ? OR p.description LIKE ? OR p.required_roles LIKE ?)";
            $q = '%' . $filters['q'] . '%';
            array_push($params, $q, $q, $q);
        }
        $sql .= " ORDER BY p.id DESC LIMIT " . (int)$limit;
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function latest(int $limit = 6): array {
        $stmt = Database::pdo()->prepare("SELECT p.*, c.company_name, cat.name AS category_name FROM projects p JOIN clients c ON c.id = p.client_id LEFT JOIN categories cat ON cat.id = p.category_id ORDER BY p.id DESC LIMIT $limit");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function allByClient(int $clientId): array {
        $stmt = Database::pdo()->prepare("SELECT * FROM projects WHERE client_id = ? ORDER BY id DESC");
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    public static function countOpen(): int {
        return (int)Database::pdo()->query("SELECT COUNT(*) FROM projects WHERE status = 'open'")->fetchColumn();
    }
}

class Application {
    public static function apply(int $projectId, int $talentId, string $coverLetter): int {
        $stmt = Database::pdo()->prepare("SELECT id FROM applications WHERE project_id = ? AND talent_id = ?");
        $stmt->execute([$projectId, $talentId]);
        if ($stmt->fetch()) {
            throw new RuntimeException('You already applied to this project.');
        }
        $stmt = Database::pdo()->prepare("INSERT INTO applications (project_id, talent_id, cover_letter, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$projectId, $talentId, $coverLetter]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function byTalent(int $talentId): array {
        $stmt = Database::pdo()->prepare("SELECT a.*, p.title, p.status AS project_status, c.company_name
            FROM applications a
            JOIN projects p ON p.id = a.project_id
            JOIN clients c ON c.id = p.client_id
            WHERE a.talent_id = ? ORDER BY a.id DESC");
        $stmt->execute([$talentId]);
        return $stmt->fetchAll();
    }

    public static function byProject(int $projectId): array {
        $stmt = Database::pdo()->prepare("SELECT a.*, t.nickname, t.talent_code, c.name AS category_name, u.full_name
            FROM applications a
            JOIN talents t ON t.id = a.talent_id
            JOIN categories c ON c.id = t.category_id
            JOIN users u ON u.id = t.user_id
            WHERE a.project_id = ? ORDER BY a.id DESC");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    public static function approve(int $id): void {
        $stmt = Database::pdo()->prepare("UPDATE applications SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function reject(int $id): void {
        $stmt = Database::pdo()->prepare("UPDATE applications SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function markInProgress(int $id): void {
        $stmt = Database::pdo()->prepare("UPDATE applications SET status = 'in_progress' WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function countByTalent(int $talentId): int {
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM applications WHERE talent_id = ?");
        $stmt->execute([$talentId]);
        return (int)$stmt->fetchColumn();
    }

    public static function pendingCount(): int {
        return (int)Database::pdo()->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn();
    }
}

class RequestManager {
    public static function create(int $clientId, int $talentId, ?int $projectId, string $message): int {
        $stmt = Database::pdo()->prepare("SELECT id FROM requests WHERE client_id = ? AND talent_id = ? AND IFNULL(project_id,0) = IFNULL(?,0) AND status IN ('pending','approved','in_progress')");
        $stmt->execute([$clientId, $talentId, $projectId]);
        if ($stmt->fetch()) {
            throw new RuntimeException('A request already exists for this talent.');
        }
        $stmt = Database::pdo()->prepare("INSERT INTO requests (client_id, talent_id, project_id, message, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$clientId, $talentId, $projectId, $message]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function byClient(int $clientId): array {
        $stmt = Database::pdo()->prepare("SELECT r.*, t.nickname, t.talent_code, p.title AS project_title
            FROM requests r
            JOIN talents t ON t.id = r.talent_id
            LEFT JOIN projects p ON p.id = r.project_id
            WHERE r.client_id = ? ORDER BY r.id DESC");
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    public static function byTalent(int $talentId): array {
        $stmt = Database::pdo()->prepare("SELECT r.*, p.title AS project_title, c.company_name
            FROM requests r
            LEFT JOIN projects p ON p.id = r.project_id
            JOIN clients c ON c.id = r.client_id
            WHERE r.talent_id = ? ORDER BY r.id DESC");
        $stmt->execute([$talentId]);
        return $stmt->fetchAll();
    }

    public static function latestPending(int $limit = 10): array {
        $stmt = Database::pdo()->prepare("SELECT r.*, t.nickname, p.title AS project_title, c.company_name
            FROM requests r
            JOIN talents t ON t.id = r.talent_id
            LEFT JOIN projects p ON p.id = r.project_id
            JOIN clients c ON c.id = r.client_id
            WHERE r.status = 'pending'
            ORDER BY r.id DESC LIMIT $limit");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status): void {
        $stmt = Database::pdo()->prepare("UPDATE requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    public static function pendingCount(): int {
        return (int)Database::pdo()->query("SELECT COUNT(*) FROM requests WHERE status = 'pending'")->fetchColumn();
    }
}

class UploadManager {
    public static function save(array $file, string $targetType): ?string {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'mp4'];
        $check = Security::validateUpload($file, $allowed, MAX_UPLOAD_SIZE);
        if (!$check['ok']) {
            throw new RuntimeException($check['error']);
        }
        $ext = $check['ext'];
        $folder = in_array($ext, ['mp4'], true) ? 'videos' : ($targetType === 'profile' ? 'profile' : 'images');
        $dir = storage_paths()[$folder];
        $relativeFolder = $folder === 'profile' ? 'uploads/profile_photos' : ($folder === 'images' ? 'uploads/portfolio_images' : 'uploads/portfolio_videos');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!is_writable($dir)) {
            throw new RuntimeException('Upload folder is not writable.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $path = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new RuntimeException('Unable to save uploaded file.');
        }
        return $relativeFolder . '/' . $name;
    }
}

class Auth {
    public static function currentUser(): ?array {
        if (!empty($_SESSION['user_id'])) {
            $stmt = Database::pdo()->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([(int)$_SESSION['user_id']]);
            $user = $stmt->fetch();
            return $user ?: null;
        }
        return null;
    }

    public static function isLoggedIn(): bool {
        return self::currentUser() !== null;
    }

    public static function login(string $email, string $password, bool $remember = false): void {
        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new RuntimeException('Invalid email or password.');
        }
        if ((int)$user['is_active'] !== 1) {
            throw new RuntimeException('This account is disabled.');
        }
        SessionManager::regenerate();
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        User::updateLastLogin((int)$user['id']);

        if ($remember) {
            self::issueRememberMe((int)$user['id']);
        }
        log_action((int)$user['id'], 'login', 'User logged in');
    }

    public static function register(array $data): int {
        $role = $data['role'];
        if (!in_array($role, ['project_owner', 'talent'], true)) {
            throw new RuntimeException('Only project owners and talents can register.');
        }
        if (User::findByEmail($data['email'])) {
            throw new RuntimeException('This email is already registered.');
        }

        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $userId = User::create([
            'role' => $role,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password_hash' => $passwordHash,
            'bio' => $data['bio'] ?? null,
            'is_active' => 1,
            'is_approved' => 1,
        ]);

        if ($role === 'talent') {
            $categoryStmt = Database::pdo()->prepare("SELECT id FROM categories ORDER BY id ASC LIMIT 1");
            $categoryStmt->execute();
            $categoryId = (int)$categoryStmt->fetchColumn();
            $chosenCategory = (int)($data['category_id'] ?: $categoryId);
            Talent::create($userId, [
                'talent_code' => self::generateTalentCode($chosenCategory),
                'nickname' => $data['nickname'],
                'category_id' => $chosenCategory,
                'wilaya' => $data['wilaya'],
                'bio' => $data['bio'] ?? '',
                'skills' => $data['skills'] ?? '',
                'experience_years' => (int)($data['experience_years'] ?? 0),
                'availability' => $data['availability'] ?? 'Available',
                'status' => 'pending',
                'profile_photo' => null,
            ]);
        } else {
            $trialDays = (int)Settings::get('free_trial_days', 30);
            Client::create($userId, [
                'company_name' => $data['company_name'],
                'wilaya' => $data['wilaya'],
                'company_bio' => $data['bio'] ?? '',
                'subscription_status' => 'trial',
                'trial_ends_at' => date('Y-m-d', strtotime("+$trialDays days")),
                'subscription_ends_at' => null,
                'status' => 'approved',
            ]);
            $client = Client::profileByUserId($userId);
            if ($client) {
                $amount = 0.00;
                $stmt = Database::pdo()->prepare("INSERT INTO subscriptions (client_id, plan_name, amount, starts_at, ends_at, status) VALUES (?, 'Trial', ?, CURDATE(), ?, 'trial')");
                $stmt->execute([(int)$client['id'], $amount, date('Y-m-d', strtotime("+$trialDays days"))]);
            }
        }

        log_action($userId, 'register', 'New account registered');
        return $userId;
    }

    private static function generateTalentCode(int $categoryId): string {
        $map = [1 => 'ACT', 2 => 'SCR', 3 => 'PHO', 4 => 'EDI', 5 => 'DES', 6 => 'MUA', 7 => 'STY'];
        $prefix = $map[$categoryId] ?? 'TAL';
        do {
            $code = $prefix . '-' . random_int(1000, 9999);
            $stmt = Database::pdo()->prepare("SELECT id FROM talents WHERE talent_code = ?");
            $stmt->execute([$code]);
        } while ($stmt->fetch());
        return $code;
    }

    public static function logout(): void {
        if (!empty($_SESSION['user_id'])) {
            log_action((int)$_SESSION['user_id'], 'logout', 'User logged out');
        }
        if (!empty($_COOKIE[REMEMBER_COOKIE])) {
            self::clearRememberCookie();
        }
        SessionManager::destroy();
    }

    private static function issueRememberMe(int $userId): void {
        $selector = Security::randomSelector(8);
        $token = Security::randomSelector(32);
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        $stmt = Database::pdo()->prepare("INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $selector, $hash, $expires]);
        setcookie(REMEMBER_COOKIE, $selector . ':' . $token, [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function clearRememberCookie(): void {
        setcookie(REMEMBER_COOKIE, '', time() - 3600, '/');
    }

    public static function checkRememberMe(): void {
        if (self::isLoggedIn() || empty($_COOKIE[REMEMBER_COOKIE])) {
            return;
        }
        $parts = explode(':', (string)$_COOKIE[REMEMBER_COOKIE], 2);
        if (count($parts) !== 2) {
            return;
        }
        [$selector, $token] = $parts;
        $stmt = Database::pdo()->prepare("SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > NOW()");
        $stmt->execute([$selector]);
        $row = $stmt->fetch();
        if (!$row || !hash_equals($row['token_hash'], hash('sha256', $token))) {
            return;
        }
        SessionManager::regenerate();
        $_SESSION['user_id'] = (int)$row['user_id'];
        $user = User::findById((int)$row['user_id']);
        if ($user) {
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
        }
    }

    public static function requestReset(string $email): string {
        $user = User::findByEmail($email);
        if (!$user) {
            return '';
        }
        $token = Security::randomSelector(24);
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $stmt = Database::pdo()->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([(int)$user['id'], $hash, $expires]);
        return url('reset-password', ['token' => $token]);
    }

    public static function resetPassword(string $token, string $newPassword): void {
        $hash = hash('sha256', $token);
        $stmt = Database::pdo()->prepare("SELECT * FROM password_resets WHERE token_hash = ? AND expires_at > NOW() AND used_at IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Invalid or expired reset token.');
        }
        $stmt = Database::pdo()->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), (int)$row['user_id']]);
        $stmt = Database::pdo()->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
        $stmt->execute([(int)$row['id']]);
    }

    public static function requireLogin(): array {
        $user = self::currentUser();
        if (!$user) {
            redirect(url('login'));
        }
        return $user;
    }

    public static function requireRole(array $roles): array {
        $user = self::requireLogin();
        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            echo '<div class="p-8 text-center">Access denied.</div>';
            exit;
        }
        return $user;
    }
}

class AdminPanel {
    public static function stats(): array {
        return [
            'talents' => Talent::countApproved(),
            'clients' => Client::count(),
            'projects' => Project::countOpen(),
            'requests' => RequestManager::pendingCount(),
            'applications' => Application::pendingCount(),
            'revenue' => User::revenue(),
            'pending_talents' => Talent::countPending(),
            'unread_notifications' => 0,
        ];
    }

    public static function render(): void {
        $user = Auth::requireRole(['super_admin', 'agent']);
        $stats = self::stats();
        $section = $_GET['section'] ?? 'overview';
        echo '<div class="grid lg:grid-cols-4 gap-6">';
        foreach ([
            ['Talents', $stats['talents']],
            ['Clients', $stats['clients']],
            ['Projects', $stats['projects']],
            ['Revenue (DZD)', number_format($stats['revenue'], 2)],
        ] as $card) {
            echo '<div class="rounded-3xl border bg-white p-6 shadow-sm"><div class="text-sm text-slate-500">' . e($card[0]) . '</div><div class="text-3xl font-bold mt-2">' . e((string)$card[1]) . '</div></div>';
        }
        echo '</div>';

        echo '<div class="mt-8 grid lg:grid-cols-4 gap-6">';
        echo '<div class="lg:col-span-3 space-y-6">';
        echo self::tabs($section, $user['role']);
        if ($section === 'users') self::renderUsers($user['role']);
        elseif ($section === 'talents') self::renderTalents();
        elseif ($section === 'projects') self::renderProjects();
        elseif ($section === 'requests') self::renderRequests();
        elseif ($section === 'settings') self::renderSettings($user['role']);
        elseif ($section === 'payments') self::renderPayments();
        else self::renderOverview();
        echo '</div>';
        echo '<div class="space-y-6">';
        echo self::renderSideWidget();
        echo '</div>';
        echo '</div>';
    }

    private static function tabs(string $section, string $role): string {
        $items = [
            'overview' => 'Overview',
            'users' => 'Users',
            'talents' => 'Talents',
            'projects' => 'Projects',
            'requests' => 'Requests',
            'settings' => 'Settings',
            'payments' => 'Payments',
        ];
        if ($role === 'agent') {
            unset($items['users']);
        }
        $html = '<div class="flex flex-wrap gap-2">';
        foreach ($items as $key => $label) {
            $active = $section === $key ? 'bg-black text-white' : 'bg-white border text-slate-700';
            $html .= '<a class="px-4 py-2 rounded-full text-sm ' . $active . '" href="' . e(url('dashboard', ['section' => $key])) . '">' . e($label) . '</a>';
        }
        $html .= '</div>';
        return $html;
    }

    private static function renderOverview(): void {
        $recentRequests = RequestManager::latestPending(5);
        echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        echo '<h2 class="text-xl font-bold mb-4">Latest Pending Requests</h2>';
        echo '<div class="space-y-3">';
        foreach ($recentRequests as $r) {
            echo '<div class="flex items-center justify-between gap-4 rounded-2xl border p-4">';
            echo '<div><div class="font-semibold">' . e($r['company_name']) . ' → ' . e($r['nickname']) . '</div><div class="text-sm text-slate-500">' . e($r['project_title'] ?? 'No project linked') . '</div></div>';
            echo '<div class="text-xs px-3 py-1 rounded-full bg-pink-100 text-pink-700">' . e($r['status']) . '</div>';
            echo '</div>';
        }
        if (!$recentRequests) echo '<div class="text-slate-500">No pending requests.</div>';
        echo '</div></div>';
    }

    private static function renderUsers(string $role): void {
        echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        echo '<h2 class="text-xl font-bold mb-4">Manage Users</h2>';
        $users = User::allByRole();
        echo '<div class="overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left text-slate-500 border-b"><th class="py-3">Name</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr></thead><tbody>';
        foreach ($users as $u) {
            echo '<tr class="border-b"><td class="py-3">' . e($u['full_name']) . '</td><td>' . e($u['email']) . '</td><td>' . e($u['role']) . '</td><td>' . ((int)$u['is_active'] ? 'Active' : 'Disabled') . '</td><td class="py-2">';
            if ($role === 'super_admin') {
                echo '<form method="post" class="flex gap-2 items-center">';
                echo Security::csrfField();
                echo '<input type="hidden" name="action" value="admin_toggle_user">';
                echo '<input type="hidden" name="user_id" value="' . (int)$u['id'] . '">';
                echo '<select name="new_role" class="border rounded-xl px-2 py-1 text-xs">';
                foreach (['super_admin','agent','project_owner','talent'] as $r) {
                    $sel = $u['role'] === $r ? 'selected' : '';
                    echo '<option value="' . e($r) . '" ' . $sel . '>' . e($r) . '</option>';
                }
                echo '</select>';
                echo '<button class="px-3 py-1 rounded-xl bg-black text-white text-xs">Save</button>';
                echo '</form>';
            } else {
                echo '<span class="text-xs text-slate-400">Agent view</span>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table></div></div>';
    }

    private static function renderTalents(): void {
        $talents = Talent::all();
        echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        echo '<h2 class="text-xl font-bold mb-4">Talent Approvals</h2>';
        echo '<div class="space-y-3">';
        foreach ($talents as $t) {
            $profile = User::findById((int)$t['user_id']);
            echo '<div class="rounded-2xl border p-4 flex items-center justify-between gap-4">';
            echo '<div><div class="font-semibold">' . e($t['nickname']) . ' <span class="text-xs text-slate-500">' . e($t['talent_code']) . '</span></div><div class="text-sm text-slate-500">' . e($t['wilaya']) . ' · ' . e($t['status']) . '</div></div>';
            echo '<div class="flex gap-2">';
            if ($t['status'] !== 'approved') {
                echo '<form method="post">' . Security::csrfField() . '<input type="hidden" name="action" value="admin_approve_talent"><input type="hidden" name="talent_id" value="' . (int)$t['id'] . '"><button class="px-3 py-1 rounded-xl bg-pink-500 text-white text-xs">Approve</button></form>';
            }
            echo '</div></div>';
        }
        echo '</div></div>';
    }

    private static function renderProjects(): void {
        $projects = Project::latest(20);
        echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        echo '<h2 class="text-xl font-bold mb-4">Projects</h2>';
        echo '<div class="space-y-3">';
        foreach ($projects as $p) {
            echo '<div class="rounded-2xl border p-4 flex justify-between gap-4">';
            echo '<div><div class="font-semibold">' . e($p['title']) . '</div><div class="text-sm text-slate-500">' . e($p['company_name']) . ' · ' . e($p['wilaya']) . '</div></div>';
            echo '<div class="text-xs px-3 py-1 rounded-full bg-slate-100">' . e($p['status']) . '</div>';
            echo '</div>';
        }
        echo '</div></div>';
    }

    private static function renderRequests(): void {
        $requests = RequestManager::latestPending(20);
        echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        echo '<h2 class="text-xl font-bold mb-4">Request Mediation</h2>';
        echo '<div class="space-y-3">';
        foreach ($requests as $r) {
            echo '<div class="rounded-2xl border p-4">';
            echo '<div class="flex justify-between gap-4"><div><div class="font-semibold">' . e($r['company_name']) . ' requested ' . e($r['nickname']) . '</div><div class="text-sm text-slate-500">' . e($r['project_title'] ?? 'No project') . '</div></div>';
            echo '<div class="text-xs px-3 py-1 rounded-full bg-amber-100 text-amber-700">' . e($r['status']) . '</div></div>';
            echo '<div class="mt-3 flex gap-2">';
            echo '<form method="post">' . Security::csrfField() . '<input type="hidden" name="action" value="request_status"><input type="hidden" name="request_id" value="' . (int)$r['id'] . '"><input type="hidden" name="status" value="approved"><button class="px-3 py-1 rounded-xl bg-green-600 text-white text-xs">Approve</button></form>';
            echo '<form method="post">' . Security::csrfField() . '<input type="hidden" name="action" value="request_status"><input type="hidden" name="request_id" value="' . (int)$r['id'] . '"><input type="hidden" name="status" value="rejected"><button class="px-3 py-1 rounded-xl bg-slate-800 text-white text-xs">Reject</button></form>';
            echo '</div></div>';
        }
        if (!$requests) echo '<div class="text-slate-500">No pending requests.</div>';
        echo '</div></div>';
    }

    private static function renderSettings(string $role): void {
        $settings = Settings::all();
        echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        echo '<h2 class="text-xl font-bold mb-4">Platform Settings</h2>';
        echo '<form method="post" class="grid md:grid-cols-2 gap-4">';
        echo Security::csrfField();
        echo '<input type="hidden" name="action" value="save_settings">';
        foreach (['monthly_subscription_fee' => 'Monthly Subscription Fee', 'special_monthly_offer' => 'Special Monthly Offer', 'agent_monthly_pay' => 'Agent Monthly Pay', 'free_trial_days' => 'Free Trial Days', 'platform_commission_percent' => 'Commission Percent'] as $k => $label) {
            if ($role === 'agent' && $k === 'agent_monthly_pay') continue;
            echo '<label class="block"><span class="text-sm font-medium">' . e($label) . '</span><input name="' . e($k) . '" value="' . e((string)($settings[$k] ?? '')) . '" class="mt-1 w-full rounded-2xl border px-4 py-3"></label>';
        }
        echo '<div class="md:col-span-2"><button class="px-5 py-3 rounded-2xl bg-black text-white">Save Settings</button></div>';
        echo '</form></div>';
    }

    private static function renderPayments(): void {
        $rows = Database::pdo()->query("SELECT p.*, c.company_name FROM payments p LEFT JOIN clients c ON c.id = p.client_id ORDER BY p.id DESC LIMIT 20")->fetchAll();
        echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        echo '<h2 class="text-xl font-bold mb-4">Revenue & Payments</h2>';
        echo '<div class="overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left border-b text-slate-500"><th class="py-3">Client</th><th>Purpose</th><th>Amount</th><th>Status</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr class="border-b"><td class="py-3">' . e($r['company_name'] ?? '—') . '</td><td>' . e($r['purpose']) . '</td><td>' . e(number_format((float)$r['amount'], 2)) . '</td><td>' . e($r['status']) . '</td></tr>';
        }
        echo '</tbody></table></div></div>';
    }

    private static function renderSideWidget(): string {
        $out = '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        $out .= '<h3 class="font-bold mb-3">Platform Revenue</h3>';
        $out .= '<div class="text-3xl font-black">' . e(number_format(User::revenue(), 2)) . ' DZD</div>';
        $out .= '<p class="text-sm text-slate-500 mt-2">Monthly subscription and mediation revenue.</p>';
        $out .= '</div>';
        return $out;
    }
}

class TalentPanel {
    public static function render(array $user): void {
        $profile = Talent::profileByUserId((int)$user['id']);
        $stats = [
            'completion' => $profile ? Talent::completion((int)$profile['id']) : 0,
            'portfolio' => $profile ? Talent::portfolioCount((int)$profile['id']) : 0,
            'applications' => $profile ? Application::countByTalent((int)$profile['id']) : 0,
        ];
        echo '<div class="grid lg:grid-cols-3 gap-6">';
        foreach ([
            ['Profile Completion', $stats['completion'] . '%'],
            ['Portfolio Items', (string)$stats['portfolio']],
            ['Applications', (string)$stats['applications']],
        ] as $card) {
            echo '<div class="rounded-3xl border bg-white p-6 shadow-sm"><div class="text-sm text-slate-500">' . e($card[0]) . '</div><div class="text-3xl font-bold mt-2">' . e($card[1]) . '</div></div>';
        }
        echo '</div>';

        echo '<div class="mt-8 grid lg:grid-cols-3 gap-6">';
        echo '<div class="lg:col-span-2 space-y-6">';
        self::renderProfileEditor($profile);
        self::renderPortfolio($profile);
        echo '</div>';
        echo '<div class="space-y-6">';
        self::renderApplications($profile);
        echo '</div>';
        echo '</div>';
    }

    private static function renderProfileEditor(?array $profile): void {
        $categories = Database::pdo()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
        echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        echo '<h2 class="text-xl font-bold mb-4">My Talent Profile</h2>';
        echo '<form method="post" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4">';
        echo Security::csrfField();
        echo '<input type="hidden" name="action" value="save_talent_profile">';
        echo '<label><span class="text-sm font-medium">Nickname</span><input name="nickname" value="' . e($profile['nickname'] ?? '') . '" class="mt-1 w-full rounded-2xl border px-4 py-3"></label>';
        echo '<label><span class="text-sm font-medium">Category</span><select name="category_id" class="mt-1 w-full rounded-2xl border px-4 py-3">';
        foreach ($categories as $cat) {
            $sel = isset($profile['category_id']) && (int)$profile['category_id'] === (int)$cat['id'] ? 'selected' : '';
            echo '<option value="' . (int)$cat['id'] . '" ' . $sel . '>' . e($cat['name']) . '</option>';
        }
        echo '</select></label>';
        echo '<label><span class="text-sm font-medium">Wilaya</span><input name="wilaya" value="' . e($profile['wilaya'] ?? '') . '" class="mt-1 w-full rounded-2xl border px-4 py-3"></label>';
        echo '<label><span class="text-sm font-medium">Experience (years)</span><input type="number" min="0" name="experience_years" value="' . e((string)($profile['experience_years'] ?? 0)) . '" class="mt-1 w-full rounded-2xl border px-4 py-3"></label>';
        echo '<label class="md:col-span-2"><span class="text-sm font-medium">Skills</span><textarea name="skills" rows="3" class="mt-1 w-full rounded-2xl border px-4 py-3">' . e($profile['skills'] ?? '') . '</textarea></label>';
        echo '<label class="md:col-span-2"><span class="text-sm font-medium">Bio</span><textarea name="bio" rows="4" class="mt-1 w-full rounded-2xl border px-4 py-3">' . e($profile['bio'] ?? '') . '</textarea></label>';
        echo '<label><span class="text-sm font-medium">Availability</span><input name="availability" value="' . e($profile['availability'] ?? 'Available') . '" class="mt-1 w-full rounded-2xl border px-4 py-3"></label>';
        echo '<label><span class="text-sm font-medium">Profile Photo</span><input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp" class="mt-1 w-full rounded-2xl border px-4 py-3 bg-white"></label>';
        echo '<div class="md:col-span-2"><button class="px-5 py-3 rounded-2xl bg-black text-white">Save Profile</button></div>';
        echo '</form></div>';
    }

    private static function renderPortfolio(?array $profile): void {
        if (!$profile) return;
        $items = Talent::portfolio((int)$profile['id']);
        echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        echo '<div class="flex items-center justify-between mb-4"><h2 class="text-xl font-bold">Portfolio</h2><span class="text-sm text-slate-500">' . count($items) . ' items</span></div>';
        echo '<form method="post" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4 mb-6">';
        echo Security::csrfField();
        echo '<input type="hidden" name="action" value="upload_portfolio">';
        echo '<label><span class="text-sm font-medium">Title</span><input name="title" class="mt-1 w-full rounded-2xl border px-4 py-3"></label>';
        echo '<label><span class="text-sm font-medium">Media Type</span><select name="media_type" class="mt-1 w-full rounded-2xl border px-4 py-3"><option value="image">Image</option><option value="video">Video</option></select></label>';
        echo '<label class="md:col-span-2"><span class="text-sm font-medium">Description</span><textarea name="description" rows="3" class="mt-1 w-full rounded-2xl border px-4 py-3"></textarea></label>';
        echo '<label><span class="text-sm font-medium">File</span><input type="file" name="media_file" accept=".jpg,.jpeg,.png,.webp,.mp4" class="mt-1 w-full rounded-2xl border px-4 py-3 bg-white"></label>';
        echo '<label class="flex items-center gap-2 pt-7"><input type="checkbox" name="is_featured" value="1"> <span class="text-sm">Feature this work</span></label>';
        echo '<div class="md:col-span-2"><button class="px-5 py-3 rounded-2xl bg-pink-500 text-white">Upload</button></div>';
        echo '</form>';
        echo '<div class="grid md:grid-cols-2 gap-4">';
        foreach ($items as $item) {
            echo '<div class="rounded-2xl border p-4">';
            echo '<div class="font-semibold">' . e($item['title']) . '</div><div class="text-sm text-slate-500">' . e($item['media_type']) . '</div>';
            echo '<div class="mt-2 text-sm text-slate-600">' . e($item['description'] ?? '') . '</div>';
            if ($item['file_path']) echo '<div class="mt-3 text-xs text-slate-500">' . e($item['file_path']) . '</div>';
            echo '</div>';
        }
        if (!$items) echo '<div class="text-slate-500">No portfolio items yet.</div>';
        echo '</div></div>';
    }

    private static function renderApplications(?array $profile): void {
        echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        echo '<h2 class="text-xl font-bold mb-4">My Applications</h2>';
        if (!$profile) { echo '<div class="text-slate-500">Complete your profile first.</div>'; echo '</div>'; return; }
        $apps = Application::byTalent((int)$profile['id']);
        echo '<div class="space-y-3">';
        foreach ($apps as $a) {
            echo '<div class="rounded-2xl border p-4">';
            echo '<div class="font-semibold">' . e($a['title']) . '</div>';
            echo '<div class="text-sm text-slate-500">' . e($a['company_name']) . '</div>';
            echo '<div class="mt-2 text-xs px-3 py-1 rounded-full inline-block bg-slate-100">' . e($a['status']) . '</div>';
            echo '</div>';
        }
        if (!$apps) echo '<div class="text-slate-500">No applications yet.</div>';
        echo '</div></div>';
    }
}

class ClientPanel {
    public static function render(array $user): void {
        $profile = Client::profileByUserId((int)$user['id']);
        if (!$profile) {
            echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">Client profile not found.</div>';
            return;
        }
        $stats = Client::dashboardStats((int)$profile['id']);
        echo '<div class="grid lg:grid-cols-3 gap-6">';
        foreach ([
            ['Projects', (string)$stats['projects']],
            ['Requests', (string)$stats['requests']],
            ['Applications Received', (string)$stats['applications']],
        ] as $card) {
            echo '<div class="rounded-3xl border bg-white p-6 shadow-sm"><div class="text-sm text-slate-500">' . e($card[0]) . '</div><div class="text-3xl font-bold mt-2">' . e($card[1]) . '</div></div>';
        }
        echo '</div>';

        echo '<div class="mt-8 grid lg:grid-cols-3 gap-6">';
        echo '<div class="lg:col-span-2 space-y-6">';
        self::renderProjectForm($profile);
        self::renderMyProjects($profile);
        echo '</div>';
        echo '<div class="space-y-6">';
        self::renderMyRequests($profile);
        echo '</div>';
        echo '</div>';
    }

    private static function renderProjectForm(array $profile): void {
        $settings = Settings::all();
        $trialEnds = $profile['trial_ends_at'] ? strtotime($profile['trial_ends_at']) : 0;
        $trialActive = $profile['subscription_status'] === 'trial' && $trialEnds >= strtotime(app_date());
        echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        echo '<div class="flex items-center justify-between"><h2 class="text-xl font-bold">Create Project</h2>';
        echo '<div class="text-sm ' . ($trialActive ? 'text-green-600' : 'text-amber-600') . '">' . e($trialActive ? 'Trial active' : 'Subscription required') . '</div></div>';
        if (!$trialActive && $profile['subscription_status'] !== 'active') {
            echo '<p class="text-sm text-slate-500 mt-2">The first month is free. After that, renew your subscription to continue posting projects.</p>';
        }
        $categories = Database::pdo()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
        echo '<form method="post" class="grid md:grid-cols-2 gap-4 mt-4">';
        echo Security::csrfField();
        echo '<input type="hidden" name="action" value="save_project">';
        echo '<label><span class="text-sm font-medium">Title</span><input name="title" class="mt-1 w-full rounded-2xl border px-4 py-3"></label>';
        echo '<label><span class="text-sm font-medium">Category</span><select name="category_id" class="mt-1 w-full rounded-2xl border px-4 py-3"><option value="">Select category</option>';
        foreach ($categories as $cat) { echo '<option value="' . (int)$cat['id'] . '">' . e($cat['name']) . '</option>'; }
        echo '</select></label>';
        echo '<label><span class="text-sm font-medium">Wilaya</span><input name="wilaya" class="mt-1 w-full rounded-2xl border px-4 py-3"></label>';
        echo '<label><span class="text-sm font-medium">Budget (DZD)</span><input type="number" min="0" step="0.01" name="budget" class="mt-1 w-full rounded-2xl border px-4 py-3"></label>';
        echo '<label><span class="text-sm font-medium">Deadline</span><input type="date" name="deadline" class="mt-1 w-full rounded-2xl border px-4 py-3"></label>';
        echo '<label><span class="text-sm font-medium">Needed Roles</span><input name="required_roles" placeholder="Actor, Photographer" class="mt-1 w-full rounded-2xl border px-4 py-3"></label>';
        echo '<label class="md:col-span-2"><span class="text-sm font-medium">Description</span><textarea name="description" rows="4" class="mt-1 w-full rounded-2xl border px-4 py-3"></textarea></label>';
        echo '<div class="md:col-span-2"><button class="px-5 py-3 rounded-2xl bg-black text-white">Publish Project</button></div>';
        echo '</form></div>';
    }

    private static function renderMyProjects(array $profile): void {
        $projects = Project::allByClient((int)$profile['id']);
        echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        echo '<h2 class="text-xl font-bold mb-4">My Projects</h2>';
        foreach ($projects as $p) {
            echo '<div class="rounded-2xl border p-4 mb-3">';
            echo '<div class="flex justify-between gap-4"><div><div class="font-semibold">' . e($p['title']) . '</div><div class="text-sm text-slate-500">' . e($p['wilaya']) . ' · ' . e(number_format((float)$p['budget'], 2)) . ' DZD</div></div><div class="text-xs px-3 py-1 rounded-full bg-slate-100">' . e($p['status']) . '</div></div>';
            echo '</div>';
        }
        if (!$projects) echo '<div class="text-slate-500">No projects yet.</div>';
        echo '</div>';
    }

    private static function renderMyRequests(array $profile): void {
        $requests = RequestManager::byClient((int)$profile['id']);
        echo '<div class="rounded-3xl border bg-white p-6 shadow-sm">';
        echo '<h2 class="text-xl font-bold mb-4">Talent Requests</h2>';
        foreach ($requests as $r) {
            echo '<div class="rounded-2xl border p-4 mb-3">';
            echo '<div class="font-semibold">' . e($r['nickname']) . '</div>';
            echo '<div class="text-sm text-slate-500">' . e($r['project_title'] ?? 'No project') . '</div>';
            echo '<div class="text-xs mt-2 px-3 py-1 rounded-full inline-block bg-slate-100">' . e($r['status']) . '</div>';
            echo '</div>';
        }
        if (!$requests) echo '<div class="text-slate-500">No requests yet.</div>';
        echo '</div>';
    }
}

function log_action(?int $userId, string $action, string $context): void {
    try {
        $stmt = Database::pdo()->prepare("INSERT INTO activity_logs (user_id, action, context, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $context, Security::clientIp()]);
    } catch (Throwable $e) {
        log_error_message('Activity log failed: ' . $e->getMessage());
    }
}

function ensure_storage(): void {
    foreach (storage_paths() as $path) {
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
    }
}

function table_exists(string $table): bool {
    try {
        $stmt = Database::pdo()->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function installation_status(): array {
    $checks = [
        'php_version' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'db_connected' => false,
        'tables_loaded' => false,
        'uploads_writable' => false,
        'log_writable' => false,
    ];
    try {
        $pdo = Database::pdo();
        $checks['db_connected'] = true;
        $required = ['users','talents','clients','projects','applications','requests','portfolio_items','categories','notifications','settings','activity_logs'];
        $checks['tables_loaded'] = true;
        foreach ($required as $tbl) {
            if (!table_exists($tbl)) {
                $checks['tables_loaded'] = false;
                break;
            }
        }
    } catch (Throwable $e) {
        $checks['db_connected'] = false;
    }
    $checks['uploads_writable'] = is_writable(__DIR__ . '/uploads') || is_writable(__DIR__ . '/uploads/profile_photos');
    $checks['log_writable'] = is_writable(__DIR__ . '/logs');
    return $checks;
}

function render_installation_checker(array $checks, ?string $dbError = null): void {
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e(APP_NAME) . ' Installation</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-slate-50 text-black">';
    echo '<div class="min-h-screen p-6 flex items-center justify-center">';
    echo '<div class="max-w-3xl w-full rounded-3xl border bg-white shadow-sm p-8">';
    echo '<div class="flex items-center justify-between gap-4 mb-6"><div><div class="text-sm text-pink-500 font-semibold">' . e(APP_NAME) . '</div><h1 class="text-3xl font-black">Installation Checker</h1></div><div class="text-sm px-3 py-1 rounded-full bg-pink-100 text-pink-700">Ready for production</div></div>';
    $items = [
        'PHP Version 8.0+' => $checks['php_version'] ?? false,
        'Database Connected' => $checks['db_connected'] ?? false,
        'Tables Loaded' => $checks['tables_loaded'] ?? false,
        'Uploads Writable' => $checks['uploads_writable'] ?? false,
        'Logs Writable' => $checks['log_writable'] ?? false,
    ];
    echo '<div class="grid md:grid-cols-2 gap-4">';
    foreach ($items as $label => $ok) {
        echo '<div class="rounded-2xl border p-4 flex items-center justify-between"><span>' . e($label) . '</span><span class="font-bold ' . ($ok ? 'text-green-600' : 'text-red-600') . '">' . ($ok ? '✓' : '✕') . '</span></div>';
    }
    echo '</div>';
    if ($dbError) {
        echo '<div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">' . e($dbError) . '</div>';
    }
    echo '<div class="mt-6 text-sm text-slate-600">Import <code>database.sql</code>, update database credentials at the top of <code>index.php</code>, and refresh this page.</div>';
    echo '</div></div></body></html>';
}

function nav_notifications(?array $user): int {
    return $user ? NotificationManager::unreadCount((int)$user['id']) : 0;
}

function render_header(string $title, ?array $user): void {
    $unread = nav_notifications($user);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . ' · ' . e(APP_NAME) . '</title>';
    echo '<script src="https://cdn.tailwindcss.com"></script>';
    echo '<style>body{font-feature-settings:"ss01" 1,"ss02" 1;}</style>';
    echo '</head><body class="bg-slate-50 text-slate-950">';
    echo '<div class="min-h-screen">';
    echo '<header class="sticky top-0 z-50 border-b bg-white/95 backdrop-blur">';
    echo '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">';
    echo '<div class="h-16 flex items-center justify-between gap-4">';
    echo '<button id="sidebarToggle" class="lg:hidden px-3 py-2 rounded-xl border">☰</button>';
    echo '<a href="' . e(url('home')) . '" class="font-black text-xl tracking-tight">' . e(APP_NAME) . '<span class="text-pink-500">.</span></a>';
    echo '<nav class="hidden md:flex items-center gap-5 text-sm text-slate-600">';
    echo '<a href="' . e(url('talents')) . '">Talents</a>';
    echo '<a href="' . e(url('projects')) . '">Projects</a>';
    echo '<a href="' . e(url('categories')) . '">Categories</a>';
    echo '</nav>';
    echo '<div class="flex items-center gap-3">';
    if ($user) {
        echo '<a class="relative px-3 py-2 rounded-xl border" href="' . e(url('notifications')) . '">Notifications';
        if ($unread > 0) echo '<span class="ml-2 inline-flex items-center justify-center rounded-full bg-pink-500 px-2 py-0.5 text-[11px] text-white">' . (int)$unread . '</span>';
        echo '</a>';
        echo '<span class="hidden sm:inline text-sm text-slate-500">' . e($user['full_name']) . '</span>';
        echo '<form method="post" class="inline">';
        echo Security::csrfField();
        echo '<input type="hidden" name="action" value="logout">';
        echo '<button class="px-4 py-2 rounded-xl bg-black text-white text-sm">Logout</button>';
        echo '</form>';
    } else {
        echo '<a class="px-4 py-2 rounded-xl border text-sm" href="' . e(url('login')) . '">Login</a>';
        echo '<a class="px-4 py-2 rounded-xl bg-black text-white text-sm" href="' . e(url('register')) . '">Join</a>';
    }
    echo '</div></div></div></header>';

    echo '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">';
    if ($user) {
        $role = $user['role'];
        echo '<div class="flex gap-6">';
        echo '<aside id="sidebar" class="fixed lg:sticky top-16 left-0 z-40 w-72 h-[calc(100vh-4rem)] -translate-x-full lg:translate-x-0 transition-transform bg-white border-r lg:border rounded-r-3xl lg:rounded-3xl p-4 overflow-y-auto">';
        echo '<div class="text-xs uppercase tracking-widest text-slate-400 mb-3">Navigation</div>';
        $menu = [
            ['Dashboard', url('dashboard')],
            ['Talents', url('talents')],
            ['Projects', url('projects')],
            ['Notifications', url('notifications')],
        ];
        if (in_array($role, ['super_admin','agent'], true)) {
            $menu[] = ['Admin Panel', url('dashboard', ['section' => 'overview'])];
        }
        if ($role === 'project_owner') {
            $menu[] = ['My Projects', url('dashboard')];
            $menu[] = ['Browse Talents', url('talents')];
            $menu[] = ['Requests', url('dashboard', ['section' => 'requests'])];
        }
        if ($role === 'talent') {
            $menu[] = ['My Profile', url('dashboard')];
            $menu[] = ['Applications', url('dashboard')];
            $menu[] = ['Portfolio', url('dashboard')];
        }
        foreach ($menu as $m) {
            echo '<a class="block px-4 py-3 rounded-2xl hover:bg-slate-100 mb-2" href="' . e($m[1]) . '">' . e($m[0]) . '</a>';
        }
        echo '<div class="mt-6 rounded-3xl bg-pink-50 p-4">';
        echo '<div class="text-sm font-semibold">' . e(Settings::get('site_tagline', 'Discover Talent. Create Opportunities.')) . '</div>';
        echo '<div class="text-xs text-slate-500 mt-2">Algerian creative mediation platform.</div>';
        echo '</div>';
        echo '</aside>';
        echo '<main class="flex-1 min-w-0 lg:pl-0 pt-6 pb-20">';
    } else {
        echo '<main class="pt-8 pb-20">';
    }
    foreach (get_flashes() as $flash) {
        $class = $flash['type'] === 'success' ? 'bg-green-50 text-green-700 border-green-200' : (($flash['type'] === 'error') ? 'bg-red-50 text-red-700 border-red-200' : 'bg-slate-50 text-slate-700 border-slate-200');
        echo '<div class="mb-4 rounded-2xl border px-4 py-3 ' . $class . '">' . e($flash['message']) . '</div>';
    }
}

function render_footer(): void {
    echo '</main></div></div>';
    echo '<script>
    const btn = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");
    if (btn && sidebar) {
        btn.addEventListener("click", () => {
            sidebar.classList.toggle("-translate-x-full");
        });
    }
    </script>';
    echo '</body></html>';
}

function render_home(): void {
    $featuredTalents = Talent::featured(6);
    $latestProjects = Project::latest(6);
    $categories = Database::pdo()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    $stats = [
        'Talents' => Talent::countApproved(),
        'Clients' => Client::count(),
        'Projects' => Project::countOpen(),
        'Revenue' => number_format(User::revenue(), 0) . ' DZD',
    ];
    echo '<section class="grid lg:grid-cols-2 gap-8 items-center py-10">';
    echo '<div>';
    echo '<div class="inline-flex items-center rounded-full bg-pink-50 px-4 py-2 text-sm text-pink-600 font-semibold">Premium Algerian talent marketplace</div>';
    echo '<h1 class="mt-5 text-5xl md:text-6xl font-black leading-tight">Discover Talent.<br>Create Opportunities.</h1>';
    echo '<p class="mt-5 text-lg text-slate-600 max-w-xl">Mayase connects directors, producers, agencies, brands, project owners, and talents across Algeria with a secure mediation flow.</p>';
    echo '<div class="mt-8 flex flex-wrap gap-3">';
    echo '<a href="' . e(url('talents')) . '" class="px-6 py-3 rounded-2xl bg-black text-white">Explore Talents</a>';
    echo '<a href="' . e(url('register')) . '" class="px-6 py-3 rounded-2xl border">Post a Project</a>';
    echo '</div></div>';
    echo '<div class="rounded-[2rem] border bg-white shadow-sm p-6">';
    echo '<div class="grid sm:grid-cols-2 gap-4">';
    foreach ($stats as $k => $v) {
        echo '<div class="rounded-3xl bg-slate-50 p-5"><div class="text-sm text-slate-500">' . e($k) . '</div><div class="mt-2 text-3xl font-black">' . e((string)$v) . '</div></div>';
    }
    echo '</div></div></section>';

    echo '<section class="py-6"><div class="grid md:grid-cols-3 gap-4">';
    foreach ($categories as $cat) {
        echo '<div class="rounded-3xl border bg-white p-5 shadow-sm"><div class="text-sm text-pink-500 font-semibold">Category</div><div class="text-xl font-bold mt-1">' . e($cat['name']) . '</div></div>';
    }
    echo '</div></section>';

    echo '<section class="py-10">';
    echo '<div class="flex items-end justify-between mb-5"><h2 class="text-2xl font-bold">Featured Talents</h2><a class="text-sm text-pink-600" href="' . e(url('talents')) . '">View all</a></div>';
    echo '<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">';
    foreach ($featuredTalents as $t) {
        talent_card($t);
    }
    echo '</div></section>';

    echo '<section class="py-10">';
    echo '<div class="flex items-end justify-between mb-5"><h2 class="text-2xl font-bold">Latest Projects</h2><a class="text-sm text-pink-600" href="' . e(url('projects')) . '">Browse projects</a></div>';
    echo '<div class="grid lg:grid-cols-2 gap-5">';
    foreach ($latestProjects as $p) {
        project_card($p);
    }
    echo '</div></section>';

    echo '<section class="py-10">';
    echo '<h2 class="text-2xl font-bold mb-5">How It Works</h2>';
    echo '<div class="grid md:grid-cols-4 gap-4">';
    foreach ([
        ['1','Create a profile','Talents and project owners register securely.'],
        ['2','Publish opportunities','Project owners post briefs, budgets, and deadlines.'],
        ['3','Request & apply','Talents apply; owners can request specific talent.'],
        ['4','Approve and mediate','Admins approve, mediate, and track progress.']
    ] as $step) {
        echo '<div class="rounded-3xl border bg-white p-5 shadow-sm"><div class="text-pink-500 font-black text-2xl">' . e($step[0]) . '</div><div class="font-semibold mt-3">' . e($step[1]) . '</div><p class="text-sm text-slate-500 mt-2">' . e($step[2]) . '</p></div>';
    }
    echo '</div></section>';
}

function talent_card(array $t): void {
    echo '<div class="rounded-3xl border bg-white p-5 shadow-sm">';
    echo '<div class="aspect-[4/3] rounded-2xl bg-gradient-to-br from-pink-50 to-slate-100 flex items-center justify-center overflow-hidden">';
    if (!empty($t['avatar_path'])) {
        echo '<img src="' . e($t['avatar_path']) . '" alt="" class="w-full h-full object-cover">';
    } else {
        echo '<div class="text-5xl font-black text-slate-300">' . e(substr($t['nickname'], 0, 1)) . '</div>';
    }
    echo '</div>';
    echo '<div class="mt-4 flex items-start justify-between gap-3">';
    echo '<div><div class="text-xs uppercase tracking-wider text-slate-400">' . e($t['talent_code']) . '</div><h3 class="font-bold text-lg">' . e($t['nickname']) . '</h3><p class="text-sm text-slate-500">' . e($t['category_name']) . ' · ' . e($t['wilaya']) . '</p></div>';
    echo '<a href="' . e(url('talent', ['id' => $t['id']])) . '" class="px-3 py-2 rounded-xl bg-black text-white text-sm h-fit">View Profile</a>';
    echo '</div></div>';
}

function project_card(array $p): void {
    echo '<div class="rounded-3xl border bg-white p-5 shadow-sm">';
    echo '<div class="flex items-center justify-between gap-4">';
    echo '<div><div class="text-xs uppercase tracking-wider text-slate-400">' . e($p['company_name']) . '</div><h3 class="font-bold text-lg">' . e($p['title']) . '</h3></div>';
    echo '<div class="text-xs px-3 py-1 rounded-full bg-slate-100">' . e($p['status']) . '</div>';
    echo '</div>';
    echo '<p class="text-sm text-slate-600 mt-3">' . e($p['description']) . '</p>';
    echo '<div class="mt-4 flex flex-wrap gap-2 text-xs">';
    echo '<span class="px-3 py-1 rounded-full bg-pink-50 text-pink-700">' . e($p['category_name'] ?? 'General') . '</span>';
    echo '<span class="px-3 py-1 rounded-full bg-slate-100">' . e($p['wilaya']) . '</span>';
    echo '<span class="px-3 py-1 rounded-full bg-slate-100">' . e(number_format((float)$p['budget'], 2)) . ' DZD</span>';
    echo '</div>';
    echo '<div class="mt-4"><a href="' . e(url('project', ['id' => $p['id']])) . '" class="text-pink-600 text-sm font-semibold">Open project</a></div>';
    echo '</div>';
}

function render_talents_page(?array $user = null): void {
    $filters = [
        'category_id' => $_GET['category_id'] ?? '',
        'wilaya' => $_GET['wilaya'] ?? '',
        'q' => $_GET['q'] ?? '',
    ];
    $talents = Talent::search($filters, 36);
    $categories = Database::pdo()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    $wilayas = ['Alger','Oran','Tlemcen','Constantine','Annaba','Sétif','Béjaïa','Batna','Blida','Mostaganem','Tizi Ouzou','Ouargla','Adrar'];
    echo '<section class="py-8">';
    echo '<div class="flex items-end justify-between gap-4 mb-5"><div><h1 class="text-3xl font-black">Talent Directory</h1><p class="text-slate-500 mt-2">Search Algerian creative professionals by role, wilaya, or keyword.</p></div></div>';
    echo '<form class="grid md:grid-cols-4 gap-4 mb-8">';
    echo '<input type="hidden" name="page" value="talents">';
    echo '<input name="q" value="' . e((string)$filters['q']) . '" placeholder="Keyword" class="rounded-2xl border px-4 py-3 bg-white">';
    echo '<select name="category_id" class="rounded-2xl border px-4 py-3 bg-white"><option value="">All categories</option>';
    foreach ($categories as $cat) {
        $sel = ((string)$filters['category_id'] === (string)$cat['id']) ? 'selected' : '';
        echo '<option value="' . (int)$cat['id'] . '" ' . $sel . '>' . e($cat['name']) . '</option>';
    }
    echo '</select>';
    echo '<select name="wilaya" class="rounded-2xl border px-4 py-3 bg-white"><option value="">All wilayas</option>';
    foreach ($wilayas as $w) {
        $sel = ((string)$filters['wilaya'] === $w) ? 'selected' : '';
        echo '<option value="' . e($w) . '" ' . $sel . '>' . e($w) . '</option>';
    }
    echo '</select>';
    echo '<button class="rounded-2xl bg-black text-white px-5 py-3">Search</button>';
    echo '</form>';
    echo '<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">';
    foreach ($talents as $t) talent_card($t);
    echo '</div></section>';
}

function render_projects_page(): void {
    $filters = [
        'status' => $_GET['status'] ?? '',
        'wilaya' => $_GET['wilaya'] ?? '',
        'q' => $_GET['q'] ?? '',
    ];
    $projects = Project::search($filters, 36);
    $wilayas = ['Alger','Oran','Tlemcen','Constantine','Annaba','Sétif','Béjaïa','Batna','Blida','Mostaganem','Tizi Ouzou','Ouargla','Adrar'];
    echo '<section class="py-8">';
    echo '<div class="flex items-end justify-between gap-4 mb-5"><div><h1 class="text-3xl font-black">Projects</h1><p class="text-slate-500 mt-2">Browse open opportunities posted by Algerian project owners.</p></div></div>';
    echo '<form class="grid md:grid-cols-4 gap-4 mb-8">';
    echo '<input type="hidden" name="page" value="projects">';
    echo '<input name="q" value="' . e((string)$filters['q']) . '" placeholder="Keyword" class="rounded-2xl border px-4 py-3 bg-white">';
    echo '<select name="status" class="rounded-2xl border px-4 py-3 bg-white"><option value="">All statuses</option>';
    foreach (['open','closed','in_progress','completed'] as $s) {
        $sel = ((string)$filters['status'] === $s) ? 'selected' : '';
        echo '<option value="' . e($s) . '" ' . $sel . '>' . e($s) . '</option>';
    }
    echo '</select>';
    echo '<select name="wilaya" class="rounded-2xl border px-4 py-3 bg-white"><option value="">All wilayas</option>';
    foreach ($wilayas as $w) {
        $sel = ((string)$filters['wilaya'] === $w) ? 'selected' : '';
        echo '<option value="' . e($w) . '" ' . $sel . '>' . e($w) . '</option>';
    }
    echo '</select>';
    echo '<button class="rounded-2xl bg-black text-white px-5 py-3">Search</button>';
    echo '</form>';
    echo '<div class="grid lg:grid-cols-2 gap-5">';
    foreach ($projects as $p) project_card($p);
    echo '</div></section>';
}

function render_talent_profile_page(?array $user = null): void {
    $id = (int)($_GET['id'] ?? 0);
    $talent = Talent::profileById($id);
    if (!$talent) {
        echo '<div class="py-12">Talent not found.</div>';
        return;
    }
    $portfolio = Talent::portfolio((int)$talent['id']);
    $viewer = $user;
    echo '<section class="py-8">';
    echo '<div class="grid lg:grid-cols-3 gap-8">';
    echo '<div class="lg:col-span-2">';
    echo '<div class="rounded-[2rem] border bg-white p-6 shadow-sm">';
    echo '<div class="flex flex-col md:flex-row gap-6">';
    echo '<div class="w-40 h-40 rounded-3xl bg-slate-100 overflow-hidden flex items-center justify-center">';
    if (!empty($talent['profile_photo'])) {
        echo '<img src="' . e($talent['profile_photo']) . '" class="w-full h-full object-cover">';
    } else {
        echo '<div class="text-5xl font-black text-slate-300">' . e(substr($talent['nickname'], 0, 1)) . '</div>';
    }
    echo '</div>';
    echo '<div class="flex-1">';
    echo '<div class="text-xs uppercase tracking-widest text-slate-400">' . e($talent['talent_code']) . '</div>';
    echo '<h1 class="text-3xl font-black mt-1">' . e($talent['nickname']) . '</h1>';
    echo '<div class="mt-2 text-slate-600">' . e($talent['category_name']) . ' · ' . e($talent['wilaya']) . '</div>';
    echo '<div class="mt-4 flex flex-wrap gap-2 text-xs">';
    echo '<span class="px-3 py-1 rounded-full bg-pink-50 text-pink-700">' . e($talent['availability']) . '</span>';
    echo '<span class="px-3 py-1 rounded-full bg-slate-100">' . e((string)$talent['experience_years']) . ' years</span>';
    echo '</div>';
    echo '</div></div>';
    echo '<div class="mt-6"><h2 class="font-bold mb-2">Bio</h2><p class="text-slate-600">' . e($talent['bio']) . '</p></div>';
    echo '<div class="mt-6 grid md:grid-cols-2 gap-4">';
    echo '<div><h3 class="font-bold mb-2">Skills</h3><p class="text-slate-600">' . e($talent['skills']) . '</p></div>';
    echo '<div><h3 class="font-bold mb-2">Experience</h3><p class="text-slate-600">' . e((string)$talent['experience_years']) . ' years in the Algerian creative market.</p></div>';
    echo '</div>';

    if (Security::canSeePrivateContacts($viewer)) {
        echo '<div class="mt-6 rounded-2xl bg-slate-50 p-4">';
        echo '<div class="font-semibold">Private contact details</div>';
        echo '<div class="text-sm text-slate-600 mt-1">' . e($talent['full_name']) . ' · ' . e($talent['email']) . ' · ' . e($talent['phone'] ?? '—') . '</div>';
        echo '</div>';
    } else {
        echo '<div class="mt-6 rounded-2xl bg-pink-50 p-4 text-sm text-pink-700">Private contact details are protected and visible only to administrators.</div>';
    }
    echo '</div>';

    echo '<div class="space-y-6">';
    echo '<div class="rounded-[2rem] border bg-white p-6 shadow-sm">';
    echo '<h2 class="font-bold text-xl mb-4">Request Talent</h2>';
    if ($viewer && $viewer['role'] === 'project_owner') {
        echo '<button type="button" onclick="document.getElementById(\'requestModal\').classList.remove(\'hidden\')" class="rounded-2xl bg-black text-white px-5 py-3">Open Request Form</button>';
        echo '<div id="requestModal" class="hidden fixed inset-0 z-50 bg-black/50 p-4 flex items-center justify-center">';
        echo '<div class="w-full max-w-lg rounded-[2rem] bg-white p-6 shadow-xl">';
        echo '<div class="flex items-center justify-between mb-4"><h3 class="text-xl font-bold">Request this talent</h3><button type="button" onclick="document.getElementById(\'requestModal\').classList.add(\'hidden\')" class="text-2xl leading-none">&times;</button></div>';
        echo '<form method="post" class="space-y-4">';
        echo Security::csrfField();
        echo '<input type="hidden" name="action" value="request_talent">';
        echo '<input type="hidden" name="talent_id" value="' . (int)$talent['id'] . '">';
        echo '<label class="block"><span class="text-sm font-medium">Project ID (optional)</span><input type="number" name="project_id" class="mt-1 w-full rounded-2xl border px-4 py-3"></label>';
        echo '<label class="block"><span class="text-sm font-medium">Message</span><textarea name="message" rows="5" class="mt-1 w-full rounded-2xl border px-4 py-3">We would like to discuss a collaboration with you.</textarea></label>';
        echo '<button class="w-full rounded-2xl bg-pink-500 text-white px-5 py-3">Send Request</button>';
        echo '</form></div></div>';
    } elseif ($viewer) {
        echo '<div class="text-sm text-slate-500">Only project owners can send mediation requests.</div>';
    } else {
        echo '<a href="' . e(url('login')) . '" class="inline-flex rounded-2xl bg-black text-white px-5 py-3">Login to request</a>';
    }
    echo '</div>';

    echo '<div class="rounded-[2rem] border bg-white p-6 shadow-sm">';
    echo '<div class="flex items-center justify-between mb-4"><h2 class="font-bold text-xl">Portfolio</h2><span class="text-sm text-slate-500">' . count($portfolio) . '</span></div>';
    if ($portfolio) {
        echo '<div class="space-y-3">';
        foreach ($portfolio as $item) {
            echo '<div class="rounded-2xl border p-4"><div class="font-semibold">' . e($item['title']) . '</div><div class="text-sm text-slate-500">' . e($item['description'] ?? '') . '</div></div>';
        }
        echo '</div>';
    } else {
        echo '<div class="text-slate-500">No portfolio uploaded yet.</div>';
    }
    echo '</div>';
    echo '</div>';
    echo '</div></section>';
}

function render_project_view_page(?array $user = null): void {
    $id = (int)($_GET['id'] ?? 0);
    $project = Project::find($id);
    if (!$project) {
        echo '<div class="py-12">Project not found.</div>';
        return;
    }
    $apps = Application::byProject($id);
    echo '<section class="py-8">';
    echo '<div class="grid lg:grid-cols-3 gap-8">';
    echo '<div class="lg:col-span-2">';
    echo '<div class="rounded-[2rem] border bg-white p-6 shadow-sm">';
    echo '<div class="flex items-center justify-between gap-4">';
    echo '<div><div class="text-xs uppercase tracking-widest text-slate-400">' . e($project['company_name']) . '</div><h1 class="text-3xl font-black mt-1">' . e($project['title']) . '</h1></div>';
    echo '<div class="text-xs px-3 py-1 rounded-full bg-slate-100">' . e($project['status']) . '</div>';
    echo '</div>';
    echo '<p class="mt-5 text-slate-600">' . e($project['description']) . '</p>';
    echo '<div class="mt-5 flex flex-wrap gap-2 text-xs">';
    echo '<span class="px-3 py-1 rounded-full bg-pink-50 text-pink-700">' . e($project['wilaya']) . '</span>';
    echo '<span class="px-3 py-1 rounded-full bg-slate-100">' . e(number_format((float)$project['budget'], 2)) . ' DZD</span>';
    echo '<span class="px-3 py-1 rounded-full bg-slate-100">Deadline: ' . e($project['deadline'] ?? '-') . '</span>';
    echo '<span class="px-3 py-1 rounded-full bg-slate-100">Roles: ' . e($project['required_roles'] ?? '-') . '</span>';
    echo '</div>';
    if ($user && $user['role'] === 'talent') {
        $profile = Talent::profileByUserId((int)$user['id']);
        echo '<div class="mt-6 rounded-2xl bg-pink-50 p-4">';
        echo '<form method="post" class="space-y-3">';
        echo Security::csrfField();
        echo '<input type="hidden" name="action" value="apply_project">';
        echo '<input type="hidden" name="project_id" value="' . (int)$project['id'] . '">';
        echo '<label class="block"><span class="text-sm font-medium">Cover Letter</span><textarea name="cover_letter" rows="4" class="mt-1 w-full rounded-2xl border px-4 py-3">I am interested in this collaboration.</textarea></label>';
        echo '<button class="rounded-2xl bg-black text-white px-5 py-3">Apply to Project</button>';
        echo '</form></div>';
    } elseif (!$user) {
        echo '<div class="mt-6"><a href="' . e(url('login')) . '" class="rounded-2xl bg-black text-white px-5 py-3 inline-flex">Login to apply</a></div>';
    }
    echo '</div>';
    echo '</div>';
    echo '<div class="space-y-6">';
    echo '<div class="rounded-[2rem] border bg-white p-6 shadow-sm">';
    echo '<h2 class="text-xl font-bold mb-4">Applications</h2>';
    if ($user && in_array($user['role'], ['super_admin','agent','project_owner'], true)) {
        echo '<div class="space-y-3">';
        foreach ($apps as $a) {
            echo '<div class="rounded-2xl border p-4"><div class="font-semibold">' . e($a['nickname']) . '</div><div class="text-sm text-slate-500">' . e($a['status']) . '</div></div>';
        }
        if (!$apps) echo '<div class="text-slate-500">No applications yet.</div>';
    } else {
        echo '<div class="text-slate-500">Hidden.</div>';
    }
    echo '</div></div>';
    echo '</div></section>';
}

function render_notifications_page(array $user): void {
    $notes = NotificationManager::latest((int)$user['id'], 20);
    echo '<section class="py-8">';
    echo '<h1 class="text-3xl font-black mb-5">Notifications</h1>';
    echo '<div class="space-y-3">';
    foreach ($notes as $n) {
        echo '<div class="rounded-3xl border bg-white p-5 shadow-sm ' . ((int)$n['is_read'] ? 'opacity-70' : '') . '">';
        echo '<div class="flex items-center justify-between gap-4">';
        echo '<div><div class="font-semibold">' . e($n['title']) . '</div><div class="text-sm text-slate-500 mt-1">' . e($n['body']) . '</div></div>';
        if (!(int)$n['is_read']) {
            echo '<form method="post">';
            echo Security::csrfField();
            echo '<input type="hidden" name="action" value="mark_notification">';
            echo '<input type="hidden" name="notification_id" value="' . (int)$n['id'] . '">';
            echo '<button class="px-3 py-2 rounded-xl bg-pink-500 text-white text-xs">Mark read</button>';
            echo '</form>';
        }
        echo '</div></div>';
    }
    if (!$notes) echo '<div class="text-slate-500">No notifications.</div>';
    echo '</div></section>';
}

function render_login_page(): void {
    echo '<div class="max-w-lg mx-auto py-12">';
    echo '<div class="rounded-[2rem] border bg-white p-8 shadow-sm">';
    echo '<h1 class="text-3xl font-black">Welcome back</h1>';
    echo '<p class="text-slate-500 mt-2">Sign in to manage your profile, projects, and mediation requests.</p>';
    echo '<form method="post" class="mt-6 space-y-4">';
    echo Security::csrfField();
    echo '<input type="hidden" name="action" value="login">';
    echo '<label class="block"><span class="text-sm font-medium">Email</span><input type="email" name="email" class="mt-1 w-full rounded-2xl border px-4 py-3" required></label>';
    echo '<label class="block"><span class="text-sm font-medium">Password</span><input type="password" name="password" class="mt-1 w-full rounded-2xl border px-4 py-3" required></label>';
    echo '<label class="flex items-center gap-2"><input type="checkbox" name="remember" value="1"><span class="text-sm">Remember me</span></label>';
    echo '<button class="w-full rounded-2xl bg-black text-white px-5 py-3">Login</button>';
    echo '</form>';
    echo '<div class="mt-4 text-sm text-slate-500"><a class="text-pink-600" href="' . e(url('forgot-password')) . '">Forgot password?</a></div>';
    echo '</div></div>';
}

function render_register_page(): void {
    $categories = Database::pdo()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    echo '<div class="max-w-4xl mx-auto py-12">';
    echo '<div class="rounded-[2rem] border bg-white p-8 shadow-sm">';
    echo '<h1 class="text-3xl font-black">Create your account</h1>';
    echo '<p class="text-slate-500 mt-2">Register as a project owner or a talent.</p>';
    echo '<form method="post" class="mt-6 grid md:grid-cols-2 gap-4">';
    echo Security::csrfField();
    echo '<input type="hidden" name="action" value="register">';
    echo '<label><span class="text-sm font-medium">Role</span><select name="role" class="mt-1 w-full rounded-2xl border px-4 py-3"><option value="project_owner">Project Owner</option><option value="talent">Talent</option></select></label>';
    echo '<label><span class="text-sm font-medium">Full Name</span><input name="full_name" class="mt-1 w-full rounded-2xl border px-4 py-3" required></label>';
    echo '<label><span class="text-sm font-medium">Email</span><input type="email" name="email" class="mt-1 w-full rounded-2xl border px-4 py-3" required></label>';
    echo '<label><span class="text-sm font-medium">Phone</span><input name="phone" class="mt-1 w-full rounded-2xl border px-4 py-3"></label>';
    echo '<label><span class="text-sm font-medium">Password</span><input type="password" name="password" class="mt-1 w-full rounded-2xl border px-4 py-3" required></label>';
    echo '<label><span class="text-sm font-medium">Wilaya</span><input name="wilaya" class="mt-1 w-full rounded-2xl border px-4 py-3" required></label>';
    echo '<label class="md:col-span-2"><span class="text-sm font-medium">Bio</span><textarea name="bio" rows="3" class="mt-1 w-full rounded-2xl border px-4 py-3"></textarea></label>';
    echo '<label class="md:col-span-2"><span class="text-sm font-medium">Project owner company name</span><input name="company_name" class="mt-1 w-full rounded-2xl border px-4 py-3" placeholder="Only for project owners"></label>';
    echo '<label><span class="text-sm font-medium">Talent nickname</span><input name="nickname" class="mt-1 w-full rounded-2xl border px-4 py-3" placeholder="Only for talents"></label>';
    echo '<label><span class="text-sm font-medium">Talent category</span><select name="category_id" class="mt-1 w-full rounded-2xl border px-4 py-3">';
    foreach ($categories as $cat) echo '<option value="' . (int)$cat['id'] . '">' . e($cat['name']) . '</option>';
    echo '</select></label>';
    echo '<label><span class="text-sm font-medium">Experience years</span><input type="number" min="0" name="experience_years" class="mt-1 w-full rounded-2xl border px-4 py-3" value="0"></label>';
    echo '<label><span class="text-sm font-medium">Availability</span><input name="availability" class="mt-1 w-full rounded-2xl border px-4 py-3" value="Available"></label>';
    echo '<label class="md:col-span-2"><span class="text-sm font-medium">Skills</span><textarea name="skills" rows="3" class="mt-1 w-full rounded-2xl border px-4 py-3"></textarea></label>';
    echo '<div class="md:col-span-2"><button class="rounded-2xl bg-black text-white px-5 py-3">Register</button></div>';
    echo '</form></div></div>';
}

function render_forgot_password_page(): void {
    echo '<div class="max-w-lg mx-auto py-12"><div class="rounded-[2rem] border bg-white p-8 shadow-sm">';
    echo '<h1 class="text-3xl font-black">Password reset</h1>';
    echo '<p class="text-slate-500 mt-2">Generate a local reset link without external email services.</p>';
    echo '<form method="post" class="mt-6 space-y-4">';
    echo Security::csrfField();
    echo '<input type="hidden" name="action" value="forgot_password">';
    echo '<label class="block"><span class="text-sm font-medium">Email</span><input type="email" name="email" class="mt-1 w-full rounded-2xl border px-4 py-3" required></label>';
    echo '<button class="w-full rounded-2xl bg-black text-white px-5 py-3">Generate reset link</button>';
    echo '</form></div></div>';
}

function render_reset_password_page(): void {
    $token = (string)($_GET['token'] ?? '');
    echo '<div class="max-w-lg mx-auto py-12"><div class="rounded-[2rem] border bg-white p-8 shadow-sm">';
    echo '<h1 class="text-3xl font-black">Set a new password</h1>';
    echo '<form method="post" class="mt-6 space-y-4">';
    echo Security::csrfField();
    echo '<input type="hidden" name="action" value="reset_password">';
    echo '<input type="hidden" name="token" value="' . e($token) . '">';
    echo '<label class="block"><span class="text-sm font-medium">New Password</span><input type="password" name="password" class="mt-1 w-full rounded-2xl border px-4 py-3" required></label>';
    echo '<button class="w-full rounded-2xl bg-black text-white px-5 py-3">Update password</button>';
    echo '</form></div></div>';
}

function handle_post_actions(?array $user): void {
    if (!is_post()) return;
    $action = $_POST['action'] ?? '';
    if ($action !== 'login' && $action !== 'forgot_password' && $action !== 'reset_password' && $action !== 'register') {
        Security::verifyCsrf();
    }

    try {
        switch ($action) {
            case 'login':
                Security::verifyCsrf();
                Auth::login(trim((string)$_POST['email']), (string)$_POST['password'], !empty($_POST['remember']));
                flash('success', 'Welcome back!');
                redirect(url('dashboard'));
                break;

            case 'register':
                Security::verifyCsrf();
                $data = [
                    'role' => (string)($_POST['role'] ?? ''),
                    'full_name' => Security::sanitizeText((string)($_POST['full_name'] ?? '')),
                    'email' => trim((string)($_POST['email'] ?? '')),
                    'phone' => trim((string)($_POST['phone'] ?? '')),
                    'password' => (string)($_POST['password'] ?? ''),
                    'wilaya' => Security::sanitizeText((string)($_POST['wilaya'] ?? '')),
                    'bio' => Security::sanitizeText((string)($_POST['bio'] ?? '')),
                    'company_name' => Security::sanitizeText((string)($_POST['company_name'] ?? '')),
                    'nickname' => Security::sanitizeText((string)($_POST['nickname'] ?? '')),
                    'category_id' => (int)($_POST['category_id'] ?? 0),
                    'experience_years' => (int)($_POST['experience_years'] ?? 0),
                    'availability' => Security::sanitizeText((string)($_POST['availability'] ?? 'Available')),
                    'skills' => Security::sanitizeText((string)($_POST['skills'] ?? '')),
                ];
                $errors = Validator::required($data, ['role','full_name','email','password','wilaya']);
                if (!Validator::email($data['email'])) $errors['email'] = 'Invalid email.';
                if (!Validator::minLen($data['password'], 8)) $errors['password'] = 'Password must be at least 8 characters.';
                if ($data['role'] === 'talent' && !$data['nickname']) $errors['nickname'] = 'Talent nickname is required.';
                if ($data['role'] === 'project_owner' && !$data['company_name']) $errors['company_name'] = 'Company name is required.';
                if ($errors) {
                    flash('error', implode(' ', $errors));
                    redirect(url('register'));
                }
                Auth::register($data);
                flash('success', 'Account created successfully.');
                redirect(url('login'));
                break;

            case 'logout':
                Security::verifyCsrf();
                Auth::logout();
                flash('success', 'Logged out.');
                redirect(url('home'));
                break;

            case 'save_talent_profile':
                $current = Auth::requireRole(['talent']);
                $profile = Talent::profileByUserId((int)$current['id']);
                if (!$profile) throw new RuntimeException('Talent profile not found.');
                $photoPath = $profile['profile_photo'] ?? null;
                if (!empty($_FILES['profile_photo']['name'])) {
                    $photoPath = UploadManager::save($_FILES['profile_photo'], 'profile');
                }
                Talent::update((int)$profile['id'], [
                    'nickname' => Security::sanitizeText((string)($_POST['nickname'] ?? $profile['nickname'])),
                    'category_id' => (int)($_POST['category_id'] ?? $profile['category_id']),
                    'wilaya' => Security::sanitizeText((string)($_POST['wilaya'] ?? $profile['wilaya'])),
                    'bio' => Security::sanitizeText((string)($_POST['bio'] ?? $profile['bio'])),
                    'skills' => Security::sanitizeText((string)($_POST['skills'] ?? $profile['skills'])),
                    'experience_years' => (int)($_POST['experience_years'] ?? $profile['experience_years']),
                    'availability' => Security::sanitizeText((string)($_POST['availability'] ?? $profile['availability'])),
                    'profile_photo' => $photoPath,
                    'status' => $profile['status'] ?: 'pending',
                ]);
                flash('success', 'Profile updated.');
                log_action((int)$current['id'], 'talent_profile_update', 'Updated talent profile');
                redirect(url('dashboard'));
                break;

            case 'upload_portfolio':
                $current = Auth::requireRole(['talent']);
                $profile = Talent::profileByUserId((int)$current['id']);
                if (!$profile) throw new RuntimeException('Talent profile not found.');
                if (empty($_FILES['media_file']['name'])) throw new RuntimeException('Please choose a portfolio file.');
                $filePath = UploadManager::save($_FILES['media_file'], (($_POST['media_type'] ?? 'image') === 'video') ? 'video' : 'profile');
                $stmt = Database::pdo()->prepare("INSERT INTO portfolio_items (talent_id, title, media_type, file_path, description, is_featured) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    (int)$profile['id'],
                    Security::sanitizeText((string)($_POST['title'] ?? 'Untitled')),
                    ($_POST['media_type'] ?? 'image') === 'video' ? 'video' : 'image',
                    $filePath,
                    Security::sanitizeText((string)($_POST['description'] ?? '')),
                    !empty($_POST['is_featured']) ? 1 : 0,
                ]);
                flash('success', 'Portfolio item uploaded.');
                log_action((int)$current['id'], 'portfolio_upload', 'Uploaded portfolio item');
                redirect(url('dashboard'));
                break;

            case 'save_project':
                $current = Auth::requireRole(['project_owner']);
                $client = Client::profileByUserId((int)$current['id']);
                if (!$client) throw new RuntimeException('Client profile not found.');
                $trialEnds = $client['trial_ends_at'] ? strtotime($client['trial_ends_at']) : 0;
                $trialActive = $client['subscription_status'] === 'trial' && $trialEnds >= strtotime(app_date());
                if (!$trialActive && $client['subscription_status'] !== 'active') {
                    throw new RuntimeException('Subscription required to publish projects.');
                }
                $projectErrors = Validator::required($_POST, ['title','description','wilaya']);
                if ($projectErrors) {
                    throw new RuntimeException(implode(' ', $projectErrors));
                }
                $projectId = Project::create((int)$client['id'], [
                    'title' => Security::sanitizeText((string)($_POST['title'] ?? '')),
                    'description' => Security::sanitizeText((string)($_POST['description'] ?? '')),
                    'category_id' => (int)($_POST['category_id'] ?? 0),
                    'required_roles' => Security::sanitizeText((string)($_POST['required_roles'] ?? '')),
                    'wilaya' => Security::sanitizeText((string)($_POST['wilaya'] ?? '')),
                    'budget' => (float)($_POST['budget'] ?? 0),
                    'deadline' => !empty($_POST['deadline']) ? $_POST['deadline'] : null,
                    'status' => 'open',
                ]);
                NotificationManager::createForRoles(['super_admin','agent'], 'project_update', 'New project posted', 'A new project was created by ' . $client['company_name'] . '.');
                log_action((int)$current['id'], 'project_create', 'Created project #' . $projectId);
                flash('success', 'Project published.');
                redirect(url('dashboard'));
                break;

            case 'apply_project':
                $current = Auth::requireRole(['talent']);
                $profile = Talent::profileByUserId((int)$current['id']);
                if (!$profile) throw new RuntimeException('Talent profile not found.');
                $projectId = (int)($_POST['project_id'] ?? 0);
                Application::apply($projectId, (int)$profile['id'], Security::sanitizeText((string)($_POST['cover_letter'] ?? '')));
                $project = Project::find($projectId);
                if ($project) {
                    NotificationManager::createForRoles(['super_admin','agent'], 'new_application', 'New application received', $profile['nickname'] . ' applied to ' . $project['title'] . '.');
                    NotificationManager::create((int)$project['client_user_id'], 'new_application', 'New application received', $profile['nickname'] . ' applied to your project.');
                }
                flash('success', 'Application sent.');
                log_action((int)$current['id'], 'apply_project', 'Applied to project #' . $projectId);
                redirect(url('project', ['id' => $projectId]));
                break;

            case 'request_talent':
                $current = Auth::requireRole(['project_owner']);
                $client = Client::profileByUserId((int)$current['id']);
                if (!$client) throw new RuntimeException('Client profile not found.');
                $requestId = RequestManager::create((int)$client['id'], (int)($_POST['talent_id'] ?? 0), !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null, Security::sanitizeText((string)($_POST['message'] ?? '')));
                $talent = Talent::profileById((int)($_POST['talent_id'] ?? 0));
                if ($talent) {
                    NotificationManager::create((int)$talent['user_id'], 'new_request', 'New talent request', $client['company_name'] . ' wants to contact you.');
                }
                NotificationManager::createForRoles(['super_admin','agent'], 'new_request', 'New request submitted', $client['company_name'] . ' submitted a mediation request.');
                log_action((int)$current['id'], 'request_talent', 'Created request #' . $requestId);
                flash('success', 'Request sent to mediation.');
                redirect(url('talent', ['id' => (int)($_POST['talent_id'] ?? 0)]));
                break;

            case 'save_settings':
                Auth::requireRole(['super_admin','agent']);
                foreach (['monthly_subscription_fee','special_monthly_offer','agent_monthly_pay','free_trial_days','platform_commission_percent'] as $k) {
                    if (isset($_POST[$k])) Settings::set($k, trim((string)$_POST[$k]));
                }
                flash('success', 'Settings saved.');
                redirect(url('dashboard', ['section' => 'settings']));
                break;

            case 'admin_toggle_user':
                $current = Auth::requireRole(['super_admin']);
                $userId = (int)($_POST['user_id'] ?? 0);
                $newRole = (string)($_POST['new_role'] ?? 'talent');
                if (!in_array($newRole, ['super_admin','agent','project_owner','talent'], true)) {
                    throw new RuntimeException('Invalid role.');
                }
                $stmt = Database::pdo()->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$newRole, $userId]);
                flash('success', 'User updated.');
                log_action((int)$current['id'], 'admin_toggle_user', 'Changed user #' . $userId . ' to ' . $newRole);
                redirect(url('dashboard', ['section' => 'users']));
                break;

            case 'admin_approve_talent':
                $current = Auth::requireRole(['super_admin','agent']);
                $tid = (int)($_POST['talent_id'] ?? 0);
                $stmt = Database::pdo()->prepare("UPDATE talents SET status='approved' WHERE id=?");
                $stmt->execute([$tid]);
                $talent = Talent::profileById($tid);
                if ($talent) NotificationManager::create((int)$talent['user_id'], 'profile_approval', 'Profile approved', 'Your talent profile is now approved.');
                flash('success', 'Talent approved.');
                log_action((int)$current['id'], 'approve_talent', 'Approved talent #' . $tid);
                redirect(url('dashboard', ['section' => 'talents']));
                break;

            case 'request_status':
                Auth::requireRole(['super_admin','agent']);
                $rid = (int)($_POST['request_id'] ?? 0);
                $status = (string)($_POST['status'] ?? 'pending');
                if (!in_array($status, ['approved','rejected','in_progress','completed','pending'], true)) throw new RuntimeException('Invalid status.');
                RequestManager::updateStatus($rid, $status);
                flash('success', 'Request status updated.');
                redirect(url('dashboard', ['section' => 'requests']));
                break;

            case 'mark_notification':
                $current = Auth::requireLogin();
                NotificationManager::markRead((int)($_POST['notification_id'] ?? 0), (int)$current['id']);
                redirect(url('notifications'));
                break;

            case 'forgot_password':
                Security::verifyCsrf();
                $link = Auth::requestReset(trim((string)$_POST['email']));
                if ($link) {
                    flash('success', 'Reset link generated: ' . $link);
                } else {
                    flash('error', 'If the email exists, a reset link has been generated.');
                }
                redirect(url('forgot-password'));
                break;

            case 'reset_password':
                Security::verifyCsrf();
                Auth::resetPassword((string)($_POST['token'] ?? ''), (string)($_POST['password'] ?? ''));
                flash('success', 'Password updated.');
                redirect(url('login'));
                break;

            default:
                break;
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        $fallback = 'home';
        if ($action === 'login') $fallback = 'login';
        elseif ($action === 'register') $fallback = 'register';
        elseif ($action === 'forgot_password') $fallback = 'forgot-password';
        elseif ($action === 'reset_password') $fallback = 'reset-password';
        elseif ($action === 'save_talent_profile' || $action === 'upload_portfolio' || $action === 'save_project' || $action === 'apply_project' || $action === 'request_talent' || $action === 'save_settings' || $action === 'admin_toggle_user' || $action === 'admin_approve_talent' || $action === 'request_status') $fallback = 'dashboard';
        redirect(url($fallback));
    }
}

function boot_remember_me(): void {
    try {
        Auth::checkRememberMe();
    } catch (Throwable $e) {
        log_error_message('Remember-me failed: ' . $e->getMessage());
    }
}

function app_ready(): bool {
    try {
        $pdo = Database::pdo();
    } catch (Throwable $e) {
        return false;
    }
    $required = ['users','talents','clients','projects','applications','requests','portfolio_items','categories','notifications','settings','activity_logs'];
    foreach ($required as $table) {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if (!$stmt->fetchColumn()) return false;
        } catch (Throwable $e) {
            return false;
        }
    }
    return true;
}

function page_title(string $page): string {
    return match ($page) {
        'home' => APP_NAME,
        'dashboard' => 'Dashboard',
        'talents' => 'Talents',
        'projects' => 'Projects',
        'login' => 'Login',
        'register' => 'Register',
        'talent' => 'Talent Profile',
        'project' => 'Project',
        'notifications' => 'Notifications',
        'forgot-password' => 'Password Reset',
        'reset-password' => 'Set New Password',
        default => APP_NAME,
    };
}

// --------------------------------------------------------------------
// Boot
// --------------------------------------------------------------------
ensure_storage();

// Check if we need to install
$pdoReady = false;
$dbError = null;
try {
    // Try to connect to the database (without selecting it yet)
    Database::pdo();
    $pdoReady = true;
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

$installed = $pdoReady && app_ready();

if (!$installed) {
    // Attempt automatic installation
    $installSuccess = false;
    $installError = null;
    try {
        // Connect without a database first to create it if needed
        $dsn = sprintf('mysql:host=%s;charset=%s', DB_HOST, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");

        // Execute the full SQL script (split by semicolon)
        $sqlStatements = array_filter(array_map('trim', explode(';', SQL_INSTALL)));
        foreach ($sqlStatements as $stmt) {
            if (!empty($stmt)) {
                $pdo->exec($stmt);
            }
        }
        $installSuccess = true;
    } catch (Throwable $e) {
        $installError = $e->getMessage();
    }

    if ($installSuccess) {
        // Installation succeeded, refresh the page to load the app
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        // Show error details
        $checks = installation_status();
        render_installation_checker($checks, $installError ?? $dbError);
        exit;
    }
}

// If we get here, the app is installed and ready
SessionManager::start();
boot_remember_me();
handle_post_actions(Auth::currentUser());
$user = Auth::currentUser();
$page = $_GET['page'] ?? ($user ? 'dashboard' : 'home');

render_header(page_title((string)$page), $user);

switch ($page) {
    case 'home':
        render_home();
        break;
    case 'talents':
        render_talents_page($user);
        break;
    case 'projects':
        render_projects_page();
        break;
    case 'talent':
        render_talent_profile_page($user);
        break;
    case 'project':
        render_project_view_page($user);
        break;
    case 'login':
        render_login_page();
        break;
    case 'register':
        render_register_page();
        break;
    case 'notifications':
        if (!$user) { redirect(url('login')); }
        render_notifications_page($user);
        break;
    case 'forgot-password':
        render_forgot_password_page();
        break;
    case 'reset-password':
        render_reset_password_page();
        break;
    case 'dashboard':
        $current = Auth::requireLogin();
        if (in_array($current['role'], ['super_admin','agent'], true)) {
            AdminPanel::render();
        } elseif ($current['role'] === 'talent') {
            TalentPanel::render($current);
        } elseif ($current['role'] === 'project_owner') {
            ClientPanel::render($current);
        } else {
            echo '<div class="py-12">Role dashboard not available.</div>';
        }
        break;
    case 'categories':
        $cats = Database::pdo()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
        echo '<section class="py-8"><h1 class="text-3xl font-black mb-6">Categories</h1><div class="grid md:grid-cols-3 gap-4">';
        foreach ($cats as $cat) echo '<div class="rounded-3xl border bg-white p-5 shadow-sm"><div class="font-bold">' . e($cat['name']) . '</div><div class="text-xs text-slate-500 mt-1">' . e($cat['slug']) . '</div></div>';
        echo '</div></section>';
        break;
    default:
        render_home();
        break;
}

render_footer();