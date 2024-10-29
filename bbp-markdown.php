<?php

/*
 * Plugin Name: bbP Markdown
 * Description: Elegant Markdown support for your bbPress forums.
 * Plugin URI: https://wordpress.org/plugins/bbp-markdown/
 * Author: Markus Echterhoff
 * Author URI: https://www.markusechterhoff.com
 * Version: 1.5
 * License: GPLv3 or later
 * Text Domain: bbp-markdown
 * Domain Path: /languages
 */

/*
 * inspired by / uses code from
 *
 * - bbPress 2.5.14 by the bbPress community https://wordpress.org/plugins/bbpress/
 * - bbPress - Jetpack Markdown 0.2.0 by John James Jacoby https://github.com/stuttter/bbp-jp-markdown
 * - WP Markdown 1.5.1 by Stephen Harris https://wordpress.org/plugins/wp-markdown/
 * - PHP Markdown 1.7 by Michel Fortin https://michelf.ca/projects/php-markdown/
 * - Tabby Responsive Tabs 1.2.3 by Michael Atkins https://wordpress.org/plugins/tabby-responsive-tabs/
 * - ResponsiveTabs.js 1.1.0 by Pete Love http://www.petelove.com/responsiveTabs/
 */

require_once plugin_dir_path( __FILE__ ) . '/lib/MarkdownInterface.php';	// PHP Markdown Lib 1.7.0
require_once plugin_dir_path( __FILE__ ) . '/lib/Markdown.php';				// PHP Markdown Lib 1.7.0
require_once plugin_dir_path( __FILE__ ) . '/lib/MarkdownExtra.php';		// PHP Markdown Lib 1.7.0

use Michelf\MarkdownExtra;

add_action( 'plugins_loaded', 'bbpmd_load_plugin_textdomain' );
function bbpmd_load_plugin_textdomain() {
    load_plugin_textdomain( 'bbp-markdown', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

add_action( 'wp_enqueue_scripts', 'bbpmd_load_scripts' );
function bbpmd_load_scripts() {
    if ( !function_exists( 'is_bbpress' ) || ( !is_bbpress() && !apply_filters( 'bbpmd_load_scripts', false ) ) ) {
    	return;
    }

    wp_enqueue_style( 'bbpmd-css', plugins_url( 'assets/css/bbp-markdown.css', __FILE__  ) );
	wp_enqueue_style( 'bbpmd-tabby', plugins_url( 'assets/css/tabby.css', __FILE__  ) );
	wp_enqueue_style( 'bbpmd-tabby-improvements', plugins_url( 'assets/css/tabby-improvements.css', __FILE__ ) );
	wp_enqueue_script( 'bbpmd-responsive-tabs', plugins_url( 'assets/js/responsiveTabs.js', __FILE__ ), array( 'jquery' ), false, true );
	wp_enqueue_script( 'bbpmd-js', plugins_url( 'assets/js/bbp-markdown.js', __FILE__ ), array( 'jquery', 'bbpmd-responsive-tabs' ), false, true );

	wp_localize_script( 'bbpmd-js', 'bbpmd_data', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'bbpmd' . get_home_url() )
	));
}

// allow additional html tags in posts
add_filter( 'bbp_kses_allowed_tags', 'bbpmd_add_allowed_tags' );
function bbpmd_add_allowed_tags( $tags ) {
	$tags['p'] = array();
	$tags['hr'] = array();
	$tags['table'] = array();
	$tags['thead'] = array();
	$tags['tbody'] = array();
	$tags['tfoot'] = array();
	$tags['th'] = array();
	$tags['tr'] = array();
	$tags['td'] = array();
	$tags['h1'] = array();
	$tags['h2'] = array();
	$tags['h3'] = array();
	$tags['h4'] = array();
	$tags['h5'] = array();
	$tags['h6'] = array();
	return $tags;
}

// ignore options to use wp editor
add_filter( 'bbp_use_wp_editor', '__return_false' );

