# Dia das Crianças — Quiz de Nostalgia

Plugin WordPress — Fase 1: motor do quiz.

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
- Tela administrativa inicial.
- Estrutura preparada para participantes, fotos, ranking e card.
- Seed inicial com perguntas e categorias para testes.

## Importante
A Fase 1 ainda não inclui cadastro de participante, upload de foto, ranking público ou geração de card. Esses recursos serão adicionados nas fases seguintes.

## Tabelas
- `wp_dcdc_questions`
- `wp_dcdc_answers`
- `wp_dcdc_categories`
- `wp_dcdc_participants`
