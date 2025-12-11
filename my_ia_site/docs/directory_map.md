# Mapa de Estructura de Directorios - My IA Site

Este documento describe la organización de archivos y carpetas del proyecto, para facilitar el mantenimiento y futuras ampliaciones.

---

## Estructura General

/srv/www/htdocs/my_ia_site/
│
├── admin/ # Páginas de administración global del portal (usuarios, roles, dashboard)
│
├── assets/ # Recursos generales (CSS, imágenes, fuentes) [deprecated; ahora en /static/]
│
├── config/ # Configuración global del portal y módulos
│
├── inc/ # Funciones PHP compartidas (autenticación, utilidades, sidebar, logging)
│
├── logs/ # Almacenamiento de logs de la plataforma
│
├── modules/ # Cada módulo funcional independiente (webchat, agenda, etc.)
│ └── webchat_stream/
│ ├── admin/ # Administración del módulo webchat
│ ├── assets/ # Recursos propios del módulo
│ ├── config/ # Configuración específica del módulo
│ ├── inc/ # Funciones auxiliares/modulares del módulo
│ ├── public/ # Páginas públicas o compartidas
│ └── restricted/ # Páginas privadas del módulo (requieren login)
│
├── public/ # Páginas públicas generales (landing, login, ayuda, etc.) [deprecated; ahora en raíz]
│
├── static/ # Recursos estáticos globales accesibles directamente por web
│ └── css/ # Hojas de estilo globales, temas y overrides
│ └── js/ # Scripts JS globales para todo el portal
│ └── img/ # Imágenes, logos, iconos globales
│
├── personalize/ # Preferencias y personalización visual/usuario
│
├── index.php # Punto de entrada principal al portal (requiere autenticación)
│
├── login.php # Formulario de acceso de usuarios
│
├── logout.php # Cierre de sesión
│
├── DIRECTORY_MAP.md # Este fichero: documentación de estructura
│
└── ...otros archivos relevantes (README, .htaccess, etc.)


---

## Descripción de directorios

### `/admin/`
Paneles y scripts de administración global del portal:  
- Gestión de usuarios, roles, configuración general.

### `/assets/`
Recursos generales antiguos (pueden mantenerse para compatibilidad, pero **se recomienda usar `/static/` para CSS/JS/imagenes**).

### `/config/`
Ficheros de configuración global del sitio y los módulos.  
- Ejemplo: `users.php`, `config.php`, configuraciones específicas.

### `/inc/`
Funciones PHP reutilizables y utilidades:
- `auth.php`: autenticación y sesiones.
- `roles.php`: gestión de roles/permisos.
- `logger.php`: logging global.
- `sidebar.php`: menú lateral común.
- Otros helpers globales.

### `/logs/`
Archivos de log (errores, accesos, acciones).  
Se recomienda proteger con `.htaccess` o reglas Apache.

### `/modules/`
Cada módulo funcional es un subdirectorio propio con su estructura:
- `admin/`: admin del módulo.
- `assets/`: recursos estáticos del módulo (CSS/JS solo para ese módulo).
- `config/`: config específica.
- `inc/`: helpers del módulo.
- `public/`: páginas abiertas/modo público del módulo.
- `restricted/`: páginas que requieren login.

### `/public/`
Páginas públicas generales (landing, login...).  
*(Ahora se recomienda poner estas páginas en la raíz si el DocumentRoot es la raíz del proyecto.)*

### `/static/`
**Nuevo estándar para recursos estáticos globales**.  
Accesibles por URL `/static/...`

#### `/static/css/`
- **Todas las hojas de estilo globales del portal**
- Ejemplo: `main.css`, `theme-dark.css`, `webchat.css`
- Cada página o módulo puede incluir `/static/css/main.css` para mantener coherencia visual.

#### `/static/js/`
- Scripts JavaScript globales (funciones comunes, helpers).
- Ejemplo: validaciones de formularios, interacción UI global.

#### `/static/img/`
- Imágenes, iconos, logos compartidos entre módulos y páginas.

### `/personalize/`
Preferencias visuales, selector de tema, personalizaciones de usuario.

### Archivos raíz
- `index.php`: página de inicio y dashboard principal (requiere login)
- `login.php`: formulario de acceso
- `logout.php`: cierre de sesión
- `DIRECTORY_MAP.md`: este documento

---

## **Recomendaciones**

- **Referenciar CSS y JS siempre desde `/static/`** para uniformidad en todos los módulos y páginas.
    - Ejemplo:  
      ```html
      <link rel="stylesheet" href="/static/css/main.css">
      <script src="/static/js/app.js"></script>
      ```
- **Mantener módulos funcionalmente aislados** dentro de `/modules/`.
- **Reutilizar componentes comunes** (`/inc/`, `/static/`) para evitar duplicidades.
- Proteger siempre `/inc/`, `/config/`, `/logs/` y carpetas sensibles.

---

*Actualiza este documento si cambias la estructura o añades nuevos módulos importantes.*

