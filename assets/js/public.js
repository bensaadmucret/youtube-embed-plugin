document.addEventListener('DOMContentLoaded', function () {
    // Utiliser la délégation d'événements sur un conteneur parent stable si les boutons sont dans des conteneurs qui peuvent être remplacés.
    // Pour l'instant, on part du principe que yt-embed-main-container est stable ou que le bouton lui-même n'est pas entièrement remplacé.
    document.body.addEventListener('click', function (event) {
        if (event.target.matches('.yt-embed-load-more-button')) {
            event.preventDefault();
            const button = event.target;
            const channelId = button.dataset.channelId;
            const count = button.dataset.count;
            const layout = button.dataset.layout;
            const nextPageToken = button.dataset.nextPageToken;
            const containerId = button.dataset.containerId;
            const videosContainer = document.querySelector(`#${containerId} .yt-public-videos-container`);

            if (!videosContainer) {
                console.error('YouTube Embed: Videos container not found for ID:', containerId);
                return;
            }

            // Afficher un indicateur de chargement (simple changement de texte pour l'instant)
            const originalButtonText = button.textContent;
            button.textContent = 'Chargement...';
            button.disabled = true;

            const formData = new FormData();
            formData.append('action', 'yt_embed_load_more_videos'); // Action WordPress AJAX
            formData.append('channel_id', channelId);
            formData.append('count', count);
            formData.append('layout', layout);
            formData.append('page_token', nextPageToken);
            formData.append('_ajax_nonce', ytEmbedPublic.nonce); // Nonce pour la sécurité

            fetch(ytEmbedPublic.ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.data.html) {
                        videosContainer.insertAdjacentHTML('beforeend', data.data.html);
                    }
                    if (data.data.nextPageToken) {
                        button.dataset.nextPageToken = data.data.nextPageToken;
                        button.textContent = originalButtonText;
                        button.disabled = false;
                    } else {
                        button.remove(); // Plus de vidéos, supprimer le bouton
                    }
                } else {
                    console.error('YouTube Embed: Error loading more videos.', data.data ? data.data.message : 'Unknown error');
                    button.textContent = 'Erreur'; // Indiquer une erreur
                    // Laisser le bouton pour une nouvelle tentative ou le supprimer selon la préférence
                    setTimeout(() => { // Réinitialiser après un délai en cas d'erreur
                        button.textContent = originalButtonText;
                        button.disabled = false;
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('YouTube Embed: AJAX request failed.', error);
                button.textContent = originalButtonText;
                button.disabled = false;
            });
        }
    });
});
