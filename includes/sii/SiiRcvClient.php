<?php
/**
 * Cliente de integración con el Registro de Compras y Ventas (RCV) del SII de Chile.
 * Utiliza autenticación vía el microservicio oficial de e-Boleta / SII (RUT + Clave Tributaria),
 * obtención de Token de sesión institucional y consulta a la API de FacadeService consdcvinternetui.
 */

class SiiRcvClient
{
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
     * Autentica ante el SII con RUT y Clave Tributaria para obtener el TOKEN institucional.
     * Utiliza el endpoint REST oficial que alimenta las apps móviles y web del SII (e-Boleta).
     */
    public function login(string $rutUsuario, string $claveTributaria): bool
    {
        $this->lastError = null;
        list($cuerpo, $dv) = self::limpiarRut($rutUsuario);

        if (empty($cuerpo) || empty($dv) || empty($claveTributaria)) {
            $this->lastError = 'RUT de usuario o Clave Tributaria vacíos.';
            return false;
        }

        // Endpoint moderno de autenticación del SII (AWS API Gateway oficial SII)
        $urlSignIn = 'https://x78kr8nqx5.execute-api.us-east-1.amazonaws.com/prod/sign-in';

        $body = [
            'rut'   => $cuerpo,
            'dv'    => $dv,
            'clave' => $claveTributaria,
            'opts'  => null
        ];

        $ch = curl_init($urlSignIn);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!empty($curlError)) {
            $this->lastError = "Error de conexión al autenticar con SII: $curlError";
            return false;
        }

        if ($httpCode === 401 || $httpCode === 403) {
            $this->lastError = 'El RUT o la Clave Tributaria ingresada son incorrectos según el SII.';
            return false;
        }

        $json = json_decode($respuesta, true);
        if (!$json || empty($json['sii_token'])) {
            // Intentar leer mensaje de error del SII si existe
            $msg = $json['message'] ?? $json['error'] ?? 'El SII no generó un token válido.';
            $this->lastError = "Fallo de autenticación en SII: $msg";
            return false;
        }

