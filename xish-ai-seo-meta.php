<?php
/**
 * Plugin Name: Xish AI SEO Meta
 * Plugin URI: https://xish.example.com/plugins/xish-ai-seo-meta
 * Description: 一键调用大语言模型为单篇文章生成 SEO 标题、描述、焦点关键词与社交分享文案。支持自带 OpenAI 兼容 API Key，免费版即可完整使用单篇生成。
 * Version: 1.0.0
 * Requires at least: 6.4
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * Author: Xish
 * Author URI: https://xish.example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: xish-ai-seo-meta
 * Domain Path: /languages
 *
 * 说明：免费版完整可用（用户自备 API Key 即可单篇生成）。
 * 批量生成、中英文多语言、历史文章回填、对接 Rank Math / Yoast 等高级能力由独立的 Pro 插件提供。
 */

// 防止文件被直接通过浏览器访问
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 统一前缀常量，避免与其它插件或主题发生冲突
define( 'XISH_AISM_VERSION', '1.0.0' );
define( 'XISH_AISM_FILE', __FILE__ );
define( 'XISH_AISM_PATH', plugin_dir_path( __FILE__ ) );
define( 'XISH_AISM_URL', plugin_dir_url( __FILE__ ) );

// 拆分到 includes/ 下的各模块。
require_once XISH_AISM_PATH . 'includes/settings.php';
require_once XISH_AISM_PATH . 'includes/generator.php';
require_once XISH_AISM_PATH . 'includes/metabox.php';
