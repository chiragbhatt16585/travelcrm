-- Run this if upgrading an existing Travel World database
USE travelworld;

ALTER TABLE enquiries
  ADD COLUMN IF NOT EXISTS status ENUM('new','contacted','follow_up','interested','booked','not_interested','closed') NOT NULL DEFAULT 'new' AFTER message,
  ADD COLUMN IF NOT EXISTS priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium' AFTER status,
  ADD COLUMN IF NOT EXISTS admin_notes TEXT NULL AFTER priority,
  ADD COLUMN IF NOT EXISTS client_response TEXT NULL AFTER admin_notes,
  ADD COLUMN IF NOT EXISTS next_followup_date DATE NULL AFTER client_response,
  ADD COLUMN IF NOT EXISTS last_contacted_at DATETIME NULL AFTER next_followup_date,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER last_contacted_at;

CREATE TABLE IF NOT EXISTS enquiry_followups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  enquiry_id INT UNSIGNED NOT NULL,
  note TEXT NOT NULL,
  status_from VARCHAR(40) DEFAULT NULL,
  status_to VARCHAR(40) DEFAULT NULL,
  next_followup_date DATE DEFAULT NULL,
  created_by VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_followups_enquiry
    FOREIGN KEY (enquiry_id) REFERENCES enquiries(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;
