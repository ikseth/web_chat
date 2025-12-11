<?php
declare(strict_types=1);

/**
 * db.example.php
 *
 * Ejemplo de configuración de conexión a la base de datos ia_nest.
 * Copia este archivo a `db.php` y rellena los valores reales en cada entorno.
 */

define('IA_NEST_DB_DSN',  'pgsql:host=CORE_POSTGRES_HOST;port=5432;dbname=core_db');
define('IA_NEST_DB_USER', 'core_user');
define('IA_NEST_DB_PASS', 'CAMBIA_ESTA_CONTRASEÑA');

/*
 * NOTAS:
 * - CORE_POSTGRES_HOST debe ser accesible desde el contenedor / servidor PHP.
 *   Por ejemplo:
 *     - Si PHP corre en el mismo host que el contenedor de Postgres, puede ser "127.0.0.1"
 *       con el puerto que tengas mapeado.
 *     - Si estás en Docker y PHP habla con Postgres por nombre de servicio, pon ese nombre.
 */