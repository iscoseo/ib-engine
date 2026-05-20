# IB Engine

Motor de secciones modulares y editor visual inline para WordPress. Unifica `ib-sections-engine` + `ib-visual-editor` en un solo plugin.

---

## 1. Instalación

1. Subir la carpeta `ib-engine/` a `/wp-content/plugins/`
2. Activar desde **Plugins > Plugins instalados**
3. El plugin crea automáticamente el shortcode `[sections]`

---

## 2. Estructura de página

Cada página de WordPress se corresponde con una carpeta en el tema activo:

```
tema-activo/paginas/
├── global.css              ← tokens CSS y layout compartidos (todo el sitio)
└── home-1/                 ← slug de la página en WordPress
    ├── page.css            ← estilos específicos de esta página
    ├── page.js             ← JS específico de esta página (opcional)
    └── sections/
        ├── 01-hero.php
        ├── 02-logos.php
        ├── 03-featured.php
        └── ...
```

**Reglas:**
- El nombre de la carpeta (`home-1`) debe coincidir con el slug de la página en WordPress
- Las secciones se cargan en orden alfabético (`01-hero`, `02-logos`, ...)
- `page.css` y `page.js` se cargan automáticamente si existen
- `global.css` se carga en todas las páginas

---

## 3. Uso del shortcode

En el editor de WordPress, insertar:

```
[sections id="all"]
```

**Opciones:**

| Shortcode | Efecto |
|---|---|
| `[sections id="all"]` | Carga todas las secciones de la carpeta |
| `[sections id="01-hero, 03-cta"]` | Carga solo las secciones indicadas, en ese orden |
| `[sections path="ruta-custom" id="all"]` | Carga desde una carpeta específica |

---

## 4. Anatomía de una sección PHP

```php
<!-- SECTION: hero START -->
<section class="ib-section ib-section--hero" id="hero">
    <div class="ib-hero__content ib-container ib-flex ib-flex--col ib-gap-md">
        
        <!-- Texto editable con HTML (permite <strong>, <br>) -->
        <h1 class="ib-hero__title" <?php ib_text('hero_title', 'La <strong>tranquilidad</strong> de tener...', 'post'); ?>></h1>
        
        <!-- Texto editable plano -->
        <p class="ib-hero__subtitle" <?php ib_text('hero_subtitle', 'Tu asesor energético digital.', 'html'); ?>></p>
        
        <!-- URLs y atributos: HTML directo, sin ib_text() -->
        <a href="https://ejemplo.com" target="_blank">Botón</a>
        
    </div>
</section>
<!-- SECTION: hero END -->
```

**Reglas para secciones:**
- Marcadores `<!-- SECTION: nombre START/END -->` obligatorios (ayudan al debug)
- Todas las clases con prefijo `ib-`
- Todo el CSS bajo `#ib-engine` (el motor envuelve automáticamente)
- URLs y atributos NO editables van en HTML directo, sin funciones PHP
- Cada sección es autocontenida — no comparte código con otras páginas

---

## 5. API de funciones

### `ib_text()` — texto editable (recomendada)

```php
<span <?php ib_text('clave', 'default', 'html'); ?>></span>
```

| Parámetro | Tipo | Descripción |
|---|---|---|
| `$key` | string | Clave única del campo (ej: `hero_title`) |
| `$default` | string | Valor por defecto si no hay contenido guardado |
| `$escape` | `'post'` o `'html'` | `'post'` = `wp_kses_post` (permite HTML). `'html'` = `esc_html` (texto plano) |

`ib_text()` emite automáticamente:
- `data-ib-editable="clave"` — para el editor visual
- `contenteditable="false"` — bloqueado por defecto (se desbloquea con 🔒)
- `class="ib-locked"` — estado visual bloqueado

### Funciones legacy (compatibles)

| Función | Uso |
|---|---|
| `ib_get_val('key', 'default')` | Solo leer valor (sin atributos de edición) |
| `ib_edit('key')` | Solo emitir atributos de edición (sin valor) |
| `ib_val('key', 'default')` | Echo de `ib_get_val` |

`ib_text()` unifica `ib_edit()` + `ib_get_val()`. Se recomienda migrar gradualmente.

---

## 6. Editor visual

Los usuarios con permisos de edición (`edit_posts`) ven una barra flotante en la parte inferior:

```
[Editor] [🔒] [Guardar] [Descargar] [Deshacer] | [Reset]
```

| Botón | Acción |
|---|---|
| **🔒 / 🔓** | Bloquea o desbloquea la edición de todos los campos. Por defecto bloqueado. |
| **Guardar** | Persiste los cambios en BD (`_ib_content_data`) y en `content.json` |
| **Deshacer** | Restaura el último guardado desde `content.bak.json` + BD |
| **Descargar** | Exporta el contenido actual como JSON |
| **Reset** | Borra BD y archivos → vuelve a los defaults del PHP |

