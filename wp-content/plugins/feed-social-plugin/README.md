# Feed Social

Plugin WordPress para exibir um feed social com mídia, curtidas, comentários, scroll infinito e notificações em tempo real — sem serviços de terceiros para interações ou streaming.

**Versão:** 1.3.0  
**Autor:** Marcos  
**Requisitos:** WordPress 5.0+, PHP 7.4+, links permanentes ativos (não usar estrutura "simples")

---

## Funcionalidades

### Feed público
- Shortcode `[feed_social]` para embutir o feed em qualquer página
- Carregamento inicial de **5 posts**, depois **2 posts por vez** ao rolar a página
- Scroll infinito com sentinela dedicado e spinner de carregamento
- Suporte a imagem destacada, galeria de mídias (imagens e vídeos) e carrossel Swiper para múltiplas mídias
- Layout responsivo, estilo inspirado em redes sociais

### Curtidas
- Curtir/descurtir posts pelo ícone de coração
- Identificação do visitante por e-mail (salvo no `localStorage` do navegador)
- Uma curtida por e-mail por post
- Contagem atualizada em tempo real via REST API

### Comentários
- Painel expansível ao clicar no ícone de comentário
- Listagem e envio de comentários via REST API
- Campos: nome, e-mail e texto (nome e e-mail salvos no `localStorage`)

### Notificações em tempo real (SSE)
- Quando um post do tipo **Feed Social** é publicado, visitantes com o site aberto recebem:
  - **Toast** no canto da tela com resumo do post
  - **Notificação nativa do navegador** (se o usuário tiver concedido permissão)
- O feed recarrega automaticamente ao receber um novo post
- Implementado com **Server-Sent Events (SSE)** nativo, endpoint REST:
  ```
  /wp-json/feed-social/v1/events
  ```
- Scripts de notificação carregam em **todas as páginas** do site; o feed completo só carrega onde o shortcode está presente

### Administração
- Custom Post Type `feed-social` no painel WordPress
- Metabox de galeria de mídias (seletor nativo do WordPress)
- Suporte a título, editor, imagem destacada, revisões e autor

---

## Instalação

### Via upload (produção ou local)

1. Compacte a pasta `feed-social-plugin` em um arquivo `.zip`
   - A raiz do zip deve conter a pasta do plugin com `feed-social.php` dentro
2. No WordPress: **Plugins → Adicionar novo → Enviar plugin**
3. Ative o plugin
4. Acesse **Configurações → Links permanentes** e clique em **Salvar alterações** (atualiza as rotas)

### Via pasta (desenvolvimento)

1. Copie `feed-social-plugin` para `wp-content/plugins/`
2. Ative em **Plugins**
3. Salve os links permanentes

Na ativação, o plugin:
- Cria as tabelas `wp_feed_social_likes` e `wp_feed_social_comments`
- Registra o post type e as rewrite rules
- Limpa o cache da URL da página do feed

---

## Uso

### 1. Criar a página do feed

1. Crie uma nova página (ex.: slug `feed-social`)
2. Insira o shortcode:

```
[feed_social]
```

3. Publique a página

O plugin detecta automaticamente qual página contém o shortcode e usa essa URL nos links das notificações.

### 2. Publicar conteúdo

1. No admin, vá em **Feed Social → Adicionar novo**
2. Preencha título e conteúdo
3. (Opcional) Defina imagem destacada
4. (Opcional) Adicione mídias na metabox **Galeria de Mídias**
5. Publique

Posts em rascunho ou pendente **não** aparecem no feed e **não** disparam notificações.

### 3. Experiência do visitante

| Ação | Comportamento |
|------|----------------|
| Abrir a página do feed | Carrega 5 posts mais recentes |
| Rolar até o final | Carrega mais 2 posts com spinner |
| Clicar no coração | Pede e-mail (1ª vez), registra curtida |
| Clicar no comentário | Abre painel com lista e formulário |
| Estar em qualquer página do site | Pode receber notificação ao publicar novo post |

---

## Estrutura do plugin

