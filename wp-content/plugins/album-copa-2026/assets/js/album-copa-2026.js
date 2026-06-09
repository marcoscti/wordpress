document.addEventListener('DOMContentLoaded', function() {
    // 1. Preview de upload de arquivo
    const fileInput = document.getElementById('album_copa_2026_foto');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : '';
            const fileNameDisplay = document.querySelector('.album-copa-2026-file-name');
            if (fileNameDisplay) {
                fileNameDisplay.textContent = fileName;
            }
        });
    }

    // 2. Funcionalidade de Curtida
    const likeForms = document.querySelectorAll('.album-copa-2026-like-form');
    likeForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const button = this.querySelector('.album-copa-2026-like-button');
            
            const formData = new FormData();
            formData.append('action', 'album_copa_2026_like');
            formData.append('post_id', postId);
            formData.append('nonce', AlbumCopa2026.nonce);

            fetch(AlbumCopa2026.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualiza contador de likes na UI
                    button.childNodes[2].textContent = ' ' + data.data.likes;
                    alert(AlbumCopa2026.texts.likeSuccess);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // 3. Alternar exibição de Comentários
    const toggleButtons = document.querySelectorAll('.album-copa-2026-toggle-comments');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const card = this.closest('.album-copa-2026-card');
            const commentsList = card.querySelector('.album-copa-2026-comments-list');
            const commentForm = card.querySelector('.album-copa-2026-comment-form');
            
            const isVisible = commentsList.style.display !== 'none';
            commentsList.style.display = isVisible ? 'none' : 'block';
            commentForm.style.display = isVisible ? 'none' : 'block';
            this.setAttribute('aria-expanded', !isVisible);
        });
    });

    // 4. Submissão de Comentário
    const commentForms = document.querySelectorAll('.album-copa-2026-comment-form');
    commentForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const postId = this.dataset.postId;
            const messageDiv = this.querySelector('.album-copa-2026-comment-message');
            const listDiv = this.closest('.album-copa-2026-card').querySelector('.album-copa-2026-comments-list');
            
            const formData = new FormData(this);
            formData.append('action', 'album_copa_2026_comment');
            formData.append('post_id', postId);
            formData.append('nonce', AlbumCopa2026.nonce);

            fetch(AlbumCopa2026.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<span style="color: green;">' + AlbumCopa2026.texts.commentSuccess + '</span>';
                    listDiv.insertAdjacentHTML('beforeend', data.data.html);
                    this.reset();
                } else {
                    messageDiv.innerHTML = '<span style="color: red;">' + data.data.message + '</span>';
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // 5. Toggle de "Leia Mais"
    const readMoreButtons = document.querySelectorAll('.album-copa-2026-read-more');
    readMoreButtons.forEach(button => {
        button.addEventListener('click', function() {
            const cardBody = this.closest('.album-copa-2026-card-body');
            const shortText = cardBody.querySelector('.album-copa-2026-caption-short');
            const fullText = cardBody.querySelector('.album-copa-2026-caption-more');
            
            if (fullText.style.display === 'none') {
                fullText.style.display = 'block';
                shortText.style.display = 'none';
                this.textContent = 'Leia menos';
            } else {
                fullText.style.display = 'none';
                shortText.style.display = 'block';
                this.textContent = 'Leia mais';
            }
        });
    });
});
