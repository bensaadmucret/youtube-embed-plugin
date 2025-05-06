<?php
/**
 * Plugin Name: YouTube Embed Plugin
 * Description: Intègre les dernières vidéos YouTube dans WordPress via shortcode.
 * Version: 1.0
 * Author: Bensaad Mohammed
 * Author URI: https://www.linkedin.com/in/mohammed-bensaad-developpeur
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if (!defined('ABSPATH')) exit;

define('YT_EMBED_PATH', plugin_dir_path(__FILE__));
define('YT_EMBED_URL', plugin_dir_url(__FILE__));

require_once YT_EMBED_PATH . 'includes/class-yt-embed-admin.php';
require_once YT_EMBED_PATH . 'includes/class-yt-embed-api.php';
require_once YT_EMBED_PATH . 'includes/class-yt-embed-shortcode.php';

function yt_embed_init() {
    new YT_Embed_Admin();
    new YT_Embed_Shortcode();
}
add_action('plugins_loaded', 'yt_embed_init');

// 📦 Création de la table à l'activation
register_activation_hook(__FILE__, 'yt_embed_create_table');
function yt_embed_create_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'yt_embed_channels';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        channel_name varchar(255) NOT NULL,
        channel_id varchar(255) NOT NULL,
        wp_category_id bigint(20),
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// suppression de la table à la suppression du plugin
register_uninstall_hook(__FILE__, 'yt_embed_delete_table');
function yt_embed_delete_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'yt_embed_channels';
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

