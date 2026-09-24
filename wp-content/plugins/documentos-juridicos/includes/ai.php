<?php
if (!defined('ABSPATH')) exit;

/**
 * Estrutura inicial para processamento assíncrono por IA.
 * A integração com a API é deixada desacoplada para receber a chave
 * e o modelo escolhidos pelo projeto.
 */
function dj_ai_register_meta() {
    register_post_meta('documento_juridico', '_dj_ai_status', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'default' => 'pending',
    ]);
    register_post_meta('documento_juridico', '_dj_ai_attempts', [
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
        'default' => 0,
    ]);
    register_post_meta('documento_juridico', '_dj_ai_error', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'default' => '',
    ]);
}
add_action('init', 'dj_ai_register_meta');

function dj_ai_schedule($post_id) {
    if (get_post_type($post_id) !== 'documento_juridico') return;
    if (wp_next_scheduled('dj_process_ai_document', [$post_id])) return;
    wp_schedule_single_event(time() + 10, 'dj_process_ai_document', [$post_id]);
}
add_action('save_post_documento_juridico', 'dj_ai_schedule', 20);

/**
 * Tenta extrair texto de um PDF usando Smalot\PdfParser se disponível,
 * ou `pdftotext` se presente no sistema. Caso contrário, retorna WP_Error.
 */
function dj_extract_text_from_pdf($url) {
    $resp = wp_remote_get($url, ['timeout' => 30]);
    if (is_wp_error($resp)) return $resp;
    $code = wp_remote_retrieve_response_code($resp);
    if ($code !== 200) return new WP_Error('fetch_failed', 'Não foi possível baixar o PDF: ' . $code);
    $body = wp_remote_retrieve_body($resp);
    if (strpos($body, '%PDF') === false) return new WP_Error('not_pdf', 'Arquivo baixado não parece ser um PDF.');

    // Usar Smalot\PdfParser se instalado via composer
    if (class_exists('\Smalot\PdfParser\Parser')) {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseContent($body);
            $text = $pdf->getText();
            return trim($text);
        } catch (Exception $e) {
            return new WP_Error('pdf_parse_error', $e->getMessage());
        }
    }

    // Tentar usar pdftotext via shell
    if (function_exists('shell_exec')) {
        $which = trim(shell_exec('command -v pdftotext 2>/dev/null'));
        if ($which) {
            $tmp_in = wp_tempnam('dj_pdf_');
            $tmp_out = $tmp_in . '.txt';
            file_put_contents($tmp_in, $body);
            $cmd = escapeshellcmd($which) . ' ' . escapeshellarg($tmp_in) . ' ' . escapeshellarg($tmp_out);
            shell_exec($cmd);
            if (file_exists($tmp_out)) {
                $text = file_get_contents($tmp_out);
                @unlink($tmp_in);
                @unlink($tmp_out);
                return trim($text);
            }
            @unlink($tmp_in);
        }
    }

    return new WP_Error('no_extractor', 'Nenhum método de extração de PDF disponível. Instale "smalot/pdfparser" via composer ou habilite "pdftotext" no sistema.');
}

/**
 * Chama a API de IA (OpenAI Chat Completions) para gerar um resumo.
 */
function dj_call_ai($prompt_text) {
    $key = get_option('dj_ai_api_key');
    $model = get_option('dj_ai_model', 'gpt-4o-mini');
    if (empty($key)) return new WP_Error('no_key', 'API key não configurada nas configurações do plugin.');

    $system = 'Você é um assistente que resume documentos jurídicos em português, mantendo precisão e objetividade.';
    $user_prompt = get_option('dj_ai_prompt', 'Resuma o conteúdo abaixo em 3-5 linhas.');

    $messages = [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $user_prompt . "\n\n" . $prompt_text],
    ];

    $body = wp_json_encode([
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => 800,
        'temperature' => 0.2,
    ]);

    $resp = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $key,
            'Content-Type' => 'application/json',
        ],
        'body' => $body,
        'timeout' => 60,
    ]);

    if (is_wp_error($resp)) return $resp;
    $code = wp_remote_retrieve_response_code($resp);
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if ($code !== 200) return new WP_Error('api_error', wp_remote_retrieve_body($resp));
    if (empty($data['choices'][0]['message']['content'])) return new WP_Error('no_summary', 'Resposta da API sem conteúdo.');
    return trim($data['choices'][0]['message']['content']);
}

function dj_process_ai_document_handler($post_id) {
    if (get_post_type($post_id) !== 'documento_juridico') return;

    update_post_meta($post_id, '_dj_ai_status', 'processing');
    $attempts = (int) get_post_meta($post_id, '_dj_ai_attempts', true);
    update_post_meta($post_id, '_dj_ai_attempts', $attempts + 1);

    $pdf_url = get_post_meta($post_id, '_dj_pdf_url', true);
    if (empty($pdf_url)) {
        update_post_meta($post_id, '_dj_ai_status', 'failed');
        update_post_meta($post_id, '_dj_ai_error', 'URL do PDF não informada');
        return;
    }

    $text = dj_extract_text_from_pdf($pdf_url);
    if (is_wp_error($text)) {
        update_post_meta($post_id, '_dj_ai_status', 'failed');
        update_post_meta($post_id, '_dj_ai_error', $text->get_error_message());
        return;
    }

    $summary = dj_call_ai($text);
    if (is_wp_error($summary)) {
        update_post_meta($post_id, '_dj_ai_status', 'failed');
        update_post_meta($post_id, '_dj_ai_error', $summary->get_error_message());
        return;
    }

    update_post_meta($post_id, '_dj_ai_summary', $summary);
    // Salva resumo em meta e atualiza o conteúdo do post para torná-lo pesquisável.
    update_post_meta($post_id, '_dj_ai_summary', $summary);
    // Guarda conteúdo original se ainda não guardado
    if (!get_post_meta($post_id, '_dj_original_content', true)) {
        $post_obj = get_post($post_id);
        if ($post_obj) update_post_meta($post_id, '_dj_original_content', $post_obj->post_content);
    }
    // Atualiza post_content adicionando o resumo no final para que a busca o encontre
    $post_obj = get_post($post_id);
    if ($post_obj) {
        $new_content = $post_obj->post_content . "\n\nResumo IA: " . $summary;
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $new_content,
        ]);
    }
    update_post_meta($post_id, '_dj_ai_status', 'completed');
    update_post_meta($post_id, '_dj_ai_error', '');
}

add_action('dj_process_ai_document', 'dj_process_ai_document_handler');
