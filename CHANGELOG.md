# Changelog

## [1.0.5] — 2026-05-20

### Fixed
- Texto del tooltip simplificado: "Reset este elemento" → "Reset"

## [1.0.4] — 2026-05-20

### Added
- **Reset por clave**: nuevo endpoint `ib_reset_key`. Cada elemento editable tiene un tooltip rojo flotante (`↺`) que resetea solo esa clave a su valor por defecto PHP, sin afectar al resto.
- **Tooltip flotante**: aparece al hacer click en un elemento editable (editor desbloqueado). Posicionado arriba-derecha, fuera del cuadro de texto.
- **Scroll oculta tooltip**: al hacer scroll, el tooltip se oculta y el elemento pierde el foco. El editor permanece desbloqueado.

### Fixed
- **Layout shift en botones**: al desbloquear el editor, los `<span>` con `contenteditable` dentro de botones ya no desplazan el layout vertical.
- **Click en tooltip no funcionaba**: `focusout` con `relatedTarget` null en algunos navegadores impedía el click en el tooltip. Eliminado, ahora se gestiona con click en documento + scroll.

### Changed
- **Editor permanece unlocked**: una vez desbloqueado con 🔓, sigue activo hasta que el usuario guarde o bloquee explícitamente.
- **AGENTS.md**: documentación actualizada con reset por clave y nuevo endpoint.

## [1.0.3] — 2026-05-20

### Added
- Icono del plugin vía `puc_pre_inject_info`
- AGENTS.md con guía completa del plugin
- CHANGELOG.md

### Changed
- Bump version para release v1.0.3

## [1.0.2] — 2026-05-20

### Changed
- Bump version: 1.0.1 → 1.0.2
- Eliminados logs de debug
- Preparado para auto-update

## [1.0.1] — 2026-05-20

### Fixed
- `Version:` en cabecera del plugin ahora coincide con el tag del release para que Plugin Update Checker funcione correctamente.

## [1.0.0] — 2026-05-19

### Added
- Plugin unificado IB Engine (fusión de ib-sections-engine + ib-visual-editor)
- Motor de secciones: shortcode `[sections]`, carga modular de PHP, CSS/JS por página
- Editor visual inline: `ib_text()`, `ib_get_val()`
- AJAX: save (BD + content.json), reset, undo (content.bak.json)
- Auto-update desde GitHub con Plugin Update Checker v5.6
- Toolbar flotante: lock/unlock, guardar, descargar JSON, deshacer, reset