**Comportamiento:**
- Campos bloqueados: cursor normal, borde gris sutil al hover
- Campos desbloqueados: cursor de texto, resalte verde al editar
- Al guardar, se crea automáticamente un backup en `content.bak.json`

---

## 7. Flujo de contenido

```
Editor (toolbar) → AJAX → ib_save_content
                              ├── update_post_meta(_ib_content_data)  ← BD
                              ├── backup → content.bak.json
                              └── file_put_contents(content.json)

Al renderizar:
ib_text() / ib_get_val()
    ├── ¿Hay datos en BD (_ib_content_data)? → usar BD
    └── No → usar el default del PHP
```

**Deshacer:**
```
content.bak.json → copy → content.json + update_post_meta(BD)
```

**Reset:**
```
delete_post_meta(_ib_content_data) + unlink(content.json)
→ la página vuelve a mostrar los defaults del PHP
```

---

## 8. Convenciones CSS

Todo el contenido del motor se renderiza dentro de `#ib-engine`:

```css
/* CORRECTO */
#ib-engine .ib-hero__title { font-size: 60px; }
#ib-engine .ib-btn--gradient { background: var(--gradient-primary); }

/* INCORRECTO */
.hero__title { font-size: 60px; }       /* sin prefijo ib- */
.btn { background: red; }               /* puede colisionar con el tema */
```

**Tokens:** Las variables CSS viven en `global.css`. Las secciones las consumen con `var()`:

```css
.ib-hero__title {
    color: var(--color-human, #2B2F37);
    font-size: var(--font-size-h1, 60px);
}
```

---

## 9. Convenciones JS

El JS específico de cada página va en `page.js`. El motor lo carga automáticamente en el footer.

```js
// Selectores siempre bajo #ib-engine
document.querySelectorAll('#ib-engine .ib-faq__question').forEach(item => {
    item.addEventListener('click', () => { ... });
});
```

**Reglas:**
- Sin jQuery ni librerías externas (salvo el editor, que usa jQuery para su toolbar)
- Sin `<script>` inline en las secciones PHP
- Todo encapsulado bajo `#ib-engine`

---

## 10. Auto-updates (opcional)

Para habilitar actualizaciones automáticas desde GitHub:

1. Descargar [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker/releases) (último release)
2. Extraer la carpeta `plugin-update-checker/` dentro de `ib-engine/lib/`
3. El plugin lo detecta y se activa solo

```
ib-engine/lib/plugin-update-checker/
├── plugin-update-checker.php
├── Puc/
└── ...
```

**Workflow de versionado:**
1. Hacer cambios en `main`
2. Crear un **release** en GitHub con tag semántico: `v1.0.0`, `v1.1.0`
3. Todos los sitios ven "Actualizar ahora" en WordPress

---

## 11. Preguntas frecuentes

**¿Por qué no veo la toolbar del editor?**
La toolbar solo aparece si tienes permisos de edición (`edit_posts`) y la página tiene al menos un campo con `ib_text()` o `ib_edit()`.

**¿Dónde se guardan los cambios?**
En la base de datos (`_ib_content_data`) y en el archivo `content.json` dentro de la carpeta `sections/`. Ambos se sincronizan al guardar.

**¿Cómo añadir una página nueva?**
Crear una carpeta con el slug exacto de la página WP dentro de `paginas/`, añadir `sections/` con los archivos PHP, y usar `[sections id="all"]` en el editor de WordPress.

**¿Puedo usar el mismo shortcode en varias páginas?**
Sí. El motor detecta el slug de la página actual y carga las secciones de la carpeta correspondiente automáticamente.

**¿Se pierden los cambios al actualizar el plugin?**
No. El contenido se guarda en la BD de WordPress y en `content.json` dentro del tema. Actualizar el plugin no borra datos.

**¿Por qué `position: sticky` no funciona en mis secciones?**
Asegúrate de que `#ib-engine` use `overflow-x: clip` (no `overflow-x: hidden`). El valor `hidden` rompe el sticky.

---

## 12. Archivos del plugin

```
ib-engine/
├── ib-engine.php              ← Entry point del plugin (cabecera WP)
├── engine/
│   ├── sections.php           ← Motor: shortcode [sections], carga de archivos
│   └── editor.php             ← Editor visual: ib_text(), AJAX, funciones
├── assets/
│   ├── editor.css             ← Estilos de la toolbar flotante
│   └── editor.js              ← Lógica de la toolbar (jQuery)
├── lib/
│   └── plugin-update-checker/ ← Auto-update (instalación manual, opcional)
└── README.md
```
