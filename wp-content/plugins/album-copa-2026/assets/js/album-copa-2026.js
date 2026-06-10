document.addEventListener('DOMContentLoaded', function() {
    // 1. Preview de upload de arquivo
    const fileInput = document.getElementById('album_copa_2026_foto');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileNameDisplay = document.querySelector('.album-copa-2026-file-name');
            if (!file) return;

            // Exibe nome provisório
            if (fileNameDisplay) {
                fileNameDisplay.textContent = file.name + ' (preparando recorte...)';
            }

            // Inicia fluxo de recorte
            openCropperModal(file, fileInput, fileNameDisplay);
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

function openCropperModal(file, fileInput, fileNameDisplay) {
    const reader = new FileReader();
    reader.onload = function(e) {
        // Cria elementos do modal
        const overlay = document.createElement('div');
        overlay.style.position = 'fixed';
        overlay.style.top = 0;
        overlay.style.left = 0;
        overlay.style.right = 0;
        overlay.style.bottom = 0;
        overlay.style.background = 'rgba(0,0,0,0.7)';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.zIndex = 99999;

        const container = document.createElement('div');
        container.style.background = '#fff';
        container.style.padding = '12px';
        container.style.maxWidth = '90%';
        container.style.maxHeight = '100vh';
        container.style.overflow = 'scroll';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';

        const img = document.createElement('img');
        img.src = e.target.result;
        img.style.maxWidth = '100%';
        img.style.display = 'block';

        const btns = document.createElement('div');
        btns.style.marginTop = '8px';
        btns.style.textAlign = 'right';

        const cropBtn = document.createElement('button');
        cropBtn.type = 'button';
        cropBtn.textContent = 'Recortar e Usar';
        cropBtn.style.marginRight = '8px';

        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.textContent = 'Cancelar';

        btns.appendChild(cropBtn);
        btns.appendChild(cancelBtn);

        container.appendChild(img);
        container.appendChild(btns);
        overlay.appendChild(container);
        document.body.appendChild(overlay);

        // Inicializa Cropper com aspectRatio 3:4
        let cropper = null;
        try {
            cropper = new Cropper(img, {
                aspectRatio: 3 / 4,
                viewMode: 1,
                autoCropArea: 1,
            });
        } catch (err) {
            console.error('Cropper initialization failed:', err);
            alert('Erro ao iniciar ferramenta de recorte.');
            overlay.remove();
            return;
        }

        cancelBtn.addEventListener('click', function() {
            if (cropper) cropper.destroy();
            overlay.remove();
            if (fileNameDisplay) fileNameDisplay.textContent = '';
            fileInput.value = ''; // limpa seleção
        });

        cropBtn.addEventListener('click', function() {
            if (!cropper) return;
            const canvas = cropper.getCroppedCanvas();
            if (!canvas) {
                alert('Falha ao obter o recorte.');
                return;
            }

            canvas.toBlob(function(blob) {
                const newFileName = file.name;
                const newFile = new File([blob], newFileName, { type: blob.type });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(newFile);
                fileInput.files = dataTransfer.files;

                if (fileNameDisplay) fileNameDisplay.textContent = newFileName + ' (recortado)';

                cropper.destroy();
                overlay.remove();
            }, file.type || 'image/jpeg');
        });
    };
    reader.readAsDataURL(file);
}
