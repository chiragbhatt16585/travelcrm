-- Travel World database schema
CREATE DATABASE IF NOT EXISTS travelworld CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE travelworld;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL DEFAULT 'Admin',
  role ENUM('superadmin','staff') NOT NULL DEFAULT 'staff',
  created_by VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_permissions (
  admin_id INT UNSIGNED NOT NULL,
  permission VARCHAR(40) NOT NULL,
  PRIMARY KEY (admin_id, permission),
  CONSTRAINT fk_admin_permissions FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS destinations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  tagline VARCHAR(255) DEFAULT NULL,
  description TEXT,
  hero_image VARCHAR(255) DEFAULT NULL,
  type ENUM('domestic','international') NOT NULL DEFAULT 'domestic',
  duration_label VARCHAR(50) DEFAULT NULL,
  starting_price DECIMAL(10,2) DEFAULT NULL,
  meta_left VARCHAR(255) DEFAULT NULL,
  meta_right VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS packages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  destination_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  nights INT UNSIGNED DEFAULT NULL,
  days INT UNSIGNED DEFAULT NULL,
  route VARCHAR(255) DEFAULT NULL,
  package_type VARCHAR(120) DEFAULT NULL,
  starting_price DECIMAL(10,2) DEFAULT NULL,
  hero_image VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_packages_destination
    FOREIGN KEY (destination_id) REFERENCES destinations(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS package_itinerary (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  package_id INT UNSIGNED NOT NULL,
  day_label VARCHAR(50) NOT NULL,
  description TEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_itinerary_package
    FOREIGN KEY (package_id) REFERENCES packages(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS package_hotels (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  package_id INT UNSIGNED NOT NULL,
  location_name VARCHAR(120) NOT NULL,
  category VARCHAR(80) NOT NULL,
  hotel_name VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_hotels_package
    FOREIGN KEY (package_id) REFERENCES packages(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS package_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  package_id INT UNSIGNED NOT NULL,
  item_type ENUM('inclusion','exclusion') NOT NULL,
  content VARCHAR(500) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_items_package
    FOREIGN KEY (package_id) REFERENCES packages(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enquiries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(30) DEFAULT NULL,
  destination VARCHAR(120) DEFAULT NULL,
  adults SMALLINT UNSIGNED DEFAULT NULL,
  children SMALLINT UNSIGNED DEFAULT NULL,
  message TEXT,
  status ENUM('new','contacted','follow_up','interested','booked','not_interested','closed') NOT NULL DEFAULT 'new',
  priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  admin_notes TEXT,
  client_response TEXT,
  next_followup_date DATE DEFAULT NULL,
  last_contacted_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

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

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(80) PRIMARY KEY,
  setting_value TEXT NOT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('client_template', 'classic');
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('quote_terms', 'This quotation is valid for 7 days from the date of issue and is subject to availability.\nRates are for the hotels, room category, and travel dates mentioned. Any change may revise the price.\nThe booking is confirmed only after the advance payment is received.\nServices not listed under inclusions are payable extra.\nThe company is not liable for delays caused by weather, traffic, or other events outside its control.\nGovernment taxes, permits, or hotel rate revisions, if applicable, will be charged extra.'),
('quote_payment_terms', '50% of the package cost is payable in advance to confirm the booking.\nThe balance is payable 15 days before the date of travel.\nPlease share the payment reference after transfer so the booking can be updated.');

CREATE TABLE IF NOT EXISTS quotations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  enquiry_id INT UNSIGNED NOT NULL,
  token VARCHAR(32) NOT NULL UNIQUE,
  option_no INT UNSIGNED NOT NULL DEFAULT 1,
  title VARCHAR(200) NOT NULL,
  nights INT UNSIGNED DEFAULT NULL,
  days INT UNSIGNED DEFAULT NULL,
  travel_date DATE DEFAULT NULL,
  adults INT UNSIGNED NOT NULL DEFAULT 2,
  price_per_person DECIMAL(10,2) DEFAULT NULL,
  child_with_bed INT UNSIGNED NOT NULL DEFAULT 0,
  child_with_bed_price DECIMAL(10,2) DEFAULT NULL,
  child_without_bed INT UNSIGNED NOT NULL DEFAULT 0,
  child_without_bed_price DECIMAL(10,2) DEFAULT NULL,
  room_note VARCHAR(255) DEFAULT NULL,
  child_free_note VARCHAR(255) DEFAULT NULL,
  inclusions TEXT,
  exclusions TEXT,
  notes TEXT,
  status ENUM('draft','sent','accepted','rejected') NOT NULL DEFAULT 'draft',
  selected_package_id INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_quotations_enquiry FOREIGN KEY (enquiry_id) REFERENCES enquiries(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quotation_days (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quotation_id INT UNSIGNED NOT NULL,
  day_date DATE DEFAULT NULL,
  heading VARCHAR(200) NOT NULL,
  description TEXT,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_qdays_quote FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quotation_hotel_packages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quotation_id INT UNSIGNED NOT NULL,
  code VARCHAR(100) NOT NULL,
  label VARCHAR(120) DEFAULT NULL,
  price_per_person DECIMAL(10,2) DEFAULT NULL,
  child_with_bed_price DECIMAL(10,2) DEFAULT NULL,
  child_without_bed_price DECIMAL(10,2) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_qpackages_quote FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
  UNIQUE KEY uq_quote_package_code (quotation_id, code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quotation_stays (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quotation_id INT UNSIGNED NOT NULL,
  package_id INT UNSIGNED NOT NULL,
  nights INT UNSIGNED DEFAULT NULL,
  city VARCHAR(120) NOT NULL,
  hotel_name VARCHAR(255) NOT NULL,
  meal_plan VARCHAR(120) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_qstays_quote FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
  CONSTRAINT fk_qstays_package FOREIGN KEY (package_id) REFERENCES quotation_hotel_packages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quotation_payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  quotation_id INT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  paid_on DATE NOT NULL,
  pay_mode ENUM('bank','upi','cash') NOT NULL DEFAULT 'bank',
  txn_no VARCHAR(32) NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_quote FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
  UNIQUE KEY uq_payment_txn (txn_no)
) ENGINE=InnoDB;