// remove bbp filters
function bbpmd_remove_bbp_filters() {

	// remove bbp input content filters (we'll run them manually if we need to)
	remove_filter( 'bbp_new_reply_pre_content',  'bbp_encode_bad',  10 );
	remove_filter( 'bbp_new_reply_pre_content',  'bbp_code_trick',  20 );
	remove_filter( 'bbp_new_reply_pre_content',  'bbp_filter_kses', 30 );
	remove_filter( 'bbp_new_reply_pre_content',  'balanceTags', 40 );
	remove_filter( 'bbp_new_topic_pre_content',  'bbp_encode_bad',  10 );
	remove_filter( 'bbp_new_topic_pre_content',  'bbp_code_trick',  20 );
	remove_filter( 'bbp_new_topic_pre_content',  'bbp_filter_kses', 30 );
	remove_filter( 'bbp_new_topic_pre_content',  'balanceTags', 40 );
	remove_filter( 'bbp_edit_reply_pre_content', 'bbp_encode_bad',  10 );
	remove_filter( 'bbp_edit_reply_pre_content', 'bbp_code_trick',  20 );
	remove_filter( 'bbp_edit_reply_pre_content', 'bbp_filter_kses', 30 );
	remove_filter( 'bbp_edit_reply_pre_content', 'balanceTags', 40 );
	remove_filter( 'bbp_edit_topic_pre_content', 'bbp_encode_bad',  10 );
	remove_filter( 'bbp_edit_topic_pre_content', 'bbp_code_trick',  20 );
	remove_filter( 'bbp_edit_topic_pre_content', 'bbp_filter_kses', 30 );
	remove_filter( 'bbp_edit_topic_pre_content', 'balanceTags', 40 );

	// remove bbp form output content filter (we'll run them manually if we need to)
	remove_filter( 'bbp_get_form_topic_content', 'bbp_code_trick_reverse' );
	remove_filter( 'bbp_get_form_reply_content', 'bbp_code_trick_reverse' );
	remove_filter( 'bbp_get_form_topic_content', 'esc_textarea'           );
	remove_filter( 'bbp_get_form_reply_content', 'esc_textarea'           );
	remove_filter( 'bbp_get_form_topic_content', 'trim'                   );
	remove_filter( 'bbp_get_form_reply_content', 'trim'                   );

	// load markdown from post meta or legacy html from post
	add_filter( 'bbp_get_form_topic_content', 'bbpmd_load_markdown_if_present', 8 );
	add_filter( 'bbp_get_form_reply_content', 'bbpmd_load_markdown_if_present', 8 );

	if ( apply_filters( 'bbpmd_remove_output_filters_for_all_posts', false ) ) {
		global $wp_embed;
		remove_filter( 'bbp_get_reply_content', 'bbp_make_clickable', 4    );
		remove_filter( 'bbp_get_reply_content', 'wptexturize',        6    );
		remove_filter( 'bbp_get_reply_content', 'convert_chars',      8    );
		remove_filter( 'bbp_get_reply_content', 'capital_P_dangit',   10   );
		//remove_filter( 'bbp_get_reply_content', 'convert_smilies',    20   );
		remove_filter( 'bbp_get_reply_content', 'force_balance_tags', 30   );
		remove_filter( 'bbp_get_reply_content', 'wpautop',            40   );
		//remove_filter( 'bbp_get_reply_content', 'bbp_rel_nofollow',   50   );
		remove_filter( 'bbp_get_reply_content', array( $wp_embed, 'autoembed' ), 2 );
		remove_filter( 'bbp_get_topic_content', 'bbp_make_clickable', 4    );
		remove_filter( 'bbp_get_topic_content', 'wptexturize',        6    );
		remove_filter( 'bbp_get_topic_content', 'convert_chars',      8    );
		remove_filter( 'bbp_get_topic_content', 'capital_P_dangit',   10   );
		//remove_filter( 'bbp_get_topic_content', 'convert_smilies',    20   );
		remove_filter( 'bbp_get_topic_content', 'force_balance_tags', 30   );
		remove_filter( 'bbp_get_topic_content', 'wpautop',            40   );
		//remove_filter( 'bbp_get_topic_content', 'bbp_rel_nofollow',   50   );
		remove_filter( 'bbp_get_topic_content', array( $wp_embed, 'autoembed' ), 2 );

	} else {
		// remove output filters before display of markdown content (and automatically restore for mixed content)
		add_filter( 'bbp_get_reply_content', 'bbpmd_maybe_remove_reply_content_filters', ~PHP_INT_MAX, 2 );
		add_filter( 'bbp_get_topic_content', 'bbpmd_maybe_remove_topic_content_filters', ~PHP_INT_MAX, 2 );

	}
}
add_action( 'bbp_ready', 'bbpmd_remove_bbp_filters' );

