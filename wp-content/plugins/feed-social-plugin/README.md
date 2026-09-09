# Feed Social

Plugin WordPress para exibir um feed social com mídia, curtidas, comentários, visualizações, stories, destaques e notificações em tempo real, sem depender de serviços externos para interações ou streaming.

**Versão:** 3.3.2
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

### Notificações de novos conteúdos
- Quando um post do tipo Feed Social ou um story é publicado, visitantes com o site aberto podem receber:
  - toast visual na página
  - notificação nativa do navegador (quando o usuário concede permissão)
- O toast exibe a logo do Feed Social, o título, um resumo e o link para conferir o conteúdo
- O feed é recarregado automaticamente ao detectar um novo conteúdo
- O navegador consulta um arquivo JSON estático a cada 15 segundos:

```text
/wp-content/uploads/feed-social-sse-event.json
```

O arquivo é criado ou substituído no momento da publicação e expira após 5 minutos. A consulta feita pelo navegador é atendida diretamente pelo Apache ou Nginx, sem iniciar uma requisição PHP e sem consultar o banco de dados. Assim, 10 visitantes geram apenas requisições estáticas periódicas; não ficam 10 workers PHP mantidos em execução.

#### Como o fluxo funciona
1. O WordPress detecta a transição do post para `publish`.
2. O plugin aceita os post types `feed-social` e `social_story`.
3. O plugin monta um evento com ID, tipo, título, resumo, URL, imagem e data.
4. O evento é salvo em `wp-content/uploads/feed-social-sse-event.json` com validade de 300 segundos.
5. Cada página com o feed busca esse arquivo com `cache: no-store` e um parâmetro de data para evitar cache intermediário.
6. A primeira leitura apenas registra o ID atual. Leituras posteriores só exibem a notificação quando o ID mudar.
7. Ao clicar na notificação, o visitante é levado ao endereço configurado para o conteúdo.

#### Sobre o SSE
O plugin não mantém mais um endpoint SSE ativo. O código de publicação permanece em `includes/sse.php` por compatibilidade de organização, mas serve apenas para gerar o arquivo JSON estático. O polling foi escolhido no lugar do SSE porque uma conexão SSE mantém uma requisição PHP aberta por visitante.

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
- A opção **Usar como capa do destaque** define um story como imagem da bolha; ele não aparece na sequência de stories nem dentro do conteúdo do destaque
- Para cada destaque, associe uma capa e um ou mais stories de conteúdo usando a mesma categoria
- Os stories são exibidos do mais recente para o mais antigo; em empate, o cadastro com maior ID aparece primeiro
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
| `PUT` | `/comment/{id}` | Editar comentário (`id`) |
| `GET` | `/comments` | Lista comentários de um post (`post_id`) |
| `GET` | `/user` | Busca usuário por email (`email`) |

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
| Notificação não aparece | Confirme a permissão do navegador, verifique se o arquivo `wp-content/uploads/feed-social-sse-event.json` está acessível e publique um novo conteúdo após a página carregar |
| Bolha do destaque não aparece | Confirme o shortcode `[feed_social_destaques]`, a associação do story à taxonomia **Destaques** e se existe conteúdo publicado além da capa |
| 404 na API | Salve os links permanentes novamente |

---

## Changelog

### 3.3.2
- Validação obrigatória de nome e e-mail no navegador e no servidor
- Suporte a capas de destaques sem exibi-las como stories
- Ordenação de stories por data e ID decrescentes
- Notificações por arquivo JSON estático para evitar conexões PHP persistentes

### 3.3.1
- Melhorias no fluxo de notificações e redução do uso de workers PHP

### 3.3.0
- Edição de comentários via API REST (`PUT /comment/{id}`)
- Busca de perfil de usuário via API REST (`GET /user`)
- Melhorias na página administrativa com suporte a ordenação nas métricas de posts
- Correções de compatibilidade e otimizações internas

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
