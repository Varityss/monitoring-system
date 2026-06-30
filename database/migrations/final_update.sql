-- Final non-destructive update for existing monitoring-system databases.
-- Run this after making a backup. The statements are safe for MariaDB/MySQL
-- versions that support IF NOT EXISTS in ALTER TABLE.

ALTER TABLE equipment
  ADD COLUMN IF NOT EXISTS barcode varchar(255) DEFAULT NULL AFTER serial_number,
  ADD COLUMN IF NOT EXISTS updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
  ADD UNIQUE KEY IF NOT EXISTS barcode (barcode),
  ADD KEY IF NOT EXISTS status (status),
  ADD KEY IF NOT EXISTS cabinet (cabinet);

ALTER TABLE requests
  ADD COLUMN IF NOT EXISTS admin_comment text AFTER comment,
  ADD COLUMN IF NOT EXISTS updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
  ADD KEY IF NOT EXISTS status (status);

ALTER TABLE equipment_issues
  ADD COLUMN IF NOT EXISTS issued_to varchar(255) DEFAULT NULL AFTER request_id,
  ADD COLUMN IF NOT EXISTS cabinet varchar(100) DEFAULT NULL AFTER issued_to,
  ADD COLUMN IF NOT EXISTS issued_by int DEFAULT NULL AFTER cabinet,
  ADD COLUMN IF NOT EXISTS notes text AFTER issued_by,
  ADD KEY IF NOT EXISTS issued_by (issued_by);

ALTER TABLE request_equipment
  ADD COLUMN IF NOT EXISTS issued_at timestamp NULL DEFAULT NULL AFTER equipment_id,
  ADD COLUMN IF NOT EXISTS returned_at timestamp NULL DEFAULT NULL AFTER issued_at,
  ADD COLUMN IF NOT EXISTS status varchar(50) DEFAULT 'reserved' AFTER returned_at,
  ADD KEY IF NOT EXISTS status (status);

CREATE TABLE IF NOT EXISTS activity_logs (
  id int NOT NULL AUTO_INCREMENT,
  user_id int DEFAULT NULL,
  action varchar(100) NOT NULL,
  entity_type varchar(100) NOT NULL,
  entity_id int DEFAULT NULL,
  description text,
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY user_id (user_id),
  KEY entity (entity_type, entity_id),
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
