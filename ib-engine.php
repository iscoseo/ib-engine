<?php
/**
 * Plugin Name: IB Engine
 * Plugin URI: https://iscobelda.com/
 * Description: Crea páginas modulares con editor visual inline. Edita el contenido directamente desde el frontend — sin formularios ni panel de administración.
 * Version: 1.0.0
 * Author: Iscobelda
 * Author URI: https://iscobelda.com/
 * Text Domain: ib-engine
 *
 * INSTALACIÓN:
 *   Subir la carpeta ib-engine/ a /wp-content/plugins/
 *   Activar desde Plugins > Plugins instalados
 *
 * USO:
 *   [sections id="all"]                           Carga todas las secciones
 *   [sections id="01-hero, 03-cta"]               Carga secciones específicas
 *   [sections path="ruta-custom" id="all"]        Fuerza carpeta concreta
 *
 * LOCALIZACIÓN DE SECCIONES:
 *   Las secciones viven en [tema-activo]/paginas/.
 *   El motor detecta child themes automáticamente.
 */

if (!defined('ABSPATH')) exit;

define('IB_ENGINE_VERSION', '1.0.0');
define('IB_ENGINE_FILE', __FILE__);
define('IB_ENGINE_DIR', plugin_dir_path(__FILE__));

// Auto-updater desde GitHub
if (file_exists(IB_ENGINE_DIR . 'lib/plugin-update-checker/plugin-update-checker.php')) {
    require_once IB_ENGINE_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';
    $ibUpdater = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/iscoseo/ib-engine',
        __FILE__
    );
    
    // Aumentar timeout de 3s a 15s
    add_filter('puc_request_timeout-ib-engine', function() { return 15; });
    
    // DEBUG: Quitar después de verificar que funciona
    if (is_admin() && isset($_GET['ib-debug'])) {
        delete_site_transient('update_plugins');
        set_site_transient('update_plugins', null);
        wp_clean_plugins_cache();
        $ibUpdater->checkForUpdates();
        
        // Llamada directa a la API — probar con /releases (todas) y /latest
        $response1 = wp_remote_get('https://api.github.com/repos/iscoseo/ib-engine/releases?per_page=5', [
            'timeout' => 15,
            'headers' => ['User-Agent' => 'WordPress/' . get_bloginfo('version')]
        ]);
        $response2 = wp_remote_get('https://api.github.com/repos/iscoseo/ib-engine/releases/latest', [
            'timeout' => 15,
            'headers' => ['User-Agent' => 'WordPress/' . get_bloginfo('version')]
        ]);
        
        add_action('admin_notices', function() use ($response1, $response2) {
            echo '<div class="notice notice-info"><p><strong>IB Engine Debug:</strong></p>';
            
            // /releases (list)
            echo '<p><strong>/releases:</strong> ';
            if (is_wp_error($response1)) {
                echo 'Error: ' . $response1->get_error_message();
            } else {
                $code = wp_remote_retrieve_response_code($response1);
                $body = json_decode(wp_remote_retrieve_body($response1));
                echo 'HTTP ' . $code . ' | Count: ' . (is_array($body) ? count($body) : 0);
                if (is_array($body) && !empty($body)) {
                    foreach($body as $r) {
                        echo '<br>— ' . $r->tag_name . ' (draft:' . ($r->draft?'yes':'no') . ' prerelease:' . ($r->prerelease?'yes':'no') . ')';
                    }
                }
            }
            echo '</p>';
            
            // /latest
            echo '<p><strong>/latest:</strong> ';
            if (is_wp_error($response2)) {
                echo 'Error: ' . $response2->get_error_message();
            } else {
                $code = wp_remote_retrieve_response_code($response2);
                $body = json_decode(wp_remote_retrieve_body($response2));
                echo 'HTTP ' . $code;
                if ($body && isset($body->tag_name)) {
                    echo ' | Tag: ' . $body->tag_name;
                }
            }
            echo '</p>';
            echo '</div>';
        });
    }
}

// Motor de secciones
require_once IB_ENGINE_DIR . 'engine/sections.php';

// Editor visual
require_once IB_ENGINE_DIR . 'engine/editor.php';
