<?php
/**
 * Fichier de désinstallation pour YouTube Embed Plugin
 *
 * Ce fichier est exécuté lors de la suppression du plugin.
 *
 * @package         YouTubeEmbedPlugin
 */

// Si WordPress n'est pas désinstallé directement, alors on quitte.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'yt_embed_channels';

// Supprimer la table de la base de données
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Supprimer les options du plugin (décommentez et adaptez si nécessaire)
// delete_option('yt_embed_api_key');
// delete_option('yt_embed_other_option');

