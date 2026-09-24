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
            <h1 id="dcdc-quiz-title">Quanto de criança ainda existe em você?</h1>
            <p>Responda, relembre sua infância e descubra qual tipo de criança você foi.</p>
        </div>

        <div class="dcdc-start-actions">
            <button type="button" class="dcdc-btn dcdc-start-btn" aria-label="Começar o desafio do quiz">
                Começar o desafio
            </button>
            <span class="dcdc-mini-copy">15 perguntas • resultado em segundos</span>
        </div>
    </section>

    <section class="dcdc-screen dcdc-question-screen" hidden aria-live="polite">
        <div class="dcdc-progress-wrap" aria-label="Progresso do quiz">
            <div class="dcdc-progress-meta">
                <span class="dcdc-progress-label">Pergunta 1 de 15</span>
                <span class="dcdc-progress-counter">1/15</span>
            </div>
            <div class="dcdc-progress" aria-hidden="true">
                <i class="dcdc-progress-bar"></i>
            </div>
        </div>

        <div class="dcdc-question-card">
            <span class="dcdc-decade">MEMÓRIAS</span>
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
            <div class="dcdc-reveal-block">
                <span class="dcdc-reveal-label">Você fez...</span>
                <div class="dcdc-score-reveal">
                    <span class="dcdc-score-reveal-value">0</span>
                </div>
            </div>

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
                        <label for="dcdc-participant-photo">Foto de infância</label>
                        <input id="dcdc-participant-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" />
                    </div>

                    <button type="submit" class="dcdc-btn dcdc-participant-btn">Salvar no ranking</button>
                </form>
                <div class="dcdc-participant-feedback" role="status" hidden></div>
            </div>

            <div class="dcdc-ranking">
                <div class="dcdc-ranking-header">
                    <h3>Ranking</h3>
                </div>
                <ol class="dcdc-ranking-list"></ol>
            </div>
        </div>
    </section>

    <div class="dcdc-error" role="alert" hidden></div>
</div>
