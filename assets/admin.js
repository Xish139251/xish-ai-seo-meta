/**
 * 编辑页「用 AI 生成」按钮的交互。
 * 点击后向后端发起生成请求，把返回的标题/描述回填到表单字段。
 */
jQuery( function ( $ ) {
    $( '#xish_aism_generate_btn' ).on( 'click', function ( e ) {
        e.preventDefault();

        var $btn     = $( this );
        var $spinner = $( '#xish_aism_spinner' );
        var $status  = $( '#xish_aism_status' );

        $btn.prop( 'disabled', true );
        $spinner.addClass( 'is-active' );
        $status.text( '生成中…' );

        // 新文章尚未保存时没有 post_ID，必须先存草稿再生成。
        var postId = $( '#post_ID' ).val();
        if ( ! postId ) {
            $status.text( '请先保存草稿，再点「用 AI 生成」。' );
            $btn.prop( 'disabled', false );
            $spinner.removeClass( 'is-active' );
            return;
        }

        $.post(
            XISH_AISM.ajax_url,
            {
                action: 'xish_aism_generate',
                nonce: XISH_AISM.nonce,
                post_id: $( '#post_ID' ).val()
            },
            function ( resp ) {
                if ( resp.success ) {
                    $( '#xish_aism_title' ).val( resp.data.title );
                    $( '#xish_aism_desc' ).val( resp.data.description );
                    $( '#xish_aism_keywords' ).val( resp.data.keywords );
                    $( '#xish_aism_social' ).val( resp.data.social );
                    $status.text( '已生成 ✅' );
                } else {
                    $status.text( '错误：' + resp.data.message );
                }
            },
            'json'
        )
        .fail( function () {
            $status.text( '请求失败，请稍后重试。' );
        } )
        .always( function () {
            $btn.prop( 'disabled', false );
            $spinner.removeClass( 'is-active' );
        } );
    } );
} );
