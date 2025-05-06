<?php

class YT_Embed_API {

    public static function fetch_latest_videos($channel_id, $max = 3) {
        // Définir la clé unique du transient
        $transient_key = 'yt_embed_videos_' . esc_attr($channel_id) . '_' . intval($max);

        // Essayer de récupérer les données depuis le cache
        $cached_videos = get_transient($transient_key);
        if (false !== $cached_videos) {
            return $cached_videos; // Retourner les données mises en cache
        }

        // Si pas de cache, continuer pour récupérer depuis l'API
        $api_key = null;

        // Essayer de charger la clé depuis le fichier de configuration
        if (defined('YT_EMBED_API_KEY')) {
            $api_key = YT_EMBED_API_KEY;
        } else {
            // Sinon, essayer de la charger depuis les options WordPress
            $api_key = get_option('yt_embed_api_key');
        }

        if (!$api_key) return [];

        $url = "https://www.googleapis.com/youtube/v3/search?key={$api_key}&channelId={$channel_id}&part=snippet,id&order=date&maxResults={$max}";
        $response = wp_remote_get($url);

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            // En cas d'erreur ou de réponse non-200, retourner un tableau vide et ne pas mettre en cache une erreur
            // On pourrait vouloir mettre en cache une erreur pendant une courte période pour éviter de marteler l'API
            // set_transient($transient_key, [], 5 * MINUTE_IN_SECONDS); // Exemple: cache l'erreur pour 5 minutes
            return [];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($data['items']) || empty($data['items'])) {
            // Si aucune vidéo n'est retournée, mettre en cache un résultat vide pour éviter des appels répétés
            set_transient($transient_key, [], 1 * HOUR_IN_SECONDS); // Cache un résultat vide pour 1 heure
            return [];
        }

        $videos = [];
        foreach ($data['items'] as $item) {
            if (isset($item['id']['kind']) && $item['id']['kind'] === 'youtube#video') {
                $videos[] = [
                    'video_id' => $item['id']['videoId'],
                    'title'    => $item['snippet']['title'],
                    'thumbnail' => $item['snippet']['thumbnails']['medium']['url']
                ];
            }
        }

        // Mettre les vidéos récupérées en cache
        set_transient($transient_key, $videos, 1 * HOUR_IN_SECONDS); // Cache pour 1 heure

        return $videos;
    }
}
