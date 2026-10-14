-- Migración 0014: Sincronizar costo en ventas históricas que quedaron con costo 0 asignándoles el CostoCompra actual del catálogo
UPDATE detalleventas dv
JOIN productos p ON dv.ProductoID = p.ProductoID
SET dv.CostoUnitario = p.CostoCompra
WHERE (dv.CostoUnitario = 0 OR dv.CostoUnitario IS NULL)
  AND p.CostoCompra > 0;
