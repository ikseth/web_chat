# Web Chat Portal

> Nota: última actualización realizada el 2025-12-11 15:03:01 CET.

Portal PHP sencillo pensado como laboratorio de administración de chats IA. Este repositorio contiene:

- `my_ia_site/`: aplicación principal con módulos, configuración y recursos estáticos.
- `bkp/`: carpeta local (ignorara por Git) para almacenar copias de seguridad temporales.
- `docs/`: documentación funcional y de estructura dentro de `my_ia_site/docs/`.

## Requisitos

- PHP 8.1+
- Servidor web configurado para servir `my_ia_site/` como document root.

## Puesta en marcha

1. Clone el repositorio y copie `my_ia_site/config/` con los secretos adecuados.
2. Publique el directorio en su servidor o ejecute `php -S localhost:8000 -t my_ia_site`.
3. Revise `modules/webchat_stream/config/webchat_config.php` para apuntar al endpoint correcto.

## Estructura recomendada

```
.
├── bkp/                 # Copias locales no versionadas
├── my_ia_site/          # Código fuente y assets
└── README.md
```

Consulte `my_ia_site/docs/directory_map.md` para un desglose detallado del árbol de directorios.
