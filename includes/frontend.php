<?php
// 前台输出：把已生成的 SEO 元数据写入页面 <head>。
// 设计目标：主题无关 + 单套输出。
//  - 命中 Yoast / Rank Math 时完全让位（它们的输出是权威）。
//  - 否则用输出缓冲接管整页 HTML：删掉主题自带的 description / keywords /
//    og:title / og:description / og:type / twitter:card / 标题，并注入我们的单套标签。
//  - 不删除主题提供的 og:url / og:image / og:site_name，避免破坏社交分享缩略图。

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'template_redirect', 'xish_aism_maybe_start_buffer', 9999 );

/**
 * 是否检测到主流 SEO 插件（Yoast / Rank Math）。检测到则本插件让位。
 */
function xish_aism_seo_plugin_active() {
    if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Frontend' ) ) {
        return true;
    }
    if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath\\Frontend' ) ) {
        return true;
    }
    return false;
}

/**
 * 仅在「文章/页面详情页」+「我们确有数据」+「无主流 SEO 插件」时启动缓冲接管。
 */
function xish_aism_maybe_start_buffer() {
    if ( ! is_singular( xish_aism_supported_post_types() ) ) {
        return;
    }
    if ( xish_aism_seo_plugin_active() ) {
        return;
    }
    $post_id = get_the_ID();
    if ( ! $post_id ) {
        return;
    }
    $title = get_post_meta( $post_id, '_xish_aism_seo_title', true );
    $desc  = get_post_meta( $post_id, '_xish_aism_seo_description', true );
    $kw    = get_post_meta( $post_id, '_xish_aism_seo_keywords', true );
    if ( '' === $title && '' === $desc && '' === $kw ) {
        return; // 没数据就不搅和主题原本的输出
    }
    $pid = $post_id; // 闭包捕获，避免缓冲回调时全局 $post 失效
    ob_start(
        function ( $html ) use ( $pid ) {
            return xish_aism_render_head( $html, $pid );
        }
    );
}

/**
 * 输出缓冲回调：去重 + 接管，返回修改后的整页 HTML。
 */
function xish_aism_render_head( $html, $post_id ) {
    $title = get_post_meta( $post_id, '_xish_aism_seo_title', true );
    $desc  = get_post_meta( $post_id, '_xish_aism_seo_description', true );
    $kw    = get_post_meta( $post_id, '_xish_aism_seo_keywords', true );

    // 组装我们的单套标签
    $block = '';
    if ( '' !== $desc ) {
        $block .= "\n<meta name=\"description\" content=\"" . esc_attr( $desc ) . "\">";
    }
    if ( '' !== $kw ) {
        $block .= "\n<meta name=\"keywords\" content=\"" . esc_attr( $kw ) . "\">";
    }
    if ( '' !== $title ) {
        $block .= "\n<meta property=\"og:title\" content=\"" . esc_attr( $title ) . "\">";
    }
    if ( '' !== $desc ) {
        $block .= "\n<meta property=\"og:description\" content=\"" . esc_attr( $desc ) . "\">";
        $block .= "\n<meta property=\"og:type\" content=\"article\">";
    }
    if ( '' !== $title || '' !== $desc ) {
        $block .= "\n<meta name=\"twitter:card\" content=\"summary\">";
        if ( '' !== $title ) {
            $block .= "\n<meta name=\"twitter:title\" content=\"" . esc_attr( $title ) . "\">";
        }
        if ( '' !== $desc ) {
            $block .= "\n<meta name=\"twitter:description\" content=\"" . esc_attr( $desc ) . "\">";
        }
    }
    $block .= "\n";

    // 1) 删掉主题已有的冲突标签（保留 og:url / og:image / og:site_name 等有用项）
    $html = preg_replace( '/<meta\s+name=["\']description["\'][^>]*>/i', '', $html );
    $html = preg_replace( '/<meta\s+name=["\']keywords["\'][^>]*>/i', '', $html );
    $html = preg_replace( '/<meta\s+property=["\']og:(title|description|type)["\'][^>]*>/i', '', $html );
    $html = preg_replace( '/<meta\s+name=["\']twitter:(card|title|description)["\'][^>]*>/i', '', $html );

    // 2) 接管 <title> 内文（仅当我们有 SEO 标题时）
    if ( '' !== $title ) {
        $html = preg_replace_callback(
            '/<title[^>]*>(.*?)<\/title>/is',
            function ( $m ) use ( $title ) {
                return '<title>' . esc_html( $title ) . '</title>';
            },
            $html,
            1
        );
    }

    // 3) 注入到 </head> 之前；主题若没有 </head> 则兜底到 <body> 前
    $html = preg_replace( '/<\/head>/i', $block . '</head>', $html, 1, $count );
    if ( ! $count ) {
        $html = preg_replace( '/<body[^>]*>/i', $block . '$0', $html, 1 );
    }
    return $html;
}