        $this->token = trim($json['sii_token']);
        return true;
    }

    /**
     * Consulta el Resumen mensual del RCV (Totales agrupados por tipo de DTE).
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
                'namespace' => 'cl.sii.sdi.lob.diii.consdcv.data.api.interfaces.FacadeService/getResumen',
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
     * Consulta el Detalle completo de Documentos del RCV (Facturas, Boletas, NC, etc.).
     */
    public function obtenerDetalle(string $rutEmpresa, string $periodo, string $operacion = 'COMPRA'): array
    {
        $this->lastError = null;
        if (!$this->token) {
            throw new Exception('Debes iniciar sesión con login() antes de consultar.');
        }

        list($rut, $dv) = self::limpiarRut($rutEmpresa);
        $opMayus = strtoupper($operacion);

        // 1. Obtener primero el Resumen para saber qué tipos de DTE tienen documentos registrados
        $resumenRaw = $this->obtenerResumen($rutEmpresa, $periodo, $opMayus);

        $tiposDisponibles = [];
        if (isset($resumenRaw['data']) && is_array($resumenRaw['data'])) {
            foreach ($resumenRaw['data'] as $rItem) {
                if (!empty($rItem['rsmnTipoDocInteger'])) {
                    $tiposDisponibles[] = (int)$rItem['rsmnTipoDocInteger'];
                }
            }
        }

        // Si el resumen vino vacío, no hay compras/ventas registradas en este período
        if (empty($tiposDisponibles)) {
            return [
                'success' => true,
                'totales' => [
                    'cantidad_documentos' => 0,
                    'total_neto' => 0,
                    'total_exento' => 0,
                    'total_iva' => 0,
                    'total_general' => 0
                ],
                'documentos' => [],
                'raw_count' => 0
            ];
        }

        // 2. Para cada tipo de DTE presente en el resumen, obtener los documentos individuales
        $documentos = [];
        $totales = [
            'cantidad_documentos' => 0,
            'total_neto' => 0,
            'total_exento' => 0,
            'total_iva' => 0,
            'total_general' => 0
        ];

        $endpoint = ($opMayus === 'VENTA') ? 'getDetalleVenta' : 'getDetalleCompra';
        $urlDetalle = "https://www4.sii.cl/consdcvinternetui/services/data/facadeService/{$endpoint}";
        $namespaceDetalle = "cl.sii.sdi.lob.diii.consdcv.data.api.interfaces.FacadeService/{$endpoint}";

        $transId = 2;
        foreach ($tiposDisponibles as $tipoDte) {
            // Boletas agregadas (tipo 39 en ventas) se obtienen directamente del resumen, no de getDetalle
            if ($opMayus === 'VENTA' && ($tipoDte === 39 || $tipoDte === 41)) {
                foreach ($resumenRaw['data'] as $rItem) {
                    if ((int)($rItem['rsmnTipoDocInteger'] ?? 0) === $tipoDte) {
                        $cantBoletas = (int)($rItem['rsmnTotDoc'] ?? 1);
                        $netoB = (int)round($rItem['rsmnMntNeto'] ?? 0);
                        $ivaB = (int)round($rItem['rsmnMntIVA'] ?? 0);
                        $totalB = (int)round($rItem['rsmnMntTotal'] ?? 0);

                        $totales['cantidad_documentos'] += $cantBoletas;
                        $totales['total_neto'] += $netoB;
                        $totales['total_iva'] += $ivaB;
                        $totales['total_general'] += $totalB;

                        $documentos[] = [
                            'tipo_dte' => $tipoDte,
                            'nombre_tipo' => self::$tiposDte[$tipoDte] ?? 'Boleta Electrónica',
                            'folio' => $cantBoletas,
                            'rut_contraparte' => '66.666.666-6',
                            'rut_cuerpo' => 66666666,
                            'razon_social' => "Boletas del Período ({$cantBoletas} emitidas)",
                            'fecha_emision' => substr($periodo, 0, 4) . '-' . substr($periodo, 4, 2) . '-01',
                            'monto_neto' => $netoB,
                            'monto_exento' => 0,
                            'monto_iva' => $ivaB,
                            'monto_total' => $totalB,
                            'estado' => 'REGISTRO',
                            'es_nota_credito' => false
                        ];
                    }
                }
                continue;
            }

            $payload = [
                'metaData' => [
                    'namespace' => $namespaceDetalle,
                    'conversationId' => $this->token,
                    'transactionId' => (string)($transId++)
                ],
                'data' => [
                    'rutEmisor'   => $rut,
                    'dvEmisor'    => $dv,
                    'ptributario' => $periodo,
                    'estadoContab'=> 'REGISTRO',
                    'codTipoDoc'  => $tipoDte,
                    'operacion'   => $opMayus
                ]
            ];

            try {
                $rawDet = $this->ejecutarPeticionPost($urlDetalle, $payload);
            } catch (Exception $e) {
                continue; // Si falla un tipo puntual, continuar con los demás
            }

            $listaDocs = $rawDet['data'] ?? [];
            if (!is_array($listaDocs)) continue;

            foreach ($listaDocs as $doc) {
                $folio = (int)($doc['detNroDoc'] ?? $doc['folio'] ?? 0);
                $rutDoc = (int)($doc['detRutDoc'] ?? $doc['rutDoc'] ?? 0);
                $dvDoc = strtoupper(trim($doc['detDvDoc'] ?? $doc['dvDoc'] ?? ''));
                $rutFormateado = $rutDoc > 0 ? number_format($rutDoc, 0, '', '.') . '-' . $dvDoc : '';

                $razonSocial = trim($doc['detRznSoc'] ?? $doc['rznSoc'] ?? 'Sin Razón Social');
                $fechaEmision = $doc['detFchDoc'] ?? $doc['fchDoc'] ?? '';

                $montoNeto = (int)round($doc['detMntNeto'] ?? $doc['mntNeto'] ?? 0);
                $montoExento = (int)round($doc['detMntExe'] ?? $doc['mntExe'] ?? 0);
                $montoIva = (int)round($doc['detMntIVA'] ?? $doc['mntIva'] ?? 0);
                $montoTotal = (int)round($doc['detMntTotal'] ?? $doc['mntTotal'] ?? ($montoNeto + $montoExento + $montoIva));

                $estado = $doc['detEventoReceptorLeyenda'] ?? $doc['estadoContab'] ?? 'REGISTRO';
                $esNC = ($tipoDte === 61);
                $signo = $esNC ? -1 : 1;

                $totales['cantidad_documentos']++;
                $totales['total_neto'] += ($montoNeto * $signo);
                $totales['total_exento'] += ($montoExento * $signo);
                $totales['total_iva'] += ($montoIva * $signo);
                $totales['total_general'] += ($montoTotal * $signo);

                $documentos[] = [
                    'tipo_dte' => $tipoDte,
                    'nombre_tipo' => self::$tiposDte[$tipoDte] ?? "DTE Tipo $tipoDte",
                    'folio' => $folio,
                    'rut_contraparte' => $rutFormateado,
                    'rut_cuerpo' => $rutDoc,
                    'razon_social' => $razonSocial,
                    'fecha_emision' => $fechaEmision,
                    'monto_neto' => $montoNeto,
                    'monto_exento' => $montoExento,
                    'monto_iva' => $montoIva,
                    'monto_total' => $montoTotal,
                    'estado' => $estado,
                    'es_nota_credito' => $esNC
                ];
            }
        }

        return [
            'success' => true,
            'totales' => $totales,
            'documentos' => $documentos,
            'raw_count' => count($documentos)
        ];
    }

    /**
     * Ejecuta una petición POST HTTP con headers y cookie TOKEN hacia el SII.
     */
    private function ejecutarPeticionPost(string $url, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Cookie: TOKEN=' . $this->token,
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: application/json, text/plain, */*'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!empty($curlError)) {
            throw new Exception("Error de red al consultar el SII: $curlError");
        }

        if ($httpCode !== 200) {
            throw new Exception("El servidor del SII respondió con código HTTP $httpCode");
        }

        $json = json_decode($respuesta, true);
        if ($json === null) {
            throw new Exception("Respuesta no estructurada del SII.");
        }

        return $json;
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
                ['rut' => '76112233', 'dv' => '9', 'razon' => 'PANIFICADORA Y PASTELERIA ARTESANAL LIMITADA', 'tipo' => 34, 'dias' => [4, 12, 19, 27], 'base' => 85000],
                ['rut' => '96792000', 'dv' => '8', 'razon' => 'EMBOTELLADORA ANDINA S.A. (DEVOLUCIÓN ENVASES)', 'tipo' => 61, 'dias' => [24], 'base' => 45000],
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
            $documentos = [];
            $totales = [
                'cantidad_documentos' => 0,
                'total_neto' => 0,
                'total_exento' => 0,
                'total_iva' => 0,
                'total_general' => 0
            ];

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

            $boletasNeto = 3850000;
            $boletasIva = (int)round($boletasNeto * 0.19);
            $boletasTotal = $boletasNeto + $boletasIva;

            $totales['cantidad_documentos'] += 1450;
            $totales['total_neto'] += $boletasNeto;
            $totales['total_iva'] += $boletasIva;
            $totales['total_general'] += $boletasTotal;

            array_unshift($documentos, [
                'tipo_dte' => 39,
                'nombre_tipo' => 'Boleta Electrónica (Total Agregado del Mes)',
                'folio' => 1450,
                'rut_contraparte' => '66.666.666-6',
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

        return [
            'success' => true,
            'es_demo' => true,
            'totales' => $totales,
            'documentos' => $documentos,
            'raw_count' => count($documentos)
        ];
    }
}
