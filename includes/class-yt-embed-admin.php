<?php

class YT_Embed_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_plugin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_plugin_menu() {
        add_menu_page(
            'YouTube Embed',
            'YouTube Embed',
            'manage_options',
            'yt-embed-settings',
            [$this, 'render_settings_page'],
            'dashicons-video-alt3'
        );
    }

    public function enqueue_admin_assets($hook) {
    // On charge uniquement sur notre page admin
    if ($hook !== 'toplevel_page_yt-embed-settings') return;

    // CSS custom
    wp_enqueue_style('yt-embed-admin-style', YT_EMBED_URL . 'assets/css/admin.css');

    // JS admin
    wp_enqueue_script('yt-embed-admin-script', YT_EMBED_URL . 'assets/js/admin.js', [], false, true);
}

    public function render_settings_page() {
        $this->handle_form_submission();
        global $wpdb;
        $table = $wpdb->prefix . 'yt_embed_channels';
        $channels = $wpdb->get_results("SELECT * FROM $table");
        $api_key_from_config = defined('YT_EMBED_API_KEY');
        $current_api_key = $api_key_from_config ? '******** (Définie dans yt-embed-config.php)' : get_option('yt_embed_api_key');
        ?>

        <!-- Clé API -->
        <form method="post" class="mb-6">
            <?php wp_nonce_field('yt_embed_save_api_key_action', 'yt_embed_save_api_key_nonce'); ?>
            <h2>🔑 Clé API YouTube</h2>
            <?php if ($api_key_from_config): ?>
                <div class="p-3 mb-3 text-sm text-blue-700 bg-blue-100 rounded-lg" role="alert">
                    <span class="font-medium">Info:</span> La clé API est actuellement définie via le fichier <code>yt-embed-config.php</code> et ne peut pas être modifiée ici.
                </div>
            <?php endif; ?>
            <input type="text" name="yt_api_key" value="<?= esc_attr($current_api_key) ?>" class="border p-2 w-full" <?= $api_key_from_config ? 'disabled' : '' ?> required>
            <button type="submit" name="save_api_key" class="bg-green-600 text-white px-4 py-2 rounded mt-2" <?= $api_key_from_config ? 'disabled' : '' ?>>Enregistrer</button>
        </form>

        <!-- Ajout de chaîne -->
        <form method="post" class="mb-6">
            <?php wp_nonce_field('yt_embed_add_channel_action', 'yt_embed_add_channel_nonce'); ?>
            <h2>➕ Ajouter une chaîne</h2>
            <div class="flex flex-wrap gap-4">
                <input type="text" name="channel_name" placeholder="Nom" required class="border p-2 w-1/4">
                <input type="text" name="channel_id" placeholder="ID de la chaîne" required class="border p-2 w-1/4">
                <select name="wp_category_id" class="border p-2 w-1/4">
                    <?php foreach (get_categories(['hide_empty' => false]) as $cat): ?>
                        <option value="<?= $cat->term_id ?>"><?= esc_html($cat->name) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" name="add_channel" value="Ajouter" class="bg-blue-600 text-white px-4 py-2 rounded">
            </div>
        </form>

        <!-- Liste -->
        <h2>📺 Chaînes enregistrées</h2>
        <?php foreach ($channels as $channel):
            $videos = YT_Embed_API::fetch_latest_videos($channel->channel_id); ?>
            <div class="mb-6">
                <strong><?= esc_html($channel->channel_name) ?></strong>
                <form method="post">
                    <?php wp_nonce_field('yt_embed_delete_channel_action_' . $channel->id, 'yt_embed_delete_channel_nonce'); ?>
                    <input type="hidden" name="delete_channel_id" value="<?= $channel->id ?>">
                    <button type="submit" class="text-red-600">🗑 Supprimer</button>
                </form>
                <div class="yt-videos-grid mt-2">
                    <?php foreach ($videos as $video): ?>
                        <div class="border p-2 bg-white shadow">
                            <img src="<?= esc_url($video['thumbnail']) ?>" alt="">
                            <p class="font-medium"><?= esc_html($video['title']) ?></p>
                            <a href="https://youtu.be/<?= $video['video_id'] ?>" class="text-blue-600">Voir sur YouTube</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach;
    }

    private function handle_form_submission() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes pour effectuer cette action.', 'youtube-embed-plugin'));
        }
        global $wpdb;
        $table = $wpdb->prefix . 'yt_embed_channels';

        if (isset($_POST['save_api_key'])) {
            // On ne sauvegarde que si la clé n'est PAS définie par le fichier de config
            if (!defined('YT_EMBED_API_KEY')) {
                check_admin_referer('yt_embed_save_api_key_action', 'yt_embed_save_api_key_nonce');
                update_option('yt_embed_api_key', sanitize_text_field($_POST['yt_api_key']));
            }
        }

        if (isset($_POST['add_channel'])) {
            check_admin_referer('yt_embed_add_channel_action', 'yt_embed_add_channel_nonce');
            $wpdb->insert($table, [
                'channel_name' => sanitize_text_field($_POST['channel_name']),
                'channel_id' => sanitize_text_field($_POST['channel_id']),
                'wp_category_id' => intval($_POST['wp_category_id'])
            ]);
        }

        if (isset($_POST['delete_channel_id'])) {
            check_admin_referer('yt_embed_delete_channel_action_' . $_POST['delete_channel_id'], 'yt_embed_delete_channel_nonce');
            $wpdb->delete($table, ['id' => intval($_POST['delete_channel_id'])]);
        }
    }
}
