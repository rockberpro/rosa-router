-- Notes:
--  - UUID columns use CHAR(36) with DEFAULT (UUID())
--  - VARCHAR sizes chosen to allow indexing under utf8mb4 (191)
--  - Engine set to InnoDB and charset utf8mb4

DROP TABLE IF EXISTS `api_tokens`;
DROP TABLE IF EXISTS `api_keys`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `logs`;

CREATE TABLE `users` (
  `id` CHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
  `username` VARCHAR(191) NOT NULL,
  `password` TEXT NOT NULL,
  `hash_alg` VARCHAR(64) NOT NULL,
  `audience` VARCHAR(191) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_users_username` ON `users` (`username`);
CREATE INDEX `idx_users_audience` ON `users` (`audience`);

CREATE TABLE `api_keys` (
  `id` CHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
  `audience` VARCHAR(191) NOT NULL,
  `key` VARCHAR(255) NOT NULL,
  `hash_alg` VARCHAR(64) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_api_keys_audience` ON `api_keys` (`audience`);
CREATE INDEX `idx_api_keys_key` ON `api_keys` (`key`);

CREATE TABLE `api_tokens` (
  `id` CHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
  `user_id` CHAR(36) NOT NULL,
  `audience` VARCHAR(191) NOT NULL,
  `type` VARCHAR(64) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `hash_alg` VARCHAR(64) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revoked_at` TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT `fk_api_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_api_tokens_audience` ON `api_tokens` (`audience`);
CREATE INDEX `idx_api_tokens_key` ON `api_tokens` (`token`);

CREATE TABLE `logs` (
  `id` CHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
  `channel` VARCHAR(191) NULL,
  `level` VARCHAR(64) NULL,
  `message` TEXT NULL,
  `context` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `logs_channel` ON `logs` (`channel`);
CREATE INDEX `logs_level` ON `logs` (`level`);
CREATE INDEX `logs_created_at` ON `logs` (`created_at`);
