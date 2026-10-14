-- Migración 0015: Tablas para almacenamiento local y caché del RCV del SII y Cuadratura Diaria
CREATE TABLE IF NOT EXISTS `sii_documentos_rcv` (
    `ID` INT AUTO_INCREMENT PRIMARY KEY,
    `Operacion` ENUM('COMPRA', 'VENTA') NOT NULL,
    `Periodo` VARCHAR(6) NOT NULL,
    `FechaEmision` DATE NOT NULL,
    `TipoDTE` INT NOT NULL,
    `NombreTipo` VARCHAR(100) NOT NULL,
    `Folio` BIGINT NOT NULL,
    `RutContraparte` VARCHAR(20) NOT NULL,
    `RutCuerpo` INT NOT NULL DEFAULT 0,
    `RazonSocial` VARCHAR(255) NOT NULL,
    `MontoNeto` INT NOT NULL DEFAULT 0,
    `MontoExento` INT NOT NULL DEFAULT 0,
    `MontoIVA` INT NOT NULL DEFAULT 0,
    `MontoTotal` INT NOT NULL DEFAULT 0,
    `EstadoSII` VARCHAR(100) NULL,
    `Conciliado` TINYINT(1) NOT NULL DEFAULT 0,
    `LocalID` INT NULL,
    `FechaSincronizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_rcv_doc` (`Operacion`, `Periodo`, `TipoDTE`, `Folio`, `RutCuerpo`),
    INDEX `idx_rcv_periodo` (`Periodo`, `Operacion`),
    INDEX `idx_rcv_fecha` (`FechaEmision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sii_cuadratura_diaria` (
    `Fecha` DATE PRIMARY KEY,
    `Periodo` VARCHAR(6) NOT NULL,
    `TotalCaja` INT NOT NULL DEFAULT 0,
    `CantVentasCaja` INT NOT NULL DEFAULT 0,
    `TotalSII` INT NOT NULL DEFAULT 0,
    `CantDtesSII` INT NOT NULL DEFAULT 0,
    `Diferencia` INT NOT NULL DEFAULT 0,
    `Estado` VARCHAR(50) NOT NULL DEFAULT 'PENDIENTE',
    `FechaActualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cuad_periodo` (`Periodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
