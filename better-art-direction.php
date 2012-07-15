<?php
/*
Plugin Name: Better Art Direction
Plugin URI: http://github.com/davatron5000/better-art-direction
Description: A fork of the Art Direction Plugin by Noël Jackson (http://noel.io).
Author: David Rupert
Version: 1.0
Author URI: http://daverupert.com
*/
load_plugin_textdomain('art-direction', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );


/* Display */
add_action('wp_head', 'art_inline');
function art_inline($data) {
    global $post, $global_styles, $page_styles;
    if(is_single() or is_page())
    $page_styles .= str_replace( '#postid', $post->ID, get_post_meta($post->ID, 'art_direction_page', true) )."\n";
    $global_styles .= str_replace( '#postid', $post->ID, get_post_meta($post->ID, 'art_direction_global', true) )."\n";
    $data = "<!-- Better Art Direction Styles -->\n".$page_styles.$global_styles;
    echo $data;
}


/* Publish */
add_action('publish_page','art_save_postdata');
add_action('publish_post','art_save_postdata');
add_action('save_post','art_save_postdata');
add_action('edit_post','art_save_postdata');


/* Save Data */
function art_save_postdata( $post_id ) {
    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times
    if ( !wp_verify_nonce( $_POST['art-direction-nonce'], plugin_basename(__FILE__) ) ) :
        return $post_id;
    endif;

    // if page matches post type
    if ( 'page' == $_POST['post_type'] ) :
        if ( !current_user_can( 'edit_page', $post_id ) ) :
            return $post_id;
        endif;
    else :
        if ( !current_user_can( 'edit_post', $post_id ) ) :
            return $post_id;
        endif;
    endif;

    // OK, we're authenticated: we need to find and save the data
    delete_post_meta( $post_id, 'art_direction_page' );
    delete_post_meta( $post_id, 'art_direction_global' );

    if(trim($_POST['page-code']) != '') :
        add_post_meta( $post_id, 'art_direction_page', stripslashes($_POST['page-code']) );
    endif;

    if(trim($_POST['global-code']) != '') :
        add_post_meta( $post_id, 'art_direction_global', stripslashes($_POST['global-code']) );
    endif;

    return true;
}


/* admin interface */
add_action('admin_menu', 'art_add_meta_box');
add_action('admin_head', 'art_admin_head');

function art_admin_head() { ?>
<style>
    .clear {
        clear: both;
    }

    #global-code,
    #page-code {
        width: 100%;
        height: 300px;
    }

    .global,
    .page {
        width: 48%;
        float:
        left;
    }

    .global {
        margin-right: 3%;
    }

    .tellmemore {
        display: none;
    }

    .art-submit {
        clear: both;
    }

    #art-direction-box h4 span {
        font-weight: normal;
    }

    #instructions-title {
        color: rgb(0,0,180);
        text-transform: uppercase;
    }
</style>

<?php }

function art_add_meta_box() {
    if( function_exists( 'add_meta_box' ) ) :
        if( current_user_can('edit_posts') ) :
            add_meta_box( 'art-direction-box', __( 'Art Direction', 'art-direction' ), 'art_meta_box', 'post', 'normal' );
        endif;

        if( current_user_can('edit_pages') ) :
            add_meta_box( 'art-direction-box', __( 'Art Direction', 'art-direction' ), 'art_meta_box', 'page', 'normal' );
        endif;
    endif;
}

