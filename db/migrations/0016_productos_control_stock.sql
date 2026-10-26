-- Migración 0016: Agregar opción para controlar stock por producto (ControlaStock)
-- ALGORITHM=INSTANT permite ejecución inmediata sin bloqueo de tabla en MySQL 8.0.
ALTER TABLE `productos` ADD COLUMN `ControlaStock` tinyint(1) NOT NULL DEFAULT 1 AFTER `Activo`;
