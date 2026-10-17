<?php
// 调用 LLM 为单篇文章生成 SEO 元数据。
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 为指定文章生成 SEO 元数据。
 *
 * @param int    $post_id 文章 ID。
 * @param string $lang    可选，覆盖语言偏好；免费版取设置，Pro 可传入多语言。
 * @return array|WP_Error 成功返回 array(title, description, keywords, social)，失败返回 WP_Error。
 */
function xish_aism_generate( $post_id, $lang = '' ) {
    $post = get_post( $post_id );
    if ( ! $post ) {
        return new WP_Error( 'no_post', __( '文章不存在。', 'xish-ai-seo-meta' ) );
    }

    $settings = xish_aism_get_settings();
    $api_key  = xish_aism_get_api_key();
    if ( empty( $api_key ) ) {
        return new WP_Error( 'no_key', __( '请先在设置页填写 API Key。', 'xish-ai-seo-meta' ) );
    }
    if ( empty( $settings['endpoint'] ) ) {
        return new WP_Error( 'no_endpoint', __( '请先在设置页填写 API 端点。', 'xish-ai-seo-meta' ) );
    }

    // 解析目标语言
    $lang = $lang ? $lang : $settings['language'];
    $is_zh = ( 'zh' === $lang ) || ( 'auto' === $lang && function_exists( 'determine_locale' ) && strpos( determine_locale(), 'zh' ) === 0 );
    $language_label = $is_zh ? '中文' : 'English';

    $title   = get_the_title( $post );
    $content = wp_strip_all_tags( $post->post_content );
    // 截断以控制 token 用量；少数未启用 mbstring 扩展的主机会缺失 mb_substr，做兜底
    $content = function_exists( 'mb_substr' ) ? mb_substr( $content, 0, 4000 ) : substr( $content, 0, 4000 );

    $prompt = sprintf(
        '你是一名资深 SEO 编辑。用%s为下面这篇文章生成元数据，只返回 JSON：{"title": SEO标题(不超过60字符), "description": 描述(不超过160字符), "keywords": 逗号分隔的焦点关键词(不超过5个), "social": 社交分享文案(不超过140字符)}。文章标题：%s。文章正文：%s',
        $language_label,
        $title,
        $content
    );

    $body = array(
        'model'    => $settings['model'],
        'messages' => array(
            array( 'role' => 'system', 'content' => '你是 SEO 助手，只输出 JSON，不要任何额外解释。' ),
            array( 'role' => 'user', 'content' => $prompt ),
        ),
        'temperature' => 0.7,
    );

    // JSON mode 仅官方 OpenAI 端点支持；LongCat 等兼容端点会拒绝 response_format（返回 400）。
    // 对这类端点省略该参数，改为依赖 prompt 指令 + 下方健壮解析。
    if ( false !== strpos( $settings['endpoint'], 'api.openai.com' ) ) {
        $body['response_format'] = array( 'type' => 'json_object' );
    }

    // 使用 WP 内置 HTTP API，避免直接调用 curl，安全性与兼容性更好
    $response = wp_remote_post(
        $settings['endpoint'],
        array(
            'timeout' => 45,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    if ( 200 !== $code ) {
        return new WP_Error(
            'api_error',
            sprintf(
                /* translators: %d: HTTP status code returned by the configured API endpoint. */
                __( 'API 返回错误码 %d。', 'xish-ai-seo-meta' ),
                $code
            )
        );
    }

    $raw = wp_remote_retrieve_body( $response );
    $data = json_decode( $raw, true );
    if ( empty( $data['choices'][0]['message']['content'] ) ) {
        return new WP_Error( 'empty', __( '模型未返回内容。', 'xish-ai-seo-meta' ) );
    }

    // 健壮解析：兼容模型把 JSON 用 ```json 代码块包裹、或前后带多余文字的情况。
    $content = trim( $data['choices'][0]['message']['content'] );
    if ( preg_match( '/```(?:json)?\s*(.*?)\s*```/s', $content, $m ) ) {
        $content = trim( $m[1] );
    }
    $parsed = json_decode( $content, true );
    if ( ! is_array( $parsed ) && preg_match( '/\{.*\}/s', $content, $m ) ) {
        $parsed = json_decode( $m[0], true );
    }
    if ( ! is_array( $parsed ) ) {
        return new WP_Error( 'bad_json', __( '模型返回格式异常。', 'xish-ai-seo-meta' ) );
    }

    // 输出前清洗，避免模型返回的内容包含意外字符
    return array(
        'title'       => sanitize_text_field( $parsed['title'] ?? '' ),
        'description' => sanitize_text_field( $parsed['description'] ?? '' ),
        'keywords'    => sanitize_text_field( $parsed['keywords'] ?? '' ),
        'social'      => sanitize_text_field( $parsed['social'] ?? '' ),
    );
}
