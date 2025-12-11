<?php
// proxy_stream.php — Webchat IA (Streaming) con logging robusto
// Entorno objetivo: openSUSE Tumbleweed (Apache wwwrun:www)
// Requisitos externos:
//   - inc/auth.php  -> auth_user(), auth_role(), require_login(), load_users()
//   - prompt_helpers.php -> construir_prompt_final(), cargar_json_config(), calcular_edad(), obtener_mpaa_para_edad()

// ---- Diagnóstico en desarrollo (puedes desactivarlo en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Includes para acceso a memoria ia_nest y configuración BD
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../inc/ia_nest_memory.php';

require_once __DIR__ . '/../../../inc/auth.php';
require_once __DIR__ . '/prompt_helpers.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// --------------------- Utilidades locales ---------------------
/**
 * Devuelve la ruta absoluta al docroot del proyecto (/srv/www/htdocs/my_ia_site).
 */
function root_dir(): string {
    // Estamos en: /srv/www/htdocs/my_ia_site/modules/webchat_stream/inc
    return dirname(__DIR__, 3);
}

function ia_nest_send_event(array $event): void
{
    if (!isset($event['origin_ts'])) {
        // Mejor en UTC para tenerlo canónico
        $dt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $event['origin_ts'] = $dt->format('Y-m-d\TH:i:s.uP'); 
        // Ej: 2025-12-05T21:15:43.123456+00:00
    }

    $url = 'http://192.168.50.73:5678/webhook/ia-nest/events';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 2,
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        error_log('ia_nest_send_event error: ' . curl_error($ch));
    }
    curl_close($ch);
}


/**
 * Escribe una línea (string) en un fichero de log (creando el directorio si falta),
 * con FILE_APPEND + LOCK_EX. Devuelve true si ok, false si falla.
 * En caso de error, registra motivo en error_log.
 */
function safe_log_append(string $logFile, string $line): bool {
    $logDir = dirname($logFile);

    // Crear directorio si hiciera falta
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0770, true);
        @chmod($logDir, 0770);
    }

    // Diagnóstico de directorio
    if (!is_dir($logDir)) {
        error_log("webchat_stream: NO existe el directorio de logs: $logDir");
        return false;
    }
    if (!is_writable($logDir)) {
        $euid = function_exists('posix_geteuid') ? posix_geteuid() : get_current_user();
        error_log("webchat_stream: Directorio de logs NO escribible: $logDir (euid: $euid)");
        return false;
    }

    // Si el archivo existe pero no es escribible, lo dejamos en el log del sistema
    if (file_exists($logFile) && !is_writable($logFile)) {
        clearstatcache(true, $logFile);
        $perms = substr(sprintf('%o', @fileperms($logFile)), -4);
        $owner = function_exists('posix_getpwuid') ? (posix_getpwuid(@fileowner($logFile))['name'] ?? 'unknown') : 'unknown';
        $group = function_exists('posix_getgrgid') ? (posix_getgrgid(@filegroup($logFile))['name'] ?? 'unknown') : 'unknown';
        error_log("webchat_stream: Log existe pero NO escribible: $logFile (perms:$perms owner:$owner group:$group)");
        // Intento de normalización mínima:
        @chmod($logFile, 0660);
    }

    $ok = @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    if ($ok === false) {
        $err = error_get_last();
        error_log("webchat_stream: file_put_contents FALLÓ en $logFile. Motivo: " . ($err['message'] ?? 'desconocido'));
        return false;
    }
    return true;
}

/**
 * Extrae un valor desde $input (JSON) y, si no está, desde $_POST (compat).
 */
function in_req(array $input, string $key, $default = null) {
    if (array_key_exists($key, $input)) return $input[$key];
    if (isset($_POST[$key])) return $_POST[$key];
    return $default;
}

// --------------------- Entrada y validación ---------------------
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = [];

if (!isset($input['text']) || !is_string($input['text'])) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Input inválido";
    exit;
}

// Extrae identidad y rol
$user = auth_user();
$user_data = load_users()[$user] ?? [];
$role = auth_role() ?: ($_SESSION['role'] ?? 'user');

// Roles & expertos permitidos
$roles_cfg_path = root_dir() . '/config/roles.json';
$roles = cargar_json_config($roles_cfg_path);
$rol_info = null;
if (is_array($roles)) {
    foreach ($roles as $r) {
        if (isset($r['id']) && $r['id'] === $role) { $rol_info = $r; break; }
    }
}

// Parámetros de conversación
$experto = in_req($input, 'experto', 'general');
if ($rol_info && isset($rol_info['expertos_permitidos']) && is_array($rol_info['expertos_permitidos'])) {
    if (!in_array($experto, $rol_info['expertos_permitidos'], true)) {
        $experto = 'general';
    }
}
$idioma   = in_req($input, 'idioma', 'es');
$pregunta = (string)$input['text'];

// Construir prompt final
$prompt_final = construir_prompt_final($user, $user_data, $role, $experto, $idioma, $pregunta);

