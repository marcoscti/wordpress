<div id="dcdc-quiz" class="dcdc-quiz" aria-live="polite">
    <section class="dcdc-screen dcdc-start is-active" aria-labelledby="dcdc-quiz-title">
        <div class="dcdc-hero-art" aria-hidden="true">
            <span class="dcdc-shape dcdc-shape--yellow"></span>
            <span class="dcdc-shape dcdc-shape--pink"></span>
            <span class="dcdc-shape dcdc-shape--blue"></span>
            <span class="dcdc-shape dcdc-shape--purple"></span>
        </div>

        <div class="dcdc-hero-content">
            <span class="dcdc-badge">DIA DAS CRIANÇAS</span>
            <h1 id="dcdc-quiz-title">E se a sua criança interior pudesse responder?</h1>
            <p>Tem brincadeiras que ficaram para trás.</br>Mas será que a criança que você foi também ficou?</p>
        </div>

        <div class="dcdc-start-actions">
            <button type="button" class="dcdc-btn dcdc-start-btn" aria-label="Começar o desafio do quiz">
                Começar o desafio
            </button>
        </div>
    </section>

    <section class="dcdc-screen dcdc-question-screen" hidden aria-live="polite">
        <div class="dcdc-progress-wrap" aria-label="Progresso do quiz">
            <div class="dcdc-progress-meta">
                <span class="dcdc-progress-label"></span>
                <span class="dcdc-progress-counter"></span>
            </div>
            <div class="dcdc-progress" aria-hidden="true">
                <i class="dcdc-progress-bar"></i>
            </div>
        </div>

        <div class="dcdc-question-card">
            <span class="dcdc-decade"></span>
            <h2 class="dcdc-question" id="dcdc-question-text"></h2>
            <div class="dcdc-options" role="list" aria-label="Respostas possíveis"></div>
            <div class="dcdc-feedback" aria-live="polite" hidden></div>
            <div class="dcdc-question-actions">
                <button type="button" class="dcdc-btn dcdc-next-btn" disabled aria-label="Continuar para a próxima pergunta">
                    Continuar
                </button>
            </div>
        </div>
    </section>

    <section class="dcdc-screen dcdc-result-screen" hidden>
        <div class="dcdc-result">
            <div class="dcdc-result-card">
                <span class="dcdc-result-emoji" aria-hidden="true">🏆</span>
                <p class="dcdc-result-title">Seu resultado</p>
                <strong class="dcdc-score">0 pontos</strong>
                <h2 class="dcdc-category">Resultado</h2>
                <p class="dcdc-description">Uma infância cheia de boas lembranças.</p>
                <button type="button" class="dcdc-btn dcdc-restart-btn">Refazer o desafio</button>
            </div>

            <div class="dcdc-participant-panel">
                <h3>Cadastre-se no ranking</h3>
                <form class="dcdc-participant-form" novalidate>
                    <div class="dcdc-form-row">
                        <label for="dcdc-participant-name">Seu nome</label>
                        <input id="dcdc-participant-name" name="name" type="text" maxlength="80" placeholder="Digite seu nome" required />
                    </div>

                    <div class="dcdc-form-row">
                        <label for="dcdc-participant-unit">Unidade em que atua</label>
                        <select id="dcdc-participant-unit" name="unit" required>
                            <option value="">Selecione uma unidade</option>
                            <?php foreach (DCDC_Quiz::units() as $unit) : ?>
                                <option value="<?php echo esc_attr($unit); ?>"><?php echo esc_html($unit); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="dcdc-form-row">
                        <label for="dcdc-participant-photo">Foto do participante</label>
                        <input id="dcdc-participant-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required />
                        <small class="dcdc-photo-crop-status" data-photo-crop-status aria-live="polite">A foto será recortada no formato quadrado (1:1).</small>
                    </div>

                    <button type="submit" class="dcdc-btn dcdc-participant-btn">Salvar no ranking</button>
                </form>
                <button type="button" class="dcdc-btn dcdc-card-btn" disabled>Gerar meu cartão</button>
                <div class="dcdc-participant-feedback" role="status" hidden></div>
            </div>

        </div>
        <dialog class="dcdc-crop-dialog" data-photo-crop-dialog aria-labelledby="dcdc-crop-title">
            <div class="dcdc-crop-dialog__content">
                <h3 id="dcdc-crop-title">Ajuste sua foto</h3>
                <p>Arraste a imagem para escolher o enquadramento quadrado.</p>
                <div class="dcdc-crop-dialog__image-wrap">
                    <img class="dcdc-crop-dialog__image" data-photo-crop-image alt="Prévia do recorte da foto" />
                </div>
                <div class="dcdc-crop-dialog__actions">
                    <button type="button" class="dcdc-btn dcdc-crop-cancel">Cancelar</button>
                    <button type="button" class="dcdc-btn dcdc-crop-confirm" disabled>Recortar e usar</button>
                </div>
            </div>
        </dialog>
    </section>

    <div class="dcdc-error" role="alert" hidden></div>
</div>