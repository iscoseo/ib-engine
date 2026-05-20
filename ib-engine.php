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
    
    // DEBUG: Forzar comprobación
    if (is_admin() && isset($_GET['ib-debug'])) {
        delete_site_transient('update_plugins');
        set_site_transient('update_plugins', null);
        wp_clean_plugins_cache();
        
        // Forzar el check ahora
        $ibUpdater->checkForUpdates();
        
        // Ver qué pilló de GitHub
        $api = new ReflectionProperty($ibUpdater, 'api');
        $api->setAccessible(true);
        $github = $api->getValue($ibUpdater);
        
        $latest = $github->getLatestRelease();
        
        add_action('admin_notices', function() use ($ibUpdater, $latest, $github) {
            echo '<div class="notice notice-info"><p><strong>IB Engine Debug:</strong></p>';
            echo '<p>Latest release: <code>' . ($latest ? $latest->tagName : 'NULL') . '</code></p>';
            echo '<p>Username: <code>' . $github->userName . '</code> | Repo: <code>' . $github->repositoryName . '</code></p>';
            
            // Probar API directamente
            $apiUrl = 'https://api.github.com/repos/' . $github->userName . '/' . $github->repositoryName . '/releases/latest';
            $response = wp_remote_get($apiUrl, ['headers' => ['User-Agent' => 'WordPress/' . get_bloginfo('version')]]);
            echo '<p>API status: <code>' . wp_remote_retrieve_response_code($response) . '</code></p>';
            if (is_wp_error($response)) {
                echo '<p>Error: <code>' . $response->get_error_message() . '</code></p>';
            } else {
                $body = json_decode(wp_remote_retrieve_body($response));
                echo '<p>API Tag: <code>' . ($body->tag_name ?? 'N/A') . '</code></p>';
            }
            echo '</div>';
        });
    }
}

// Motor de secciones
require_once IB_ENGINE_DIR . 'engine/sections.php';

// Editor visual
require_once IB_ENGINE_DIR . 'engine/editor.php';
