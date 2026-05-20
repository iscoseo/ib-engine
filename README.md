# IB Engine

Motor de secciones modulares y editor visual inline para WordPress.

## Instalación

1. Subir la carpeta `ib-engine/` a `/wp-content/plugins/`
2. Activar desde Plugins > Plugins instalados

## Uso

En cualquier página de WordPress, usar el shortcode:

```
[sections id="all"]
```

Las secciones PHP deben estar en `[tema-activo]/paginas/[slug-de-pagina]/sections/`.

## Editor visual

Al activar el plugin, los usuarios logueados con permisos de edición verán una toolbar flotante:

- **Candado** 🔒: bloquea/desbloquea la edición inline
- **Guardar**: persiste los cambios en BD y `content.json`
- **Deshacer**: restaura el último guardado
- **Descargar**: exporta el contenido como JSON
- **Reset**: vuelve a los defaults del PHP

### ib_text()

Función unificada para texto editable:

```php
<span <?php ib_text('hero_title', 'La tranquilidad de tener...', 'post'); ?>></span>
```

- `'post'` → wp_kses_post (permite HTML)
- `'html'` → esc_html (texto plano)

## Auto-updates

Este plugin se actualiza automáticamente desde GitHub vía [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker). Para lanzar una nueva versión, crear un release en GitHub con tag semántico (ej: `v1.1.0`).
