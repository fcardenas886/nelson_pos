<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/barcode_helper.php';
checkRole(['Administrador', 'Supervisor']);

$pdo = getDB();
$message = '';
$error = '';

// Procesar formulario de guardar o editar producto, o alternar estado activo/inactivo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (($_POST['action'] ?? '') === 'toggle_activo') {
        $id = (int)($_POST['producto_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE productos SET Activo = NOT Activo WHERE ProductoID = ?");
        $stmt->execute([$id]);
        $message = 'Estado del producto actualizado.';
    } else {
        $id = !empty($_POST['producto_id']) ? (int)$_POST['producto_id'] : null;
        $nombre = trim($_POST['nombre'] ?? '');
        $codigo = trim($_POST['codigo_barras'] ?? '') ?: null;
        $categoriaID = (int)($_POST['categoria_id'] ?? 1);
        $precioVenta = (int)($_POST['precio_venta'] ?? 0);
        $costoCompra = (int)($_POST['costo_compra'] ?? 0);
        $stock = (float)($_POST['stock'] ?? 0);
        $stockMinimo = (float)($_POST['stock_minimo'] ?? 0);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $esPesable = isset($_POST['es_pesable']) ? 1 : 0;
        $esPrecioVariable = isset($_POST['es_precio_variable']) ? 1 : 0;
        $codigoPLU = !empty($_POST['codigo_plu']) ? trim($_POST['codigo_plu']) : null;

        $modoBalanza = obtenerModoBalanza($pdo);
        if (!$esPesable) {
            $codigoPLU = null;
        }

        // Validar PLU si es pesable: siempre 4 dígitos numéricos (lo que cabe en la etiqueta EAN-13)
        if ($esPesable && $codigoPLU !== null && !preg_match('/^[0-9]{4}$/', $codigoPLU)) {
            $error = 'El Código PLU debe tener exactamente 4 dígitos numéricos.';
        }

        if (empty($error)) {
            if (!empty($nombre) && ($precioVenta >= 0 || $esPrecioVariable)) {
                try {
                    // Si no se proporcionó código de barras, autogenerar código interno estándar EAN-8
                    if (empty($codigo)) {
                        $codigo = generarSiguienteEAN8($pdo);
                    }

                    if ($id) {
                        $stmt = $pdo->prepare("
                            UPDATE productos
                            SET CodigoBarras = :codigo, Nombre = :nombre, CategoriaID = :cat,
                                PrecioVenta = :precio, CostoCompra = :costo,
                                StockMinimo = :stockmin, Activo = :activo,
                                EsPesable = :espesable, EsPrecioVariable = :esvariable, CodigoPLU = :plu
                            WHERE ProductoID = :id
                        ");
                        $stmt->execute([
                            ':codigo' => $codigo,
                            ':nombre' => $nombre,
                            ':cat' => $categoriaID,
                            ':precio' => $precioVenta,
                            ':costo' => $costoCompra,
                            ':stockmin' => $stockMinimo,
                            ':activo' => $activo,
                            ':espesable' => $esPesable,
                            ':esvariable' => $esPrecioVariable,
                            ':plu' => $codigoPLU,
                            ':id' => $id
                        ]);

                        // Propagar costo actualizado a ventas históricas que registraron costo $0
                        if ($costoCompra > 0) {
                            $stmtSync = $pdo->prepare("
                                UPDATE detalleventas 
                                SET CostoUnitario = :costo 
                                WHERE ProductoID = :pid AND (CostoUnitario = 0 OR CostoUnitario IS NULL)
                            ");
                            $stmtSync->execute([
                                ':costo' => $costoCompra,
                                ':pid' => $id
                            ]);
                        }
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO productos (CodigoBarras, Nombre, CategoriaID, PrecioVenta, CostoCompra, Stock, StockMinimo, Activo, EsPesable, EsPrecioVariable, CodigoPLU)
                            VALUES (:codigo, :nombre, :cat, :precio, :costo, :stock, :stockmin, :activo, :espesable, :esvariable, :plu)
                        ");
                        $stmt->execute([
                            ':codigo' => $codigo,
                            ':nombre' => $nombre,
                            ':cat' => $categoriaID,
                            ':precio' => $precioVenta,
                            ':costo' => $costoCompra,
                            ':stock' => $stock,
                            ':stockmin' => $stockMinimo,
                            ':activo' => $activo,
                            ':espesable' => $esPesable,
                            ':esvariable' => $esPrecioVariable,
                            ':plu' => $codigoPLU
                        ]);
                        $id = (int)$pdo->lastInsertId();

                        // Registrar movimiento de apertura (Stock Inicial) en Kardex
                        if ($id > 0) {
                            $stmtKardexIni = $pdo->prepare("
                                INSERT INTO kardex (ProductoID, TipoTransaccion, CantidadEntrada, CantidadSalida, StockSaldo, ValorUnitario, CostoMedioPonderado, FechaMovimiento)
                                VALUES (:pid, 'INICIAL', :cant_in, 0, :saldo, :costo, :cmp, NOW())
                            ");
                            $stmtKardexIni->execute([
                                ':pid' => $id,
                                ':cant_in' => $stock,
                                ':saldo' => $stock,
                                ':costo' => (int)($costoCompra > 0 ? $costoCompra : $precioVenta),
                                ':cmp' => (int)($costoCompra > 0 ? $costoCompra : $precioVenta)
                            ]);
                        }
                    }

                    // Pesable sin PLU: asignarlo automáticamente a partir del ID ya conocido
                    if ($esPesable && $codigoPLU === null) {
                        $codigoPLU = generarCodigoPLU($pdo, $id, $modoBalanza);
                        $pdo->prepare("UPDATE productos SET CodigoPLU = ? WHERE ProductoID = ?")->execute([$codigoPLU, $id]);
                    }
                    $message = 'Producto guardado exitosamente.' . ($esPesable ? " Código PLU: $codigoPLU." : '');
                } catch (PDOException $e) {
                    $error = ($e->errorInfo[1] ?? 0) == 1062 && str_contains($e->getMessage(), 'CodigoPLU')
                        ? "El Código PLU $codigoPLU ya está asignado a otro producto."
                        : 'Error al guardar el producto: ' . $e->getMessage();
                } catch (Exception $e) {
                    $error = 'Error al guardar el producto: ' . $e->getMessage();
                }
            } else {
                $error = 'Nombre y Precio de Venta son requeridos.';
            }
        }
    }
}

// Búsqueda (por nombre, código principal, códigos alternativos o PLU)
$search = trim($_GET['q'] ?? '');
if (!empty($search)) {
    $stmtP = $pdo->prepare("
        SELECT p.*, c.Nombre AS Categoria,
               (SELECT COUNT(*) FROM productoscodigos pc WHERE pc.ProductoID = p.ProductoID) AS TotalCodigosAlt
        FROM productos p
        LEFT JOIN categorias c ON p.CategoriaID = c.CategoriaID
        WHERE p.Nombre LIKE :q 
           OR p.CodigoBarras = :exact_q 
           OR p.CodigoPLU = :plu_q
           OR EXISTS (SELECT 1 FROM productoscodigos pc WHERE pc.ProductoID = p.ProductoID AND pc.CodigoBarras = :exact_q2)
        ORDER BY p.Nombre ASC
    ");
    $stmtP->execute([
        ':q' => "%$search%",
        ':exact_q' => $search,
        ':plu_q' => $search,
        ':exact_q2' => $search
    ]);
} else {
    $stmtP = $pdo->query("
        SELECT p.*, c.Nombre AS Categoria,
               (SELECT COUNT(*) FROM productoscodigos pc WHERE pc.ProductoID = p.ProductoID) AS TotalCodigosAlt
        FROM productos p
        LEFT JOIN categorias c ON p.CategoriaID = c.CategoriaID
        ORDER BY p.Nombre ASC LIMIT 100
    ");
}
$productos = $stmtP->fetchAll();

// Categorías para el selector
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY Nombre ASC")->fetchAll();

// Tipo de balanza: define el formato del PLU que se muestra y genera en el formulario
$modoBalanzaVista = obtenerModoBalanza($pdo);

include __DIR__ . '/views/productos.view.php';
require_once __DIR__ . '/includes/footer.php';
