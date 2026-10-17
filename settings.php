<?php
// 插件设置页与选项的集中管理。所有函数、选项名统一使用前缀 xish_aism_。
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 选项在数据库中的键名
define( 'XISH_AISM_OPTION', 'xish_aism_settings' );

/**
 * 注册设置、字段与设置分区。
 */
add_action( 'admin_init', 'xish_aism_register_settings' );
function xish_aism_register_settings() {
    register_setting(
        'xish_aism_settings_group',
        XISH_AISM_OPTION,
        array(
            'type'              => 'array',
            'sanitize_callback' => 'xish_aism_sanitize_settings',
        )
    );

    add_settings_section(
        'xish_aism_main_section',
        __( 'AI 提供商配置', 'xish-ai-seo-meta' ),
        '__return_false',
        'xish-ai-seo-meta'
    );

    $fields = array(
        'provider' => array( __( 'API 提供商', 'xish-ai-seo-meta' ), 'xish_aism_field_provider' ),
        'api_key'  => array( __( 'API Key', 'xish-ai-seo-meta' ), 'xish_aism_field_api_key' ),
        'endpoint' => array( __( 'API 端点', 'xish-ai-seo-meta' ), 'xish_aism_field_endpoint' ),
        'model'    => array( __( '模型名称', 'xish-ai-seo-meta' ), 'xish_aism_field_model' ),
        'language' => array( __( '默认语言', 'xish-ai-seo-meta' ), 'xish_aism_field_language' ),
    );

    foreach ( $fields as $key => $def ) {
        add_settings_field(
            'xish_aism_' . $key,
            $def[0],
            $def[1],
            'xish-ai-seo-meta',
            'xish_aism_main_section'
        );
    }
}

/**
 * 用 WordPress 安全盐（定义在 wp-config.php，不在数据库中）派生密钥，
 * 对 API Key 做 AES-256-CBC 加密后再入库，降低数据库被导出时密钥直接泄露的风险。
 */
function xish_aism_get_encryption_key() {
    $salt = '';
    if ( function_exists( 'wp_salt' ) ) {
        $salt = wp_salt( 'auth' );
    } elseif ( defined( 'AUTH_KEY' ) ) {
        $salt = AUTH_KEY;
    }
    return hash( 'sha256', $salt . 'xish-aism', true );
}

function xish_aism_encrypt( $plain ) {
    if ( '' === $plain ) {
        return '';
    }
    if ( ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'random_bytes' ) ) {
        // 无 OpenSSL 时退化为可逆混淆（仍优于纯明文），加前缀以便识别。
        return 'B64:' . base64_encode( $plain );
    }
    $iv      = random_bytes( 16 );
    $key     = xish_aism_get_encryption_key();
    $cipher  = openssl_encrypt( $plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
    if ( false === $cipher ) {
        return 'B64:' . base64_encode( $plain );
    }
    return 'AES:' . base64_encode( $iv . $cipher );
}