function bbpmd_maybe_remove_reply_content_filters( $content, $reply_id ) {
	if ( !empty( get_post_meta( $reply_id, 'bbpmd_markdown', true ) ) ) {
		global $wp_embed;
		global $bbpmd_removed;
		$bbpmd_removed = [];
		$bbpmd_removed['bbp_make_clickable'] = 	remove_filter( 'bbp_get_reply_content', 'bbp_make_clickable', 4    );
		$bbpmd_removed['wptexturize'] = 		remove_filter( 'bbp_get_reply_content', 'wptexturize',        6    );
		$bbpmd_removed['convert_chars'] = 		remove_filter( 'bbp_get_reply_content', 'convert_chars',      8    );
		$bbpmd_removed['capital_P_dangit'] = 	remove_filter( 'bbp_get_reply_content', 'capital_P_dangit',   10   );
		//$bbpmd_removed['convert_smilies'] = 	remove_filter( 'bbp_get_reply_content', 'convert_smilies',    20   );
		$bbpmd_removed['force_balance_tags'] = 	remove_filter( 'bbp_get_reply_content', 'force_balance_tags', 30   );
		$bbpmd_removed['wpautop'] = 			remove_filter( 'bbp_get_reply_content', 'wpautop',            40   );
		//$bbpmd_removed['bbp_rel_nofollow'] = 	remove_filter( 'bbp_get_reply_content', 'bbp_rel_nofollow',   50   );
		$bbpmd_removed['autoembed'] = 			remove_filter( 'bbp_get_reply_content', array( $wp_embed, 'autoembed' ), 2 );
		add_filter( 'bbp_get_reply_content', 'bbpmd_restore_reply_content_filters', PHP_INT_MAX, 2 );
	}
	return $content;
}

function bbpmd_restore_reply_content_filters( $content, $reply_id ) {
	global $wp_embed;
	global $bbpmd_removed;
	if ( $bbpmd_removed['bbp_make_clickable'] ) { add_filter( 'bbp_get_reply_content', 'bbp_make_clickable', 4    ); }
	if ( $bbpmd_removed['wptexturize'] ) 		{ add_filter( 'bbp_get_reply_content', 'wptexturize',        6    ); }
	if ( $bbpmd_removed['convert_chars'] ) 		{ add_filter( 'bbp_get_reply_content', 'convert_chars',      8    ); }
	if ( $bbpmd_removed['capital_P_dangit'] ) 	{ add_filter( 'bbp_get_reply_content', 'capital_P_dangit',   10   ); }
	//if ( $bbpmd_removed['convert_smilies'] ) 	{ add_filter( 'bbp_get_reply_content', 'convert_smilies',    20   ); }
	if ( $bbpmd_removed['force_balance_tags'] ) { add_filter( 'bbp_get_reply_content', 'force_balance_tags', 30   ); }
	if ( $bbpmd_removed['wpautop'] ) 			{ add_filter( 'bbp_get_reply_content', 'wpautop',            40   ); }
	//if ( $bbpmd_removed['bbp_rel_nofollow'] ) { add_filter( 'bbp_get_reply_content', 'bbp_rel_nofollow',   50   ); }
	if ( $bbpmd_removed['autoembed'] ) 			{ add_filter( 'bbp_get_reply_content', array( $wp_embed, 'autoembed' ), 2 ); }
	return $content;
}

