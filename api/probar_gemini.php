<?php
/**
 * Diagnóstico y prueba de conectividad con Google Gemini API
 * Endpoint: api/probar_gemini.php
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado. Debes iniciar sesión.']);
    exit;
}

$user = currentUser();
if (!in_array($user['rol'] ?? '', ['Administrador', 'Supervisor'], true)) {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado. Se requiere rol de Administrador o Supervisor.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];
$apiKey = trim($data['api_key'] ?? $_POST['api_key'] ?? $GLOBALS['test_key'] ?? '');

// Si no se envió en el body, consultar la guardada en la base de datos
if (empty($apiKey)) {
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT Valor FROM configuraciones WHERE Clave = 'GEMINI_API_KEY'");
        $apiKey = trim($stmt->fetchColumn() ?: '');
    } catch (Exception $e) {
        $apiKey = '';
    }
}

if (empty($apiKey)) {
    echo json_encode([
        'success' => false,
        'estado' => 'vacia',
        'mensaje' => 'No hay ninguna clave configurada. El Asistente está operando con el Motor Local Integrado (Offline, 100% disponible).'
    ]);
    exit;
}

// Validación de formato Google AI Studio (claves actuales AQ. o clásicas AIza)
if ((!str_starts_with($apiKey, 'AQ.') && !str_starts_with($apiKey, 'AIza')) || strlen($apiKey) < 30) {
    $prefijo = htmlspecialchars(substr($apiKey, 0, 7));
    $longitud = strlen($apiKey);
    echo json_encode([
        'success' => false,
        'estado' => 'formato_invalido',
        'mensaje' => "Formato no reconocido: La clave ingresada comienza con '{$prefijo}...' ({$longitud} caracteres). Las API Keys de Google AI Studio comienzan con 'AQ.' (claves Auth actuales) o 'AIza' (claves estándar).",
        'ayuda' => 'Puedes obtener una API Key gratuita directamente en https://aistudio.google.com/app/apikey'
    ]);
    exit;
}

// Probar conexión real con los modelos activos de Google AI Studio
$modelos = ['gemini-3.5-flash-lite', 'gemini-3.5-flash', 'gemini-3.7-flash', 'gemini-flash-latest'];
$modeloUsado = null;
$errorGoogle = null;
$httpCode = 0;

$bodyPrueba = json_encode([
    'contents' => [
        ['role' => 'user', 'parts' => [['text' => 'Responde solo: OK']]]
    ],
    'generationConfig' => [
        'maxOutputTokens' => 5
    ]
]);

foreach ($modelos as $modelo) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key=" . urlencode($apiKey);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyPrueba);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $res) {
        $json = json_decode($res, true);
        if (!empty($json['candidates'][0]['content']['parts'][0]['text'])) {
            $modeloUsado = $modelo;
            break;
        }
    } elseif ($httpCode === 400 || $httpCode === 403) {
        $json = json_decode($res, true);
        $errorGoogle = $json['error']['message'] ?? 'API_KEY_INVALID';
        break;
    }
}

if ($modeloUsado !== null) {
    echo json_encode([
        'success' => true,
        'estado' => 'conectado',
        'modelo' => $modeloUsado,
        'mensaje' => "¡Conexión exitosa con Google Gemini! El modelo '{$modeloUsado}' respondió correctamente. Tu clave está lista y activa."
    ]);
} else {
    $detalle = $errorGoogle ?: ($httpCode ? "Código HTTP $httpCode" : "Error de red/timeout");
    echo json_encode([
        'success' => false,
        'estado' => 'error_conexion',
        'mensaje' => "Google rechazó la solicitud ({$detalle}). Verifica que la clave esté habilitada en Google AI Studio.",
        'ayuda' => 'Puedes generar una clave nueva en https://aistudio.google.com/app/apikey'
    ]);
}
