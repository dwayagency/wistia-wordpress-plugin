/**
 * Wistia Channel Gallery Script
 * Estrae i video dal channel embed usando l'API Wistia
 */

(function() {
    'use strict';

    // Carica l'API Wistia se non è già caricata
    function loadWistiaAPI(callback) {
        if (window.Wistia) {
            callback();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://fast.wistia.com/assets/external/E-v1.js';
        script.async = true;
        script.onload = callback;
        document.head.appendChild(script);
    }

    // Funzione per estrarre i video dal channel usando l'API Wistia
    function extractVideosFromChannel(channelId, container) {
        loadWistiaAPI(function() {
            // Crea un div temporaneo per il channel embed
            const tempDiv = document.createElement('div');
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = '-9999px';
            tempDiv.style.width = '1px';
            tempDiv.style.height = '1px';
            tempDiv.className = 'wistia_channel';
            tempDiv.setAttribute('data-channel-id', channelId);
            document.body.appendChild(tempDiv);

            // Inizializza il channel embed
            window._wq = window._wq || [];
            window._wq.push({
                id: channelId,
                onReady: function(video) {
                    // Prova a ottenere i video dal channel
                    try {
                        // Il channel potrebbe esporre i video tramite l'API
                        if (video.channel && video.channel.medias) {
                            const videos = video.channel.medias.map(function(media) {
                                return {
                                    hashed_id: media.hashedId || media.hashed_id,
                                    name: media.name || 'Video'
                                };
                            });
                            
                            if (videos.length > 0) {
                                document.body.removeChild(tempDiv);
                                renderGallery(videos, container);
                                return;
                            }
                        }
                    } catch (e) {
                        console.log('Errore nell\'estrazione video:', e);
                    }

                    // Fallback: mostra l'embed del channel direttamente
                    document.body.removeChild(tempDiv);
                    container.innerHTML = '<div class="wpg-channel-wrapper"><iframe src="https://fast.wistia.com/embed/channel/' + channelId + '" allow="autoplay; fullscreen" allowfullscreen frameborder="0" loading="lazy" class="wpg-channel-iframe" title="Wistia Channel"></iframe></div>';
                }
            });

            // Inizializza Wistia
            if (window.Wistia) {
                window.Wistia.embed(channelId, {
                    container: tempDiv,
                    channel: true
                });
            }
        });
    }

    // Funzione per renderizzare la gallery
    function renderGallery(videos, container) {
        if (videos.length === 0) return;

        let html = '<div class="wpg-container">';

        // Video principale (primo video)
        if (videos.length > 0) {
            const mainVideo = videos[0];
            html += '<div class="wpg-main-video">';
            html += '<div class="wpg-main-video-wrapper">';
            html += '<iframe src="https://fast.wistia.net/embed/iframe/' + mainVideo.hashed_id + '?videoFoam=true" allow="autoplay; fullscreen" allowfullscreen frameborder="0" loading="lazy" title="' + (mainVideo.name || 'Video principale') + '"></iframe>';
            html += '</div>';
            if (mainVideo.name) {
                html += '<p class="wpg-main-video-title">' + mainVideo.name + '</p>';
            }
            html += '</div>';
        }

        // Gallery di video correlati (se ci sono altri video)
        if (videos.length > 1) {
            html += '<div class="wpg-gallery">';
            for (let i = 1; i < videos.length; i++) {
                const video = videos[i];
                html += '<div class="wpg-item">';
                html += '<div class="wpg-video-wrapper">';
                html += '<iframe src="https://fast.wistia.net/embed/iframe/' + video.hashed_id + '?videoFoam=true" allow="autoplay; fullscreen" allowfullscreen frameborder="0" loading="lazy" title="' + (video.name || 'Video') + '"></iframe>';
                html += '</div>';
                if (video.name) {
                    html += '<p class="wpg-video-title">' + video.name + '</p>';
                }
                html += '</div>';
            }
            html += '</div>';
        }

        html += '</div>';
        container.innerHTML = html;
    }

    // Inizializza quando il DOM è pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        // Cerca tutti i container con data-channel-id
        const containers = document.querySelectorAll('[data-wpg-channel-id]');
        containers.forEach(function(container) {
            const channelId = container.getAttribute('data-wpg-channel-id');
            if (channelId) {
                extractVideosFromChannel(channelId, container);
            }
        });
    }
})();
