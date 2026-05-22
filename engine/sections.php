<?php
/**
 * IB Engine — Motor de Secciones
 * 
 * USO:
 *    [sections id="all"]                           Carga todas las secciones
 *    [sections id="01-hero, 03-cta"]               Carga secciones específicas
 *    [sections path="ruta-custom" id="all"]        Fuerza carpeta concreta
 *
 * LOCALIZACIÓN:
 *    Las secciones viven en [tema-activo]/paginas/.
 *    Si hay child theme activo, lo usa automáticamente.
 */

if ( ! class_exists( 'IB_Sections_Engine' ) ) {

    class IB_Sections_Engine {

        private static $instance = null;

        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            add_shortcode( 'sections', array( $this, 'render_sections' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_page_styles' ), 20 );
        }

        /**
         * Determina la raíz del proyecto (donde viven las carpetas de las páginas)
         */
        private function get_project_paths() {
            // 1. Carpeta dentro del tema activo (child theme si existe)
            $theme_dir = get_stylesheet_directory() . '/paginas/';
            $theme_url = get_stylesheet_directory_uri() . '/paginas/';

            if ( is_dir( $theme_dir ) ) {
                return array( 'root' => $theme_dir, 'url' => $theme_url );
            }

            // 2. Fallback: Tema padre
            $parent_theme_dir = get_template_directory() . '/paginas/';
            $parent_theme_url = get_template_directory_uri() . '/paginas/';

            return array( 'root' => $parent_theme_dir, 'url' => $parent_theme_url );
        }

        /**
         * Encola estilos de página en el <head> (evita FOUC)
         */
        public function enqueue_page_styles() {
            if ( ! is_singular() ) return;

            $post = get_queried_object();
            if ( ! $post || ! isset( $post->ID ) ) return;

            $paths = $this->get_project_paths();

            // Encolar CSS Global y de Página
            $global_css_path = trailingslashit( $paths['root'] ) . 'global.css';
            if ( file_exists( $global_css_path ) ) {
                wp_enqueue_style( 'ib-global', trailingslashit( $paths['url'] ) . 'global.css', array(), filemtime( $global_css_path ) );
            }

            $page_slug = get_page_uri( $post->ID );
            if ( empty( $page_slug ) ) return;

            $page_css_path = trailingslashit( $paths['root'] ) . trailingslashit( $page_slug ) . 'page.css';
            if ( file_exists( $page_css_path ) ) {
                wp_enqueue_style( 'ib-page-' . sanitize_title( $page_slug ), trailingslashit( $paths['url'] ) . trailingslashit( $page_slug ) . 'page.css', array('ib-global'), filemtime( $page_css_path ) );
            }

            // Encolar JS Global y de Página
            $global_js_path = trailingslashit( $paths['root'] ) . 'global.js';
            if ( file_exists( $global_js_path ) ) {
                wp_enqueue_script( 'ib-global-js', trailingslashit( $paths['url'] ) . 'global.js', array(), filemtime( $global_js_path ), true );
            }

            $page_js_path = trailingslashit( $paths['root'] ) . trailingslashit( $page_slug ) . 'page.js';
            if ( file_exists( $page_js_path ) ) {
                wp_enqueue_script( 'ib-page-js-' . sanitize_title( $page_slug ), trailingslashit( $paths['url'] ) . trailingslashit( $page_slug ) . 'page.js', array(), filemtime( $page_js_path ), true );
            }
        }

        public function render_sections( $atts ) {
            $a = shortcode_atts( array(
                'id'   => 'all',
                'path' => '',
            ), $atts );

            global $post;
            if ( ! $post ) return '';

            $paths = $this->get_project_paths();
            $page_slug = ! empty( $a['path'] ) ? $a['path'] : get_page_uri( $post->ID );
            $sections_dir = trailingslashit( $paths['root'] ) . trailingslashit( $page_slug ) . 'sections/';

            // IMPORTANTE: Registrar la ruta para el Visual Editor
            update_post_meta( $post->ID, '_ib_sections_dir', $sections_dir );

            if ( ! is_dir( $sections_dir ) ) {
                return "<!-- IB Engine: Carpeta no encontrada en $sections_dir -->";
            }

            $content = '';
            $files_to_render = array();

            if ( $a['id'] === 'all' ) {
                $files = glob( $sections_dir . '*.{php,html}', GLOB_BRACE );
                if ( $files ) {
                    sort( $files );
                    $files_to_render = $files;
                }
            } else {
                $ids = array_map( 'trim', explode( ',', $a['id'] ) );
                foreach ( $ids as $id ) {
                    $php  = $sections_dir . $id . '.php';
                    $html = $sections_dir . $id . '.html';
                    if ( file_exists( $php ) ) {
                        $files_to_render[] = $php;
                    } elseif ( file_exists( $html ) ) {
                        $files_to_render[] = $html;
                    }
                }
            }

            foreach ( $files_to_render as $file ) {
                ob_start();
                include $file;
                $content .= trim( ob_get_clean() );
            }

            return $content 
                ? '<div class="aurora-bg"></div><div id="ib-engine" class="site-wrapper">' . $content . '</div>' 
                : "<!-- IB Engine: Sin secciones para renderizar -->";
        }
    }

    // Inicializar
    add_action( 'plugins_loaded', array( 'IB_Sections_Engine', 'get_instance' ) );
}

