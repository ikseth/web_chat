# Flujo de eventos Jarvis Webchat ↔ n8n ↔ PostgreSQL

Este documento describe el flujo conceptual de eventos para Jarvis Webchat,
utilizando `ia_nest_events` como memoria de corto plazo.

## 1. Componentes implicados

1. **Webchat (frontend)**  
   - Vive en el navegador del usuario (HTML/JS).
   - Solo habla con el **backend webchat**.

2. **Backend Jarvis Webchat**  
   - Expone la API para el frontend (`/api/jarvis/chat`, etc.).
   - Normaliza `user_id`, `thread_id`, `agent`, etc.
   - Envía eventos al **webhook de n8n** (escritura de memoria).
   - Lee directamente de PostgreSQL (lectura de memoria).
   - Llama al modelo de IA (p.ej. `http://127.0.0.1:8302/stream_prompt`).

3. **n8n**  
   - Expone un **webhook HTTP** que recibe los eventos.
   - Inserta las filas en `ia_nest_events` mediante un nodo PostgreSQL.
   - Puede ramificar el flujo (notificaciones, métricas, etc.) en el futuro.

4. **PostgreSQL (`core_db`)**  
   - Contiene la tabla `ia_nest_events`.
   - Es la **fuente de verdad** de la memoria de corto plazo.

## 2. Esquema lógico de conversación

Una conversación se identifica típicamente por:

- `agent` + `user_id` + `thread_id`
- Ordenada por `created_at` o `id`.

Campos relevantes (resumen):

- `source`: origen técnico del evento (p.ej. `webchat_backend`, `n8n`, `ha_webhook`).
- `agent`: agente lógico de IA (p.ej. `jarvis_webchat`, `minion_qwen`).
- `user_id`: identificador lógico del usuario.
- `channel`: canal de origen (`webchat`, `ha_voice`, `telegram`, ...).
- `thread_id`: identificador de conversación / hilo.
- `event_type`: tipo de evento (`message`, `error`, `system`, ...).
- `role`: rol conversacional (`user`, `assistant`, `system`).
- `importance`: prioridad/importancia (0 = normal, 1 = relevante, 2 = muy relevante).
- `tags`: etiquetas libres (`text[]`).
- `content`: texto principal del evento.
- `payload`: `jsonb` con metadatos, IDs internos, métricas, etc.

## 3. Flujo de ESCRITURA (nuevo mensaje en el webchat)

### 3.1. Frontend → Backend Jarvis

1. El usuario escribe un mensaje y pulsa “Enviar”.
2. El frontend hace un `POST` al backend, por ejemplo:
   - `POST /api/jarvis/chat`
   - Body: `{ user_id, thread_id, content, ... }`

3. El backend:
   - Determina `user_id` (sesión, token, etc.).
   - Genera `thread_id` si es una conversación nueva.
   - Fija `agent = "jarvis_webchat"`.
   - Fija `source = "webchat_backend"`.
   - Fija `event_type = "message"`, `role = "user"`.
   - Opcional: rellena `tags`, `importance`, etc.

### 3.2. Backend → webhook n8n (mensaje de usuario)

El backend construye un JSON conforme al contrato y lo envía al webhook de n8n. Ejemplo:

```json
{
  "source": "webchat_backend",
  "agent": "jarvis_webchat",
  "user_id": "user_42",
  "channel": "webchat",
  "thread_id": "thread_20251203_01",
  "event_type": "message",
  "role": "user",
  "importance": 0,
  "tags": ["jarvis", "webchat"],
  "content": "Jarvis, ¿puedes bajar la calefacción del salón a 20 grados?",
  "payload": {
    "frontend_message_id": "msg-usr-0001",
    "session_id": "sess-123",
    "language": "es-ES"
  }
}
```

En n8n:

1. El nodo Webhook recibe el JSON.
2. Un nodo Set/Function opcional puede aplicar defaults o validaciones.
3. Un nodo PostgreSQL (Insert) inserta los campos en `ia_nest_events`.

### 3.3. Backend → Modelo IA

En paralelo o a continuación:

1. El backend consulta la memoria (ver sección 4) para obtener el contexto.
2. Construye el prompt y llama al modelo IA (p.ej. `POST /stream_prompt`).
3. Recibe la respuesta del modelo (stream o bloque).