function bbpmd_maybe_remove_topic_content_filters( $content, $topic_id ) {
	if ( !empty( get_post_meta( $topic_id, 'bbpmd_markdown', true ) ) ) {
		global $wp_embed;
		global $bbpmd_removed;
		$bbpmd_removed = [];
		$bbpmd_removed['bbp_make_clickable'] = 	remove_filter( 'bbp_get_topic_content', 'bbp_make_clickable', 4    );
		$bbpmd_removed['wptexturize'] = 		remove_filter( 'bbp_get_topic_content', 'wptexturize',        6    );
		$bbpmd_removed['convert_chars'] = 		remove_filter( 'bbp_get_topic_content', 'convert_chars',      8    );
		$bbpmd_removed['capital_P_dangit'] = 	remove_filter( 'bbp_get_topic_content', 'capital_P_dangit',   10   );
		//$bbpmd_removed['convert_smilies'] = 	remove_filter( 'bbp_get_topic_content', 'convert_smilies',    20   );
		$bbpmd_removed['force_balance_tags'] = 	remove_filter( 'bbp_get_topic_content', 'force_balance_tags', 30   );
		$bbpmd_removed['wpautop'] = 			remove_filter( 'bbp_get_topic_content', 'wpautop',            40   );
		//$bbpmd_removed['bbp_rel_nofollow'] = 	remove_filter( 'bbp_get_topic_content', 'bbp_rel_nofollow',   50   );
		$bbpmd_removed['autoembed'] = 			remove_filter( 'bbp_get_topic_content', array( $wp_embed, 'autoembed' ), 2 );
		add_filter( 'bbp_get_topic_content', 'bbpmd_restore_topic_content_filters', PHP_INT_MAX, 2 );
	}
	return $content;
}

function bbpmd_restore_topic_content_filters( $content, $topic_id ) {
	global $wp_embed;
	global $bbpmd_removed;
	if ( $bbpmd_removed['bbp_make_clickable'] ) { add_filter( 'bbp_get_topic_content', 'bbp_make_clickable', 4    ); }
	if ( $bbpmd_removed['wptexturize'] ) 		{ add_filter( 'bbp_get_topic_content', 'wptexturize',        6    ); }
	if ( $bbpmd_removed['convert_chars'] ) 		{ add_filter( 'bbp_get_topic_content', 'convert_chars',      8    ); }
	if ( $bbpmd_removed['capital_P_dangit'] ) 	{ add_filter( 'bbp_get_topic_content', 'capital_P_dangit',   10   ); }
	//if ( $bbpmd_removed['convert_smilies'] ) 	{ add_filter( 'bbp_get_topic_content', 'convert_smilies',    20   ); }
	if ( $bbpmd_removed['force_balance_tags'] ) { add_filter( 'bbp_get_topic_content', 'force_balance_tags', 30   ); }
	if ( $bbpmd_removed['wpautop'] ) 			{ add_filter( 'bbp_get_topic_content', 'wpautop',            40   ); }
	//if ( $bbpmd_removed['bbp_rel_nofollow'] ) { add_filter( 'bbp_get_topic_content', 'bbp_rel_nofollow',   50   ); }
	if ( $bbpmd_removed['autoembed'] ) 			{ add_filter( 'bbp_get_topic_content', array( $wp_embed, 'autoembed' ), 2 ); }
	return $content;
}

function bbpmd_load_markdown_if_present( $content = '' ) {

	// only on edits
	if ( bbp_is_topic_edit() || bbp_is_reply_edit() ) {

		// display markdown instead of html if previously saved
		// note: "disable plugin -> edit1 -> enable plugin -> edit2" -> changes of edit1 are lost
		//		 we could delete existing markdown on deactivation,
		//       or we could compare saved markdown to markdown derived from html,
		//		 but the resulting markdown may differ slightly, so let's just keep it like this
		$post_id = bbp_get_global_post_field( 'ID', 'raw' );
		$markdown = get_post_meta( $post_id, 'bbpmd_markdown', true );
		if ( !empty( $markdown ) ) {
			return $markdown;
		}
	}

	return $content;
}

