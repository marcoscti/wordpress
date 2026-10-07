# Dia das Crianças — Quiz de Nostalgia

Plugin WordPress — quiz de nostalgia e ranking de participantes.

## Requisitos
- WordPress 5.8+
- PHP 7.4+
- MySQL/MariaDB compatível com WordPress

## Instalação
1. Compacte a pasta `dia-das-criancas`.
2. No WordPress, acesse **Plugins > Adicionar novo > Enviar plugin**.
3. Envie o ZIP e ative.
4. Acesse **Dia das Crianças** no menu administrativo.
5. Crie uma página e coloque o shortcode:

`[dcdc_quiz]`

## O que esta versão já possui
- Criação automática das tabelas.
- Perguntas e alternativas.
- Pontuação calculada no servidor.
- Categorias por faixa de pontuação.
- REST API interna.
- Shortcode para renderização do quiz.
- Cadastro de participante com nome, foto e unidade de atuação selecionada entre as unidades disponíveis.
- Ranking público em cards ou lista, com modal de detalhes responsivo.
- Download do cartão do participante em PNG.
- Tela administrativa inicial.
- Seed inicial com perguntas e categorias para testes.

## Importante
Ao atualizar uma instalação existente, a tabela de participantes recebe a coluna da unidade automaticamente. Cadastros antigos permanecem válidos e aparecem com a unidade não informada até serem atualizados.

## Tabelas
- `wp_dcdc_questions`
- `wp_dcdc_answers`
- `wp_dcdc_categories`
- `wp_dcdc_participants`
