<?php
/*
Plugin Name: Wistia Playlist Gallery
Description: Visualizza automaticamente una gallery di video partendo da una playlist Wistia.
Version: 1.0
Author: DWAY Agency
*/

if (!defined('ABSPATH')) exit;

// === ADMIN MENU ===
add_action('admin_menu', function () {
    add_menu_page(
        'Wistia Playlist Gallery',
        'Wistia Playlist',
        'manage_options',
        'wistia-playlist-gallery',
        'wpg_main_page',
        'dashicons-video-alt3',
        30
    );
    
    add_submenu_page(
        'wistia-playlist-gallery',
        'Impostazioni',
        'Impostazioni',
        'manage_options',
        'wistia-playlist-gallery',
        'wpg_main_page'
    );
    
    add_submenu_page(
        'wistia-playlist-gallery',
        'Genera Shortcode',
        'Genera Shortcode',
        'manage_options',
        'wistia-playlist-generator',
        'wpg_generator_page'
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

// === MAIN PAGE WITH TABS ===
function wpg_main_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
    ?>
    <div class="wrap">
        <h1>Wistia Playlist Gallery</h1>
        
        <nav class="nav-tab-wrapper">
            <a href="?page=wistia-playlist-gallery&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                Impostazioni
            </a>
            <a href="?page=wistia-playlist-gallery&tab=generator" class="nav-tab <?php echo $active_tab === 'generator' ? 'nav-tab-active' : ''; ?>">
                Genera Shortcode
            </a>
        </nav>
        
        <div class="tab-content">
            <?php if ($active_tab === 'settings'): ?>
                <?php wpg_settings_tab(); ?>
            <?php else: ?>
                <?php wpg_generator_tab(); ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// === SETTINGS TAB ===
function wpg_settings_tab() {
    ?>
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
                    <p class="description">
                        <strong>Opzionale:</strong> Inserisci il tuo API token di Wistia per recuperare automaticamente i video dalle playlist del <strong>TUO account</strong>. 
                        Puoi trovarlo nelle impostazioni del tuo account Wistia. 
                        <br><strong>Nota importante:</strong> L'API token può accedere solo alle playlist del tuo account. 
                        Per mostrare video/playlist di altri account Wistia, usa l'opzione "Inserisci Manualmente gli ID dei Video" (non richiede API token).
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wpg_playlist_id">Playlist ID Predefinita</label></th>
                <td>
                    <input 
                        type="text" 
                        id="wpg_playlist_id"
                        name="wpg_playlist_id" 
                        value="<?php echo esc_attr(get_option('wpg_playlist_id')); ?>"
                        class="regular-text"
                    >
                    <p class="description">Inserisci l'ID della playlist Wistia predefinita (solo il numero, non l'URL completo). Questa verrà usata se non specifichi un playlist_id nello shortcode.</p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
    <?php
}

// === GENERATOR PAGE ===
function wpg_generator_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    wpg_generator_tab();
}

// === GENERATOR TAB ===
function wpg_generator_tab() {
    $token = get_option('wpg_api_token');
    $playlists = [];
    $selected_playlist_id = isset($_GET['playlist_id']) ? sanitize_text_field($_GET['playlist_id']) : '';
    $error = '';
    
    // Carica le playlist se abbiamo il token
    if (!empty($token)) {
        $response = wp_remote_get(
            'https://api.wistia.com/v1/playlists.json',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . sanitize_text_field($token)
                ],
                'timeout' => 15
            ]
        );
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            if (is_array($data)) {
                $playlists = $data;
            }
        } elseif (is_wp_error($response)) {
            $error = $response->get_error_message();
        }
    }
    ?>
    <div class="wpg-generator">
        <h2>Genera Shortcode per Playlist Wistia</h2>
        
        <?php if (empty($token)): ?>
            <div class="notice notice-info">
                <p>
                    <strong>Nota:</strong> Non hai configurato l'API Token. 
                    Puoi comunque generare shortcode inserendo manualmente gli ID dei video (Opzione 2) - funziona con video di qualsiasi account Wistia!
                    <br><a href="?page=wistia-playlist-gallery&tab=settings">Configura l'API Token</a> solo se vuoi caricare automaticamente le playlist del <strong>tuo account</strong>.
                </p>
            </div>
        <?php endif; ?>
        
        <div style="margin: 20px 0;">
            <h3>Opzione 1: Usa una Playlist del Tuo Account (richiede API Token)</h3>
            <p class="description" style="margin-bottom: 15px;">
                <strong>Limitazione:</strong> Questa opzione funziona solo con le playlist del tuo account Wistia. 
                Per mostrare playlist di altri account, usa l'<strong>Opzione 2</strong> (inserimento manuale video IDs).
            </p>
            <?php if (!empty($token)): ?>
            <form method="get" action="" id="wpg-generator-form">
                <input type="hidden" name="page" value="wistia-playlist-gallery">
                <input type="hidden" name="tab" value="generator">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wpg_select_playlist">Seleziona Playlist</label></th>
                        <td>
                            <?php if (!empty($playlists)): ?>
                                <select id="wpg_select_playlist" name="playlist_id" class="regular-text">
                                    <option value="">-- Seleziona una playlist --</option>
                                    <?php foreach ($playlists as $playlist): ?>
                                        <option value="<?php echo esc_attr($playlist['id']); ?>" 
                                                <?php selected($selected_playlist_id, $playlist['id']); ?>>
                                            <?php echo esc_html($playlist['name'] . ' (ID: ' . $playlist['id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input 
                                    type="text" 
                                    id="wpg_manual_playlist_id" 
                                    name="playlist_id" 
                                    value="<?php echo esc_attr($selected_playlist_id); ?>"
                                    class="regular-text"
                                    placeholder="Inserisci Playlist ID"
                                >
                                <?php if ($error): ?>
                                    <p class="description" style="color: #d63638;">
                                        Errore nel caricamento playlist: <?php echo esc_html($error); ?>. 
                                        Puoi inserire manualmente l'ID della playlist.
                                    </p>
                                <?php else: ?>
                                    <p class="description">Inserisci l'ID della playlist Wistia (solo il numero).</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Genera Shortcode', 'primary', 'generate', false); ?>
            </form>
        <?php else: ?>
            <p class="description" style="color: #d63638;">
                Per usare questa opzione, configura l'API Token nelle <a href="?page=wistia-playlist-gallery&tab=settings">Impostazioni</a>.
            </p>
        <?php endif; ?>
        
        <div style="margin: 30px 0; border-top: 1px solid #ddd; padding-top: 20px;">
            <h3>Opzione 2: Inserisci Manualmente gli ID dei Video</h3>
            <p class="description" style="margin-bottom: 15px;">
                <strong>Questa opzione funziona con video di qualsiasi account Wistia!</strong> 
                Non richiede API token. Inserisci gli hashed_id dei video Wistia separati da virgola (es: abc123,def456,ghi789).
                <br>Puoi trovare l'hashed_id nell'URL del video su Wistia: <code>https://wistia.com/medias/abc123</code> (dove "abc123" è l'hashed_id).
            </p>
            
            <?php
            $manual_video_ids = isset($_GET['video_ids']) ? sanitize_text_field($_GET['video_ids']) : '';
            ?>
            <form method="get" action="" id="wpg-manual-form">
                <input type="hidden" name="page" value="wistia-playlist-gallery">
                <input type="hidden" name="tab" value="generator">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wpg_video_ids">Video IDs (hashed_id)</label></th>
                        <td>
                            <textarea 
                                id="wpg_video_ids" 
                                name="video_ids" 
                                rows="3"
                                class="large-text"
                                placeholder="abc123,def456,ghi789"
                            ><?php echo esc_textarea($manual_video_ids); ?></textarea>
                            <p class="description">
                                Inserisci gli hashed_id dei video Wistia separati da virgola. 
                                Puoi trovare l'hashed_id nell'URL del video su Wistia (es: https://wistia.com/medias/abc123).
                                <br><strong>Vantaggio:</strong> Funziona con video di qualsiasi account Wistia, non solo il tuo!
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Genera Shortcode', 'primary', 'generate_manual', false); ?>
            </form>
            
            <?php if (!empty($manual_video_ids)): ?>
                <?php
                $video_ids_array = array_map('trim', explode(',', $manual_video_ids));
                $video_ids_array = array_filter($video_ids_array);
                $video_ids_string = implode(',', array_map('esc_attr', $video_ids_array));
                $shortcode_manual = '[wistia_playlist_gallery video_ids="' . $video_ids_string . '"]';
                ?>
                
                <div class="wpg-shortcode-result" style="margin-top: 30px; padding: 20px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                    <h3>Shortcode Generato</h3>
                    <div style="background: #fff; padding: 15px; border: 1px solid #c3c4c7; margin: 15px 0;">
                        <code id="wpg-shortcode-manual-text" style="font-size: 14px; font-weight: bold; color: #2271b1;">
                            <?php echo esc_html($shortcode_manual); ?>
                        </code>
                        <button type="button" class="button button-secondary" onclick="wpgCopyShortcodeManual()" style="margin-left: 10px;">
                            Copia Shortcode
                        </button>
                    </div>
                    <p><strong>Video inseriti:</strong> <?php echo count($video_ids_array); ?></p>
                    <p class="description" style="margin-top: 15px;">
                        Copia lo shortcode sopra e incollalo nel contenuto della tua pagina o post per visualizzare la gallery dei video.
                    </p>
                </div>
                
                <script>
                function wpgCopyShortcodeManual() {
                    var shortcode = document.getElementById('wpg-shortcode-manual-text').textContent;
                    navigator.clipboard.writeText(shortcode).then(function() {
                        alert('Shortcode copiato negli appunti!');
                    }, function() {
                        var textarea = document.createElement('textarea');
                        textarea.value = shortcode;
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                        alert('Shortcode copiato negli appunti!');
                    });
                }
                </script>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($token)): ?>
            
            <?php if (!empty($selected_playlist_id)): ?>
                <?php
                $shortcode = '[wistia_playlist_gallery playlist_id="' . esc_attr($selected_playlist_id) . '"]';
                $playlist_info = null;
                
                // Recupera info playlist
                $playlist_response = wp_remote_get(
                    "https://api.wistia.com/v1/playlists/{$selected_playlist_id}.json",
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . sanitize_text_field($token)
                        ],
                        'timeout' => 15
                    ]
                );
                
                if (!is_wp_error($playlist_response) && wp_remote_retrieve_response_code($playlist_response) === 200) {
                    $playlist_body = wp_remote_retrieve_body($playlist_response);
                    $playlist_info = json_decode($playlist_body, true);
                }
                ?>
                
                <div class="wpg-shortcode-result" style="margin-top: 30px; padding: 20px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                    <h3>Shortcode Generato</h3>
                    <div style="background: #fff; padding: 15px; border: 1px solid #c3c4c7; margin: 15px 0;">
                        <code id="wpg-shortcode-text" style="font-size: 14px; font-weight: bold; color: #2271b1;">
                            <?php echo esc_html($shortcode); ?>
                        </code>
                        <button type="button" class="button button-secondary" onclick="wpgCopyShortcode()" style="margin-left: 10px;">
                            Copia Shortcode
                        </button>
                    </div>
                    
                    <?php if ($playlist_info): ?>
                        <div style="margin-top: 15px;">
                            <p><strong>Playlist:</strong> <?php echo esc_html($playlist_info['name'] ?? 'N/A'); ?></p>
                            <p><strong>Video nella playlist:</strong> <?php echo isset($playlist_info['medias']) ? count($playlist_info['medias']) : 0; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <p class="description" style="margin-top: 15px;">
                        Copia lo shortcode sopra e incollalo nel contenuto della tua pagina o post per visualizzare la gallery della playlist.
                    </p>
                </div>
                
                <script>
                function wpgCopyShortcode() {
                    var shortcode = document.getElementById('wpg-shortcode-text').textContent;
                    navigator.clipboard.writeText(shortcode).then(function() {
                        alert('Shortcode copiato negli appunti!');
                    }, function() {
                        // Fallback per browser più vecchi
                        var textarea = document.createElement('textarea');
                        textarea.value = shortcode;
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                        alert('Shortcode copiato negli appunti!');
                    });
                }
                </script>
            <?php endif; ?>
        <?php endif; ?>
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
        'video_ids' => '',
    ], $atts);

    $token = get_option('wpg_api_token');
    $medias = [];

    // Opzione 1: Usa video_ids manuali (non richiede API token)
    if (!empty($atts['video_ids'])) {
        $video_ids = array_map('trim', explode(',', sanitize_text_field($atts['video_ids'])));
        $video_ids = array_filter($video_ids);
        
        if (!empty($video_ids)) {
            foreach ($video_ids as $hashed_id) {
                $hashed_id = sanitize_text_field($hashed_id);
                if (!empty($hashed_id)) {
                    $medias[] = [
                        'hashed_id' => $hashed_id,
                        'name' => 'Video ' . $hashed_id
                    ];
                }
            }
        }
    }
    // Opzione 2: Usa playlist_id (richiede API token)
    elseif (!empty($atts['playlist_id']) || !empty(get_option('wpg_playlist_id'))) {
        $playlist_id = !empty($atts['playlist_id']) ? sanitize_text_field($atts['playlist_id']) : get_option('wpg_playlist_id');
        
        if (empty($token)) {
            if (current_user_can('manage_options')) {
                return '<p class="wpg-error">Per usare una playlist, devi configurare l\'API Token nelle Impostazioni. Oppure usa l\'attributo video_ids per inserire manualmente gli ID dei video.</p>';
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
                $error_message = 'Errore API Wistia (codice ' . esc_html($response_code) . ').';
                if ($response_code === 404) {
                    $error_message .= ' La playlist non è stata trovata. Verifica che il Playlist ID sia corretto e che la playlist appartenga al tuo account Wistia.';
                } elseif ($response_code === 403) {
                    $error_message .= ' Accesso negato. La playlist potrebbe appartenere a un altro account. Per mostrare playlist di altri account, usa l\'attributo video_ids invece di playlist_id.';
                } else {
                    $error_message .= ' Verifica che il Playlist ID e l\'API Token siano corretti.';
                }
                return '<p class="wpg-error">' . $error_message . '</p>';
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

        $medias = $data['medias'];
    } else {
        if (current_user_can('manage_options')) {
            return '<p class="wpg-error">Specifica un playlist_id o video_ids nello shortcode. Esempio: [wistia_playlist_gallery video_ids="abc123,def456"]</p>';
        }
        return '';
    }

    if (empty($medias)) {
        if (current_user_can('manage_options')) {
            return '<p class="wpg-error">Nessun video trovato.</p>';
        }
        return '';
    }

    $html = '<div class="wpg-gallery">';

    foreach ($medias as $media) {
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
