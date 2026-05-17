-- Daily Bread Learning Center Database Backup
-- Date: 2026-05-16 14:56:23
-- Database: schoolenrollmentdb

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `old_data` text DEFAULT NULL,
  `new_data` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollee_id` int(11) NOT NULL,
  `birth_certificate_path` varchar(500) NOT NULL,
  `id_picture_path` varchar(500) NOT NULL,
  `report_card_path` varchar(500) DEFAULT NULL,
  `proof_certification_path` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`document_id`),
  KEY `enrollee_id` (`enrollee_id`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`enrollee_id`) REFERENCES `enrollees` (`enrollee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `documents` VALUES ('1', '2', 'uploads/birth_cert_2_1778934743.jpg', 'uploads/id_picture_2_1778934743.jpg', 'uploads/report_card_2_1778934743.jpg', '');

DROP TABLE IF EXISTS `emergency_consent`;
CREATE TABLE `emergency_consent` (
  `consent_id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollee_id` int(11) NOT NULL,
  `parent_guardian_signature` varchar(100) NOT NULL,
  `date_signed` date NOT NULL,
  `receipt_path` varchar(500) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`consent_id`),
  KEY `enrollee_id` (`enrollee_id`),
  CONSTRAINT `emergency_consent_ibfk_1` FOREIGN KEY (`enrollee_id`) REFERENCES `enrollees` (`enrollee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `emergency_consent` VALUES ('1', '2', 'sadasdasdasd', '2026-05-16', NULL, NULL);

DROP TABLE IF EXISTS `enrollees`;
CREATE TABLE `enrollees` (
  `enrollee_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_type` varchar(20) NOT NULL,
  `program_level` varchar(20) NOT NULL,
  `payment_plan` varchar(50) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `birth_date` date NOT NULL,
  `place_of_birth` varchar(100) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `qualification_status` varchar(20) DEFAULT 'Pending',
  `payment_status` varchar(50) DEFAULT 'Unpaid',
  `requirements_status` varchar(20) DEFAULT 'Pending',
  `requirements_notes` text DEFAULT NULL,
  `birth_cert_received` tinyint(4) DEFAULT 0,
  `id_picture_received` tinyint(4) DEFAULT 0,
  `report_card_received` tinyint(4) DEFAULT 0,
  `immunization_record_received` tinyint(4) DEFAULT 0,
  `medical_cert_received` tinyint(4) DEFAULT 0,
  `proof_certification_received` tinyint(4) DEFAULT 0,
  `enrollment_status` varchar(20) DEFAULT 'Pending',
  `enrollment_status_date` date DEFAULT NULL,
  `enrollment_status_reason` text DEFAULT NULL,
  `is_archived` tinyint(4) DEFAULT 0,
  `archived_date` date DEFAULT NULL,
  `archive_reason` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`enrollee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `enrollees` VALUES ('1', 'New Student', 'KINDERGARTEN 1', 'Cash (Full)', '18300.00', 'Dela Cruz', 'Juan', NULL, NULL, '2020-05-10', NULL, '123 Main St, Manila', NULL, NULL, '2026-05-15 11:58:08', 'Pending', 'Unpaid', 'Pending', NULL, '0', '0', '0', '0', '0', '0', 'Pending', NULL, NULL, '0', NULL, NULL);
INSERT INTO `enrollees` VALUES ('2', 'New Student', 'KINDERGARTEN 1', 'Cash (Full)', '18300.00', 'sadsad', 'asdsadas', 'asdasdasd', 'assdasd', '2021-11-01', 'Tarlac', 'asdadasd', NULL, NULL, '2026-05-16 20:32:23', 'Pending', 'Partial', 'Pending', NULL, '0', '0', '0', '0', '0', '0', 'Pending', NULL, NULL, '0', NULL, NULL);

DROP TABLE IF EXISTS `father_info`;
CREATE TABLE `father_info` (
  `father_id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollee_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`father_id`),
  KEY `enrollee_id` (`enrollee_id`),
  CONSTRAINT `father_info_ibfk_1` FOREIGN KEY (`enrollee_id`) REFERENCES `enrollees` (`enrollee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `father_info` VALUES ('1', '1', 'Juan Dela Cruz Sr.', '09123456788', 'Engineer');
INSERT INTO `father_info` VALUES ('2', '2', 'asdasd', '09321547986', 'wesfsdsfs');

DROP TABLE IF EXISTS `mother_info`;
CREATE TABLE `mother_info` (
  `mother_id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollee_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `processed_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `received_by` varchar(100) DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`mother_id`),
  KEY `enrollee_id` (`enrollee_id`),
  CONSTRAINT `mother_info_ibfk_1` FOREIGN KEY (`enrollee_id`) REFERENCES `enrollees` (`enrollee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `mother_info` VALUES ('1', '1', 'Maria Dela Cruz', '09123456789', 'Teacher', NULL, NULL, '2026-05-15 11:58:09', NULL, NULL, NULL);
INSERT INTO `mother_info` VALUES ('2', '2', 'asdasdasd', '09656532125', 'ASDasdas', NULL, NULL, '2026-05-16 20:32:23', NULL, NULL, NULL);

DROP TABLE IF EXISTS `payment_schedule`;
CREATE TABLE `payment_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program` varchar(50) NOT NULL,
  `payment_date` varchar(50) NOT NULL,
  `cash` decimal(10,2) DEFAULT 0.00,
  `semi_annual` decimal(10,2) DEFAULT 0.00,
  `quarterly` decimal(10,2) DEFAULT 0.00,
  `monthly` decimal(10,2) DEFAULT 0.00,
  `is_total` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `payment_schedule` VALUES ('1', 'NURSERY', 'Upon enrollment', '17500.00', '8900.00', '6600.00', '5250.00', '0');
INSERT INTO `payment_schedule` VALUES ('2', 'NURSERY', '07/01/2026', '0.00', '0.00', '0.00', '1450.00', '0');
INSERT INTO `payment_schedule` VALUES ('3', 'NURSERY', '08/01/2026', '0.00', '0.00', '0.00', '1450.00', '0');
INSERT INTO `payment_schedule` VALUES ('4', 'NURSERY', '09/01/2026', '0.00', '0.00', '3900.00', '1450.00', '0');
INSERT INTO `payment_schedule` VALUES ('5', 'NURSERY', '10/01/2026', '0.00', '0.00', '0.00', '1450.00', '0');
INSERT INTO `payment_schedule` VALUES ('6', 'NURSERY', '11/01/2026', '0.00', '0.00', '8900.00', '1450.00', '0');
INSERT INTO `payment_schedule` VALUES ('7', 'NURSERY', '12/01/2026', '0.00', '0.00', '0.00', '1450.00', '0');
INSERT INTO `payment_schedule` VALUES ('8', 'NURSERY', '01/01/2027', '0.00', '0.00', '0.00', '1450.00', '0');
INSERT INTO `payment_schedule` VALUES ('9', 'NURSERY', '02/01/2027', '0.00', '0.00', '0.00', '1450.00', '0');
INSERT INTO `payment_schedule` VALUES ('10', 'NURSERY', '03/01/2027', '0.00', '0.00', '0.00', '1450.00', '0');
INSERT INTO `payment_schedule` VALUES ('11', 'NURSERY', 'TOTAL', '17500.00', '17800.00', '18100.00', '18300.00', '1');
INSERT INTO `payment_schedule` VALUES ('12', 'KINDERGARTEN 1', 'Upon enrollment', '18300.00', '9400.00', '7050.00', '5700.00', '0');
INSERT INTO `payment_schedule` VALUES ('13', 'KINDERGARTEN 1', '11/01/2026', '0.00', '9300.00', '0.00', '1500.00', '0');
INSERT INTO `payment_schedule` VALUES ('14', 'KINDERGARTEN 1', 'TOTAL', '18300.00', '18700.00', '18900.00', '19200.00', '1');
INSERT INTO `payment_schedule` VALUES ('15', 'KINDERGARTEN 2', 'Upon enrollment', '18300.00', '10100.00', '7550.00', '6200.00', '0');
INSERT INTO `payment_schedule` VALUES ('16', 'KINDERGARTEN 2', '07/01/2026', '0.00', '0.00', '0.00', '1500.00', '0');
INSERT INTO `payment_schedule` VALUES ('17', 'KINDERGARTEN 2', '08/01/2026', '0.00', '0.00', '0.00', '1500.00', '0');
INSERT INTO `payment_schedule` VALUES ('18', 'KINDERGARTEN 2', '09/01/2026', '0.00', '0.00', '3950.00', '1500.00', '0');
INSERT INTO `payment_schedule` VALUES ('19', 'KINDERGARTEN 2', '10/01/2026', '0.00', '0.00', '0.00', '1500.00', '0');
INSERT INTO `payment_schedule` VALUES ('20', 'KINDERGARTEN 2', '11/01/2026', '0.00', '9500.00', '0.00', '1500.00', '0');
INSERT INTO `payment_schedule` VALUES ('21', 'KINDERGARTEN 2', '12/01/2026', '0.00', '0.00', '3950.00', '1500.00', '0');
INSERT INTO `payment_schedule` VALUES ('22', 'KINDERGARTEN 2', '01/01/2027', '0.00', '0.00', '0.00', '1500.00', '0');
INSERT INTO `payment_schedule` VALUES ('23', 'KINDERGARTEN 2', '02/01/2027', '0.00', '0.00', '0.00', '1500.00', '0');
INSERT INTO `payment_schedule` VALUES ('24', 'KINDERGARTEN 2', '03/01/2027', '0.00', '0.00', '3950.00', '1500.00', '0');
INSERT INTO `payment_schedule` VALUES ('25', 'KINDERGARTEN 2', 'TOTAL', '18300.00', '19600.00', '19400.00', '19700.00', '1');

DROP TABLE IF EXISTS `payment_transactions`;
CREATE TABLE `payment_transactions` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollee_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_type` varchar(50) NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `processed_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `receipt_generated` tinyint(4) DEFAULT 0,
  `receipt_path` varchar(500) DEFAULT NULL,
  `processed_by_user_id` int(11) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `refund_date` date DEFAULT NULL,
  `refund_reason` text DEFAULT NULL,
  `refund_status` varchar(20) DEFAULT 'None',
  `payment_method` varchar(50) DEFAULT 'Cash',
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_verified` tinyint(4) DEFAULT 1,
  `request_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`transaction_id`),
  KEY `enrollee_id` (`enrollee_id`),
  KEY `processed_by_user_id` (`processed_by_user_id`),
  CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`enrollee_id`) REFERENCES `enrollees` (`enrollee_id`) ON DELETE CASCADE,
  CONSTRAINT `payment_transactions_ibfk_2` FOREIGN KEY (`processed_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `payment_transactions` VALUES ('1', '2', '2026-05-16', '15000.00', 'Payment', 'RCP-2026-0001', NULL, 'Cashier Office', '2026-05-16 20:38:25', '0', NULL, '5', 'Partial', '0.00', NULL, NULL, 'None', 'Cash', NULL, '1', NULL);
INSERT INTO `payment_transactions` VALUES ('2', '2', '2026-05-16', '15000.00', 'Online Payment', NULL, '', 'System Administrator', '2026-05-16 20:54:12', '0', 'uploads/payment_proofs/payment_2_1778936052.jpg', NULL, NULL, '0.00', NULL, NULL, 'None', 'gcash', 'DB2026-4E29BF', '1', NULL);

DROP TABLE IF EXISTS `refund_requests`;
CREATE TABLE `refund_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollee_id` int(11) NOT NULL,
  `request_date` date NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `refund_reason` text NOT NULL,
  `letter_path` varchar(500) NOT NULL,
  `status` enum('pending','approved','denied','processed') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `enrollee_id` (`enrollee_id`),
  KEY `approved_by` (`approved_by`),
  KEY `processed_by` (`processed_by`),
  CONSTRAINT `refund_requests_ibfk_1` FOREIGN KEY (`enrollee_id`) REFERENCES `enrollees` (`enrollee_id`) ON DELETE CASCADE,
  CONSTRAINT `refund_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `refund_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `siblings`;
CREATE TABLE `siblings` (
  `sibling_id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollee_id` int(11) NOT NULL,
  `sibling_name` varchar(100) NOT NULL,
  `sibling_birth_date` date NOT NULL,
  PRIMARY KEY (`sibling_id`),
  KEY `enrollee_id` (`enrollee_id`),
  CONSTRAINT `siblings_ibfk_1` FOREIGN KEY (`enrollee_id`) REFERENCES `enrollees` (`enrollee_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'text',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `system_settings` VALUES ('1', 'school_name', 'Daily Bread Learning Center Inc.', 'text', 'School Name', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('2', 'school_year', '2026-2027', 'text', 'Current Academic Year', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('3', 'school_address', 'Block 1, Lot 17 Palmera Springs 38, Camarin, Kalookan City', 'text', 'School Address', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('4', 'school_phone', '0923-4701532', 'text', 'School Contact Number', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('5', 'school_email', 'info@dailybread.edu.ph', 'email', 'School Email', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('6', 'gcash_number', '0923-4701532', 'text', 'GCash Account Number', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('7', 'gcash_name', 'Daily Bread Learning Center', 'text', 'GCash Account Name', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('8', 'bank_name', 'Bank of the Philippine Islands (BPI)', 'text', 'Bank Name', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('9', 'bank_account', '1234-5678-90', 'text', 'Bank Account Number', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('10', 'bank_account_name', 'Daily Bread Learning Center Inc.', 'text', 'Bank Account Name', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('11', 'enrollment_fee_nursery', '17500', 'number', 'Nursery Tuition Fee', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('12', 'enrollment_fee_k1', '18300', 'number', 'Kindergarten 1 Tuition Fee', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('13', 'enrollment_fee_k2', '18300', 'number', 'Kindergarten 2 Tuition Fee', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('14', 'backup_auto_schedule', 'daily', 'text', 'Auto Backup Schedule', '2026-05-15 11:58:08');
INSERT INTO `system_settings` VALUES ('15', 'maintenance_mode', '0', 'checkbox', 'Maintenance Mode', '2026-05-15 11:58:08');

DROP TABLE IF EXISTS `tuition_settings`;
CREATE TABLE `tuition_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program` varchar(50) NOT NULL,
  `fee_type` varchar(50) NOT NULL,
  `cash` decimal(10,2) DEFAULT 0.00,
  `semi_annual` decimal(10,2) DEFAULT 0.00,
  `quarterly` decimal(10,2) DEFAULT 0.00,
  `monthly` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_program_fee` (`program`,`fee_type`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tuition_settings` VALUES ('1', 'NURSERY', 'Registration', '500.00', '500.00', '500.00', '500.00');
INSERT INTO `tuition_settings` VALUES ('2', 'NURSERY', 'Tuition fee', '13500.00', '5400.00', '3600.00', '2250.00');
INSERT INTO `tuition_settings` VALUES ('3', 'NURSERY', 'Misc. fee', '3500.00', '3000.00', '2500.00', '2500.00');
INSERT INTO `tuition_settings` VALUES ('4', 'KINDERGARTEN 1', 'Registration', '500.00', '500.00', '500.00', '500.00');
INSERT INTO `tuition_settings` VALUES ('5', 'KINDERGARTEN 1', 'Tuition fee', '14300.00', '6400.00', '4050.00', '2650.00');
INSERT INTO `tuition_settings` VALUES ('6', 'KINDERGARTEN 1', 'Misc. fee', '3500.00', '2500.00', '2500.00', '2550.00');
INSERT INTO `tuition_settings` VALUES ('7', 'KINDERGARTEN 2', 'Registration', '500.00', '500.00', '500.00', '500.00');
INSERT INTO `tuition_settings` VALUES ('8', 'KINDERGARTEN 2', 'Tuition fee', '14300.00', '6600.00', '4550.00', '3200.00');
INSERT INTO `tuition_settings` VALUES ('9', 'KINDERGARTEN 2', 'Misc. fee', '3500.00', '3000.00', '2500.00', '2500.00');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','registrar','cashier','staff') NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES ('1', 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'System Administrator', 'admin@dailybread.edu.ph', 'admin', '2026-05-16 20:56:19', '1', NULL, NULL, '2026-05-15 11:58:08');
INSERT INTO `users` VALUES ('4', 'registrar', '7f2689caeaf33345f39230bd10eadc1b845afbc40314ed9fc75b6d9c6b1c532c', 'Registrar Office', 'registrar@dailybread.edu.ph', 'registrar', '2026-05-16 20:35:59', '1', NULL, NULL, '2026-05-15 12:01:04');
INSERT INTO `users` VALUES ('5', 'cashier', '17f6ac95a64ce52e0a827f824a7b73c324b2c9a1801e59089fa77f1b3463c0f2', 'Cashier Office', 'cashier@dailybread.edu.ph', 'cashier', '2026-05-16 20:38:11', '1', NULL, NULL, '2026-05-15 12:01:33');

SET FOREIGN_KEY_CHECKS=1;
