<?php
// 文章/页面编辑页的元数据框、保存逻辑与生成 AJAX。统一前缀 xish_aism_。
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 在文章、页面以及公开自定义类型（OneNav 的 sites / app / book、WooCommerce product 等）编辑侧栏注册元数据框
add_action( 'add_meta_boxes', 'xish_aism_register_metabox' );
function xish_aism_register_metabox() {
    $types = xish_aism_supported_post_types();
    foreach ( $types as $t ) {
        add_meta_box(
            'xish_aism_metabox',
            __( 'AI SEO Meta', 'xish-ai-seo-meta' ),
            'xish_aism_metabox_render',
            $t,
            'side',
            'high'
        );
    }
}

// 渲染元数据框。nonce 用于后续保存与 AJAX 校验。
function xish_aism_metabox_render( $post ) {
    wp_nonce_field( 'xish_aism_generate', 'xish_aism_nonce' );
    $title  = get_post_meta( $post->ID, '_xish_aism_seo_title', true );
    $desc   = get_post_meta( $post->ID, '_xish_aism_seo_description', true );
    $kw     = get_post_meta( $post->ID, '_xish_aism_seo_keywords', true );
    $social = get_post_meta( $post->ID, '_xish_aism_seo_social', true );
    ?>
    <p>
        <button type="button" class="button" id="xish_aism_generate_btn"><?php echo esc_html__( '用 AI 生成', 'xish-ai-seo-meta' ); ?></button>
        <span class="spinner" id="xish_aism_spinner" style="float:none;"></span>
    </p>
    <p>
        <label><?php echo esc_html__( 'SEO 标题', 'xish-ai-seo-meta' ); ?><br>
        <input type="text" class="widefat" id="xish_aism_title" name="xish_aism_seo_title" value="<?php echo esc_attr( $title ); ?>"></label>
    </p>
    <p>
        <label><?php echo esc_html__( 'SEO 描述', 'xish-ai-seo-meta' ); ?><br>
        <textarea class="widefat" id="xish_aism_desc" name="xish_aism_seo_description" rows="3"><?php echo esc_textarea( $desc ); ?></textarea></label>
    </p>
    <p>
        <label><?php echo esc_html__( '焦点关键词', 'xish-ai-seo-meta' ); ?><br>
        <input type="text" class="widefat" id="xish_aism_keywords" name="xish_aism_seo_keywords" value="<?php echo esc_attr( $kw ); ?>"></label>
    </p>
    <p>
        <label><?php echo esc_html__( '社交分享文案', 'xish-ai-seo-meta' ); ?><br>
        <textarea class="widefat" id="xish_aism_social" name="xish_aism_seo_social" rows="2"><?php echo esc_textarea( $social ); ?></textarea></label>
    </p>
    <p class="description" id="xish_aism_status"></p>
    <?php
}

// 保存文章时落库元数据：必须校验 nonce 与权限
add_action( 'save_post', 'xish_aism_save_meta' );
function xish_aism_save_meta( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    // 跳过修订版本与自动保存草稿，避免重复写入或写入无效 post_id。
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }
    if ( ! isset( $_POST['xish_aism_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['xish_aism_nonce'] ) ), 'xish_aism_generate' ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( isset( $_POST['xish_aism_seo_title'] ) ) {
        update_post_meta( $post_id, '_xish_aism_seo_title', sanitize_text_field( wp_unslash( $_POST['xish_aism_seo_title'] ) ) );
    }
    if ( isset( $_POST['xish_aism_seo_description'] ) ) {
        update_post_meta( $post_id, '_xish_aism_seo_description', sanitize_textarea_field( wp_unslash( $_POST['xish_aism_seo_description'] ) ) );
    }
    if ( isset( $_POST['xish_aism_seo_keywords'] ) ) {
        update_post_meta( $post_id, '_xish_aism_seo_keywords', sanitize_text_field( wp_unslash( $_POST['xish_aism_seo_keywords'] ) ) );
    }
    if ( isset( $_POST['xish_aism_seo_social'] ) ) {
        update_post_meta( $post_id, '_xish_aism_seo_social', sanitize_textarea_field( wp_unslash( $_POST['xish_aism_seo_social'] ) ) );
    }
}

// 处理生成请求：校验 nonce 与权限后调用核心函数
add_action( 'wp_ajax_xish_aism_generate', 'xish_aism_ajax_generate' );
function xish_aism_ajax_generate() {
    check_ajax_referer( 'xish_aism_generate', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    if ( ! $post_id ) {
        wp_send_json_error( array( 'message' => __( '缺少文章 ID。', 'xish-ai-seo-meta' ) ) );
        return;
    }
    // 收紧到具体文章的编辑权限，而非笼统的 edit_posts。
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( array( 'message' => __( '权限不足。', 'xish-ai-seo-meta' ) ) );
        return;
    }

    $result = xish_aism_generate( $post_id );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        return;
    }
    wp_send_json_success( $result );
}

// 仅在文章/页面编辑页加载脚本
add_action( 'admin_enqueue_scripts', 'xish_aism_enqueue' );
function xish_aism_enqueue( $hook ) {
    if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
        return;
    }
    wp_enqueue_script(
        'xish-aism-admin',
        XISH_AISM_URL . 'assets/admin.js',
        array( 'jquery' ),
        XISH_AISM_VERSION,
        true
    );
    wp_localize_script(
        'xish-aism-admin',
        'XISH_AISM',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'xish_aism_generate' ),
        )
    );
}
