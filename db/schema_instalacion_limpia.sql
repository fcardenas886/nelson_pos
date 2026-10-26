-- =============================================================================
-- MINIMARKET POS - ESQUEMA DE INSTALACIÓN LIMPIA (PLANTILLA BASE)
-- Versión: v4.5.3
-- Fecha de generación: 2026-10-09 05:29:24
-- Contiene estructura completa de tablas + datos semilla iniciales
-- (roles, usuario admin inicial y configuraciones por defecto).
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `abonoscredito`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `abonoscredito`;
CREATE TABLE `abonoscredito` (
  `AbonoID` int NOT NULL AUTO_INCREMENT,
  `ClienteID` int NOT NULL,
  `FechaAbono` datetime NOT NULL,
  `Monto` int NOT NULL,
  `MetodoPago` varchar(50) NOT NULL,
  `TurnoID` int DEFAULT NULL,
  `VentaID` int DEFAULT NULL,
  PRIMARY KEY (`AbonoID`),
  KEY `ClienteID` (`ClienteID`),
  KEY `FK_Abonos_Ventas` (`VentaID`),
  CONSTRAINT `abonoscredito_ibfk_1` FOREIGN KEY (`ClienteID`) REFERENCES `clientes` (`ClienteID`),
  CONSTRAINT `FK_Abonos_Ventas` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `ajustesstock`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `ajustesstock`;
CREATE TABLE `ajustesstock` (
  `AjusteStockID` int NOT NULL AUTO_INCREMENT,
  `UsuarioID` int NOT NULL,
  `FechaAjuste` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Motivo` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `Observaciones` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `ProveedorID` int DEFAULT NULL,
  `DocReferencia` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`AjusteStockID`),
  KEY `FK_AjustesStock_Usuarios` (`UsuarioID`),
  KEY `FK_AjustesStock_Proveedores` (`ProveedorID`),
  CONSTRAINT `FK_AjustesStock_Proveedores` FOREIGN KEY (`ProveedorID`) REFERENCES `proveedores` (`ProveedorID`),
  CONSTRAINT `FK_AjustesStock_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `auditoriaeventos`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `auditoriaeventos`;
CREATE TABLE `auditoriaeventos` (
  `AuditoriaID` int NOT NULL AUTO_INCREMENT,
  `Fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UsuarioID` int NOT NULL,
  `TipoEvento` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `Descripcion` varchar(500) COLLATE utf8mb4_spanish_ci NOT NULL,
  `SupervisorID` int DEFAULT NULL,
  `ReferenciaID` int DEFAULT NULL,
  PRIMARY KEY (`AuditoriaID`),
  KEY `FK_AuditoriaEventos_Usuarios` (`UsuarioID`),
  KEY `FK_AuditoriaEventos_Supervisor` (`SupervisorID`),
  CONSTRAINT `FK_AuditoriaEventos_Supervisor` FOREIGN KEY (`SupervisorID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `FK_AuditoriaEventos_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `cajas`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `cajas`;
CREATE TABLE `cajas` (
  `CajaID` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `Activa` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`CajaID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Datos semilla para `cajas`
INSERT INTO `cajas` (`CajaID`, `Nombre`, `Activa`) VALUES (1, 'Caja Principal 01', 1);

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `categorias`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `CategoriaID` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `Descripcion` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`CategoriaID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Datos semilla para `categorias`
INSERT INTO `categorias` (`CategoriaID`, `Nombre`, `Descripcion`) VALUES ('1', 'Abarrotes', 'Arroz, aceites, pastas, salsas y enlatados en general');
INSERT INTO `categorias` (`CategoriaID`, `Nombre`, `Descripcion`) VALUES ('2', 'Lácteos y Fiambrería', 'Leches, quesos, yogures, cecinas, jamones');
INSERT INTO `categorias` (`CategoriaID`, `Nombre`, `Descripcion`) VALUES ('3', 'Bebidas y Alcoholes', 'Bebidas gaseosas, jugos, aguas minerales, cervezas, licores');
INSERT INTO `categorias` (`CategoriaID`, `Nombre`, `Descripcion`) VALUES ('4', 'Panadería y Pastelería', 'Pan fresco del día, pasteles, masas dulces y empanadas');
INSERT INTO `categorias` (`CategoriaID`, `Nombre`, `Descripcion`) VALUES ('5', 'Frutas y Verduras', 'Frutas, verduras frescas, hortalizas y legumbres a granel');
INSERT INTO `categorias` (`CategoriaID`, `Nombre`, `Descripcion`) VALUES ('6', 'Perfumeria', 'Aseo Personal, Cosmeticos');
INSERT INTO `categorias` (`CategoriaID`, `Nombre`, `Descripcion`) VALUES ('7', 'Carniceria', 'Vacuno - Cerdo - Pollo');

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `clientes`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `ClienteID` int NOT NULL AUTO_INCREMENT,
  `RutCuerpo` int DEFAULT NULL,
  `RutDv` char(1) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Nombre` varchar(150) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Cliente Gen├®rico',
  `Telefono` varchar(20) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Email` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `PuntosAcumulados` int NOT NULL DEFAULT '0',
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  `LimiteCredito` int NOT NULL DEFAULT '0',
  `SaldoDeudor` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ClienteID`),
  UNIQUE KEY `RutCuerpo` (`RutCuerpo`),
  CONSTRAINT `CK_Clientes_Puntos` CHECK ((`PuntosAcumulados` >= 0)),
  CONSTRAINT `CK_Clientes_RutDv` CHECK ((`RutDv` in (_utf8mb4'0',_utf8mb4'1',_utf8mb4'2',_utf8mb4'3',_utf8mb4'4',_utf8mb4'5',_utf8mb4'6',_utf8mb4'7',_utf8mb4'8',_utf8mb4'9',_utf8mb4'K',_utf8mb4'k')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `compras`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `compras`;
CREATE TABLE `compras` (
  `CompraID` int NOT NULL AUTO_INCREMENT,
  `ProveedorID` int NOT NULL,
  `NotaPedidoID` int DEFAULT NULL,
  `UsuarioID` int NOT NULL,
  `FechaCompra` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `NumeroDocumento` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `MontoNeto` int NOT NULL DEFAULT '0',
  `MontoIva` int NOT NULL DEFAULT '0',
  `MontoExento` int NOT NULL DEFAULT '0',
  `MontoTotal` int NOT NULL DEFAULT '0',
  `Estado` varchar(15) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Completada',
  PRIMARY KEY (`CompraID`),
  KEY `FK_Compras_Proveedores` (`ProveedorID`),
  KEY `FK_Compras_Usuarios` (`UsuarioID`),
  KEY `FK_Compras_NotaPedido` (`NotaPedidoID`),
  CONSTRAINT `FK_Compras_NotaPedido` FOREIGN KEY (`NotaPedidoID`) REFERENCES `notaspedido` (`NotaPedidoID`),
  CONSTRAINT `FK_Compras_Proveedores` FOREIGN KEY (`ProveedorID`) REFERENCES `proveedores` (`ProveedorID`),
  CONSTRAINT `FK_Compras_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `CK_Compras_Estado` CHECK ((`Estado` in (_utf8mb4'Completada',_utf8mb4'Anulada'))),
  CONSTRAINT `CK_Compras_Exento` CHECK ((`MontoExento` >= 0)),
  CONSTRAINT `CK_Compras_Iva` CHECK ((`MontoIva` >= 0)),
  CONSTRAINT `CK_Compras_Neto` CHECK ((`MontoNeto` >= 0)),
  CONSTRAINT `CK_Compras_Total` CHECK ((`MontoTotal` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `configuraciones`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `configuraciones`;
CREATE TABLE `configuraciones` (
  `Clave` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `Valor` varchar(500) COLLATE utf8mb4_spanish_ci NOT NULL,
  `Descripcion` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`Clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Datos semilla para `configuraciones`
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('ARQUEO_CIEGO', 'true', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('ARQUEO_CIEGO_ACTIVO', 'true', 'Habilita el conteo manual obligatorio al cerrar caja');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('BALANZA_INGRESO_MANUAL', 'precio', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('BALANZA_MODO', 'manual', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('BALANZA_PREFIJO_CONSOLIDADO', '28', 'Prefijo de codigo de barra de vale consolidado (normalmente 28)');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('BALANZA_PREFIJO_INDIVIDUAL', '20', 'Prefijo de codigo de barra de etiqueta individual (normalmente 20)');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('BALANZA_TIPO_EAN', 'plu_peso', 'Formato del codigo de barra individual (plu_peso o plu_precio)');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('CREDITO_INTERNO_ACTIVO', 'true', 'Permite cobro con crédito interno/fiado');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('DTE_PROVEEDOR', 'ninguno', 'Proveedor de emision DTE: haulmer o local');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('HAULMER_AMBIENTE', 'dev', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('HAULMER_API_KEY', '', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('HAULMER_APIKEY', '928e15a2d14d4a6292345f04960f4bd3', 'API Key de Haulmer OpenFactura');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('HAULMER_EMISION_ACTIVA', 'true', 'Activa emision automatica de Boleta');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('HAULMER_ENVIRONMENT', 'dev', 'Ambiente de emision DTE Haulmer');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('HAULMER_FORMATO_PDF', '80mm', 'Formato de impresion de boleta Haulmer (80MM/LETTER)');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('IVA_PORCENTAJE', '19', 'Porcentaje de IVA por defecto');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('LOYALTY_ACTIVO', 'false', 'Activa el Club de Puntos y Fidelización');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('LOYALTY_PORCENTAJE', '1.0', 'Porcentaje de acumulación de puntos sobre el total');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('MINIMARKET_ACTECO', '521900', 'Codigo Acteco SII');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('MINIMARKET_COMUNA', 'Temuco', 'Comuna de origen SII');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('MINIMARKET_DIRECCION', 'Av. Concha y Toro 1234, Puente Alto', 'Dirección de la sucursal');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('MINIMARKET_GIRO', 'Minimarket y Venta de Abarrotes', 'Giro comercial');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('MINIMARKET_LOGO_URL', 'uploads/logo/logo_1788674281.png', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('MINIMARKET_NOMBRE', 'Almacén Don Tito', 'Nombre fantasía del negocio');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('MINIMARKET_RUT', '76.453.920-K', 'RUT del emisor comercial');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('MINIMARKET_TELEFONO', '+56 9 1234 5678', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('OPENFACTURA_AMBIENTE', 'dev', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('OPENFACTURA_API_KEY', '', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('PERMITIR_STOCK_NEGATIVO', 'true', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('POS_DESCUENTO_MAX_PORC', '1', 'Porcentaje (%) maximo de descuento permitido en caja sin clave de supervisor');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('POS_DISENO_GRID', 'tactil', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('POS_LAYOUT_MODO', 'clasico', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('POS_REQ_SUPERVISOR_CANCELAR', 'SI', 'Requerir clave de supervisor para cancelar o vaciar venta en proceso en POS');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('POS_REQ_SUPERVISOR_ELIMINAR_ITEM', 'SI', 'Requerir clave de supervisor para eliminar un producto del carrito en POS');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('SII_AMBIENTE', 'produccion', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('SII_BOT_URL', 'http://127.0.0.1:4123', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('SII_CLAVE', 'fc6543as', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('SII_RUT', '19477654-3', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('SIIBOLETA_AMBIENTE', 'certificacion', 'Ambiente SII del servidor local: certificacion o produccion');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('SIIBOLETA_URL', 'http://localhost/sii-boleta', 'URL base del servidor local sii-boleta');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('SONIDO_LECTOR_RAPIDO', 'NO', 'Activa o desactiva el sonido beep al escanear productos en el lector r??pido');
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('TEMA_COLOR_ACENTO', 'naranja', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('TEMA_MODO', 'dark', NULL);
INSERT INTO `configuraciones` (`Clave`, `Valor`, `Descripcion`) VALUES ('TICKET_PIE_PAGINA', '¡Gracias por su preferencia!', NULL);

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `cotizaciones`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `cotizaciones`;
CREATE TABLE `cotizaciones` (
  `CotizacionID` int NOT NULL AUTO_INCREMENT,
  `ClienteID` int DEFAULT NULL,
  `FechaCotizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Total` int NOT NULL DEFAULT '0',
  `UsuarioID` int NOT NULL,
  `Estado` varchar(20) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Pendiente',
  PRIMARY KEY (`CotizacionID`),
  KEY `FK_Cotizaciones_Clientes` (`ClienteID`),
  KEY `FK_Cotizaciones_Usuarios` (`UsuarioID`),
  CONSTRAINT `FK_Cotizaciones_Clientes` FOREIGN KEY (`ClienteID`) REFERENCES `clientes` (`ClienteID`),
  CONSTRAINT `FK_Cotizaciones_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `cotizacionesdetalle`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `cotizacionesdetalle`;
CREATE TABLE `cotizacionesdetalle` (
  `CotizacionDetalleID` int NOT NULL AUTO_INCREMENT,
  `CotizacionID` int NOT NULL,
  `ProductoID` int NOT NULL,
  `Cantidad` decimal(10,3) NOT NULL,
  `PrecioUnitario` int NOT NULL,
  `Descuento` int NOT NULL DEFAULT '0',
  `Subtotal` int NOT NULL,
  PRIMARY KEY (`CotizacionDetalleID`),
  KEY `FK_CotizDet_Cotizaciones` (`CotizacionID`),
  KEY `FK_CotizDet_Productos` (`ProductoID`),
  CONSTRAINT `FK_CotizDet_Cotizaciones` FOREIGN KEY (`CotizacionID`) REFERENCES `cotizaciones` (`CotizacionID`) ON DELETE CASCADE,
  CONSTRAINT `FK_CotizDet_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `CK_CotizDet_Cant` CHECK ((`Cantidad` > 0)),
  CONSTRAINT `CK_CotizDet_Desc` CHECK ((`Descuento` >= 0)),
  CONSTRAINT `CK_CotizDet_Precio` CHECK ((`PrecioUnitario` >= 0)),
  CONSTRAINT `CK_CotizDet_Sub` CHECK ((`Subtotal` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `detalleajustesstock`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `detalleajustesstock`;
CREATE TABLE `detalleajustesstock` (
  `DetalleAjusteStockID` int NOT NULL AUTO_INCREMENT,
  `AjusteStockID` int NOT NULL,
  `ProductoID` int NOT NULL,
  `Cantidad` decimal(10,3) NOT NULL,
  `CostoUnitario` int NOT NULL DEFAULT 0,
  `TipoMovimiento` varchar(10) COLLATE utf8mb4_spanish_ci NOT NULL,
  PRIMARY KEY (`DetalleAjusteStockID`),
  KEY `FK_DetalleAjustes_Ajustes` (`AjusteStockID`),
  KEY `FK_DetalleAjustes_Productos` (`ProductoID`),
  CONSTRAINT `FK_DetalleAjustes_Ajustes` FOREIGN KEY (`AjusteStockID`) REFERENCES `ajustesstock` (`AjusteStockID`),
  CONSTRAINT `FK_DetalleAjustes_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `CK_DetalleAjustes_Tipo` CHECK ((`TipoMovimiento` in (_cp850'ENTRADA',_cp850'SALIDA')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `detallecompras`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `detallecompras`;
CREATE TABLE `detallecompras` (
  `DetalleCompraID` int NOT NULL AUTO_INCREMENT,
  `CompraID` int NOT NULL,
  `ProductoID` int NOT NULL,
  `Cantidad` decimal(10,3) NOT NULL,
  `CostoUnitario` int NOT NULL,
  `Subtotal` int NOT NULL,
  PRIMARY KEY (`DetalleCompraID`),
  KEY `FK_DetalleCompras_Compras` (`CompraID`),
  KEY `FK_DetalleCompras_Productos` (`ProductoID`),
  CONSTRAINT `FK_DetalleCompras_Compras` FOREIGN KEY (`CompraID`) REFERENCES `compras` (`CompraID`),
  CONSTRAINT `FK_DetalleCompras_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `CK_DetalleCompras_Cant` CHECK ((`Cantidad` > 0)),
  CONSTRAINT `CK_DetalleCompras_Costo` CHECK ((`CostoUnitario` >= 0)),
  CONSTRAINT `CK_DetalleCompras_Subtotal` CHECK ((`Subtotal` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `detalledevoluciones`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `detalledevoluciones`;
CREATE TABLE `detalledevoluciones` (
  `DetalleDevolucionID` int NOT NULL AUTO_INCREMENT,
  `DevolucionID` int NOT NULL,
  `ProductoID` int NOT NULL,
  `Cantidad` decimal(10,3) NOT NULL,
  `MontoDevuelto` int NOT NULL,
  PRIMARY KEY (`DetalleDevolucionID`),
  KEY `FK_DetalleDevoluciones_Devoluciones` (`DevolucionID`),
  KEY `FK_DetalleDevoluciones_Productos` (`ProductoID`),
  CONSTRAINT `FK_DetalleDevoluciones_Devoluciones` FOREIGN KEY (`DevolucionID`) REFERENCES `devoluciones` (`DevolucionID`),
  CONSTRAINT `FK_DetalleDevoluciones_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `CK_DetalleDevoluciones_Cant` CHECK ((`Cantidad` > 0)),
  CONSTRAINT `CK_DetalleDevoluciones_Monto` CHECK ((`MontoDevuelto` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `detallenotaspedido`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `detallenotaspedido`;
CREATE TABLE `detallenotaspedido` (
  `DetalleNotaPedidoID` int NOT NULL AUTO_INCREMENT,
  `NotaPedidoID` int NOT NULL,
  `ProductoID` int NOT NULL,
  `CantidadPedida` decimal(10,3) NOT NULL,
  `CostoAcordado` int NOT NULL,
  PRIMARY KEY (`DetalleNotaPedidoID`),
  KEY `FK_DetalleNotasPedido_NotasPedido` (`NotaPedidoID`),
  KEY `FK_DetalleNotasPedido_Productos` (`ProductoID`),
  CONSTRAINT `FK_DetalleNotasPedido_NotasPedido` FOREIGN KEY (`NotaPedidoID`) REFERENCES `notaspedido` (`NotaPedidoID`),
  CONSTRAINT `FK_DetalleNotasPedido_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `CK_DetalleNotasPedido_Cant` CHECK ((`CantidadPedida` > 0)),
  CONSTRAINT `CK_DetalleNotasPedido_Costo` CHECK ((`CostoAcordado` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `detalleventas`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `detalleventas`;
CREATE TABLE `detalleventas` (
  `DetalleVentaID` int NOT NULL AUTO_INCREMENT,
  `VentaID` int NOT NULL,
  `ProductoID` int NOT NULL,
  `NombreItem` varchar(200) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Cantidad` decimal(10,3) NOT NULL,
  `FactorConversion` decimal(10,3) NOT NULL DEFAULT '1.000',
  `PrecioUnitario` int NOT NULL,
  `CostoUnitario` int NOT NULL DEFAULT '0',
  `Descuento` int NOT NULL DEFAULT '0',
  `EsAfecto` tinyint(1) NOT NULL,
  `Subtotal` int NOT NULL,
  PRIMARY KEY (`DetalleVentaID`),
  KEY `FK_DetalleVentas_Ventas` (`VentaID`),
  KEY `FK_DetalleVentas_Productos` (`ProductoID`),
  CONSTRAINT `FK_DetalleVentas_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `FK_DetalleVentas_Ventas` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`),
  CONSTRAINT `CK_DetalleVentas_Cant` CHECK ((`Cantidad` > 0)),
  CONSTRAINT `CK_DetalleVentas_Desc` CHECK ((`Descuento` >= 0)),
  CONSTRAINT `CK_DetalleVentas_Precio` CHECK ((`PrecioUnitario` >= 0)),
  CONSTRAINT `CK_DetalleVentas_Subtotal` CHECK ((`Subtotal` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `devoluciones`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `devoluciones`;
CREATE TABLE `devoluciones` (
  `DevolucionID` int NOT NULL AUTO_INCREMENT,
  `VentaID` int NOT NULL,
  `UsuarioID` int NOT NULL,
  `FechaDevolucion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `MontoDevuelto` int NOT NULL,
  `MetodoDevolucion` varchar(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `Motivo` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`DevolucionID`),
  KEY `FK_Devoluciones_Usuarios` (`UsuarioID`),
  KEY `IX_Devoluciones_Venta` (`VentaID`),
  CONSTRAINT `FK_Devoluciones_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `FK_Devoluciones_Ventas` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`),
  CONSTRAINT `CK_Devoluciones_Metodo` CHECK ((`MetodoDevolucion` in (_utf8mb4'Efectivo',_utf8mb4'Tarjeta',_utf8mb4'Nota de Credito',_utf8mb4'Cambio de Mercaderia'))),
  CONSTRAINT `CK_Devoluciones_Monto` CHECK ((`MontoDevuelto` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `dte_emitidos`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `dte_emitidos`;
CREATE TABLE `dte_emitidos` (
  `DteID` int NOT NULL AUTO_INCREMENT,
  `VentaID` int DEFAULT NULL,
  `DevolucionID` int DEFAULT NULL,
  `TipoDocumento` varchar(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `Folio` int NOT NULL,
  `TrackID` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `EstadoSii` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT 'Pendiente',
  `Ambiente` varchar(20) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `RutReceptor` varchar(12) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `MontoTotal` int DEFAULT NULL,
  `Mensaje` text COLLATE utf8mb4_spanish_ci,
  `PdfUrl` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `XmlUrl` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `FechaEmision` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`DteID`),
  KEY `VentaID` (`VentaID`),
  KEY `DevolucionID` (`DevolucionID`),
  CONSTRAINT `dte_emitidos_ibfk_1` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`) ON DELETE SET NULL,
  CONSTRAINT `dte_emitidos_ibfk_2` FOREIGN KEY (`DevolucionID`) REFERENCES `devoluciones` (`DevolucionID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `historialprecios`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `historialprecios`;
CREATE TABLE `historialprecios` (
  `HistorialPrecioID` int NOT NULL AUTO_INCREMENT,
  `ProductoID` int NOT NULL,
  `FechaCambio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `PrecioVentaAnterior` int NOT NULL,
  `PrecioVentaNuevo` int NOT NULL,
  `CostoCompraAnterior` int DEFAULT NULL,
  `CostoCompraNuevo` int DEFAULT NULL,
  `UsuarioID` int NOT NULL,
  PRIMARY KEY (`HistorialPrecioID`),
  KEY `FK_HistorialPrecios_Productos` (`ProductoID`),
  KEY `FK_HistorialPrecios_Usuarios` (`UsuarioID`),
  CONSTRAINT `FK_HistorialPrecios_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `FK_HistorialPrecios_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `CK_HistPrecios_CCant` CHECK ((`CostoCompraAnterior` >= 0)),
  CONSTRAINT `CK_HistPrecios_CCnue` CHECK ((`CostoCompraNuevo` >= 0)),
  CONSTRAINT `CK_HistPrecios_PVant` CHECK ((`PrecioVentaAnterior` >= 0)),
  CONSTRAINT `CK_HistPrecios_PVnue` CHECK ((`PrecioVentaNuevo` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `inventariodetalles`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `inventariodetalles`;
CREATE TABLE `inventariodetalles` (
  `InventarioDetalleID` int NOT NULL AUTO_INCREMENT,
  `InventarioID` int NOT NULL,
  `ProductoID` int NOT NULL,
  `CantidadFisica` decimal(10,3) NOT NULL,
  `StockSistemaAlMomento` decimal(10,3) DEFAULT NULL,
  `Diferencia` decimal(10,3) DEFAULT NULL,
  PRIMARY KEY (`InventarioDetalleID`),
  KEY `FK_InvDetalles_Inventarios` (`InventarioID`),
  KEY `FK_InvDetalles_Productos` (`ProductoID`),
  CONSTRAINT `FK_InvDetalles_Inventarios` FOREIGN KEY (`InventarioID`) REFERENCES `inventarios` (`InventarioID`) ON DELETE CASCADE,
  CONSTRAINT `FK_InvDetalles_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `inventarios`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `inventarios`;
CREATE TABLE `inventarios` (
  `InventarioID` int NOT NULL AUTO_INCREMENT,
  `UsuarioID` int NOT NULL,
  `Nombre` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `FechaCreacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `FechaHoraInventario` datetime NOT NULL,
  `Estado` varchar(15) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Borrador',
  `FechaProcesamiento` datetime DEFAULT NULL,
  PRIMARY KEY (`InventarioID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `kardex`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `kardex`;
CREATE TABLE `kardex` (
  `KardexID` int NOT NULL AUTO_INCREMENT,
  `ProductoID` int NOT NULL,
  `FechaMovimiento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `TipoTransaccion` varchar(30) COLLATE utf8mb4_spanish_ci NOT NULL,
  `VentaID` int DEFAULT NULL,
  `CompraID` int DEFAULT NULL,
  `AjusteStockID` int DEFAULT NULL,
  `DevolucionID` int DEFAULT NULL,
  `CantidadEntrada` decimal(10,3) NOT NULL DEFAULT '0.000',
  `CantidadSalida` decimal(10,3) NOT NULL DEFAULT '0.000',
  `StockSaldo` decimal(10,3) NOT NULL,
  `ValorUnitario` int NOT NULL DEFAULT '0',
  `CostoMedioPonderado` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`KardexID`),
  KEY `FK_Kardex_Ventas` (`VentaID`),
  KEY `FK_Kardex_Compras` (`CompraID`),
  KEY `FK_Kardex_Ajustes` (`AjusteStockID`),
  KEY `FK_Kardex_Devoluciones` (`DevolucionID`),
  KEY `IX_Kardex_Producto` (`ProductoID`),
  CONSTRAINT `FK_Kardex_Ajustes` FOREIGN KEY (`AjusteStockID`) REFERENCES `ajustesstock` (`AjusteStockID`),
  CONSTRAINT `FK_Kardex_Compras` FOREIGN KEY (`CompraID`) REFERENCES `compras` (`CompraID`),
  CONSTRAINT `FK_Kardex_Devoluciones` FOREIGN KEY (`DevolucionID`) REFERENCES `devoluciones` (`DevolucionID`),
  CONSTRAINT `FK_Kardex_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`),
  CONSTRAINT `FK_Kardex_Ventas` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`),
  CONSTRAINT `CK_Kardex_Entrada` CHECK ((`CantidadEntrada` >= 0)),
  CONSTRAINT `CK_Kardex_Pmp` CHECK ((`CostoMedioPonderado` >= 0)),
  CONSTRAINT `CK_Kardex_Salida` CHECK ((`CantidadSalida` >= 0)),
  CONSTRAINT `CK_Kardex_Tipo` CHECK ((`TipoTransaccion` in (_utf8mb4'INICIAL',_utf8mb4'VENTA',_utf8mb4'COMPRA',_utf8mb4'AJUSTE_ENTRADA',_utf8mb4'AJUSTE_SALIDA',_utf8mb4'ANULACION_VENTA',_utf8mb4'DEVOLUCION_VENTA'))),
  CONSTRAINT `CK_Kardex_ValUnit` CHECK ((`ValorUnitario` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `movimientoscaja`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `movimientoscaja`;
CREATE TABLE `movimientoscaja` (
  `MovimientoCajaID` int NOT NULL AUTO_INCREMENT,
  `TurnoID` int NOT NULL,
  `TipoMovimiento` varchar(15) NOT NULL,
  `Monto` int NOT NULL,
  `Descripcion` varchar(255) DEFAULT NULL,
  `FechaMovimiento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`MovimientoCajaID`),
  KEY `TurnoID` (`TurnoID`),
  CONSTRAINT `movimientoscaja_ibfk_1` FOREIGN KEY (`TurnoID`) REFERENCES `turnos` (`TurnoID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `notaspedido`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `notaspedido`;
CREATE TABLE `notaspedido` (
  `NotaPedidoID` int NOT NULL AUTO_INCREMENT,
  `ProveedorID` int NOT NULL,
  `UsuarioID` int NOT NULL,
  `FechaPedido` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `NumeroDocumento` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Estado` varchar(15) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Pendiente',
  `NotaPedidoOrigenID` int DEFAULT NULL,
  `Observaciones` varchar(200) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`NotaPedidoID`),
  KEY `FK_NotasPedido_Proveedores` (`ProveedorID`),
  KEY `FK_NotasPedido_Usuarios` (`UsuarioID`),
  KEY `FK_NotasPedido_Origen` (`NotaPedidoOrigenID`),
  CONSTRAINT `FK_NotasPedido_Origen` FOREIGN KEY (`NotaPedidoOrigenID`) REFERENCES `notaspedido` (`NotaPedidoID`),
  CONSTRAINT `FK_NotasPedido_Proveedores` FOREIGN KEY (`ProveedorID`) REFERENCES `proveedores` (`ProveedorID`),
  CONSTRAINT `FK_NotasPedido_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `CK_NotasPedido_Estado` CHECK ((`Estado` in (_utf8mb4'Pendiente',_utf8mb4'Recibida',_utf8mb4'Cancelada')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `pagosventa`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `pagosventa`;
CREATE TABLE `pagosventa` (
  `PagoVentaID` int NOT NULL AUTO_INCREMENT,
  `VentaID` int NOT NULL,
  `MetodoPago` varchar(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `Monto` int NOT NULL,
  PRIMARY KEY (`PagoVentaID`),
  KEY `IX_PagosVenta_Venta` (`VentaID`),
  CONSTRAINT `FK_PagosVenta_Ventas` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`),
  CONSTRAINT `CK_PagosVenta_Metodo` CHECK ((`MetodoPago` in (_utf8mb4'Efectivo',_utf8mb4'Tarjeta Debito',_utf8mb4'Tarjeta Credito',_utf8mb4'Transferencia',_utf8mb4'Puntos',_utf8mb4'Credito Interno',_utf8mb4'Vale Devolucion'))),
  CONSTRAINT `CK_PagosVenta_Monto` CHECK ((`Monto` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `permisos`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `permisos`;
CREATE TABLE `permisos` (
  `PermisoID` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `Descripcion` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`PermisoID`),
  UNIQUE KEY `Nombre` (`Nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `productos`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `ProductoID` int NOT NULL AUTO_INCREMENT,
  `CodigoBarras` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Nombre` varchar(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `Descripcion` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `CategoriaID` int NOT NULL,
  `UnidadMedida` varchar(10) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'UNIDAD',
  `Stock` decimal(10,3) NOT NULL DEFAULT '0.000',
  `StockMinimo` decimal(10,3) NOT NULL DEFAULT '0.000',
  `PrecioVenta` int NOT NULL,
  `CostoCompra` int NOT NULL DEFAULT '0',
  `EsAfecto` tinyint(1) NOT NULL DEFAULT '1',
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  `ControlaStock` tinyint(1) NOT NULL DEFAULT '1',
  `EsPesable` tinyint(1) DEFAULT '0',
  `EsPrecioVariable` tinyint(1) NOT NULL DEFAULT '0',
  `CodigoPLU` varchar(4) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`ProductoID`),
  UNIQUE KEY `CodigoBarras` (`CodigoBarras`),
  UNIQUE KEY `CodigoPLU` (`CodigoPLU`),
  KEY `FK_Productos_Categorias` (`CategoriaID`),
  CONSTRAINT `FK_Productos_Categorias` FOREIGN KEY (`CategoriaID`) REFERENCES `categorias` (`CategoriaID`),
  CONSTRAINT `CK_Productos_CostoCompra` CHECK ((`CostoCompra` >= 0)),
  CONSTRAINT `CK_Productos_PrecioVenta` CHECK ((`PrecioVenta` >= 0)),
  CONSTRAINT `CK_Productos_StockMin` CHECK ((`StockMinimo` >= 0)),
  CONSTRAINT `CK_Productos_Unidad` CHECK ((`UnidadMedida` in (_utf8mb4'UNIDAD',_utf8mb4'KG',_utf8mb4'LITRO')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `productoscodigos`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `productoscodigos`;
CREATE TABLE `productoscodigos` (
  `CodigoID` int NOT NULL AUTO_INCREMENT,
  `ProductoID` int NOT NULL,
  `CodigoBarras` varchar(50) NOT NULL,
  `Descripcion` varchar(100) DEFAULT NULL,
  `Cantidad` decimal(10,3) NOT NULL DEFAULT '1.000',
  `PrecioVenta` int DEFAULT NULL,
  `CreadoEn` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CodigoID`),
  UNIQUE KEY `uq_codigo_barras_adicional` (`CodigoBarras`),
  KEY `fk_prodcodigos_producto` (`ProductoID`),
  CONSTRAINT `fk_prodcodigos_producto` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `promociones`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `promociones`;
CREATE TABLE `promociones` (
  `PromocionID` int NOT NULL AUTO_INCREMENT,
  `ProductoID` int NOT NULL,
  `Tipo` varchar(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `CantidadMinima` decimal(10,3) NOT NULL DEFAULT '1.000',
  `DescuentoPorcentaje` decimal(5,2) NOT NULL DEFAULT '0.00',
  `PrecioOferta` int NOT NULL DEFAULT '0',
  `Activa` tinyint(1) NOT NULL DEFAULT '1',
  `FechaInicio` datetime NOT NULL,
  `FechaFin` datetime NOT NULL,
  PRIMARY KEY (`PromocionID`),
  KEY `FK_Promociones_Productos` (`ProductoID`),
  CONSTRAINT `FK_Promociones_Productos` FOREIGN KEY (`ProductoID`) REFERENCES `productos` (`ProductoID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `proveedores`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `proveedores`;
CREATE TABLE `proveedores` (
  `ProveedorID` int NOT NULL AUTO_INCREMENT,
  `RutCuerpo` int NOT NULL,
  `RutDv` char(1) COLLATE utf8mb4_spanish_ci NOT NULL,
  `RazonSocial` varchar(150) COLLATE utf8mb4_spanish_ci NOT NULL,
  `Giro` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Direccion` varchar(200) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Telefono` varchar(20) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Email` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ProveedorID`),
  UNIQUE KEY `RutCuerpo` (`RutCuerpo`),
  KEY `IX_Proveedores_Rut` (`RutCuerpo`),
  CONSTRAINT `CK_Proveedores_RutDv` CHECK ((`RutDv` in (_utf8mb4'0',_utf8mb4'1',_utf8mb4'2',_utf8mb4'3',_utf8mb4'4',_utf8mb4'5',_utf8mb4'6',_utf8mb4'7',_utf8mb4'8',_utf8mb4'9',_utf8mb4'K',_utf8mb4'k')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `reportesz`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `reportesz`;
CREATE TABLE `reportesz` (
  `ReporteZID` int NOT NULL AUTO_INCREMENT,
  `CajaID` int NOT NULL,
  `NumeroZ` int NOT NULL,
  `UsuarioID` int NOT NULL,
  `FechaEmision` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `FechaInicio` datetime NOT NULL,
  `MontoNeto` int NOT NULL DEFAULT '0',
  `MontoIva` int NOT NULL DEFAULT '0',
  `MontoExento` int NOT NULL DEFAULT '0',
  `MontoTotal` int NOT NULL DEFAULT '0',
  `CantidadBoletas` int NOT NULL DEFAULT '0',
  `CantidadFacturas` int NOT NULL DEFAULT '0',
  `PrimerFolioBoleta` int DEFAULT NULL,
  `UltimoFolioBoleta` int DEFAULT NULL,
  `PrimerFolioFactura` int DEFAULT NULL,
  `UltimoFolioFactura` int DEFAULT NULL,
  PRIMARY KEY (`ReporteZID`),
  UNIQUE KEY `UQ_ReportesZ_Caja_Numero` (`CajaID`,`NumeroZ`),
  KEY `FK_ReportesZ_Usuarios` (`UsuarioID`),
  CONSTRAINT `FK_ReportesZ_Cajas` FOREIGN KEY (`CajaID`) REFERENCES `cajas` (`CajaID`),
  CONSTRAINT `FK_ReportesZ_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `CK_ReportesZ_Boletas` CHECK ((`CantidadBoletas` >= 0)),
  CONSTRAINT `CK_ReportesZ_Facturas` CHECK ((`CantidadFacturas` >= 0)),
  CONSTRAINT `CK_ReportesZ_MontoExento` CHECK ((`MontoExento` >= 0)),
  CONSTRAINT `CK_ReportesZ_MontoIva` CHECK ((`MontoIva` >= 0)),
  CONSTRAINT `CK_ReportesZ_MontoNeto` CHECK ((`MontoNeto` >= 0)),
  CONSTRAINT `CK_ReportesZ_MontoTotal` CHECK ((`MontoTotal` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `roles`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `RolID` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `Descripcion` varchar(255) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`RolID`),
  UNIQUE KEY `Nombre` (`Nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Datos semilla para `roles`
INSERT INTO `roles` (`RolID`, `Nombre`, `Descripcion`, `Activo`) VALUES ('1', 'Administrador', 'Acceso total al sistema, configuraciones, usuarios y reportes', '1');
INSERT INTO `roles` (`RolID`, `Nombre`, `Descripcion`, `Activo`) VALUES ('2', 'Supervisor', 'Gestión de inventarios, anulación de ventas, compras y cambios de precios', '1');
INSERT INTO `roles` (`RolID`, `Nombre`, `Descripcion`, `Activo`) VALUES ('3', 'Cajero', 'Operación del punto de venta, apertura y cierre de caja asignada', '1');

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `rolespermisos`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `rolespermisos`;
CREATE TABLE `rolespermisos` (
  `RolID` int NOT NULL,
  `PermisoID` int NOT NULL,
  PRIMARY KEY (`RolID`,`PermisoID`),
  KEY `FK_RolesPermisos_Permisos` (`PermisoID`),
  CONSTRAINT `FK_RolesPermisos_Permisos` FOREIGN KEY (`PermisoID`) REFERENCES `permisos` (`PermisoID`) ON DELETE CASCADE,
  CONSTRAINT `FK_RolesPermisos_Roles` FOREIGN KEY (`RolID`) REFERENCES `roles` (`RolID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `terminalescaja`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `terminalescaja`;
CREATE TABLE `terminalescaja` (
  `TerminalID` int NOT NULL AUTO_INCREMENT,
  `NombreEquipo` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `CajaID` int NOT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`TerminalID`),
  UNIQUE KEY `NombreEquipo` (`NombreEquipo`),
  KEY `FK_TerminalesCaja_Cajas` (`CajaID`),
  CONSTRAINT `FK_TerminalesCaja_Cajas` FOREIGN KEY (`CajaID`) REFERENCES `cajas` (`CajaID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `turnos`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `turnos`;
CREATE TABLE `turnos` (
  `TurnoID` int NOT NULL AUTO_INCREMENT,
  `CajaID` int NOT NULL,
  `UsuarioID` int NOT NULL,
  `FechaApertura` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `MontoApertura` int NOT NULL,
  `FechaCierre` datetime DEFAULT NULL,
  `MontoCierreEfectivo` int DEFAULT NULL,
  `MontoCierreTarjeta` int DEFAULT NULL,
  `MontoCierreTransferencia` int DEFAULT NULL,
  `MontoCierreSistema` int DEFAULT NULL,
  `Estado` varchar(10) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Abierto',
  `Observaciones` varchar(500) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`TurnoID`),
  KEY `FK_Turnos_Cajas` (`CajaID`),
  KEY `FK_Turnos_Usuarios` (`UsuarioID`),
  CONSTRAINT `FK_Turnos_Cajas` FOREIGN KEY (`CajaID`) REFERENCES `cajas` (`CajaID`),
  CONSTRAINT `FK_Turnos_Usuarios` FOREIGN KEY (`UsuarioID`) REFERENCES `usuarios` (`UsuarioID`),
  CONSTRAINT `CK_Turnos_Estado` CHECK ((`Estado` in (_utf8mb4'Abierto',_utf8mb4'Pendiente',_utf8mb4'Cerrado'))),
  CONSTRAINT `CK_Turnos_MontoApertura` CHECK ((`MontoApertura` >= 0)),
  CONSTRAINT `CK_Turnos_MontoCierreEfectivo` CHECK ((`MontoCierreEfectivo` >= 0)),
  CONSTRAINT `CK_Turnos_MontoCierreSistema` CHECK ((`MontoCierreSistema` >= 0)),
  CONSTRAINT `CK_Turnos_MontoCierreTarjeta` CHECK ((`MontoCierreTarjeta` >= 0)),
  CONSTRAINT `CK_Turnos_MontoCierreTransferencia` CHECK ((`MontoCierreTransferencia` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `usuarios`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `UsuarioID` int NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `RutCuerpo` int NOT NULL,
  `RutDv` char(1) COLLATE utf8mb4_spanish_ci NOT NULL,
  `NombreUsuario` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `PasswordHash` varchar(255) COLLATE utf8mb4_spanish_ci NOT NULL,
  `RolID` int NOT NULL,
  `Activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`UsuarioID`),
  UNIQUE KEY `NombreUsuario` (`NombreUsuario`),
  KEY `FK_Usuarios_Roles` (`RolID`),
  CONSTRAINT `FK_Usuarios_Roles` FOREIGN KEY (`RolID`) REFERENCES `roles` (`RolID`),
  CONSTRAINT `CK_Usuarios_RutDv` CHECK ((`RutDv` in (_cp850'0',_cp850'1',_cp850'2',_cp850'3',_cp850'4',_cp850'5',_cp850'6',_cp850'7',_cp850'8',_cp850'9',_cp850'K',_cp850'k')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Datos semilla para `usuarios`
INSERT INTO `usuarios` (`UsuarioID`, `Nombre`, `RutCuerpo`, `RutDv`, `NombreUsuario`, `PasswordHash`, `RolID`, `Activo`) VALUES ('1', 'Alejandro Silva', '15999888', 'K', 'admin', 'Demo1234!', '1', '1');

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `valescanjes`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `valescanjes`;
CREATE TABLE `valescanjes` (
  `CanjeID` int NOT NULL AUTO_INCREMENT,
  `ValeID` int NOT NULL,
  `VentaID` int NOT NULL,
  `Monto` int NOT NULL,
  `FechaCanje` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CanjeID`),
  KEY `FK_Canjes_Vale` (`ValeID`),
  KEY `FK_Canjes_Venta` (`VentaID`),
  CONSTRAINT `FK_Canjes_Vale` FOREIGN KEY (`ValeID`) REFERENCES `valesdevolucion` (`ValeID`),
  CONSTRAINT `FK_Canjes_Venta` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `valesdevolucion`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `valesdevolucion`;
CREATE TABLE `valesdevolucion` (
  `ValeID` int NOT NULL AUTO_INCREMENT,
  `CodigoVale` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `MontoOriginal` int NOT NULL,
  `MontoDisponible` int NOT NULL,
  `FechaEmision` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Estado` varchar(15) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Activo',
  `VentaID` int DEFAULT NULL,
  PRIMARY KEY (`ValeID`),
  UNIQUE KEY `CodigoVale` (`CodigoVale`),
  KEY `FK_Vales_Ventas` (`VentaID`),
  CONSTRAINT `FK_Vales_Ventas` FOREIGN KEY (`VentaID`) REFERENCES `ventas` (`VentaID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- -----------------------------------------------------------------------------
-- Estructura de tabla: `ventas`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `ventas`;
CREATE TABLE `ventas` (
  `VentaID` int NOT NULL AUTO_INCREMENT,
  `TurnoID` int NOT NULL,
  `ClienteID` int DEFAULT NULL,
  `ReporteZID` int DEFAULT NULL,
  `FechaVenta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `TipoDocumento` varchar(15) COLLATE utf8mb4_spanish_ci NOT NULL,
  `TipoDte` int DEFAULT NULL,
  `Folio` int DEFAULT NULL,
  `MontoNeto` int NOT NULL DEFAULT '0',
  `MontoIva` int NOT NULL DEFAULT '0',
  `MontoExento` int NOT NULL DEFAULT '0',
  `DescuentoGlobal` int NOT NULL DEFAULT '0',
  `MontoTotal` int NOT NULL DEFAULT '0',
  `MontoPagado` int NOT NULL DEFAULT '0',
  `Vuelto` int NOT NULL DEFAULT '0',
  `PuntosGanados` int NOT NULL DEFAULT '0',
  `PuntosCanjeados` int NOT NULL DEFAULT '0',
  `Estado` varchar(15) COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Completada',
  `DtePdfPath` varchar(500) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `DteToken` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`VentaID`),
  UNIQUE KEY `UX_Ventas_FolioDte` (`TipoDte`,`Folio`),
  KEY `FK_Ventas_Turnos` (`TurnoID`),
  KEY `FK_Ventas_Clientes` (`ClienteID`),
  KEY `FK_Ventas_ReportesZ` (`ReporteZID`),
  KEY `IX_Ventas_Fecha` (`FechaVenta`),
  CONSTRAINT `FK_Ventas_Clientes` FOREIGN KEY (`ClienteID`) REFERENCES `clientes` (`ClienteID`),
  CONSTRAINT `FK_Ventas_ReportesZ` FOREIGN KEY (`ReporteZID`) REFERENCES `reportesz` (`ReporteZID`),
  CONSTRAINT `FK_Ventas_Turnos` FOREIGN KEY (`TurnoID`) REFERENCES `turnos` (`TurnoID`),
  CONSTRAINT `CK_Ventas_DescGlobal` CHECK ((`DescuentoGlobal` >= 0)),
  CONSTRAINT `CK_Ventas_Estado` CHECK ((`Estado` in (_utf8mb4'Completada',_utf8mb4'Anulada'))),
  CONSTRAINT `CK_Ventas_MontoExento` CHECK ((`MontoExento` >= 0)),
  CONSTRAINT `CK_Ventas_MontoIva` CHECK ((`MontoIva` >= 0)),
  CONSTRAINT `CK_Ventas_MontoNeto` CHECK ((`MontoNeto` >= 0)),
  CONSTRAINT `CK_Ventas_MontoPagado` CHECK ((`MontoPagado` >= 0)),
  CONSTRAINT `CK_Ventas_MontoTotal` CHECK ((`MontoTotal` >= 0)),
  CONSTRAINT `CK_Ventas_PuntosCanjeados` CHECK ((`PuntosCanjeados` >= 0)),
  CONSTRAINT `CK_Ventas_PuntosGanados` CHECK ((`PuntosGanados` >= 0)),
  CONSTRAINT `CK_Ventas_TipoDoc` CHECK ((`TipoDocumento` in (_utf8mb4'Boleta',_utf8mb4'Factura',_utf8mb4'Sin Documento'))),
  CONSTRAINT `CK_Ventas_Vuelto` CHECK ((`Vuelto` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

SET FOREIGN_KEY_CHECKS = 1;
