<?php
/**
 * Cliente de integración con el Registro de Compras y Ventas (RCV) del SII de Chile.
 * Soporta autenticación con RUT y Clave Tributaria, gestión de cookies de sesión,
 * consulta de resúmenes mensuales y detalle de documentos tributarios (compras y ventas).
 */

class SiiRcvClient
{
    private $cookieFile;
    private $token = null;
    private $lastError = null;

    // Catálogo estándar de tipos de Documentos Tributarios Electrónicos (DTE) del SII
    public static $tiposDte = [
        33 => 'Factura Electrónica',
        34 => 'Factura Exenta Electrónica',
        39 => 'Boleta Electrónica',
        41 => 'Boleta Exenta Electrónica',
        43 => 'Liquidación Factura Electrónica',
        46 => 'Factura de Compra Electrónica',
        52 => 'Guía de Despacho Electrónica',
        56 => 'Nota de Débito Electrónica',
        61 => 'Nota de Crédito Electrónica'
    ];

    public function __construct()
    {
        $this->cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sii_rcv_cookie_' . uniqid() . '.txt';
    }

    public function __destruct()
    {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Limpia un RUT eliminando puntos, espacios y devolviendo [cuerpo, dv]
     */
    public static function limpiarRut(string $rutCompleto): array
    {
        $limpio = strtoupper(trim(str_replace(['.', ' ', '-'], '', $rutCompleto)));
        if (strlen($limpio) < 2) {
            return ['', ''];
        }
        $cuerpo = substr($limpio, 0, -1);
        $dv = substr($limpio, -1);
        return [$cuerpo, $dv];
    }

    /**
     * Autentica ante el portal web del SII con RUT y Clave Tributaria.
     */
    public function login(string $rutUsuario, string $claveTributaria): bool
    {
        $this->lastError = null;
        list($cuerpo, $dv) = self::limpiarRut($rutUsuario);

        if (empty($cuerpo) || empty($dv) || empty($claveTributaria)) {
            $this->lastError = 'RUT de usuario o Clave Tributaria vacíos.';
            return false;
        }

        $urlLogin = 'https://zeusr.sii.cl/cgi_AUT2000/autenticacion.cgi';

        $postData = [
            'rut'        => $cuerpo,
            'dv'         => $dv,
            'referencia' => 'https://www.sii.cl',
            'clave'      => $claveTributaria
        ];

        $ch = curl_init($urlLogin);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!empty($curlError)) {
            $this->lastError = "Error de red al conectar con SII: $curlError";
            return false;
        }

        // Detección de errores típicos devueltos por el formulario del SII
        if (strpos($respuesta, 'Clave Incorrecta') !== false || 
            strpos($respuesta, 'Error de autenticacion') !== false ||
            strpos($respuesta, 'El RUT o la clave ingresada no son correctos') !== false) {
            $this->lastError = 'El RUT o la Clave Tributaria ingresada no son válidos ante el SII.';
            return false;
        }

        if (strpos($respuesta, 'Clave Bloqueada') !== false) {
            $this->lastError = 'La Clave Tributaria de este RUT se encuentra bloqueada en el SII. Debes desbloquearla en sii.cl.';
            return false;
        }

        // Extraer cookie TOKEN
        $this->token = $this->extraerTokenDeCookies();

        if (!$this->token) {
            // A veces el SII devuelve el token como cookie persistente o en la URL de redirección
            if (preg_match('/TOKEN=([A-Za-z0-9]+)/i', $respuesta, $m)) {
                $this->token = $m[1];
            } else {
                $this->lastError = 'El SII no generó un TOKEN de sesión válido. Posible interrupción o verificación adicional requerida.';
                return false;
            }
        }

        return true;
    }

