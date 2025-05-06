<?php

class YT_Embed_Shortcode {

    public function __construct() {
        add_shortcode('yt_embed', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'channel' => '',
            'layout' => 'grid',
            'count' => 3
        ], $atts);

        if (empty($atts['channel'])) return '<p>⛔️ Aucun ID de chaîne fourni.</p>';

        $videos = YT_Embed_API::fetch_latest_videos($atts['channel'], intval($atts['count']));
        if (empty($videos)) return '<p>😢 Aucune vidéo trouvée.</p>';

        ob_start();
        $layout = $atts['layout'] === 'list'
            ? 'flex flex-col gap-4'
            : 'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4';

        echo '<div class="' . esc_attr($layout) . '">';
        foreach ($videos as $video) {
            echo '<div class="border rounded bg-white shadow p-2">';
            echo '<img src="' . esc_url($video['thumbnail']) . '" alt="">';
            echo '<p class="font-semibold">' . esc_html($video['title']) . '</p>';
            echo '<a class="text-blue-600" href="https://youtu.be/' . esc_attr($video['video_id']) . '" target="_blank">Voir sur YouTube</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
}
