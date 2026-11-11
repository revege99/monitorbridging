-- Jalankan pada masing-masing database SIMRS sebelum memulai worker.
CREATE TABLE IF NOT EXISTS `satu_sehat_service_resources` (
    `resource_key` CHAR(64) NOT NULL,
    `no_rawat` VARCHAR(17) NOT NULL,
    `resource_type` VARCHAR(40) NOT NULL,
    `resource_id` VARCHAR(100) DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL,
    `http_code` SMALLINT UNSIGNED DEFAULT NULL,
    `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_attempt_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`resource_key`),
    KEY `satusehat_rawat_type` (`no_rawat`, `resource_type`),
    KEY `satusehat_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