    /**
     * Extrae el valor de la cookie 'TOKEN' guardada por cURL en el archivo cookieJar.
     */
    private function extraerTokenDeCookies(): ?string
    {
        if (!file_exists($this->cookieFile)) return null;

        $contenido = file_get_contents($this->cookieFile);
        if (preg_match('/(?:^|\s)TOKEN\s+([A-Za-z0-9_\-]+)/m', $contenido, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Consulta el Resumen del RCV para un período y operación (Totales por tipo de DTE).
     *
     * @param string $rutEmpresa RUT de la empresa sin puntos (ej: '76123456-7')
     * @param string $periodo Año y Mes en formato YYYYMM (ej: '202610')
     * @param string $operacion 'COMPRA' o 'VENTA'
     * @return array
     */
    public function obtenerResumen(string $rutEmpresa, string $periodo, string $operacion = 'COMPRA'): array
    {
        $this->lastError = null;
        if (!$this->token) {
            throw new Exception('Debes iniciar sesión con login() antes de consultar.');
        }

        list($rut, $dv) = self::limpiarRut($rutEmpresa);
        $url = 'https://www4.sii.cl/consdcvinternetui/services/data/facadeService/getResumen';

        $payload = [
            'metaData' => [
                'namespace' => 'cl.sii.sdi.lob.dcv.consdcv.data.facade.interfaces.FacadeServiceRemote',
                'conversationId' => $this->token,
                'transactionId' => '1'
            ],
            'data' => [
                'rutEmisor'   => $rut,
                'dvEmisor'    => $dv,
                'ptributario' => $periodo,
                'estadoContab'=> 'REGISTRO',
                'operacion'   => strtoupper($operacion)
            ]
        ];

        return $this->ejecutarPeticionPost($url, $payload);
    }

    /**
     * Consulta el Detalle de Documentos del RCV (Facturas, Boletas, NC, etc.).
     *
     * @param string $rutEmpresa RUT de la empresa
     * @param string $periodo Formato YYYYMM (ej: '202610')
     * @param string $operacion 'COMPRA' o 'VENTA'
     * @return array
     */
    public function obtenerDetalle(string $rutEmpresa, string $periodo, string $operacion = 'COMPRA'): array
    {
        $this->lastError = null;
        if (!$this->token) {
            throw new Exception('Debes iniciar sesión con login() antes de consultar.');
        }

        list($rut, $dv) = self::limpiarRut($rutEmpresa);
        $endpoint = (strtoupper($operacion) === 'VENTA') ? 'getDetalleVenta' : 'getDetalleCompra';
        $url = "https://www4.sii.cl/consdcvinternetui/services/data/facadeService/{$endpoint}";

        $payload = [
            'metaData' => [
                'namespace' => 'cl.sii.sdi.lob.dcv.consdcv.data.facade.interfaces.FacadeServiceRemote',
                'conversationId' => $this->token,
                'transactionId' => '1'
            ],
            'data' => [
                'rutEmisor'   => $rut,
                'dvEmisor'    => $dv,
                'ptributario' => $periodo,
                'estadoContab'=> 'REGISTRO',
                'operacion'   => strtoupper($operacion)
            ]
        ];

        $raw = $this->ejecutarPeticionPost($url, $payload);
        return $this->normalizarDetalle($raw, $operacion);
    }

    /**
     * Ejecuta una petición POST HTTP con headers y cookies de sesión hacia el SII.
     */
    private function ejecutarPeticionPost(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Cookie: TOKEN=' . $this->token,
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: application/json, text/plain, */*'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!empty($curlError)) {
            throw new Exception("Error de conexión al consultar el RCV: $curlError");
        }

        if ($httpCode !== 200) {
            throw new Exception("El servidor del SII respondió con código HTTP $httpCode");
        }

        $json = json_decode($respuesta, true);
        if ($json === null) {
            throw new Exception("Respuesta no estructurada del SII (posible corte de sesión o página de mantenimiento).");
        }

        return $json;
    }

    /**
     * Normaliza y aplana la respuesta del SII a un formato consistente para la vista.
     */
    private function normalizarDetalle(array $raw, string $operacion): array
    {
        $documentos = [];
        $totales = [
            'cantidad_documentos' => 0,
            'total_neto' => 0,
            'total_exento' => 0,
            'total_iva' => 0,
            'total_general' => 0
        ];

        // En consdcvinternetui la lista suele venir en $raw['data'] o $raw['data']['dcvData']
        $items = [];
        if (isset($raw['data']) && is_array($raw['data'])) {
            if (isset($raw['data']['dcvData']) && is_array($raw['data']['dcvData'])) {
                $items = $raw['data']['dcvData'];
            } elseif (isset($raw['data'][0])) {
                $items = $raw['data'];
            }
        }

        foreach ($items as $doc) {
            $tipoDte = (int)($doc['tipoDoc'] ?? $doc['tipoDte'] ?? 33);
            $nombreTipo = self::$tiposDte[$tipoDte] ?? "DTE Tipo $tipoDte";
            $folio = (int)($doc['folio'] ?? 0);

            // Identificar contraparte
            $rutContraparte = '';
            if (strtoupper($operacion) === 'COMPRA') {
                $rutNum = $doc['rutEmisor'] ?? $doc['rutProveedor'] ?? '';
                $rutDv = $doc['dvEmisor'] ?? $doc['dvProveedor'] ?? '';
            } else {
                $rutNum = $doc['rutReceptor'] ?? $doc['rutCliente'] ?? '';
                $rutDv = $doc['dvReceptor'] ?? $doc['dvCliente'] ?? '';
            }
            if (!empty($rutNum)) {
                $rutContraparte = number_format((int)$rutNum, 0, '', '.') . '-' . strtoupper($rutDv);
            }

            $razonSocial = trim($doc['rznSoc'] ?? $doc['razonSocial'] ?? $doc['rznSocRecep'] ?? 'Sin Razón Social');
            $fechaEmision = $doc['fchDoc'] ?? $doc['fechaEmision'] ?? date('Y-m-d');

            $montoNeto = (int)round($doc['mntNeto'] ?? $doc['montoNeto'] ?? 0);
            $montoExento = (int)round($doc['mntExe'] ?? $doc['montoExento'] ?? 0);
            $montoIva = (int)round($doc['mntIva'] ?? $doc['montoIva'] ?? 0);
            $montoTotal = (int)round($doc['mntTotal'] ?? $doc['montoTotal'] ?? ($montoNeto + $montoExento + $montoIva));

            $estado = $doc['estadoContab'] ?? $doc['estado'] ?? 'REGISTRO';

            // Notas de crédito restan en el resumen fiscal
            $signo = ($tipoDte === 61) ? -1 : 1;

            $totales['cantidad_documentos']++;
            $totales['total_neto'] += ($montoNeto * $signo);
            $totales['total_exento'] += ($montoExento * $signo);
            $totales['total_iva'] += ($montoIva * $signo);
            $totales['total_general'] += ($montoTotal * $signo);

            $documentos[] = [
                'tipo_dte' => $tipoDte,
                'nombre_tipo' => $nombreTipo,
                'folio' => $folio,
                'rut_contraparte' => $rutContraparte,
                'rut_cuerpo' => (int)$rutNum,
                'razon_social' => $razonSocial,
                'fecha_emision' => $fechaEmision,
                'monto_neto' => $montoNeto,
                'monto_exento' => $montoExento,
                'monto_iva' => $montoIva,
                'monto_total' => $montoTotal,
                'estado' => $estado,
                'es_nota_credito' => ($tipoDte === 61)
            ];
        }

        return [
            'success' => true,
            'totales' => $totales,
            'documentos' => $documentos,
            'raw_count' => count($documentos)
        ];
    }

    /**
     * Generador de datos de prueba / demostración realistas para testear la UI y conciliación
     * sin necesidad de ingresar credenciales reales del SII.
     */
    public static function generarDatosDemo(string $periodo, string $operacion = 'COMPRA'): array
    {
        $year = substr($periodo, 0, 4);
        $month = substr($periodo, 4, 2);

        if (strtoupper($operacion) === 'COMPRA') {
            $proveedoresFrecuentes = [
                ['rut' => '96792000', 'dv' => '8', 'razon' => 'EMBOTELLADORA ANDINA S.A.', 'tipo' => 33, 'dias' => [3, 11, 22], 'base' => 345000],
                ['rut' => '96543000', 'dv' => '4', 'razon' => 'COMPAÑIA CERVECERIAS UNIDAS S.A. (CCU)', 'tipo' => 33, 'dias' => [5, 17], 'base' => 482000],
                ['rut' => '96512000', 'dv' => '1', 'razon' => 'SOPROLE S.A.', 'tipo' => 33, 'dias' => [2, 9, 16, 25], 'base' => 198000],
                ['rut' => '76001234', 'dv' => '5', 'razon' => 'DISTRIBUIDORA DE ABARROTES CENTRAL SPA', 'tipo' => 33, 'dias' => [8, 20], 'base' => 620000],
                ['rut' => '76112233', 'dv' => '9', 'razon' => 'PANIFICADORA Y PASTELERIA ARTESANAL LIMITADA', 'tipo' => 34, 'dias' => [4, 12, 19, 27], 'base' => 85000], // Exenta
                ['rut' => '96792000', 'dv' => '8', 'razon' => 'EMBOTELLADORA ANDINA S.A. (DEVOLUCIÓN ENVASES)', 'tipo' => 61, 'dias' => [24], 'base' => 45000], // Nota de Crédito
            ];

            $documentos = [];
            $totales = [
                'cantidad_documentos' => 0,
                'total_neto' => 0,
                'total_exento' => 0,
                'total_iva' => 0,
                'total_general' => 0
            ];

            $folioBase = 142000;
            foreach ($proveedoresFrecuentes as $p) {
                foreach ($p['dias'] as $dia) {
                    $folio = $folioBase++;
                    $fecha = sprintf("%04d-%02d-%02d", $year, $month, min($dia, 28));
                    $esExento = ($p['tipo'] === 34);
                    $esNC = ($p['tipo'] === 61);

                    $variacion = rand(-5000, 15000);
                    $montoNeto = $esExento ? 0 : max(10000, $p['base'] + $variacion);
                    $montoExento = $esExento ? max(10000, $p['base'] + $variacion) : 0;
                    $montoIva = (int)round($montoNeto * 0.19);
                    $montoTotal = $montoNeto + $montoExento + $montoIva;

                    $signo = $esNC ? -1 : 1;
                    $totales['cantidad_documentos']++;
                    $totales['total_neto'] += ($montoNeto * $signo);
                    $totales['total_exento'] += ($montoExento * $signo);
                    $totales['total_iva'] += ($montoIva * $signo);
                    $totales['total_general'] += ($montoTotal * $signo);

                    $documentos[] = [
                        'tipo_dte' => $p['tipo'],
                        'nombre_tipo' => self::$tiposDte[$p['tipo']] ?? 'Factura',
                        'folio' => $folio,
                        'rut_contraparte' => number_format((int)$p['rut'], 0, '', '.') . '-' . $p['dv'],
                        'rut_cuerpo' => (int)$p['rut'],
                        'razon_social' => $p['razon'],
                        'fecha_emision' => $fecha,
                        'monto_neto' => $montoNeto,
                        'monto_exento' => $montoExento,
                        'monto_iva' => $montoIva,
                        'monto_total' => $montoTotal,
                        'estado' => 'REGISTRO',
                        'es_nota_credito' => $esNC
                    ];
                }
            }
        } else {
            // Ventas Demo
            $documentos = [];
            $totales = [
                'cantidad_documentos' => 0,
                'total_neto' => 0,
                'total_exento' => 0,
                'total_iva' => 0,
                'total_general' => 0
            ];

            // Generar ventas agregadas simuladas (Facturas emitidas y resumen de Boletas del mes)
            $clientes = [
                ['rut' => '76234567', 'dv' => '1', 'razon' => 'CASINO Y RESTAURANT EL BUEN SABOR LTDA', 'folio' => 501, 'dia' => 4, 'neto' => 125000],
                ['rut' => '77345678', 'dv' => '2', 'razon' => 'COLEGIO SAN AGUSTIN DE SANTIAGO', 'folio' => 502, 'dia' => 12, 'neto' => 240000],
                ['rut' => '76890123', 'dv' => '4', 'razon' => 'CONSTRUCTORA E INMOBILIARIA CORDILLERA SPA', 'folio' => 503, 'dia' => 18, 'neto' => 88000],
                ['rut' => '76234567', 'dv' => '1', 'razon' => 'CASINO Y RESTAURANT EL BUEN SABOR LTDA (DESCUENTO)', 'folio' => 42, 'tipo' => 61, 'dia' => 25, 'neto' => 15000],
            ];

            foreach ($clientes as $c) {
                $tipo = $c['tipo'] ?? 33;
                $esNC = ($tipo === 61);
                $neto = $c['neto'];
                $iva = (int)round($neto * 0.19);
                $total = $neto + $iva;
                $fecha = sprintf("%04d-%02d-%02d", $year, $month, $c['dia']);

                $signo = $esNC ? -1 : 1;
                $totales['cantidad_documentos']++;
                $totales['total_neto'] += ($neto * $signo);
                $totales['total_exento'] += 0;
                $totales['total_iva'] += ($iva * $signo);
                $totales['total_general'] += ($total * $signo);

                $documentos[] = [
                    'tipo_dte' => $tipo,
                    'nombre_tipo' => self::$tiposDte[$tipo] ?? 'Factura',
                    'folio' => $c['folio'],
                    'rut_contraparte' => number_format((int)$c['rut'], 0, '', '.') . '-' . $c['dv'],
                    'rut_cuerpo' => (int)$c['rut'],
                    'razon_social' => $c['razon'],
                    'fecha_emision' => $fecha,
                    'monto_neto' => $neto,
                    'monto_exento' => 0,
                    'monto_iva' => $iva,
                    'monto_total' => $total,
                    'estado' => 'REGISTRO',
                    'es_nota_credito' => $esNC
                ];
            }

            // Agregar registro agregado de Boletas Electrónicas (Tipo 39)
            $boletasNeto = 3850000;
            $boletasIva = (int)round($boletasNeto * 0.19);
            $boletasTotal = $boletasNeto + $boletasIva;

            $totales['cantidad_documentos'] += 1450; // Cantidad de boletas emitidas
            $totales['total_neto'] += $boletasNeto;
            $totales['total_iva'] += $boletasIva;
            $totales['total_general'] += $boletasTotal;

            array_unshift($documentos, [
                'tipo_dte' => 39,
                'nombre_tipo' => 'Boleta Electrónica (Total Agregado del Mes)',
                'folio' => 1450,
                'rut_contraparte' => '66666666-6',
                'rut_cuerpo' => 66666666,
                'razon_social' => 'Consumidores Finales (Ventas Boletas POS)',
                'fecha_emision' => sprintf("%04d-%02d-01", $year, $month),
                'monto_neto' => $boletasNeto,
                'monto_exento' => 0,
                'monto_iva' => $boletasIva,
                'monto_total' => $boletasTotal,
                'estado' => 'REGISTRO',
                'es_nota_credito' => false
            ]);
        }

        // Ordenar cronológicamente descendente
        usort($documentos, function($a, $b) {
            return strcmp($b['fecha_emision'], $a['fecha_emision']);
        });

        return [
            'success' => true,
            'es_demo' => true,
            'totales' => $totales,
            'documentos' => $documentos,
            'raw_count' => count($documentos)
        ];
    }
}
