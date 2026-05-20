<?php
/**
 * IB Engine — Editor Visual
 * 
 * Funciones: ib_text(), ib_get_val(), ib_edit(), ib_val()
 * AJAX: ib_save_content, ib_reset_content, ib_undo_save
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Obtener valor del campo o fallback
 */
function ib_get_val($key, $default = '') {
    $post_id = get_the_ID();
    $data = get_post_meta($post_id, '_ib_content_data', true);
    
    if (is_array($data) && isset($data[$key]) && !empty($data[$key])) {
        return $data[$key];
    }
    
    return $default;
}

function ib_val($key, $default = '') {
    echo ib_get_val($key, $default);
}

function ib_edit($key) {
    if (current_user_can('edit_posts')) {
        echo ' data-ib-editable="' . esc_attr($key) . '" contenteditable="true" style="outline: none; min-width: 10px;" ';
    }
}

/**
 * Cargar Assets (Solo para usuarios con permiso de edición)
 */
add_action('wp_enqueue_scripts', function() {
    if (current_user_can('edit_posts')) {
        wp_enqueue_style('ib-editor-css', plugins_url('assets/editor.css', IB_ENGINE_FILE), array(), IB_ENGINE_VERSION);
        wp_enqueue_script('ib-editor-js', plugins_url('assets/editor.js', IB_ENGINE_FILE), array('jquery'), IB_ENGINE_VERSION, true);
        
        wp_localize_script('ib-editor-js', 'ibEditor', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'post_id' => get_the_ID(),
            'nonce'   => wp_create_nonce('ib_editor_nonce')
        ));
    }
});

/**
 * AJAX: Guardar Contenido
 */
add_action('wp_ajax_ib_save_content', function() {
    check_ajax_referer('ib_editor_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('No autorizado');
    
    $post_id = intval($_POST['post_id']);
    $new_content = isset($_POST['content']) ? $_POST['content'] : array();
    if (is_array($new_content)) {
        $sanitized = array();
        foreach ($new_content as $key => $val) {
            $sanitized[ sanitize_text_field($key) ] = wp_kses_post($val);
        }
        $new_content = $sanitized;
    }
    
    if ($post_id > 0 && is_array($new_content)) {
        $current_data = get_post_meta($post_id, '_ib_content_data', true);
        if (!is_array($current_data)) $current_data = array();
        
        $updated_data = array_merge($current_data, $new_content);
        update_post_meta($post_id, '_ib_content_data', $updated_data);
        
        // --- NUEVO: Guardado en archivo físico ---
        $dir_path = get_post_meta($post_id, '_ib_sections_dir', true);
            if ($dir_path && is_dir($dir_path)) {
            $json_file = trailingslashit($dir_path) . 'content.json';
            $bak_file = trailingslashit($dir_path) . 'content.bak.json';

            // Backup para Deshacer: desde archivo o desde BD
            if (file_exists($json_file)) {
                copy($json_file, $bak_file);
            } elseif (is_array($current_data) && !empty($current_data)) {
                file_put_contents($bak_file, json_encode($current_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                file_put_contents($bak_file, json_encode(new stdClass(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            $json_content = json_encode($updated_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if (is_writable($dir_path)) {
                file_put_contents($json_file, $json_content);
                $msg = '¡Guardado!';
            } else {
                $msg = 'Contenido guardado en BD, pero la carpeta no tiene permisos de escritura';
                error_log("IB Editor: Carpeta no escribible: " . $dir_path);
            }
        } else {
            $msg = 'Contenido guardado en BD. Ruta de archivos no configurada o inválida.';
            error_log("IB Editor: Ruta inválida o vacía: " . $dir_path);
        }
        // -----------------------------------------
        
        wp_send_json_success($msg);
    }
    wp_send_json_error('Error al guardar datos');
});

/**
 * AJAX: Resetear Contenido (Vuelve a los archivos PHP)
 */
add_action('wp_ajax_ib_reset_content', function() {
    check_ajax_referer('ib_editor_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('No autorizado');
    
    $post_id = intval($_POST['post_id']);
    if ($post_id > 0) {
        // Borrar de la BD
        delete_post_meta($post_id, '_ib_content_data');
        
        // Borrar el archivo físico si existe
        $dir_path = get_post_meta($post_id, '_ib_sections_dir', true);
        if ($dir_path && is_dir($dir_path)) {
            $json_file = trailingslashit($dir_path) . 'content.json';
            if (file_exists($json_file)) {
                unlink($json_file);
            }
        }
        
        wp_send_json_success('Todo reseteado');
    }
    wp_send_json_error('Error al resetear');
});

/**
 * ib_text — Atajo unificado: emite atributos de editor + valor
 * Uso: <span <?php ib_text('hero_title', 'La tranquilidad de tener...', 'post'); ?>></span>
 */
function ib_text($key, $default = '', $escape = 'post') {
    if (current_user_can('edit_posts')) {
        echo ' data-ib-editable="' . esc_attr($key) . '" contenteditable="false" class="ib-locked" ';
    }
    echo '>';
    $val = ib_get_val($key, $default);
    echo $escape === 'post' ? wp_kses_post($val) : esc_html($val);
}

/**
 * AJAX: Deshacer último guardado
 */
add_action('wp_ajax_ib_undo_save', function() {
    check_ajax_referer('ib_editor_nonce', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('No autorizado');

    $post_id = intval($_POST['post_id']);
    $dir_path = get_post_meta($post_id, '_ib_sections_dir', true);
    $bak = trailingslashit($dir_path) . 'content.bak.json';
    $json = trailingslashit($dir_path) . 'content.json';

    if (!file_exists($bak)) {
        wp_send_json_error('No hay backup');
    }

    // Restaurar archivo
    copy($bak, $json);

    // Restaurar también la base de datos
    $restored = json_decode(file_get_contents($bak), true);
    if (is_array($restored)) {
        update_post_meta($post_id, '_ib_content_data', $restored);
    }

    wp_send_json_success('Deshecho');
});
