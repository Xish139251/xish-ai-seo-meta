<?php
/**
 * Plugin Name: Xish AI SEO Meta
 * Plugin URI: https://xish139251.github.io/xish-ai-seo-meta-landing
 * Description: Generate SEO titles, meta descriptions, focus keywords, and social share copy for a single post using your own OpenAI-compatible API key. The free version is fully usable for single-post generation.
 * Version: 1.0.1
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Author: Xish
 * Author URI: https://github.com/Xish139251
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: xish-ai-seo-meta
 *
 * 说明：免费版完整可用（用户自备 API Key 即可单篇生成）。
 * 批量生成、中英文多语言、历史文章回填、对接 Rank Math / Yoast 等高级能力由独立的 Pro 插件提供。
 */

// 防止文件被直接通过浏览器访问
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 统一前缀常量，避免与其它插件或主题发生冲突
define( 'XISH_AISM_VERSION', '1.0.1' );
define( 'XISH_AISM_FILE', __FILE__ );
define( 'XISH_AISM_PATH', plugin_dir_path( __FILE__ ) );
define( 'XISH_AISM_URL', plugin_dir_url( __FILE__ ) );

/**
 * 本插件支持的内容类型：文章、页面，以及主题或插件注册的公开自定义类型
 * （例如 OneNav 的 sites / app / book、WooCommerce 的 product 等）。
 * 需要增减时挂载 xish_aism_supported_post_types 过滤器即可。
 *
 * @return array 内容类型名称数组。
 */
function xish_aism_supported_post_types() {
    $types  = array( 'post', 'page' );
    $public = get_post_types( array( 'public' => true ), 'names' );
    if ( is_array( $public ) ) {
        foreach ( $public as $t ) {
            if ( 'attachment' === $t ) {
                continue; // 附件没有独立 SEO 意义
            }
            $types[] = $t;
        }
    }
    $types = array_values( array_unique( $types ) );
    return (array) apply_filters( 'xish_aism_supported_post_types', $types );
}

/**
 * 插件列表页补一个「设置」快捷入口，避免新用户装完找不到设置页。
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'xish_aism_plugin_action_links' );
function xish_aism_plugin_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=xish-ai-seo-meta' ) ) . '">'
        . esc_html__( '设置', 'xish-ai-seo-meta' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

// 拆分到 includes/ 下的各模块。
require_once XISH_AISM_PATH . 'includes/settings.php';
require_once XISH_AISM_PATH . 'includes/generator.php';
require_once XISH_AISM_PATH . 'includes/metabox.php';
require_once XISH_AISM_PATH . 'includes/frontend.php';
