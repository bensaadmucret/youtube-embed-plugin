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

    public function enqueue_admin_assets() {
        wp_enqueue_style('yt-embed-tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');
    }

    public function render_settings_page() {
        $this->handle_form_submission();
        global $wpdb;
        $table = $wpdb->prefix . 'yt_embed_channels';
        $channels = $wpdb->get_results("SELECT * FROM $table");
        ?>

        <!-- Clé API -->
        <form method="post" class="mb-6">
            <?php wp_nonce_field('yt_embed_save_api_key_action', 'yt_embed_save_api_key_nonce'); ?>
            <h2>🔑 Clé API YouTube</h2>
            <input type="text" name="yt_api_key" value="<?= esc_attr(get_option('yt_embed_api_key')) ?>" class="border p-2 w-full" required>
            <button type="submit" name="save_api_key" class="bg-green-600 text-white px-4 py-2 rounded mt-2">Enregistrer</button>
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
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mt-2">
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
            check_admin_referer('yt_embed_save_api_key_action', 'yt_embed_save_api_key_nonce');
            update_option('yt_embed_api_key', sanitize_text_field($_POST['yt_api_key']));
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
