-- Jalankan pada masing-masing database SIMRS yang belum memiliki ss_config.
-- Tabel existing tidak diubah dan data lama tidak ditimpa.
CREATE TABLE IF NOT EXISTS `ss_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `client_id` VARCHAR(255) NOT NULL,
    `client_secret` TEXT NOT NULL,
    `organization_id` VARCHAR(255) NOT NULL,
    `location_id` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
