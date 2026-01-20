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
    register_setting('wpg_settings', 'wpg_api_token', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ]);
    register_setting('wpg_settings', 'wpg_playlist_id', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ]);
});

// === SETTINGS PAGE ===
function wpg_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>Wistia Playlist Gallery</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wpg_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wpg_api_token">Wistia API Token</label></th>
                    <td>
                        <input 
                            type="text" 
                            id="wpg_api_token"
                            name="wpg_api_token" 
                            value="<?php echo esc_attr(get_option('wpg_api_token')); ?>" 
                            size="50"
                            class="regular-text"
                        >
                        <p class="description">Inserisci il tuo API token di Wistia. Puoi trovarlo nelle impostazioni del tuo account Wistia.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wpg_playlist_id">Playlist ID</label></th>
                    <td>
                        <input 
                            type="text" 
                            id="wpg_playlist_id"
                            name="wpg_playlist_id" 
                            value="<?php echo esc_attr(get_option('wpg_playlist_id')); ?>"
                            class="regular-text"
                        >
                        <p class="description">Inserisci l'ID della playlist Wistia (solo il numero, non l'URL completo).</p>
                    </td>
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
add_shortcode('wistia_playlist_gallery', function ($atts) {
    $atts = shortcode_atts([
        'playlist_id' => '',
    ], $atts);

    $token = get_option('wpg_api_token');
    $playlist_id = !empty($atts['playlist_id']) ? sanitize_text_field($atts['playlist_id']) : get_option('wpg_playlist_id');

    if (empty($token) || empty($playlist_id)) {
        if (current_user_can('manage_options')) {
            return '<p class="wpg-error">Configurazione Wistia mancante. Vai in Impostazioni > Wistia Playlist per configurare il plugin.</p>';
        }
        return '';
    }

    // Sanitizza il playlist_id per l'URL
    $playlist_id = sanitize_text_field($playlist_id);
    $api_url = esc_url_raw("https://api.wistia.com/v1/playlists/{$playlist_id}.json");

    $response = wp_remote_get(
        $api_url,
        [
            'headers' => [
                'Authorization' => 'Bearer ' . sanitize_text_field($token)
            ],
            'timeout' => 15
        ]
    );

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        if (current_user_can('manage_options')) {
            return '<p class="wpg-error">Errore connessione Wistia: ' . esc_html($error_message) . '</p>';
        }
        return '';
    }

    $response_code = wp_remote_retrieve_response_code($response);
    
    if ($response_code !== 200) {
        if (current_user_can('manage_options')) {
            $error_body = wp_remote_retrieve_body($response);
            return '<p class="wpg-error">Errore API Wistia (codice ' . esc_html($response_code) . '). Verifica che il Playlist ID e l\'API Token siano corretti.</p>';
        }
        return '';
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        if (current_user_can('manage_options')) {
            return '<p class="wpg-error">Errore nel parsing della risposta Wistia.</p>';
        }
        return '';
    }

    if (!isset($data['medias']) || !is_array($data['medias']) || empty($data['medias'])) {
        if (current_user_can('manage_options')) {
            return '<p class="wpg-error">Nessun video trovato nella playlist. Verifica che la playlist contenga dei video.</p>';
        }
        return '';
    }

    $html = '<div class="wpg-gallery">';

    foreach ($data['medias'] as $media) {
        if (!isset($media['hashed_id']) || empty($media['hashed_id'])) {
            continue;
        }

        $hashed_id = esc_attr($media['hashed_id']);
        $name = isset($media['name']) ? esc_html($media['name']) : 'Video senza titolo';

        $html .= '<div class="wpg-item">';
        $html .= '<div class="wpg-video-wrapper">';
        $html .= '<iframe 
            src="https://fast.wistia.net/embed/iframe/' . $hashed_id . '?videoFoam=true" 
            allow="autoplay; fullscreen" 
            allowfullscreen 
            frameborder="0"
            loading="lazy"
            title="' . esc_attr($name) . '">
        </iframe>';
        $html .= '</div>';
        if (!empty($name)) {
            $html .= '<p class="wpg-video-title">' . $name . '</p>';
        }
        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
});
