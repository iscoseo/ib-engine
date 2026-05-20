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
        __FILE__,
        'ib-engine'
    );
    
    add_filter('puc_request_timeout-ib-engine', function() { return 15; });
    
    // DEBUG
    if (is_admin() && isset($_GET['ib-debug'])) {
        delete_site_transient('update_plugins');
        set_site_transient('update_plugins', null);
        wp_clean_plugins_cache();
        
        // Llamar checkForUpdates directo y ver qué encuentra
        $ibUpdater->checkForUpdates();
        $state = get_site_option('external_updates-ib-engine', 'NOT FOUND');
        $updateObj = $ibUpdater->getUpdate();
        
        // Forzar wp_update_plugins también
        wp_update_plugins();
        $transient = get_site_transient('update_plugins');
        
        add_action('admin_notices', function() use ($ibUpdater, $state, $updateObj, $transient) {
            echo '<div class="notice notice-info"><p><strong>IB Engine Debug:</strong></p>';
            echo '<p>State option: <pre>' . print_r($state, true) . '</pre></p>';
            echo '<p>getUpdate(): <code>' . ($updateObj ? 'v' . $updateObj->version . ' url:' . $updateObj->download_url : 'NULL') . '</code></p>';
            echo '<p>Installed version: <code>' . $ibUpdater->getInstalledVersion() . '</code></p>';
            echo '<p>--- transient after wp_update_plugins: ---</p>';
            echo '<p>has response? <code>' . (isset($transient->response['ib-engine/ib-engine.php']) ? 'YES' : 'NO') . '</code></p>';
            echo '<p>has no_update? <code>' . (isset($transient->no_update['ib-engine/ib-engine.php']) ? 'YES' : 'NO') . '</code></p>';
            if (isset($transient->no_update['ib-engine/ib-engine.php'])) {
                echo '<p>no_update info: <pre>' . print_r($transient->no_update['ib-engine/ib-engine.php'], true) . '</pre></p>';
            }
            echo '</div>';
        });
    }
}

// Motor de secciones
require_once IB_ENGINE_DIR . 'engine/sections.php';

// Editor visual
require_once IB_ENGINE_DIR . 'engine/editor.php';