### 3.4. Backend → webhook n8n (respuesta del asistente)

Cuando el backend tiene la respuesta final:

```json
{
  "source": "webchat_backend",
  "agent": "jarvis_webchat",
  "user_id": "user_42",
  "channel": "webchat",
  "thread_id": "thread_20251203_01",
  "event_type": "message",
  "role": "assistant",
  "importance": 0,
  "tags": ["jarvis", "webchat"],
  "content": "He bajado la calefacción del salón a 20 grados. ¿Quieres que guarde esta preferencia para futuras noches?",
  "payload": {
    "backend_message_id": "msg-assistant-0001",
    "model": "mistral-7b-instruct-v0.2",
    "latency_ms": 812,
    "tokens_prompt": 210,
    "tokens_completion": 45
  }
}
```

n8n inserta este evento en `ia_nest_events` del mismo modo.

Por último, el backend devuelve la respuesta al frontend para que se muestre en el chat.

### 3.5. Errores y eventos especiales

En caso de error (por ejemplo timeout llamando al modelo IA), el backend puede enviar un evento:

```json
{
  "source": "webchat_backend",
  "agent": "jarvis_webchat",
  "user_id": "user_42",
  "channel": "webchat",
  "thread_id": "thread_20251203_01",
  "event_type": "error",
  "role": "system",
  "importance": 1,
  "tags": ["error", "model_timeout"],
  "content": "No se ha podido obtener respuesta del modelo de IA (timeout).",
  "payload": {
    "error_code": "MODEL_TIMEOUT",
    "timeout_ms": 30000,
    "endpoint": "http://127.0.0.1:8302/stream_prompt"
  }
}
```

Así la conversación refleja también los fallos y se pueden explotar con consultas o dashboards.

## 4. Flujo de LECTURA (reconstruir contexto en el backend)

La lectura de memoria se hace **directamente desde el backend** contra PostgreSQL,
sin pasar por n8n.

### 4.1. Consulta típica para contexto

Cuando llega un nuevo mensaje de usuario, el backend necesita los últimos N mensajes
del hilo actual para construir el prompt del modelo.

Consulta SQL de ejemplo:

```sql
SELECT
    role,
    content,
    event_type,
    created_at,
    tags,
    payload
FROM ia_nest_events
WHERE agent      = $1      -- 'jarvis_webchat'
  AND user_id    = $2      -- 'user_42'
  AND thread_id  = $3      -- 'thread_20251203_01'
  AND event_type = 'message'
  AND role IN ('user', 'assistant')
ORDER BY created_at DESC
LIMIT $4;                  -- por ejemplo 20
```

Pasos en el backend:

1. Ejecutar la consulta con los parámetros adecuados.
2. Invertir el orden en memoria (de más antiguo a más reciente).
3. Mapear `role = 'user'` / `role = 'assistant'` a la estructura que espera el modelo.

### 4.2. Nuevo hilo de conversación

Cuando el usuario inicia un “nuevo chat”:

1. El backend genera un nuevo `thread_id` (por ejemplo un UUID).
2. A partir de ese momento, todos los eventos de ese chat usan ese `thread_id`.
3. El contexto se limita siempre a ese hilo.

Consulta conceptual para listar hilos recientes de un usuario:

```sql
SELECT
    thread_id,
    MIN(created_at) AS started_at,
    MAX(created_at) AS last_event_at,
    COUNT(*)        AS num_events
FROM ia_nest_events
WHERE agent   = 'jarvis_webchat'
  AND user_id = 'user_42'
GROUP BY thread_id
ORDER BY last_event_at DESC
LIMIT 20;
```

### 4.3. Importancia y filtrado avanzado (visión futura)

El campo `importance` permite, en una fase posterior:

- Priorizar ciertos eventos (decisiones del usuario, configuraciones explícitas, etc.).
- Mantener siempre un subconjunto de eventos importantes aunque el hilo sea largo.
- Combinarlos con resúmenes de medio plazo para construir el contexto de forma eficiente.

Por ahora, el uso básico recomendado es:

- Leer los últimos N eventos (`importance` ignorado).
- Más adelante, combinar:
  - Resumen de la conversación (tabla aparte).
  - + últimos N eventos “crudos” de `ia_nest_events`.
