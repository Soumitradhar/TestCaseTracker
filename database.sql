-- ============================================================
--  TestFlow Database Schema v3 (with File Reports)
--  phpMyAdmin: Database > Import > Choose this file > Go
-- ============================================================

CREATE DATABASE IF NOT EXISTS `testflow` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `testflow`;

CREATE TABLE IF NOT EXISTS `projects` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `slug`       VARCHAR(60)  NOT NULL UNIQUE,
  `name`       VARCHAR(100) NOT NULL,
  `color`      VARCHAR(10)  NOT NULL DEFAULT '#00B894',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `projects` (`slug`,`name`,`color`) VALUES
  ('telemed',       'Telemed',       '#6C5CE7');

CREATE TABLE IF NOT EXISTS `test_cases` (
  `id`          INT  NOT NULL AUTO_INCREMENT,
  `tc_id`       VARCHAR(20)  NOT NULL,
  `project_id`  INT  NOT NULL,
  `title`       VARCHAR(200) NOT NULL,
  `description` TEXT,
  `priority`    ENUM('High','Medium','Low') NOT NULL DEFAULT 'Medium',
  `status`      ENUM('pending','pass','fail','skip') NOT NULL DEFAULT 'pending',
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tc` (`tc_id`,`project_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `test_runs` (
  `id`         INT  NOT NULL AUTO_INCREMENT,
  `run_id`     VARCHAR(20)  NOT NULL,
  `project_id` INT  NOT NULL,
  `total`      INT  NOT NULL DEFAULT 0,
  `pass`       INT  NOT NULL DEFAULT 0,
  `fail`       INT  NOT NULL DEFAULT 0,
  `skip`       INT  NOT NULL DEFAULT 0,
  `pending`    INT  NOT NULL DEFAULT 0,
  `snapshot`   LONGTEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_run` (`run_id`,`project_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── REPORTS (file attachments per project) ────────────────
CREATE TABLE IF NOT EXISTS `reports` (
  `id`            INT  NOT NULL AUTO_INCREMENT,
  `project_id`    INT  NOT NULL,
  `title`         VARCHAR(200) NOT NULL,
  `description`   TEXT,
  `file_name`     VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `file_type`     VARCHAR(100) NOT NULL,
  `file_size`     INT  NOT NULL DEFAULT 0,
  `uploaded_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── SEED DATA ─────────────────────────────────────────────
INSERT IGNORE INTO `test_cases` (`tc_id`,`project_id`,`title`,`description`,`priority`,`status`) VALUES
  ('TC-001',1,'Patient login flow','Patient can log in and access dashboard','High','pass'),
  ('TC-002',1,'Video consultation start','Video call initiates between doctor and patient','High','pass'),
  ('TC-003',1,'Prescription e-sign','Doctor digitally signs and sends prescription','High','pending'),
  ('TC-004',1,'Appointment rescheduling','Patient can cancel and rebook appointment','Medium','skip'),
  ('TC-005',1,'Medical records upload','Patient uploads and views PDF records','Medium','fail'),
  ('TC-006',1,'Billing invoice generation','Invoice generated correctly after consultation','Low','pending');

-- ── PDF REPORTS (per project, company format) ─────────────
CREATE TABLE IF NOT EXISTS `pdf_reports` (
  `id`            INT           NOT NULL AUTO_INCREMENT,
  `project_id`    INT           NOT NULL,
  `title`         VARCHAR(200)  NOT NULL,
  `description`   TEXT,
  `file_name`     VARCHAR(255)  NOT NULL,
  `original_name` VARCHAR(255)  NOT NULL,
  `file_size`     INT           NOT NULL DEFAULT 0,
  `uploaded_by`   VARCHAR(100)  DEFAULT NULL,
  `uploaded_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