// convert posted markdown to html and save to post content
add_filter( 'bbp_new_reply_pre_content', 'bbpmd_new_reply', 5, 1 );
add_filter( 'bbp_edit_reply_pre_content', 'bbpmd_edit_reply', 5, 1 );
add_filter( 'bbp_new_topic_pre_content', 'bbpmd_new_topic', 5, 1 );
add_filter( 'bbp_edit_topic_pre_content', 'bbpmd_edit_topic', 5, 1 );

function bbpmd_new_reply( $content ) {
	$content = bbpmd_markdown_to_html( $content );

	// adapted from bbp_new_reply_handler()
	$topic_id = $_POST['bbp_topic_id'];
	if ( current_user_can( 'unfiltered_html' ) && !empty( $_POST['_bbp_unfiltered_html_reply'] ) && wp_create_nonce( 'bbp-unfiltered-html-reply_' . $topic_id ) === $_POST['_bbp_unfiltered_html_reply'] ) {
		return $content;
	}

	return bbp_filter_kses( $content );
}

function bbpmd_edit_reply( $content ) {
	$content = bbpmd_markdown_to_html( $content );

	// adapted from bbp_edit_reply_handler()
	$reply_id = $_POST['bbp_reply_id'];
	if ( current_user_can( 'unfiltered_html' ) && !empty( $_POST['_bbp_unfiltered_html_reply'] ) && wp_create_nonce( 'bbp-unfiltered-html-reply_' . $reply_id ) === $_POST['_bbp_unfiltered_html_reply'] ) {
		return $content;
	}

	return bbp_filter_kses( $content );
}

function bbpmd_new_topic( $content ) {
	$content = bbpmd_markdown_to_html( $content );

	// adapted from bbp_new_topic_handler()
	if ( current_user_can( 'unfiltered_html' ) && !empty( $_POST['_bbp_unfiltered_html_topic'] ) && wp_create_nonce( 'bbp-unfiltered-html-topic_new' ) === $_POST['_bbp_unfiltered_html_topic'] ) {
		return $content;
	}

	return bbp_filter_kses( $content );
}

function bbpmd_edit_topic( $content ) {
	$content = bbpmd_markdown_to_html( $content );

	// adapted from bbp_edit_topic_handler()
	$topic_id = $_POST['bbp_topic_id'];
	if ( current_user_can( 'unfiltered_html' ) && !empty( $_POST['_bbp_unfiltered_html_topic'] ) && ( wp_create_nonce( 'bbp-unfiltered-html-topic_' . $topic_id ) === $_POST['_bbp_unfiltered_html_topic'] ) ) {
		return $content;
	}

	return bbp_filter_kses( $content );
}

function bbpmd_markdown_to_html( $content ) {

	// convert markdown to html
	$content = stripslashes( $content );
	$content = MarkdownExtra::defaultTransform( $content );
	$content = addslashes( $content );

	// manually apply removed bbp filters
	//$content = bbp_encode_bad( $content ); // adds <p>s where there shouldn't be any, let's assume MarkdownExtra generates proper html
	//$content = bbp_code_trick( $content ); // we have markdown, we no longer need code tricks

	return $content;
}

// save posted markdown to post meta
add_action( 'bbp_new_topic_post_extras', 'bbpmd_save_markdown' );
add_action( 'bbp_edit_topic_post_extras', 'bbpmd_save_markdown' );
add_action( 'bbp_new_reply_post_extras', 'bbpmd_save_markdown' );
add_action( 'bbp_edit_reply_post_extras', 'bbpmd_save_markdown' );

