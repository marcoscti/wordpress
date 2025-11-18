# Busca Notícias (ACF) - Sugestões AJAX

Plugin WordPress para busca instantânea via AJAX em posts, páginas ou custom post types, incluindo campos ACF. Exibe sugestões conforme o usuário digita, com suporte a filtros por categoria/taxonomia e múltiplos tipos de post.

## Instalação
1. Copie a pasta `busca-noticias-plugin` para `wp-content/plugins/`.
2. Ative o plugin pelo painel do WordPress.
3. Insira o shortcode `[busca_noticias]` em qualquer página, post ou bloco.

## Shortcode
```
[busca_noticias]
```

### Parâmetros disponíveis
| Parâmetro     | Descrição                                                                 | Exemplo                         |
|--------------|---------------------------------------------------------------------------|---------------------------------|
| `placeholder`| Texto do campo de busca                                                    | placeholder="Digite sua busca" |
| `min_chars`  | Mínimo de caracteres para iniciar busca (padrão: 3)                        | min_chars="2"                  |
| `limit`      | Máximo de resultados retornados (padrão: 50)                               | limit="10"                     |
| `post_type`  | Tipo(s) de post a buscar (`post`, `page`, `noticia`, etc)                  | post_type="noticia,page"       |
| `taxonomy`   | Taxonomia para filtrar (ex: `category`)                                    | taxonomy="category"            |
| `tax_term`   | Slug do termo da taxonomia (ex: `saude`)                                   | tax_term="saude"               |
| `category`   | Alias para `tax_term` (usado com taxonomy `category`)                      | category="saude"               |

### Exemplos de uso
- **Busca padrão (notícias):**
  ```
  [busca_noticias]
  ```
- **Busca em posts e páginas:**
  ```
  [busca_noticias post_type="post,page"]
  ```
- **Busca apenas em páginas:**
  ```
  [busca_noticias post_type="page"]
  ```
- **Busca notícias na categoria 'saude':**
  ```
  [busca_noticias post_type="noticia" taxonomy="category" tax_term="saude"]
  ```
- **Busca com placeholder personalizado:**
  ```
  [busca_noticias placeholder="Digite o que procura..."]
  ```
- **Busca limitando resultados:**
  ```
  [busca_noticias limit="8"]
  ```

## Funcionamento
- O campo de busca aparece onde o shortcode é inserido.
- Sugestões aparecem a partir do número mínimo de caracteres (`min_chars`).
- A busca considera título, conteúdo, excerpt e campos ACF (`resumo`, `revisao`, `autor`, `autor_bio`).
- Filtros por categoria/taxonomia são aplicados se informados.
- O plugin usa cache para acelerar buscas repetidas.

## Dicas rápidas
- Para filtrar por categoria, use o slug (não o nome legível):
  - Exemplo: `category="saude"` ou `tax_term="saude"`.
- Para buscar em múltiplos tipos, separe por vírgula: `post_type="noticia,page"`.
- O placeholder pode ser alterado via parâmetro ou filtro no tema.

## Personalização avançada
- Para alterar o placeholder via código (sem editar o plugin):
  ```php
  add_filter('bn_search_placeholder', function($v){ return 'Pesquisar no site...'; });
  ```
- Para estilizar, edite `assets/css/busca-noticias.css`.

## Requisitos
- WordPress 5.0+
- Advanced Custom Fields (ACF) instalado para busca nos campos personalizados

## Suporte
Dúvidas ou sugestões? Abra uma issue ou entre em contato com o autor.
