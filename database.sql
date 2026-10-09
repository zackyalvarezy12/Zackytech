CREATE DATABASE IF NOT EXISTS portfolio_native CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portfolio_native;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(160) NOT NULL,
  headline VARCHAR(255) NOT NULL,
  summary TEXT NOT NULL,
  about TEXT NOT NULL,
  domicile VARCHAR(160) DEFAULT NULL,
  education VARCHAR(180) DEFAULT NULL,
  gpa VARCHAR(20) DEFAULT NULL,
  email VARCHAR(160) DEFAULT NULL,
  phone VARCHAR(60) DEFAULT NULL,
  photo VARCHAR(255) DEFAULT NULL,
  cv_file VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS navigations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(80) NOT NULL,
  url VARCHAR(180) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS social_links (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  platform VARCHAR(80) NOT NULL,
  url VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS content_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('skill','experience','education','project','certificate','organization','blog') NOT NULL,
  title VARCHAR(180) NOT NULL,
  subtitle VARCHAR(220) DEFAULT NULL,
  organization VARCHAR(180) DEFAULT NULL,
  start_date VARCHAR(60) DEFAULT NULL,
  end_date VARCHAR(60) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  body LONGTEXT DEFAULT NULL,
  technologies VARCHAR(255) DEFAULT NULL,
  url VARCHAR(255) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  document VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_type_active_order (type, is_active, sort_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS project_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_project_images_project FOREIGN KEY (project_id) REFERENCES content_items(id) ON DELETE CASCADE,
  INDEX idx_project_images_order (project_id, sort_order, id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contacts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(80) NOT NULL,
  value VARCHAR(180) NOT NULL,
  url VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_contact_messages_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL UNIQUE,
  setting_value TEXT DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO admins (name, email, password)
VALUES ('Zacky Alvarezy', 'admin@zacky.test', '$2y$10$d30rZ9yQGSyQxHBqGj3wwOxTq105KP2hs/eXnLvA4bvKjvqRE6jHu')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO profiles (id, full_name, headline, summary, about, domicile, education, gpa, photo)
VALUES (
  1,
  'Zacky Alvarezy',
  'IT Support | IT Implementor | Web Developer | QA Tester | Junior System Analyst',
  'Lulusan D3 Sistem Informasi Politeknik Negeri Padang dengan IPK 3,61. Memiliki pengalaman Full-Stack Developer di Diskominfo Kabupaten Tanah Datar selama 120 hari.',
  'Saya adalah lulusan D3 Sistem Informasi yang tertarik pada dukungan dan implementasi teknologi, pengembangan web, pengujian kualitas, serta analisis sistem.',
  NULL,
  'D3 Sistem Informasi - Politeknik Negeri Padang',
  '3,61',
  'pp.jpg'
)
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

INSERT INTO navigations (label, url, sort_order, is_active)
SELECT 'Home', '#home', 1, 1 WHERE NOT EXISTS (SELECT 1 FROM navigations WHERE url = '#home');
INSERT INTO navigations (label, url, sort_order, is_active)
SELECT 'About', '#about', 2, 1 WHERE NOT EXISTS (SELECT 1 FROM navigations WHERE url = '#about');
INSERT INTO navigations (label, url, sort_order, is_active)
SELECT 'Skills', '#skills', 3, 1 WHERE NOT EXISTS (SELECT 1 FROM navigations WHERE url = '#skills');
INSERT INTO navigations (label, url, sort_order, is_active)
SELECT 'Experience', '#experience', 4, 1 WHERE NOT EXISTS (SELECT 1 FROM navigations WHERE url = '#experience');
INSERT INTO navigations (label, url, sort_order, is_active)
SELECT 'Education', '#education', 5, 1 WHERE NOT EXISTS (SELECT 1 FROM navigations WHERE url = '#education');
INSERT INTO navigations (label, url, sort_order, is_active)
SELECT 'Projects', '#projects', 6, 1 WHERE NOT EXISTS (SELECT 1 FROM navigations WHERE url = '#projects');
INSERT INTO navigations (label, url, sort_order, is_active)
SELECT 'Certificates', '#certificates', 7, 1 WHERE NOT EXISTS (SELECT 1 FROM navigations WHERE url = '#certificates');
INSERT INTO navigations (label, url, sort_order, is_active)
SELECT 'Organization', '#organization', 8, 1 WHERE NOT EXISTS (SELECT 1 FROM navigations WHERE url = '#organization');
INSERT INTO navigations (label, url, sort_order, is_active)
SELECT 'Blog', '#blog', 9, 1 WHERE NOT EXISTS (SELECT 1 FROM navigations WHERE url = '#blog');
INSERT INTO navigations (label, url, sort_order, is_active)
SELECT 'Contact', '#contact', 10, 1 WHERE NOT EXISTS (SELECT 1 FROM navigations WHERE url = '#contact');

INSERT INTO social_links (platform, url, sort_order, is_active)
SELECT 'GitHub', 'https://github.com/zackyalvarezy12', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM social_links WHERE platform = 'GitHub' AND url = 'https://github.com/zackyalvarezy12');

INSERT INTO content_items (type, title, subtitle, organization, description, body, technologies, sort_order, is_featured, is_active)
SELECT 'experience', 'Full-Stack Developer', 'Pengalaman selama 120 hari', 'Diskominfo Kabupaten Tanah Datar',
       'Pengalaman Full-Stack Developer di Diskominfo Kabupaten Tanah Datar selama 120 hari.', NULL, NULL, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM content_items WHERE type = 'experience' AND title = 'Full-Stack Developer');

INSERT INTO content_items (type, title, subtitle, organization, description, sort_order, is_featured, is_active)
SELECT 'education', 'D3 Sistem Informasi', 'IPK 3,61', 'Politeknik Negeri Padang',
       'Lulusan D3 Sistem Informasi Politeknik Negeri Padang dengan IPK 3,61.', 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM content_items WHERE type = 'education' AND title = 'D3 Sistem Informasi');

INSERT INTO settings (setting_key, setting_value)
VALUES
('site_title', 'Zacky Alvarezy - Portfolio'),
('site_description', 'Portfolio profesional Zacky Alvarezy untuk posisi IT Support, IT Implementor, Web Developer, QA Tester, dan Junior System Analyst.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
