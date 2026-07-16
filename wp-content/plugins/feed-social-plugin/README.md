# Feed Social

Plugin WordPress para exibir um feed social com mídia, curtidas, comentários, visualizações, stories, destaques e notificações em tempo real, sem depender de serviços externos para interações ou streaming.

**Versão:** 3.0.2  
**Autor:** Marcos Cordeiro  
**Requisitos:** WordPress 5.0+, PHP 7.4+, links permanentes ativos

---

## Recursos atuais

### Feed público
- Shortcode `[feed_social]` para inserir o feed em qualquer página
- Carregamento inicial de 5 posts e mais 2 por vez ao rolar a página
- Scroll infinito com sentinela, spinner e controle de fim de conteúdo
- Suporte a imagem destacada, galeria de mídias (imagens e vídeos) e carrossel para múltiplas mídias
- Modal de post com visualização de mídia, legenda, curtidas, comentários e contagem de visualizações
- Legenda exibida acima dos comentários; no mobile, aparece com botão “Leia mais / Leia menos”

### Curtidas, comentários e visualizações
- Curtir e descurtir posts pelo botão de coração
- Identificação do usuário por e-mail, salva no navegador via `localStorage`
- Envio e listagem de comentários via REST API
- Registro de visualizações ao abrir o post no modal
- Contagem atualizada em tempo real sem depender de plugins adicionais

### Stories e destaques
- CPT `social_story` para criar stories
- Shortcode `[feed_social_storie]` para exibir o story principal
- Shortcode `[feed_social_destaques]` para exibir blocos de destaques com categorias
- Suporte a vídeo do story, expiração opcional em 24 horas e conteúdo editorial

### Notificações em tempo real (SSE)
- Quando um post do tipo Feed Social é publicado, visitantes com o site aberto podem receber:
  - toast visual na página
  - notificação nativa do navegador (quando o usuário concede permissão)
- O feed é recarregado automaticamente ao receber um novo conteúdo
- Implementado com Server-Sent Events (SSE) e endpoint REST:

```text
/wp-json/feed-social/v1/events
```

### Administração
- Custom Post Type `feed-social` para publicar posts do feed
- Metabox de galeria de mídias com seleção nativa do WordPress
- Metabox para stories com opção de expiração e vídeo
- Página administrativa com métricas de posts e usuários

---

## Instalação

### Via upload
1. Compacte a pasta do plugin em um arquivo `.zip`
2. No WordPress, acesse **Plugins → Adicionar novo → Enviar plugin**
3. Ative o plugin
4. Acesse **Configurações → Links permanentes** e clique em **Salvar alterações**

### Via pasta
1. Copie a pasta do plugin para `wp-content/plugins/`
2. Ative em **Plugins**
3. Salve os links permanentes

Na ativação, o plugin cria e valida as tabelas de banco necessárias, registra os tipos de post e limpa a URL da página do feed quando necessário.

---

## Uso

### 1. Criar a página do feed
1. Crie uma página (por exemplo, com slug `feed-social`)
2. Adicione o shortcode:

```text
[feed_social]
```

3. Publique a página

### 2. Publicar conteúdo
1. Vá em **Feed Social → Adicionar novo**
2. Preencha título e conteúdo
3. (Opcional) Defina imagem destacada
4. (Opcional) Adicione mídias na metabox **Galeria de Mídias**
5. Publique

Posts em rascunho ou pendente não aparecem no feed e não disparam notificações.

### 3. Usar stories e destaques
- Crie posts do tipo **Story** para formar o carrossel de stories
- Crie categorias na taxonomia **Destaques** e associe stories a elas
- Use os shortcodes:

```text
[feed_social_storie]
[feed_social_destaques]
```

---

## Estrutura do plugin

```text
feed-social-plugin/
├── feed-social.php
├── README.md
├── assets/
│   ├── css/feed-social.css
│   └── js/feed-social.js
└── includes/
    ├── admin-settings.php
    ├── database.php
    ├── metaboxes.php
    ├── post-type.php
    ├── rest-api.php
    ├── shortcode.php
    ├── shortcode-story.php
    └── sse.php
```

---

## API REST

Namespace: `feed-social/v1`

| Método | Rota | Descrição |
|--------|------|-----------|
| `GET` | `/posts` | Lista posts com `offset` e `per_page` |
| `GET` | `/post/{id}` | Retorna um post específico |
| `POST` | `/like` | Curtir ou descurtir (`post_id`, `email`) |
| `POST` | `/comment` | Enviar comentário (`post_id`, `name`, `email`, `comment`) |
| `GET` | `/comments` | Lista comentários de um post (`post_id`) |
| `GET` | `/events` | Stream SSE de novos posts |

Exemplo:

```text
GET /wp-json/feed-social/v1/posts?offset=0&per_page=5
```

---

## Banco de dados

| Tabela | Uso |
|--------|-----|
| `{prefix}feed_social_likes` | Curtidas por post e e-mail |
| `{prefix}feed_social_comments` | Comentários por post |
| `{prefix}feed_social_views` | Visualizações por post |
| `{prefix}feed_social_users` | Perfil de usuários usados nas interações |

---

## Dependências externas

Carregadas apenas quando o shortcode está presente:
- [Swiper](https://swiperjs.com/) — carrossel de mídias e destaques

jQuery é fornecido pelo próprio WordPress.

---

## Checklist pós-instalação

- [ ] Plugin ativado
- [ ] Links permanentes salvos
- [ ] Página com `[feed_social]` publicada
- [ ] Pelo menos um post do tipo **Feed Social** publicado para teste
- [ ] HTTPS ativo (recomendado para notificações do navegador)

---

## Solução de problemas

| Problema | Possível solução |
|----------|------------------|
| Feed vazio | Verifique se há posts publicados do tipo Feed Social |
| Scroll não carrega mais | Recarregue com Ctrl+F5 e confira o console |
| Curtida/comentário falha | Verifique a API REST em `/wp-json/feed-social/v1/posts` |
| Notificação não aparece | Confirme permissão do navegador e teste o endpoint `/wp-json/feed-social/v1/events` |
| 404 na API | Salve os links permanentes novamente |

---

## Changelog

### 3.0.2
- Exibição da legenda do post no modal, com expansão em mobile
- Suporte a stories e destaques
- Página administrativa com métricas e usuários
- Registro de visualizações e melhoria no fluxo de comentários/curtidas

### 3.0.0
- Reestruturação do plugin com REST API, modal de post e notificações SSE

---

## Licença

Uso interno / projeto do autor. Ajuste conforme necessário para distribuição.
