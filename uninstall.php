<?php
// 插件被删除时清理本插件创建的选项与文章元数据。
// WP 仅在真正卸载时定义 WP_UNINSTALL_PLUGIN，可借此判断是否为合法卸载流程。
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( function_exists( 'delete_post_meta_by_key' ) ) {
    delete_post_meta_by_key( '_xish_aism_seo_title' );
    delete_post_meta_by_key( '_xish_aism_seo_description' );
    delete_post_meta_by_key( '_xish_aism_seo_keywords' );
    delete_post_meta_by_key( '_xish_aism_seo_social' );
    // 顺带清理 Pro 写入的多语言键，避免卸载后留下孤儿元数据。
    delete_post_meta_by_key( '_xish_aism_seo_title_zh' );
    delete_post_meta_by_key( '_xish_aism_seo_title_en' );
    delete_post_meta_by_key( '_xish_aism_seo_description_zh' );
    delete_post_meta_by_key( '_xish_aism_seo_description_en' );
}

delete_option( 'xish_aism_settings' );