function xish_aism_decrypt( $value ) {
    if ( '' === $value ) {
        return '';
    }
    if ( strpos( $value, 'AES:' ) === 0 ) {
        $raw = base64_decode( substr( $value, 4 ), true );
        if ( false === $raw || strlen( $raw ) < 17 ) {
            return '';
        }
        $iv        = substr( $raw, 0, 16 );
        $cipher    = substr( $raw, 16 );
        $key       = xish_aism_get_encryption_key();
        $decrypted = openssl_decrypt( $cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
        return false === $decrypted ? '' : $decrypted;
    }
    if ( strpos( $value, 'B64:' ) === 0 ) {
        $decoded = base64_decode( substr( $value, 4 ), true );
        return false === $decoded ? '' : $decoded;
    }
    // 兼容升级前已存的明文。
    return $value;
}

/**
 * 读取并解密 API Key（供 generator 调用）。
 */
function xish_aism_get_api_key() {
    $settings = xish_aism_get_settings();
    return xish_aism_decrypt( $settings['api_key'] ?? '' );
}

/**
 * 默认选项，保证任何字段缺失时都有合理值。
 */
function xish_aism_default_settings() {
    return array(
        'provider' => 'openai',
        'api_key'  => '',
        'endpoint' => 'https://api.openai.com/v1/chat/completions',
        'model'    => 'gpt-4o-mini',
        'language' => 'auto',
    );
}

/**
 * 读取并合并已保存选项与默认值。
 */
function xish_aism_get_settings() {
    $defaults = xish_aism_default_settings();
    $saved    = get_option( XISH_AISM_OPTION, array() );
    if ( ! is_array( $saved ) ) {
        $saved = array();
    }
    return wp_parse_args( $saved, $defaults );
}

/**
 * 保存前统一清洗输入。
 */
function xish_aism_sanitize_settings( $input ) {
    $existing = xish_aism_get_settings();
    $out = array();
    $out['provider'] = isset( $input['provider'] ) ? sanitize_text_field( $input['provider'] ) : 'openai';
    // 设置页不再回显明文密钥：留空表示沿用已保存的密钥，只有填了新值才更新。
    $submitted_key   = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';
    // 留空表示沿用已保存的密钥；填了新值则加密后再入库。
    $out['api_key']  = ( '' === $submitted_key ) ? $existing['api_key'] : xish_aism_encrypt( $submitted_key );
    $out['endpoint'] = isset( $input['endpoint'] ) ? esc_url_raw( $input['endpoint'] ) : '';
    $out['model']    = isset( $input['model'] ) ? sanitize_text_field( $input['model'] ) : 'gpt-4o-mini';
    $out['language'] = isset( $input['language'] ) ? sanitize_text_field( $input['language'] ) : 'auto';
    return $out;
}

/**
 * 在"设置"下添加独立菜单项。
 */
add_action( 'admin_menu', 'xish_aism_admin_menu' );
function xish_aism_admin_menu() {
    add_options_page(
        __( 'Xish AI SEO Meta', 'xish-ai-seo-meta' ),
        __( 'AI SEO Meta', 'xish-ai-seo-meta' ),
        'manage_options',
        'xish-ai-seo-meta',
        'xish_aism_settings_page'
    );
}

/**
 * 渲染设置页。upsell 仅展示在本页底部，不出现全站横幅（符合 wp.org 规范）。
 */
function xish_aism_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    // 升级入口指向插件官网（GitHub Pages 落地页）。wp.org 不允许插件内直接外链第三方
    // 支付平台，故此处只链官网；官网内的购买按钮再跳转爱发电，既合规又保留免费→Pro 漏斗。
    $pro_url = 'https://xish139251.github.io/xish-ai-seo-meta-landing/';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Xish AI SEO Meta 设置', 'xish-ai-seo-meta' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'xish_aism_settings_group' );
            do_settings_sections( 'xish-ai-seo-meta' );
            submit_button();
            ?>
        </form>

        <hr>
        <h2><?php echo esc_html__( '升级到 Pro', 'xish-ai-seo-meta' ); ?></h2>
        <p><?php echo esc_html__( '免费版已可单篇生成。Pro 一次性买断 ¥99，解锁以下能力：', 'xish-ai-seo-meta' ); ?></p>
        <ul style="list-style:disc;padding-left:20px;margin:0 0 12px;">
            <li><?php echo esc_html__( '批量生成：一次处理多篇 / 全部文章', 'xish-ai-seo-meta' ); ?></li>
            <li><?php echo esc_html__( '中英文多语言：标题 / 描述分别存储', 'xish-ai-seo-meta' ); ?></li>
            <li><?php echo esc_html__( '历史文章回填：旧文章一键补全 SEO 元数据', 'xish-ai-seo-meta' ); ?></li>
            <li><?php echo esc_html__( '一键写入 Rank Math / Yoast', 'xish-ai-seo-meta' ); ?></li>
        </ul>
        <p><a class="button button-primary" href="<?php echo esc_url( $pro_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( '¥99 买断 · 了解 Pro 版', 'xish-ai-seo-meta' ); ?></a></p>
    </div>
    <?php
}

/**
 * 各字段渲染函数。
 */
function xish_aism_field_provider() {
    $s = xish_aism_get_settings();
    $providers = array(
        'openai' => __( 'OpenAI 兼容', 'xish-ai-seo-meta' ),
        'custom' => __( '自定义端点', 'xish-ai-seo-meta' ),
    );
    echo '<select name="' . XISH_AISM_OPTION . '[provider]">';
    foreach ( $providers as $k => $label ) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr( $k ),
            selected( $s['provider'], $k, false ),
            esc_html( $label )
        );
    }
    echo '</select>';
}

function xish_aism_field_api_key() {
    $s = xish_aism_get_settings();
    $has_key = ( '' !== $s['api_key'] );
    printf(
        '<input type="password" class="regular-text" name="%s[api_key]" value="" autocomplete="new-password" placeholder="%s">',
        XISH_AISM_OPTION,
        $has_key ? esc_attr__( '已保存密钥，留空则不修改', 'xish-ai-seo-meta' ) : ''
    );
    echo '<p class="description">' . esc_html__( '密钥仅存储于本站数据库，不会发送到你所配置端点以外的任何服务器。留空表示沿用已保存的密钥。', 'xish-ai-seo-meta' ) . '</p>';
}

function xish_aism_field_endpoint() {
    $s = xish_aism_get_settings();
    printf(
        '<input type="url" class="regular-text" name="%s[endpoint]" value="%s">',
        XISH_AISM_OPTION,
        esc_attr( $s['endpoint'] )
    );
}

function xish_aism_field_model() {
    $s = xish_aism_get_settings();
    printf(
        '<input type="text" class="regular-text" name="%s[model]" value="%s">',
        XISH_AISM_OPTION,
        esc_attr( $s['model'] )
    );
}

function xish_aism_field_language() {
    $s = xish_aism_get_settings();
    $langs = array(
        'auto' => __( '跟随站点语言', 'xish-ai-seo-meta' ),
        'zh'   => __( '中文', 'xish-ai-seo-meta' ),
        'en'   => __( 'English', 'xish-ai-seo-meta' ),
    );
    echo '<select name="' . XISH_AISM_OPTION . '[language]">';
    foreach ( $langs as $k => $label ) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr( $k ),
            selected( $s['language'], $k, false ),
            esc_html( $label )
        );
    }
    echo '</select>';
}