function art_meta_box() {
    global $post; ?>
<form action="art-direction_submit" method="get" accept-charset="utf-8">
    <?php
    // Use nonce for verification
    echo '<input type="hidden" name="art-direction-nonce" id="art-direction-nonce" value="' .
        wp_create_nonce( plugin_basename(__FILE__) ) . '" />'; ?>

    <script>
    /* <![CDATA[ */
    jQuery(document).ready(function() {
        jQuery('.help').click(function() {
            var anchor = this.href.substr( this.href.indexOf('#') );
            jQuery(this).hide();
            jQuery(anchor).toggle();
            return false;
        });

        jQuery('#art-direction-box textarea').focus(function() {
            jQuery('#location').attr('class', this.id);
            var location = jQuery('#location').attr('class');
        });
        jQuery('#style-insert').click(function() {
            var location = jQuery('#location').attr('class');
            edInsertContent(location, '<' + 'style'+'>'+"\n\n"+'<'+'/style'+'>');
        });
        jQuery('#script-insert').click(function() {
            var location = jQuery('#location').attr('class');
            edInsertContent(location, '<'+'script'+'>'+"\n\n"+'<'+'/script'+'>');
        });
        function edInsertContent(which, myValue) {
            myField = document.getElementById(which);
            //IE support
            if (document.selection) {
                myField.focus();
                sel = document.selection.createRange();
                sel.text = myValue;
                myField.focus();
            }
            //MOZILLA/NETSCAPE support
            else if (myField.selectionStart || myField.selectionStart == '0') {
                var startPos = myField.selectionStart;
                var endPos = myField.selectionEnd;
                var scrollTop = myField.scrollTop;
                myField.value = myField.value.substring(0, startPos)
                              + myValue 
                              + myField.value.substring(endPos, myField.value.length);
                myField.focus();
                myField.selectionStart = startPos + myValue.length;
                myField.selectionEnd = startPos + myValue.length;
                myField.scrollTop = scrollTop;
            } else {
                myField.value += myValue;
                myField.focus();
            }
        }
    });
    /* ]]> */
    </script>

    <!-- example -->
    <p><em><code>#postid</code> <?php __("will be replaced with this entry's post ID."); ?></em></p>

    <!-- example demo -->
    <p><?php _e( "Example:", 'art-direction' );?> <code>.post-#postid</code> <?php _e( "will become", 'art-direction' ); ?> <code>.post-<?php echo $post->ID; ?></code>.</p>

    <input type="hidden" name="location" value="" id="location">

    <!-- instructions -->
    <h4 id="instructions-title">Instructions</h4>
    <p id="icon-edit-pages" class="icon32"></p>
    <p>Before making any <code>&lt;style&gt;</code> / <code>&lt;script&gt;</code> adjustments please make sure to use the <code>&lt;script&gt;</code> and/or <code>&lt;style&gt;</code> button to inject the appropriate tags.<br>
       If you don't use the inject tag button(s) provided nothing will happen and a frown will appear on your face. If the frown appears upon your<br>
       face then you're doing something wrong so try again and do as we say in these instructions to avoid such an occurance.</p>

    <!-- insert tag buttons -->
    <p>
        <input type="button" name="style-insert" class="button button-primary" value="Insert &lt;style&gt; Tag" id="style-insert">
        <input type="button" name="script-insert" class="button button-primary" value="Insert &lt;script&gt; Tag" id="script-insert">
    </p>

    <!-- global textarea window -->
    <div class="global">
        <h4><?php _e( 'Globally Scoped', 'art-direction' ); ?> <a class="help" href="#tellmemore-global">(?)</a> <span class="tellmemore" id="tellmemore-global"><?php _e( "Applies to every page on your site", 'art-direction' ); ?></span></h4>
        <textarea id="global-code" name="global-code" rows="8" cols="40"><?php echo esc_attr( get_post_meta( $post->ID,'art_direction_global', true ) ); ?></textarea>
    </div>

    <!-- page specific textarea window -->
    <div class="page">
        <h4><?php _e( 'Page Specific', 'art-direction'); ?> <a class="help" href="#tellmemore-page">(?)</a> <span class="tellmemore" id="tellmemore-page"> <?php _e( "Applies to this entry only", 'art-direction' ); ?></span></h4>
        <textarea id="page-code" name="page-code" rows="8" cols="40"><?php echo esc_attr( get_post_meta( $post->ID,'art_direction_page', true ) ); ?></textarea>
    </div>

    <div class="clear"></div>
</form>

<?php }