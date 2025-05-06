<?php

class YT_Embed_API {

    public static function fetch_latest_videos($channel_id, $max = 3, $page_token = null) {
        // Définir la clé unique du transient en incluant le page_token
        $transient_key = 'yt_embed_videos_' . md5(esc_attr($channel_id) . '_' . intval($max) . '_' . esc_attr($page_token ?? ''));

        // Essayer de récupérer les données depuis le cache
        $cached_data = get_transient($transient_key);
        if (false !== $cached_data && is_array($cached_data)) {
            return $cached_data; // Retourner les données mises en cache (qui incluent videos et nextPageToken)
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

        $default_return = ['videos' => [], 'nextPageToken' => null];

        if (!$api_key) return $default_return;

        $url = "https://www.googleapis.com/youtube/v3/search?key={$api_key}&channelId={$channel_id}&part=snippet,id&order=date&maxResults={$max}";
        if ($page_token) {
            $url .= "&pageToken=" . esc_attr($page_token);
        }

        $response = wp_remote_get($url);

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            // En cas d'erreur ou de réponse non-200, retourner la valeur par défaut et ne pas mettre en cache une erreur agressivement
            // set_transient($transient_key, $default_return, 5 * MINUTE_IN_SECONDS); // Cache l'erreur pour 5 minutes
            return $default_return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['items'])) { // Note: empty($data['items']) est aussi une condition valide si des items vides sont possibles
            // Si aucune vidéo n'est retournée, mettre en cache un résultat vide pour éviter des appels répétés
            set_transient($transient_key, $default_return, 1 * HOUR_IN_SECONDS); // Cache un résultat vide pour 1 heure
            return $default_return;
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

        $next_page_token = isset($data['nextPageToken']) ? $data['nextPageToken'] : null;
        $return_data = ['videos' => $videos, 'nextPageToken' => $next_page_token];

        // Mettre les données récupérées en cache
        set_transient($transient_key, $return_data, 1 * HOUR_IN_SECONDS); // Cache pour 1 heure

        return $return_data;
    }
}
