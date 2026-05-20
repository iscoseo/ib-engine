# IB Engine — Guía para el agente

Plugin de WordPress: motor de secciones modulares + editor visual inline.

---

## 1. Estructura del plugin

```
ib-engine/
├── ib-engine.php              ← Entry point: cabecera WP, auto-updater, carga módulos
├── engine/
│   ├── sections.php           ← Motor [sections]: shortcode, carga de archivos, enqueue CSS/JS
│   └── editor.php             ← Editor visual: ib_text(), ib_get_val(), AJAX (save/reset/undo)
├── assets/
│   ├── editor.css             ← Estilos de la toolbar flotante
│   └── editor.js              ← Lógica de la toolbar (jQuery)
├── lib/
│   └── plugin-update-checker/ ← Auto-update desde GitHub (NO modificar)
└── README.md
```

---

## 2. Auto-update (GitHub → WordPress)

El plugin se actualiza solo desde releases de GitHub usando [Plugin Update Checker v5.6](https://github.com/YahnisElsts/plugin-update-checker).

### Workflow de versionado

1. **Hacer cambios** en `main`
2. **Bump versión** en DOS sitios del archivo `ib-engine.php`:
   - Cabecera: `* Version: X.Y.Z`
   - Constante: `define('IB_ENGINE_VERSION', 'X.Y.Z');`
3. **Commit + push**
4. **Crear release en GitHub** con tag `vX.Y.Z` apuntando a ese commit
5. WordPress detecta la actualización automáticamente

### Regla de oro

El `Version:` en la cabecera del plugin en el commit taggeado **debe coincidir** con el número del release tag (sin la `v`).  
Si no coinciden, la librería pisa la versión del release con la de la cabecera y no se detecta la actualización.

### Requisitos técnicos

- Repo **público** en GitHub (la API devuelve 404 para repos privados sin token)
- Timeout aumentado a 15s (`puc_request_timeout-ib-engine`)
- Cada release debe tener el archivo con la versión correcta en ese commit

### Icono

El icono del plugin se setea vía `puc_pre_inject_info-ib-engine`. Cambiar la URL en `ib-engine.php` si se actualiza.

---

## 3. Funciones PHP disponibles para las secciones

| Función | Uso |
|---|---|
| `ib_text('key', 'default', 'post')` | Texto editable con HTML |
| `ib_text('key', 'default', 'html')` | Texto editable plano |
| `ib_get_val('key', 'default')` | Leer valor sin atributos de edición (legacy) |
| `ib_edit('key')` | Solo atributos de edición (legacy) |

---

## 4. Editor visual

La toolbar aparece para usuarios con `edit_posts`. Botones:

| Botón | Acción |
|---|---|
| 🔒/🔓 | Bloquea/desbloquea contenteditable |
| Guardar | Persiste en BD + content.json |
| Deshacer | Restaura último guardado (content.bak.json + BD) |
| Descargar | Exporta JSON |
| Reset | Vuelve a defaults PHP |

---

## 5. Persistencia de contenido

```
ib_text() → ib_get_val() → BD (_ib_content_data) → default PHP
Save → BD + content.json + content.bak.json
Undo → content.bak.json → content.json + update_post_meta
Reset → delete_post_meta + unlink(content.json)
```

---

## 6. CSS/JS de las secciones

- Las secciones viven en `[tema-activo]/paginas/[slug]/sections/`
- CSS global en `paginas/global.css`
- CSS por página en `paginas/[slug]/page.css`
- JS por página en `paginas/[slug]/page.js`
- Todo bajo `#ib-engine` con prefijo `ib-`

---

## 7. Qué NO hacer

- No modificar `lib/plugin-update-checker/`
- No crear releases sin bump de versión en el archivo
- No usar `position: sticky` sin `overflow-x: clip` en `#ib-engine`
- No hardcodear URLs en secciones (van en el PHP como HTML directo)
