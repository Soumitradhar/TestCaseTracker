-- Create pdf_reports table if it doesn't exist
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
