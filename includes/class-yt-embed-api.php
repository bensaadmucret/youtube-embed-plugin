<?php

class YT_Embed_API {

    public static function fetch_latest_videos($channel_id, $max = 3) {
        $api_key = null;

        // Essayer de charger la clé depuis le fichier de configuration
        if (defined('YT_EMBED_API_KEY')) {
            $api_key = YT_EMBED_API_KEY;
        } else {
            // Sinon, essayer de la charger depuis les options WordPress
            $api_key = get_option('yt_embed_api_key');
        }

        if (!$api_key) return [];

        $url = "https://www.googleapis.com/youtube/v3/search?key=$api_key&channelId=$channel_id&part=snippet,id&order=date&maxResults=$max";
        $response = wp_remote_get($url);
        if (is_wp_error($response)) return [];

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($data['items'])) return [];

        $videos = [];
        foreach ($data['items'] as $item) {
            if ($item['id']['kind'] === 'youtube#video') {
                $videos[] = [
                    'video_id' => $item['id']['videoId'],
                    'title' => $item['snippet']['title'],
                    'thumbnail' => $item['snippet']['thumbnails']['medium']['url']
                ];
            }
        }

        return $videos;
    }
}
