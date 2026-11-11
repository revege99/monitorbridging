-- Jalankan di masing-masing database SIMRS, seperti config_pcare.
CREATE TABLE IF NOT EXISTS `antrean_fktp_attendances` (
    `no_rawat` VARCHAR(17) NOT NULL,
    `waktu_hadir` DATETIME DEFAULT NULL,
    `waktu_tidak_hadir` DATETIME DEFAULT NULL,
    PRIMARY KEY (`no_rawat`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