```
feed-social-plugin/
├── feed-social.php          # Bootstrap, ativação e constantes
├── README.md
├── assets/
│   ├── css/feed-social.css
│   └── js/feed-social.js
└── includes/
    ├── admin-settings.php   # Reservado (painel em breve)
    ├── database.php         # Tabelas de curtidas e comentários
    ├── metaboxes.php        # Galeria de mídias no admin
    ├── post-type.php        # CPT feed-social
    ├── rest-api.php         # Endpoints REST
    ├── shortcode.php        # Shortcode e enqueue de assets
    └── sse.php              # Notificações em tempo real (SSE)
```

---

## API REST

Namespace: `feed-social/v1`

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/posts` | Lista posts (`offset`, `per_page`) |
| `POST` | `/like` | Curtir/descurtir (`post_id`, `email`) |
| `POST` | `/comment` | Enviar comentário (`post_id`, `name`, `email`, `comment`) |
| `GET` | `/comments` | Listar comentários (`post_id`) |
| `GET` | `/events` | Stream SSE de novos posts |

### Exemplo — listar posts

```
GET /wp-json/feed-social/v1/posts?offset=0&per_page=5
```

Resposta:

```json
{
  "posts": [...],
  "total": 12,
  "offset": 0,
  "has_more": true
}
```

---

## Personalização

### Quantidade de posts por carregamento

Edite em `includes/shortcode.php`, na função `fs_enqueue_feed_scripts()`:

```php
'initial_posts' => 5,   // Primeira carga
'posts_per_load' => 2,  // Cargas ao rolar
```

### Textos da interface

Os textos são passados via `wp_localize_script` e podem ser alterados no mesmo arquivo ou traduzidos com o domínio `feed-social`.

---

## Dependências externas (CDN)

Carregadas apenas na página que contém o shortcode:

- [Swiper 11](https://swiperjs.com/) — carrossel de mídias (`jsdelivr.net`)

jQuery é fornecido pelo próprio WordPress.

---

## Banco de dados

| Tabela | Uso |
|--------|-----|
| `{prefix}feed_social_likes` | Curtidas por `post_id` + `email` |
| `{prefix}feed_social_comments` | Comentários por post |

As tabelas são criadas/atualizadas na ativação e verificadas em `plugins_loaded` conforme `FS_DB_VERSION`.

---

## Implantação em produção

O plugin usa URLs dinâmicas (`home_url`, `get_rest_url`) e funciona em qualquer domínio sem alteração de código.

### Checklist pós-instalação

- [ ] Plugin ativado
- [ ] Links permanentes salvos
- [ ] Página com `[feed_social]` publicada
- [ ] Pelo menos um post **Feed Social** publicado para teste
- [ ] HTTPS ativo (recomendado para notificações do navegador)

### Notificações SSE em hospedagem

O endpoint SSE mantém uma conexão PHP aberta por até 5 minutos. Em alguns ambientes pode haver limitações:

- Timeout curto do PHP (30–60 s)
- Buffering de Nginx/Apache
- CDN ou cache (Cloudflare, etc.) bloqueando streaming

**Feed, curtidas e comentários** funcionam na maioria dos hosts. **Notificações em tempo real** dependem do servidor permitir conexões longas. Se não funcionar em produção, verifique logs do servidor e configurações de cache/CDN.

---

## Solução de problemas

| Problema | Possível solução |
|----------|------------------|
| Feed vazio | Confirme posts publicados do tipo Feed Social |
| Scroll não carrega mais | Recarregue com Ctrl+F5; verifique console do navegador |
| Curtida/comentário falha | Verifique REST API (`/wp-json/feed-social/v1/posts`) |
| Notificação não aparece | Confirme permissão do navegador; teste conexão em `/wp-json/feed-social/v1/events` |
| 404 na API | Salve links permanentes novamente |
| Carrossel não funciona | Página precisa do shortcode; Swiper vem do CDN |

---

## Changelog

### 1.3.0
- Scroll infinito com sentinela dedicado e spinner de loading
- Carregamento inicial de 5 posts e +2 ao rolar
- Paginação por `offset` na REST API

### 1.2.x
- Notificações SSE via REST API (`/events`)
- Leitura de eventos direto no banco (sem cache stale)
- Curtidas e comentários funcionais no frontend
- Toast + notificação nativa do navegador

### 0.1.0
- Versão inicial com CPT, shortcode, REST API e SSE

---

## Licença

Uso interno / projeto do autor. Ajuste conforme necessário para distribuição.
