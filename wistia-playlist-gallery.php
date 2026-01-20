<?php
/*
Plugin Name: Wistia Playlist Gallery
Description: Visualizza automaticamente una gallery di video partendo da una playlist Wistia.
Version: 1.0
Author: dway
*/

if (!defined('ABSPATH')) exit;

// === ADMIN MENU ===
add_action('admin_menu', function () {
    add_options_page(
        'Wistia Playlist Gallery',
        'Wistia Playlist',
        'manage_options',
        'wistia-playlist-gallery',
        'wpg_settings_page'
    );
});

add_action('admin_init', function () {
    register_setting('wpg_settings', 'wpg_api_token');
    register_setting('wpg_settings', 'wpg_playlist_id');
});

// === SETTINGS PAGE ===
function wpg_settings_page() {
    ?>
    <div class="wrap">
        <h1>Wistia Playlist Gallery</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wpg_settings'); ?>
            <table class="form-table">
                <tr>
                    <th>Wistia API Token</th>
                    <td><input type="text" name="wpg_api_token" value="<?php echo esc_attr(get_option('wpg_api_token')); ?>" size="50"></td>
                </tr>
                <tr>
                    <th>Playlist ID</th>
                    <td><input type="text" name="wpg_playlist_id" value="<?php echo esc_attr(get_option('wpg_playlist_id')); ?>"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// === FRONTEND ASSETS ===
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'wpg-style',
        plugin_dir_url(__FILE__) . 'assets/style.css'
    );
});

// === SHORTCODE ===
add_shortcode('wistia_playlist_gallery', function () {

    $token = get_option('wpg_api_token');
    $playlist_id = get_option('wpg_playlist_id');

    if (!$token || !$playlist_id) {
        return '<p>Configurazione Wistia mancante.</p>';
    }

    $response = wp_remote_get(
        "https://api.wistia.com/v1/playlists/{$playlist_id}.json",
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ]
    );

    if (is_wp_error($response)) {
        return '<p>Errore connessione Wistia.</p>';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($data['medias'])) {
        return '<p>Nessun video trovato.</p>';
    }

    $html = '<div class="wpg-gallery">';

    foreach ($data['medias'] as $media) {
        $hashed_id = esc_attr($media['hashed_id']);
        $name = esc_html($media['name']);

        $html .= "
        <div class='wpg-item'>
            <iframe 
                src='https://fast.wistia.net/embed/iframe/{$hashed_id}' 
                allowfullscreen 
                frameborder='0'>
            </iframe>
            <p>{$name}</p>
        </div>";
    }

    $html .= '</div>';

    return $html;
});
