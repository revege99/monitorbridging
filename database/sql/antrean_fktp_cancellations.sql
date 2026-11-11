-- Jalankan pada masing-masing database SIMRS, seperti config_pcare.
CREATE TABLE IF NOT EXISTS `antrean_fktp_cancellations` (
    `no_rawat` VARCHAR(17) NOT NULL,
    `status` VARCHAR(20) NOT NULL,
    `waktu_batal` DATETIME NOT NULL,
    INDEX `antrean_fktp_cancellations_no_rawat_index` (`no_rawat`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
