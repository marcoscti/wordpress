# Repositório de Documentos Jurídicos

MVP inicial de plugin WordPress para um repositório de PDFs jurídicos.

## Funcionalidades incluídas

- CPT `documento_juridico`
- Taxonomias `dj_categoria` e `dj_assunto`
- Shortcode `[repositorio_juridico]`
- Busca dinâmica por AJAX/REST
- Filtros por categoria e assunto
- Paginação
- Contador de acessos
- Endpoint para registrar visualizações
- Estrutura de status para processamento por IA
- Metabox para URL do PDF
- Colunas administrativas de IA, PDF e acessos

## Instalação

1. Compacte/instale o plugin pelo painel do WordPress.
2. Ative o plugin.
3. Crie categorias e assuntos em **Documentos Jurídicos**.
4. Crie uma página e insira:

   `[repositorio_juridico]`

5. Cadastre documentos pelo CPT.

## Próxima etapa

O código já possui a estrutura de status da IA (`pending`, `processing`, `completed`, `failed`), mas a integração efetiva com uma API de IA e a extração de texto dos PDFs ainda devem ser implementadas.

Também é recomendável implementar upload múltiplo pela Biblioteca de Mídia e uma fila robusta com Action Scheduler antes de usar em produção.

## Observação

Este pacote é um MVP técnico para iniciar o desenvolvimento. Não deve ser considerado uma versão final de produção.
