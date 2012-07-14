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
    global $post, $global_styles, $single_styles;
    if(is_single() or is_page()) 
    $single_styles .= str_replace( '#postid', $post->ID, get_post_meta($post->ID, 'art_direction_single', true) )."\n";
    $global_styles .= str_replace( '#postid', $post->ID, get_post_meta($post->ID, 'art_direction_global', true) )."\n";
    $data = "<!-- Art Direction Styles -->\n".$single_styles.$global_styles;
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
    if ( !wp_verify_nonce( $_POST['art-direction-nonce'], plugin_basename(__FILE__) ) )
        return $post_id;
  
    if ( 'page' == $_POST['post_type'] ) {
        if ( !current_user_can( 'edit_page', $post_id ) )
            return $post_id;
    } else {
        if ( !current_user_can( 'edit_post', $post_id ) )
            return $post_id;
    }

    // OK, we're authenticated: we need to find and save the data
    delete_post_meta( $post_id, 'art_direction_single' );
    delete_post_meta( $post_id, 'art_direction_global' );

    if(trim($_POST['single-code']) != '')
        add_post_meta( $post_id, 'art_direction_single', stripslashes($_POST['single-code']) );
    if(trim($_POST['global-code']) != '')
        add_post_meta( $post_id, 'art_direction_global', stripslashes($_POST['global-code']) );
    return true;
}

/* admin interface */
add_action('admin_menu', 'art_add_meta_box');
add_action('admin_head', 'art_admin_head');

function art_admin_head() { ?>
<style type="text/css" media="screen">
    .clear { clear: both; }
    #global-code, #single-code {
        width: 100%;
        height: 300px;
    }
    .global, .single { width: 48%; float: left; }
    .global { margin-right: 3%;}
    .tellmemore { display: none; }
    .art-submit {clear: both;}
    #art-direction-box h4 span { font-weight: normal; }
</style>
<?php
}
function art_add_meta_box() {
    if( function_exists( 'add_meta_box' ) ) {
        if( current_user_can('edit_posts') )
            add_meta_box( 'art-direction-box', __( 'Art Direction', 'art-direction' ),
                'art_meta_box', 'post', 'normal' );
        if( current_user_can('edit_pages') )
            add_meta_box( 'art-direction-box', __( 'Art Direction', 'art-direction' ),
                'art_meta_box', 'page', 'normal' );
    }
}

function art_meta_box() {
    global $post;
?>
<form action="art-direction_submit" method="get" accept-charset="utf-8">
    <?php
    // Use nonce for verification
    echo '<input type="hidden" name="art-direction-nonce" id="art-direction-nonce" value="' . 
        wp_create_nonce( plugin_basename(__FILE__) ) . '" />'; ?>

    <script type="text/javascript" charset="utf-8">
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
            edInsertContent(location, '<' + 'style type="text/css" media="screen"'+'>'+"\n\n"+'<'+'/style'+'>');
        });
        jQuery('#script-insert').click(function() {
            var location = jQuery('#location').attr('class');
            edInsertContent(location, '<'+'script type="text/javascript" charset="utf-8"'+'>'+"\n\n"+'<'+'/script'+'>');
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

    <p><em><code>#postid</code> <?php __("will be replaced with this entry's post ID."); ?></em></p>

    <p><?php _e( "Example:", 'art-direction' );?> <code>.post-#postid</code> <?php _e( "will become", 'art-direction' ); ?> <code>.post-<?php echo $post->ID; ?></code>.</p>
    <input type="hidden" name="location" value="" id="location" />
    <p><input type="button" name="style-insert" class="button" value="Insert &lt;style&gt; Tag" id="style-insert" /> 
    <input type="button" name="script-insert" class="button" value="Insert &lt;script&gt; Tag" id="script-insert" /></p>

    <div class="global">
        <h4><?php _e( 'Global Code', 'art-direction' ); ?> <a class="help" href="#tellmemore-global">(?)</a> <span class="tellmemore" id="tellmemore-global"><?php _e( "will be inserted on every archive this entry appears on.", 'art-direction' ); ?></span></h4>
        <textarea id="global-code" name="global-code" rows="8" cols="40"><?php echo esc_attr( get_post_meta( $post->ID,'art_direction_global', true ) ); ?></textarea>
    </div>
    <div class="single">
    <h4><?php _e( 'Single Page Code', 'art-direction'); ?> <a class="help" href="#tellmemore-single">(?)</a> <span class="tellmemore" id="tellmemore-single"> <?php _e( "will only show up on this entry's single page.", 'art-direction' ); ?></span></h4>
        <textarea id="single-code" name="single-code" rows="8" cols="40"><?php echo esc_attr( get_post_meta( $post->ID,'art_direction_single', true ) ); ?></textarea>
    </div>
    <div class="clear"></div>
</form>
<?php
}

