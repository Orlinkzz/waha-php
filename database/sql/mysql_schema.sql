-- MySQL Schema for WAHA PHP Library
-- Generated on 2026-06-23

-- Table: waha_sessions
CREATE TABLE IF NOT EXISTS `waha_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_name` VARCHAR(255) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'inactive',
  `qr_code` TEXT,
  `connected_at` TIMESTAMP NULL,
  `disconnected_at` TIMESTAMP NULL,
  `metadata` JSON,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_session_name` (`session_name`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: waha_contacts
CREATE TABLE IF NOT EXISTS `waha_contacts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` BIGINT UNSIGNED NOT NULL,
  `contact_id` VARCHAR(255) NOT NULL,
  `phone_number` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255),
  `is_blocked` BOOLEAN NOT NULL DEFAULT 0,
  `is_business` BOOLEAN NOT NULL DEFAULT 0,
  `metadata` JSON,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_contact_id` (`contact_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_phone_number` (`phone_number`),
  KEY `idx_name` (`name`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: waha_messages
CREATE TABLE IF NOT EXISTS `waha_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` BIGINT UNSIGNED NOT NULL,
  `message_id` VARCHAR(255) NOT NULL,
  `chat_id` VARCHAR(255) NOT NULL,
  `from_me` BOOLEAN NOT NULL DEFAULT 0,
  `message_type` VARCHAR(50) NOT NULL DEFAULT 'text',
  `content` TEXT,
  `timestamp` BIGINT NOT NULL,
  `metadata` JSON,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_message_id` (`message_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_chat_id` (`chat_id`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_from_me` (`from_me`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: waha_message_logs
CREATE TABLE IF NOT EXISTS `waha_message_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` BIGINT UNSIGNED NOT NULL,
  `chat_id` VARCHAR(255) NOT NULL,
  `message_type` VARCHAR(50) NOT NULL DEFAULT 'text',
  `content` TEXT,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `error_message` TEXT,
  `sent_at` TIMESTAMP NULL,
  `metadata` JSON,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_chat_id` (`chat_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_at` (`sent_at`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;