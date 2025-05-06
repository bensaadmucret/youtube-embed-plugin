<?php

class YT_Embed_Shortcode {

    public function __construct() {
        add_shortcode('yt_embed', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
    }

    public function enqueue_public_assets() {
        // On pourrait ajouter une condition ici pour ne charger le CSS que si le shortcode est sur la page
        // Pour l'instant, on le charge sur toutes les pages publiques.
        if (!is_admin()) { // S'assurer que ce n'est chargé que sur le front-end
            wp_enqueue_style(
                'yt-embed-public-style',
                YT_EMBED_URL . 'assets/css/public.css',
                [], // Dépendances
                filemtime(YT_EMBED_PATH . 'assets/css/public.css') // Version basée sur la date de modification du fichier
            );
        }
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'channel' => '',
            'layout' => 'grid',
            'count' => 3
        ], $atts);

        if (empty($atts['channel'])) return '<p class="yt-embed-error-message">⛔️ Aucun ID de chaîne fourni.</p>';

        $videos = YT_Embed_API::fetch_latest_videos($atts['channel'], intval($atts['count']));
        if (empty($videos)) return '<p class="yt-embed-no-videos-message">😢 Aucune vidéo trouvée pour cette chaîne ou la clé API est invalide.</p>';

        ob_start();
        $layout_class = $atts['layout'] === 'list'
            ? 'yt-public-videos-list'
            : 'yt-public-videos-grid';

        echo '<div class="yt-public-videos-container '. esc_attr($layout_class) . '">';
        foreach ($videos as $video) {
            echo '<div class="yt-public-video-item">';
            echo '<img src="' . esc_url($video['thumbnail']) . '" alt="' . esc_attr($video['title']) . '">';
            echo '<h3 class="yt-video-title">' . esc_html($video['title']) . '</h3>';
            echo '<a class="yt-video-link" href="https://youtu.be/' . esc_attr($video['video_id']) . '" target="_blank" rel="noopener noreferrer">Voir sur YouTube</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
}
