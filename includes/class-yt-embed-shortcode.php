<?php

class YT_Embed_Shortcode {

    public function __construct() {
        add_shortcode('yt_embed', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);

        // Actions AJAX pour charger plus de vidéos
        add_action('wp_ajax_yt_embed_load_more_videos', [$this, 'handle_load_more_videos']);
        add_action('wp_ajax_nopriv_yt_embed_load_more_videos', [$this, 'handle_load_more_videos']);
    }

    public function enqueue_public_assets() {
        // S'assurer que ce n'est chargé que sur le front-end
        if (is_admin()) {
            return;
        }

        // Enqueue le style public
        wp_enqueue_style(
            'yt-embed-public-style',
            YT_EMBED_URL . 'assets/css/public.css',
            [], // Dépendances
            filemtime(YT_EMBED_PATH . 'assets/css/public.css') // Version
        );

        // Enqueue le script public
        wp_enqueue_script(
            'yt-embed-public-script',
            YT_EMBED_URL . 'assets/js/public.js',
            [], // Dépendances (ex: ['jquery'] si on utilisait jQuery)
            filemtime(YT_EMBED_PATH . 'assets/js/public.js'), // Version
            true // Charger dans le footer
        );

        // Passer des variables PHP au script JavaScript
        wp_localize_script(
            'yt-embed-public-script', // Le handle du script auquel on attache les données
            'ytEmbedPublic',          // Nom de l'objet JavaScript qui contiendra les données
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('yt_embed_load_more_nonce') // Action de nonce pour la vérification
            ]
        );
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'channel' => '',
            'layout' => 'grid',
            'count' => 3, // Nombre d'éléments par page
        ], $atts);

        if (empty($atts['channel'])) {
            return '<p class="yt-embed-error-message">⛔️ Aucun ID de chaîne fourni.</p>';
        }

        // Récupération initiale des vidéos (première page)
        $api_response = YT_Embed_API::fetch_latest_videos($atts['channel'], intval($atts['count']));
        $videos = $api_response['videos'];
        $next_page_token = $api_response['nextPageToken'];

        if (empty($videos)) {
            return '<p class="yt-embed-no-videos-message">😢 Aucune vidéo trouvée pour cette chaîne ou la clé API est invalide.</p>';
        }

        ob_start();
        $layout_class = $atts['layout'] === 'list'
            ? 'yt-public-videos-list'
            : 'yt-public-videos-grid';

        // L'ID du conteneur principal est important pour le ciblage par JavaScript
        $container_id = 'yt-embed-container-' . uniqid();
        echo '<div id="' . esc_attr($container_id) . '" class="yt-embed-main-container">'; // Conteneur global

        echo '<div class="yt-public-videos-container '. esc_attr($layout_class) . '">';
        // Boucle pour afficher les vidéos (peut être externalisée dans une méthode privée plus tard pour réutilisation par AJAX)
        foreach ($videos as $video) {
            $this->render_single_video_item($video);
        }
        echo '</div>'; // Fin de yt-public-videos-container

        // Afficher le bouton "Charger plus" si un nextPageToken existe
        if ($next_page_token) {
            echo '<div class="yt-embed-load-more-wrapper">';
            echo '<button class="yt-embed-load-more-button" ';
            echo 'data-channel-id="' . esc_attr($atts['channel']) . '" ';
            echo 'data-count="' . intval($atts['count']) . '" ';
            echo 'data-layout="' . esc_attr($atts['layout']) . '" '; // On aura besoin du layout pour rendre les nouveaux items
            echo 'data-next-page-token="' . esc_attr($next_page_token) . '" ';
            echo 'data-container-id="' . esc_attr($container_id) . '"'; // Pour que le JS sache où ajouter les vidéos
            echo '>Charger plus de vidéos</button>';
            echo '</div>';
        }

        echo '</div>'; // Fin du conteneur global
        return ob_get_clean();
    }

    /**
     * Affiche un seul élément vidéo.
     * Utilisé par render_shortcode et potentiellement par la réponse AJAX.
     *
     * @param array $video Données de la vidéo.
     */
    private function render_single_video_item($video) {
        echo '<div class="yt-public-video-item">';
        echo '<div class="yt-video-player-wrapper">';
        echo '<iframe ';
        echo 'src="https://www.youtube.com/embed/' . esc_attr($video['video_id']) . '" ';
        echo 'title="' . esc_attr($video['title']) . '" ';
        echo 'frameborder="0" ';
        echo 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" ';
        echo 'allowfullscreen';
        echo '></iframe>';
        echo '</div>';
        echo '<h3 class="yt-video-title">' . esc_html($video['title']) . '</h3>';
        echo '</div>';
    }

    /**
     * Gère la requête AJAX pour charger plus de vidéos.
     */
    public function handle_load_more_videos() {
        // Vérifier le nonce pour la sécurité
        check_ajax_referer('yt_embed_load_more_nonce', '_ajax_nonce');

        // Récupérer et valider les paramètres
        $channel_id = isset($_POST['channel_id']) ? sanitize_text_field($_POST['channel_id']) : null;
        $count = isset($_POST['count']) ? intval($_POST['count']) : 3;
        $page_token = isset($_POST['page_token']) ? sanitize_text_field($_POST['page_token']) : null;
        // $layout = isset($_POST['layout']) ? sanitize_text_field($_POST['layout']) : 'grid'; // Layout n'est pas utilisé ici pour le rendu, mais on pourrait le passer si on faisait un rendu différent

        if (!$channel_id || !$page_token) {
            wp_send_json_error(['message' => 'Paramètres manquants ou invalides.']);
            return;
        }

        $api_response = YT_Embed_API::fetch_latest_videos($channel_id, $count, $page_token);
        $videos = $api_response['videos'];
        $next_page_token = $api_response['nextPageToken'];

        if (empty($videos)) {
            wp_send_json_success(['html' => '', 'nextPageToken' => null]); // Pas d'erreur, mais pas de vidéos, donc pas de nextPageToken non plus
            return;
        }

        ob_start();
        foreach ($videos as $video) {
            $this->render_single_video_item($video);
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'nextPageToken' => $next_page_token
        ]);
    }
}