function bbpmd_save_markdown( $post_id ) {
	if ( !empty( $_POST['bbp_topic_content'] ) ) {
		$markdown = $_POST['bbp_topic_content'];
	} else if ( !empty( $_POST['bbp_reply_content'] ) ) {
		$markdown = $_POST['bbp_reply_content'];
	}
	if ( !empty( $markdown ) ) {
		update_post_meta( $post_id, 'bbpmd_markdown', $markdown );
	}
}

// add tabs to editor
add_action( 'bbp_theme_before_reply_form_content', 'bbpmd_before_editor' );
add_action( 'bbp_theme_after_reply_form_content', 'bbpmd_after_editor' );
add_action( 'bbp_theme_before_topic_form_content', 'bbpmd_before_editor' );
add_action( 'bbp_theme_after_topic_form_content', 'bbpmd_after_editor' );

function bbpmd_before_editor() {
?>
<div class="bbpmd">
	<div class="responsive-tabs">
		<h2 class="bbpmd-markdown-header"><?php echo apply_filters( 'bbpmd_markdown_title', __( 'Write', 'bbp-markdown' ) ); ?></h2>
		<div class="bbpmd-markdown-panel">
<?php
}

function bbpmd_after_editor() {
?>
		</div>
		<h2 class="bbpmd-preview-header"><?php echo apply_filters( 'bbpmd_preview_title', __( 'Preview', 'bbp-markdown' ) ); ?></h2>
		<div class="bbpmd-preview-panel"></div>
		<h2 class="bbpmd-help-header"><?php echo apply_filters( 'bbpmd_help_title', __( 'Help', 'bbp-markdown' ) ); ?></h2>
		<div class="bbpmd-help-panel">
		<?php do_action( 'bbpmd_help_content' ); ?>
		</div>
	</div>
</div>
<?php
}

