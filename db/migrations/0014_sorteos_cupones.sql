-- Migración 0014: Sistema de Sorteos y Cupones (Virtuales y Físicos)
-- Permite emitir cupones en ventas y realizar sorteos con tómbola virtual interactiva.

CREATE TABLE IF NOT EXISTS sorteos (
    SorteoID INT AUTO_INCREMENT PRIMARY KEY,
    Titulo VARCHAR(150) NOT NULL,
    Descripcion TEXT NULL,
    Premios TEXT NOT NULL COMMENT 'JSON array o listado de premios a sortear',
    TipoRegla ENUM('monto_cada_x', 'por_venta') NOT NULL DEFAULT 'monto_cada_x' COMMENT 'monto_cada_x: 1 cupón cada $X; por_venta: 1 cupón por cada venta',
    MontoMinimoCupon DECIMAL(10,2) NOT NULL DEFAULT 5000.00 COMMENT 'Monto base en CLP para generar cupones',
    RequiereCliente TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 si exige cliente registrado, 0 si acepta público general',
    ImprimirCuponEnTicket TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 para imprimir talón recortable en ticket térmico',
    FechaInicio DATE NOT NULL,
    FechaFin DATE NOT NULL,
    Estado ENUM('Activo', 'Pausado', 'Finalizado') NOT NULL DEFAULT 'Activo',
    CreadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sorteos_estado (Estado, FechaInicio, FechaFin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sorteos_cupones (
    CuponID INT AUTO_INCREMENT PRIMARY KEY,
    SorteoID INT NOT NULL,
    VentaID INT NULL,
    NumeroCupon INT NOT NULL COMMENT 'Número correlativo visible del cupón',
    CodigoCupon VARCHAR(30) NOT NULL UNIQUE COMMENT 'Código alfanumérico identificador ej. SOR-0001',
    ClienteID INT NULL,
    NombreParticipante VARCHAR(150) NULL,
    TelefonoParticipante VARCHAR(50) NULL,
    MontoVenta DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    EsGanador TINYINT(1) NOT NULL DEFAULT 0,
    PremioGanado VARCHAR(150) NULL,
    LugarPremio INT NULL,
    FechaGanador DATETIME NULL,
    NotasGanador TEXT NULL,
    EmitidoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sorteos_cupones_sorteo FOREIGN KEY (SorteoID) REFERENCES sorteos(SorteoID) ON DELETE CASCADE,
    CONSTRAINT fk_sorteos_cupones_venta FOREIGN KEY (VentaID) REFERENCES ventas(VentaID) ON DELETE SET NULL,
    INDEX idx_cupon_sorteo_num (SorteoID, NumeroCupon),
    INDEX idx_cupon_ganador (SorteoID, EsGanador),
    INDEX idx_cupon_codigo (CodigoCupon)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
