=== Album Copa 2026 ===
Contributors: Marcos Cordeiro
Tags: copa 2026, album, figurinhas, ajax, moderation, world cup
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

O plugin **Album Copa 2026** permite a criação de um álbum de figurinhas digital interativo. Desenvolvido para engajamento de usuários, ele permite que visitantes enviem fotos que, após aprovação administrativa, compõem uma galeria pública.
O plugin automatiza o processo de "recorte" da imagem usando inteligência artificial e gerencia toda a interação social dos usuários.

Recursos principais:
* **Submissão Simples:** Formulário para upload de fotos com validação de campos (nome, e-mail).
* **Fluxo de Moderação:** Figurinhas entram como "Pendentes" e o administrador decide quando publicar.
* **Notificações:** Envio automático de e-mail para o autor informando sobre a aprovação da figurinha.
* **Remoção de Fundo (Inteligência Artificial):** Integração com a API do Remove.bg para criar transparência nas fotos automaticamente (requer chave de API).
* **Interatividade AJAX:** Sistema de curtidas e comentários sem recarregamento de página.
* **Segurança:** Uso de Nonces para proteger todas as operações de envio e interação.
* **SEO Ready:** Gera URLs amigáveis para cada figurinha individualmente.

== Installation ==

1. Faça o upload da pasta `album-copa-2026` para o diretório `/wp-content/plugins/`.
2. Ative o plugin através do menu 'Plugins' no painel administrativo do WordPress.
3. (Opcional) Vá até **Album Copa 2026 > Configurações** e insira sua chave de API do Remove.bg para habilitar o recorte automático.
4. Crie uma página chamada "Enviar Figurinha" e cole o shortcode `[album_copa_2026_form]`.
5. Crie uma página chamada "Álbum Digital" e cole o shortcode `[figurinhas_list]`.

== FAQ ==

= Como obtenho a API Key do Remove.bg? =
Crie uma conta gratuita em remove.bg e acesse a seção 'API' no seu perfil para gerar uma chave.

= O que acontece se eu não usar uma API Key? =
As figurinhas serão publicadas com a foto original enviada pelo usuário, sem o efeito de transparência no fundo.

= O plugin suporta comentários? =
Sim, o sistema de comentários é proprietário do plugin e funciona via AJAX para manter a performance.

== Shortcodes ==

* `[album_copa_2026_form]`: Exibe o formulário de envio da figurinha.
* `[figurinhas_list]`: Exibe a galeria de figurinhas aprovadas com sistema de likes e comentários.

== Frequently Asked Questions ==

= Como funciona a aprovação? =
As novas figurinhas aparecem no menu **Album Copa 2026**. Para aprovar, edite o item, marque o checkbox "Aprovado" no box lateral e clique em Atualizar. O post será publicado automaticamente e o e-mail enviado ao autor.

= Posso personalizar o estilo da galeria? =
Sim, o plugin carrega um arquivo CSS dedicado em `assets/css/album-copa-2026.css` que pode ser customizado conforme o layout do seu tema.

= Como configuro a remoção de fundo das imagens? =
Vá para **Album Copa 2026 > Configurações** no painel administrativo e insira sua chave de API do Remove.bg. Se a chave não for fornecida, as imagens serão aprovadas sem a remoção do fundo.

= Onde as fotos são armazenadas? =
As fotos enviadas são integradas à Biblioteca de Mídia do WordPress e associadas como "Imagem Destacada" ao respectivo post da figurinha.

== Screenshots ==

1. Interface do formulário de submissão no frontend.
2. Galeria de figurinhas exibindo curtidas e lista de comentários.
3. Meta boxes administrativas com informações de contato do autor.

== Changelog ==

= 1.0.0 =
* Versão inicial.
* Implementação do Custom Post Type `figurinhas-copa-2026`.
* Sistema de moderação com troca automática de status de post.
* Adicionada integração com a API do Remove.bg para remoção de fundo de imagens.
* Sistema de Likes e Comentários via AJAX.
* Padronização de assets (CSS e JS) e segurança com Nonces.
* Internacionalização básica via Text Domain.

== Upgrade Notice ==

= 1.0.0 =
Lançamento da versão estável inicial. Instalação recomendada para novos projetos de álbuns interativos.
