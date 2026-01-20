<?php
/*
Plugin Name: Wistia Channel Gallery
Description: Visualizza channel e gallery di video Wistia. Supporta channel, video singoli e gallery personalizzate.
Version: 1.0
Author: DWAY Agency
*/

if (!defined('ABSPATH')) exit;

// === ADMIN MENU ===
add_action('admin_menu', function () {
    add_menu_page(
        'Wistia Channel Gallery',
        'Wistia Channel',
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
    register_setting('wpg_settings', 'wpg_channel_id', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ]);
    register_setting('wpg_settings', 'wpg_api_token', [
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
        <h1>Wistia Channel Gallery</h1>
        
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
                <th scope="row"><label for="wpg_channel_id">Channel ID Predefinito</label></th>
                <td>
                    <input 
                        type="text" 
                        id="wpg_channel_id"
                        name="wpg_channel_id" 
                        value="<?php echo esc_attr(get_option('wpg_channel_id')); ?>"
                        class="regular-text"
                        placeholder="bkfd9ulu5l"
                    >
                    <p class="description">
                        Inserisci il Channel ID Wistia predefinito (es: bkfd9ulu5l dall'URL https://fast.wistia.com/embed/channel/bkfd9ulu5l). 
                        Questo verrà usato se non specifichi un channel_id nello shortcode.
                    </p>
                </td>
            </tr>
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
                        <strong>Richiesto per i channel:</strong> Inserisci il tuo API token di Wistia per ottenere automaticamente i video dai channel e creare la gallery. 
                        Puoi trovarlo nelle impostazioni del tuo account Wistia.
                        <br><strong>Nota:</strong> L'API token può accedere solo ai channel del tuo account. Per channel di altri account, usa video_ids manualmente.
                    </p>
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
    ?>
    <div class="wpg-generator">
        <h2>Genera Shortcode per Channel Wistia</h2>
        
        <div style="margin: 20px 0;">
            <h3>Opzione 1: Usa un Channel Wistia (richiede API Token)</h3>
            <p class="description" style="margin-bottom: 15px;">
                <strong>Nota:</strong> Per creare una gallery dai video del channel, serve l'API Token. 
                Il plugin recupererà automaticamente tutti i video del channel e creerà una gallery che si aggiorna automaticamente.
                <br>Inserisci il Channel ID (es: bkfd9ulu5l dall'URL https://fast.wistia.com/embed/channel/bkfd9ulu5l).
            </p>
            
            <?php
            $channel_id = isset($_GET['channel_id']) ? sanitize_text_field($_GET['channel_id']) : '';
            ?>
            <form method="get" action="" id="wpg-channel-form">
                <input type="hidden" name="page" value="wistia-playlist-gallery">
                <input type="hidden" name="tab" value="generator">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="wpg_channel_id">Channel ID</label></th>
                        <td>
                            <input 
                                type="text" 
                                id="wpg_channel_id" 
                                name="channel_id" 
                                value="<?php echo esc_attr($channel_id); ?>"
                                class="regular-text"
                                placeholder="bkfd9ulu5l"
                            >
                            <p class="description">
                                Inserisci il Channel ID di Wistia. Puoi trovarlo nell'URL del channel: 
                                <code>https://fast.wistia.com/embed/channel/bkfd9ulu5l</code> (dove "bkfd9ulu5l" è il Channel ID).
                                <br><strong>Vantaggio:</strong> Funziona con channel di qualsiasi account Wistia, non solo il tuo!
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Genera Shortcode', 'primary', 'generate_channel', false); ?>
            </form>
            
            <?php if (!empty($channel_id)): ?>
                <?php
                $shortcode_channel = '[wistia_playlist_gallery channel_id="' . esc_attr($channel_id) . '"]';
                ?>
                
                <div class="wpg-shortcode-result" style="margin-top: 30px; padding: 20px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                    <h3>Shortcode Generato</h3>
                    <div style="background: #fff; padding: 15px; border: 1px solid #c3c4c7; margin: 15px 0;">
                        <code id="wpg-shortcode-channel-text" style="font-size: 14px; font-weight: bold; color: #2271b1;">
                            <?php echo esc_html($shortcode_channel); ?>
                        </code>
                        <button type="button" class="button button-secondary" onclick="wpgCopyShortcodeChannel()" style="margin-left: 10px;">
                            Copia Shortcode
                        </button>
                    </div>
                    <p class="description" style="margin-top: 15px;">
                        Copia lo shortcode sopra e incollalo nel contenuto della tua pagina o post per visualizzare il channel Wistia.
                    </p>
                </div>
                
                <script>
                function wpgCopyShortcodeChannel() {
                    var shortcode = document.getElementById('wpg-shortcode-channel-text').textContent;
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
    </div>
    <?php
}

// === FRONTEND ASSETS ===
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'wpg-style',
        plugin_dir_url(__FILE__) . 'assets/style.css'
    );
    wp_enqueue_script(
        'wpg-script',
        plugin_dir_url(__FILE__) . 'assets/wpg-script.js',
        [],
        '1.0',
        true
    );
});

// === SHORTCODE ===
add_shortcode('wistia_playlist_gallery', function ($atts) {
    $atts = shortcode_atts([
        'video_ids' => '',
        'channel_id' => '',
    ], $atts);

    $medias = [];

    // Opzione 1: Usa channel_id
    if (!empty($atts['channel_id'])) {
        $channel_id = sanitize_text_field($atts['channel_id']);
        $token = get_option('wpg_api_token');
        
        // Prova prima con l'API se abbiamo il token
        if (!empty($token)) {
            // Secondo la documentazione Wistia, usa /medias.json con filtro channel_id
            // Supporta anche paging: per_page (max 100) e page
            $api_url = esc_url_raw("https://api.wistia.com/v1/medias.json?channel_id={$channel_id}&per_page=100&page=1");
            
            $response = wp_remote_get(
                $api_url,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . sanitize_text_field($token)
                    ],
                    'timeout' => 15
                ]
            );
            
            if (!is_wp_error($response)) {
                $response_code = wp_remote_retrieve_response_code($response);
                
                if ($response_code === 200) {
                    $body = wp_remote_retrieve_body($response);
                    $medias_data = json_decode($body, true);
                    
                    // La risposta dovrebbe essere un array di media
                    if (is_array($medias_data) && !empty($medias_data)) {
                        $medias = $medias_data;
                        
                        // Se ci sono più di 100 video, recupera anche le pagine successive
                        if (count($medias_data) === 100) {
                            $page = 2;
                            while (true) {
                                $next_url = esc_url_raw("https://api.wistia.com/v1/medias.json?channel_id={$channel_id}&per_page=100&page={$page}");
                                $next_response = wp_remote_get(
                                    $next_url,
                                    [
                                        'headers' => [
                                            'Authorization' => 'Bearer ' . sanitize_text_field($token)
                                        ],
                                        'timeout' => 15
                                    ]
                                );
                                
                                if (is_wp_error($next_response) || wp_remote_retrieve_response_code($next_response) !== 200) {
                                    break;
                                }
                                
                                $next_body = wp_remote_retrieve_body($next_response);
                                $next_medias = json_decode($next_body, true);
                                
                                if (is_array($next_medias) && !empty($next_medias)) {
                                    $medias = array_merge($medias, $next_medias);
                                    if (count($next_medias) < 100) {
                                        break; // Ultima pagina
                                    }
                                    $page++;
                                } else {
                                    break;
                                }
                            }
                        }
                    }
                } elseif ($response_code === 403) {
                    // Accesso negato
                    if (current_user_can('manage_options')) {
                        return '<p class="wpg-error">Accesso negato al channel. Verifica che il channel sia "Unlocked" nelle impostazioni Wistia e che l\'API token abbia i permessi necessari.</p>';
                    }
                    return '';
                } elseif ($response_code === 404) {
                    // Channel non trovato
                    if (current_user_can('manage_options')) {
                        return '<p class="wpg-error">Channel non trovato. Verifica che il Channel ID sia corretto e che il channel appartenga al tuo account Wistia.</p>';
                    }
                    return '';
                }
            }
        }
        
        // Se non abbiamo ottenuto i video tramite API, usa JavaScript per estrarli dal channel embed
        if (empty($medias)) {
            // Usa JavaScript per estrarre i video dal channel (funziona anche senza API token)
            $html = '<div class="wpg-channel-container" data-wpg-channel-id="' . esc_attr($channel_id) . '">';
            $html .= '<div class="wpg-loading">Caricamento video dal channel...</div>';
            $html .= '</div>';
            return $html;
        }
    }
    // Opzione 2: Usa video_ids manuali (non richiede API token)
    elseif (!empty($atts['video_ids'])) {
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
    } else {
        // Usa channel_id predefinito se non specificato
        $default_channel_id = get_option('wpg_channel_id');
        if (!empty($default_channel_id)) {
            $channel_id = sanitize_text_field($default_channel_id);
            $token = get_option('wpg_api_token');
            
            // Prova a ottenere i video del channel tramite API
            if (!empty($token)) {
                $api_url = esc_url_raw("https://api.wistia.com/v1/channels/{$channel_id}.json");
                
                $response = wp_remote_get(
                    $api_url,
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . sanitize_text_field($token)
                        ],
                        'timeout' => 15
                    ]
                );
                
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $body = wp_remote_retrieve_body($response);
                    $channel_data = json_decode($body, true);
                    
                    if (isset($channel_data['medias']) && is_array($channel_data['medias']) && !empty($channel_data['medias'])) {
                        $medias = $channel_data['medias'];
                    } elseif (isset($channel_data['media']) && is_array($channel_data['media']) && !empty($channel_data['media'])) {
                        $medias = $channel_data['media'];
                    }
                }
            }
            
            // Se non abbiamo i video, mostra errore
            if (empty($medias)) {
                if (current_user_can('manage_options')) {
                    if (empty($token)) {
                        return '<p class="wpg-error">Per usare il channel predefinito, configura l\'API Token nelle Impostazioni.</p>';
                    } else {
                        return '<p class="wpg-error">Impossibile ottenere i video dal channel predefinito. Verifica le impostazioni.</p>';
                    }
                }
                return '';
            }
        } else {
            if (current_user_can('manage_options')) {
                return '<p class="wpg-error">Specifica un channel_id o video_ids nello shortcode. Esempi:<br>
                - [wistia_playlist_gallery channel_id="bkfd9ulu5l"]<br>
                - [wistia_playlist_gallery video_ids="abc123,def456"]</p>';
            }
            return '';
        }
    }

    if (empty($medias)) {
        if (current_user_can('manage_options')) {
            return '<p class="wpg-error">Nessun video trovato.</p>';
        }
        return '';
    }

    // Separare il primo video (principale) dagli altri (gallery)
    $main_video = array_shift($medias);
    $gallery_videos = $medias;

    $html = '<div class="wpg-container">';
    
    // Video principale (grande)
    if (!empty($main_video) && isset($main_video['hashed_id'])) {
        $main_hashed_id = esc_attr($main_video['hashed_id']);
        $main_name = isset($main_video['name']) ? esc_html($main_video['name']) : 'Video principale';
        
        $html .= '<div class="wpg-main-video">';
        $html .= '<div class="wpg-main-video-wrapper">';
        $html .= '<iframe 
            src="https://fast.wistia.net/embed/iframe/' . $main_hashed_id . '?videoFoam=true" 
            allow="autoplay; fullscreen" 
            allowfullscreen 
            frameborder="0"
            loading="lazy"
            title="' . esc_attr($main_name) . '">
        </iframe>';
        $html .= '</div>';
        if (!empty($main_name)) {
            $html .= '<p class="wpg-main-video-title">' . $main_name . '</p>';
        }
        $html .= '</div>';
    }

    // Gallery di video correlati (se ci sono altri video)
    if (!empty($gallery_videos)) {
        $html .= '<div class="wpg-gallery">';

        foreach ($gallery_videos as $media) {
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
    }

    $html .= '</div>';

    return $html;
});
