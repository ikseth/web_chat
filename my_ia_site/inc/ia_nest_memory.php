<?php
declare(strict_types=1);

/**
 * Acceso centralizado a la memoria de corto plazo (tabla ia_nest_events).
 * Las constantes IA_NEST_DB_DSN, IA_NEST_DB_USER y IA_NEST_DB_PASS deben
 * definirse en un archivo de configuración común (config.php, env, etc.).
 */

/**
 * Crea una conexión PDO hacia la base de datos de memoria.
 *
 * @return PDO
 */
function ia_nest_get_pdo(): PDO {
    // TODO: definir IA_NEST_DB_DSN/USER/PASS en la configuración global.
    $dsn  = defined('IA_NEST_DB_DSN') ? IA_NEST_DB_DSN : 'pgsql:host=localhost;dbname=core_db';
    $user = defined('IA_NEST_DB_USER') ? IA_NEST_DB_USER : 'username';
    $pass = defined('IA_NEST_DB_PASS') ? IA_NEST_DB_PASS : 'password';

    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

/**
 * Obtiene el contexto ordenado de los últimos mensajes user/assistant.
 *
 * @param string $agent    Agente lógico (p.ej. jarvis_webchat).
 * @param string $userId   Identificador del usuario.
 * @param string $threadId Identificador del hilo/conversación.
 * @param int    $limit    Número máximo de eventos a recuperar (por defecto 20).
 *
 * @return array<int, array{id:int, role:string, content:string|null, origin_ts:string|null}>
 */
function ia_nest_fetch_context(string $agent, string $userId, string $threadId, int $limit = 20): array {
    $pdo = ia_nest_get_pdo();
    $sql = <<<SQL
SELECT
    id,
    agent,
    user_id,
    thread_id,
    event_type,
    role,
    content,
    origin_ts
FROM ia_nest_events
WHERE
    agent      = :agent
    AND user_id    = :user_id
    AND thread_id  = :thread_id
    AND event_type = 'message'
    AND role IN ('user', 'assistant')
ORDER BY
    origin_ts ASC,
    CASE role
        WHEN 'user' THEN 0
        WHEN 'assistant' THEN 1
        ELSE 2
    END,
    id ASC
LIMIT :limit
SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':agent', $agent);
    $stmt->bindValue(':user_id', $userId);
    $stmt->bindValue(':thread_id', $threadId);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    return array_map(static function (array $row): array {
        return [
            'id'        => $row['id'],
            'role'      => $row['role'],
            'content'   => $row['content'],
            'origin_ts' => $row['origin_ts'],
        ];
    }, $rows);
}