// --------------------- Filtro de contenido sensible ---------------------
$cats_path = root_dir() . '/config/categorias_sensibles.json';
$categorias = cargar_json_config($cats_path);
$palabras_prohibidas = [];
if (is_array($categorias) && isset($categorias['contenido_prohibido']) && is_array($categorias['contenido_prohibido'])) {
    foreach ($categorias['contenido_prohibido'] as $grupo) {
        if (is_array($grupo)) $palabras_prohibidas = array_merge($palabras_prohibidas, $grupo);
    }
}

// Revisión básica (case-insensitive)
foreach ($palabras_prohibidas as $prohibida) {
    if (is_string($prohibida) && $prohibida !== '' && stripos($pregunta, $prohibida) !== false) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Lo siento, no puedo responder sobre ese contenido porque no es apto para tu edad.";
        exit;
    }
}

// --------------------- Respuesta en streaming ---------------------
// HEADERS antes de cualquier output
header('Content-Type: text/plain; charset=utf-8');
header('X-Accel-Buffering: no');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
@ini_set('implicit_flush', 1);

// Intenta vaciar todos los buffers de salida previos
while (ob_get_level() > 0) { @ob_end_flush(); }
ob_implicit_flush(1);

// Variables de logging
$fecha = date('Y-m-d H:i:s');
$full_response = '';
$filters = []; // (placeholder) añade aquí etiquetas si aplicas filtros en tiempo real

// cURL al endpoint de streaming (según tu MSC: 127.0.0.1:8302)
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "http://127.0.0.1:8302/stream_prompt",
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => json_encode(['text' => $prompt_final], JSON_UNESCAPED_UNICODE),
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_WRITEFUNCTION => function($ch, $data) use (&$full_response) {
        // Emitimos tal cual
        echo $data;
        @ob_flush();
        flush();
        $full_response .= $data;
        return strlen($data);
    },
    CURLOPT_HEADER => false,
    CURLOPT_TIMEOUT => 90,
]);
curl_exec($ch);
curl_close($ch);

// Fallback si el modelo no devolvió nada significativo
if (trim($full_response) === '') {
    $full_response = "Lo siento, no puedo responder sobre ese contenido porque no es apto para tu edad.";
}

// --------------------- Construcción de log ---------------------
$edad = isset($user_data['fecha_nacimiento']) ? calcular_edad($user_data['fecha_nacimiento']) : 99;

$mpaa_path = root_dir() . '/config/mpaa_levels.json';
$mpaa_levels = cargar_json_config($mpaa_path);
$nivel_mpaa = is_array($mpaa_levels) ? (obtener_mpaa_para_edad($edad, $mpaa_levels) ?? ['id' => 'desconocido', 'directrices' => []]) : ['id' => 'desconocido', 'directrices' => []];

$log = [
    "fecha"    => $fecha,
    "usuario"  => $user,
    "rol"      => $role,
    "experto"  => $experto,
    "idioma"   => $idioma,
    "prompt"   => $prompt_final,
    "pregunta" => $pregunta,
    "respuesta"=> $full_response,
    "filtros"  => $filters,
    "mpaa"     => [
        "edad"         => $edad,
        "nivel"        => $nivel_mpaa['id'] ?? 'desconocido',
        "directrices"  => $nivel_mpaa['directrices'] ?? [],
    ]
];

// Identificador sencillo de hilo por ahora: usuario + día
$threadId = 'webchat_' . date('Ymd') . '_' . $user;

// Tags básicos; luego podemos enriquecerlos (mpaa, experto, etc.)
$baseTags = ['jarvis', 'webchat'];

// Evento: mensaje del usuario
ia_nest_send_event([
    'source'          => 'webchat_backend',
    'agent'           => 'jarvis_webchat',
    'user_id'         => $user,
    'channel'         => 'webchat',
    'thread_id'       => $threadId,
    'event_type'      => 'message',
    'role'            => 'user',
    'importance'      => 0,
    'tags'            => $baseTags,
    'content'         => $pregunta,
    'parent_event_id' => null,
    'payload'         => [
        'rol'      => $role,
        'experto'  => $experto,
        'idioma'   => $idioma,
        'mpaa'     => [
            'edad'  => $edad,
            'nivel' => $nivel_mpaa['id'] ?? 'desconocido',
        ],
    ],
]);

// Evento: respuesta del asistente
ia_nest_send_event([
    'source'          => 'webchat_backend',
    'agent'           => 'jarvis_webchat',
    'user_id'         => $user,
    'channel'         => 'webchat',
    'thread_id'       => $threadId,
    'event_type'      => 'message',
    'role'            => 'assistant',
    'importance'      => 0,
    'tags'            => $baseTags,
    'content'         => $full_response,
    'parent_event_id' => null, // más adelante podemos enlazar con el id del evento user
    'payload'         => [
        'rol'      => $role,
        'experto'  => $experto,
        'idioma'   => $idioma,
        'mpaa'     => [
            'edad'  => $edad,
            'nivel' => $nivel_mpaa['id'] ?? 'desconocido',
        ],
    ],
]);

// --------------------- Escritura segura de log ---------------------
$logDir  = root_dir() . '/logs';
$logFile = $logDir . '/webchat_history-' . date('Ymd') . '.log';

$line = json_encode($log, JSON_UNESCAPED_UNICODE);
if ($line === false) {
    // En caso de caracteres no UTF-8 muy raros
    $line = json_encode($log, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
safe_log_append($logFile, $line);

// Fin del script
exit;