// adding default help content as an action. unhook to provide your own.
add_action( 'bbpmd_help_content', 'bbpmd_display_help_content' );
function bbpmd_display_help_content() {
?>
			<p><?php _e( 'This forum supports the Markdown(Extra) syntax, here are some examples:', 'bbp-markdown' ); ?></p>
			<table>
				<tr><th><?php _e( 'Markdown Code', 'bbp-markdown' ); ?></th><th><?php _e( 'Result', 'bbp-markdown' ); ?></th></tr>
				<tr><td><?php echo __( 'line 1', 'bbp-markdown' ) . '&nbsp;&nbsp;<span class="bbpmd-comment">// ' . __( 'there are two spaces at the end', 'bbp-markdown' ) . '</span><br>' . __( 'line 2', 'bbp-markdown' ); ?></td><td><?php echo '<p>' . __( 'line 1', 'bbp-markdown' ) . '<br>' . __( 'line 2', 'bbp-markdown' ) . '</p>'; ?></td></tr>
				<tr><td><?php echo __( 'paragraph 1', 'bbp-markdown' ) . '<br><span class="bbpmd-comment">// ' . __( 'this line is empty', 'bbp-markdown' ) . '</span><br>' . __( 'paragraph 2', 'bbp-markdown' ); ?></td><td><?php echo '<p>' . __( 'paragraph 1', 'bbp-markdown' ) . '</p>' . '<p>' . __( 'paragraph 2', 'bbp-markdown' ) . '</p>'; ?></td></tr>
				<tr><td>*<?php _e( 'emphasis', 'bbp-markdown' ); ?>*</td><td><em><?php _e( 'emphasis', 'bbp-markdown' ); ?></em></td></tr>
				<tr><td>**<?php _e( 'strong emphasis', 'bbp-markdown' ); ?>**</td><td><strong><?php _e( 'strong emphasis', 'bbp-markdown' ); ?></strong></td></tr>
				<tr><td>[<?php _e( 'a hyperlink', 'bbp-markdown' ); ?>](<?php echo apply_filters( 'bbpmd_help_hyperlink', get_home_url() ); ?>)</td><td><a href="<?php echo get_home_url(); ?>"><?php _e( 'a hyperlink', 'bbp-markdown' ); ?></a></td></tr>
				<tr><td>![<?php _e( 'an image', 'bbp-markdown' ); ?>](<?php echo apply_filters( 'bbpmd_help_image_display_url', get_home_url() . '/...logo.png' ); ?>)</td><td><img alt="<?php _e( 'an image', 'bbp-markdown' ); ?>" src="<?php echo apply_filters( 'bbpmd_help_image_url', get_home_url() . '/wp-includes/images/w-logo-blue.png' ); ?>" /></td></tr>
				<tr><td>&gt; <?php _e( 'this is a quote', 'bbp-markdown' ); ?></td><td><blockquote><?php _e( 'this is a quote', 'bbp-markdown' ); ?></blockquote></td></tr>
				<tr><td>* <?php _e( 'one', 'bbp-markdown' ); ?><br>* <?php _e( 'two', 'bbp-markdown' ); ?><br>&nbsp;* <?php _e( 'two', 'bbp-markdown' ); ?>.<?php _e( 'one', 'bbp-markdown' ); ?></td><td><ul><li><?php _e( 'one', 'bbp-markdown' ); ?></li><li><?php _e( 'two', 'bbp-markdown' ); ?><ul><li><?php _e( 'two', 'bbp-markdown' ); ?>.<?php _e( 'one', 'bbp-markdown' ); ?></li></ul></li></ul></td></tr>
				<tr><td>1. <?php _e( 'one', 'bbp-markdown' ); ?><br>2. <?php _e( 'two', 'bbp-markdown' ); ?><br>&nbsp;1. <?php _e( 'two', 'bbp-markdown' ); ?>.<?php _e( 'one', 'bbp-markdown' ); ?></td><td><ol><li><?php _e( 'one', 'bbp-markdown' ); ?></li><li><?php _e( 'two', 'bbp-markdown' ); ?><ol><li><?php _e( 'two', 'bbp-markdown' ); ?>.<?php _e( 'one', 'bbp-markdown' ); ?></li></ol></li></ol></td></tr>
				<tr><td># <?php _e( 'headline', 'bbp-markdown' ); ?> 1</td><td><h1><?php _e( 'headline', 'bbp-markdown' ); ?> 1</h1></td></tr>
				<tr><td>## <?php _e( 'headline', 'bbp-markdown' ); ?> 2</td><td><h2><?php _e( 'headline', 'bbp-markdown' ); ?> 2</h2></td></tr>
				<tr><td>---</td><td><hr></td></tr>
				<tr><td><?php _e( 'fruit', 'bbp-markdown' ); ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | <?php _e( 'amount', 'bbp-markdown' ); ?><br>
				---------- | ---------</br>
				<?php _e( 'apples', 'bbp-markdown' ); ?> &nbsp;| 10</br>
				<?php _e( 'oranges', 'bbp-markdown' ); ?>       |  5
				</td><td><table>
						<thead><tr><th><?php _e( 'fruit', 'bbp-markdown' ); ?></th><th><?php _e( 'amount', 'bbp-markdown' ); ?></th></tr></thead>
						<tbody><tr><td><?php _e( 'apples', 'bbp-markdown' ); ?></td><td>10</td></tr>
						<tr><td><?php _e( 'oranges', 'bbp-markdown' ); ?></td><td>5</td></tr></tbody>
				</table></td></tr>
			</table>
<?php
}

// ajax handling for preview generation
add_action( 'wp_ajax_bbpmd_preview', 'bbpmd_preview' );
add_action( 'wp_ajax_nopriv_bbpmd_preview', 'bbpmd_preview' );

function bbpmd_preview() {
	if ( !isset( $_POST['markdown'] ) || !isset( $_POST['nonce'] ) ) {
		wp_die( __( 'error', 'bbp-markdown' ) );
	}
	check_ajax_referer( 'bbpmd' . get_home_url(), 'nonce' );
	$html = MarkdownExtra::defaultTransform( stripslashes( $_POST['markdown'] ) );
	if ( current_user_can( 'unfiltered_html' ) ) {
		echo $html;
	} else {
		echo stripslashes( bbp_filter_kses( $html ) );
	}
	wp_die();
}

?>
