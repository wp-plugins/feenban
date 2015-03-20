<?php
/**
* Plugin Name: FeenBan
* Plugin URI: http://anothercoffee.net/feenban
* Description: A plugin for shadowbanning commenters.
* Version: 0.1
* Author: Anthony Lopez-Vito
* Author URI: http://anothercoffee.net
**/

/* 
 * For security as specified in
 * http://codex.wordpress.org/Writing_a_Plugin
 */
defined('ABSPATH') or die("No script kiddies please!");

/* 
 * Defs
 */
define( 'FEENBAN_VERSION', '0.1' );
define( 'FEENBAN_REQUIRED_WP_VERSION', '3.9' );
define( 'FEENBAN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FEENBAN_PLUGIN_NAME', trim( dirname( FEENBAN_PLUGIN_BASENAME ), '/' ) );
define( 'FEENBAN_PLUGIN_URL', WP_PLUGIN_URL."/".dirname( plugin_basename( __FILE__ ) ) );
define( 'FEENBAN_PLUGIN_DIR', WP_PLUGIN_DIR."/".dirname( plugin_basename( __FILE__ ) ) );
define( 'FEENBAN_PLUGIN_MODULES_DIR', FEENBAN_PLUGIN_DIR . '/modules' );
define( 'FEENBAN_TEMPLATE_COMMENTS', '/feenban-comments.php' );


/***
 *
 */
function feenban_enqueuescripts() {
	wp_enqueue_style( 'feenban-style', FEENBAN_PLUGIN_URL.'/feenban.css' );
}
add_action('wp_enqueue_scripts', feenban_enqueuescripts);

/***
 * Append the feenban shadownban control at the end of the comment
 * but only for users with edit_users permission
 */
function feenban_comment_text($content) {
	$content_display = "";
	if (is_user_logged_in()) {
		$content_display .= $content;
	} else {
		$content_display .= "Please login to show comment";
	}
	return $content_display;
}
add_filter('comment_text', feenban_comment_text);


/***
 * Replace comments template with the feenban template
 */
function feenban_comment_template( $comment_template ) {
	$template_file = "";
	$tpl_module = FEENBAN_PLUGIN_DIR . FEENBAN_TEMPLATE_COMMENTS;
	$tpl_current_theme = get_template_directory().FEENBAN_TEMPLATE_COMMENTS;

	// Favour the template in the user's current theme
	if(file_exists($tpl_current_theme)) {
		$template_file = $tpl_current_theme;
	} elseif(file_exists($tpl_module)) {
		$template_file = $tpl_module;
	} else {
		error_log("Feenban template file does not exist: ".FEENBAN_TEMPLATE_COMMENTS);
	}
	return $template_file;
}
add_filter( "comments_template", "feenban_comment_template" );


/***
 * Comments callback implementing shadowban rule
 */
function feenban_comments_callback( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment; 
	
	$current_user = wp_get_current_user();
	$comment_author_id = $comment->user_id;

	$user_owns_comment = false;
	if ($current_user->ID == $comment_author_id) {
		$user_owns_comment = true;
	}
	
	$shadowbanned_meta = get_the_author_meta( 'shadowbanned', $comment_author_id );	
	$author_is_shadowbanned	= false;
	if ( !empty($shadowbanned_meta) ){
		if (strcasecmp($shadowbanned_meta, "true") == 0) {
			$author_is_shadowbanned = true;
		}
	}
	
	$user_is_shadowbanned = false;
	$shadowbanned_meta = get_the_author_meta( 'shadowbanned', $comment_author_id );
	if ( !empty($shadowbanned_meta) ){
		if (strcasecmp($shadowbanned_meta, "true") == 0) {
			$user_is_shadowbanned = true;
		}
	}	
	?>
	
	<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
        <article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
	
	<?php
	if ( ($user_owns_comment == false) && $author_is_shadowbanned  ) {
		echo "<div class=\"shadowban\"><p>Comment not displayed due to shadowban.</p></div>";
	} else {
		feenban_display_comment($comment, $args, $depth);
	}
	
	echo "</article>";
	//* No ending </li> tag because of comment threading
}


/***
 * Display comment with Twenty Fourteen theme CSS
 */
function feenban_display_comment($comment, $args, $depth) {
	?>
		<footer class="comment-meta">
			<div class="comment-author vcard">
				<?php
				echo get_avatar( $comment, $args['avatar_size'] );

				$author = get_comment_author();
				$url    = get_comment_author_url();

				if ( ! empty( $url ) && 'http://' !== $url ) {
					$author = sprintf( '<a href="%s" rel="external nofollow" itemprop="url">%s</a>', esc_url( $url ), $author );
				}

				printf( '<b class="fn">%s</b> <span class="says">%s</span>', $author, apply_filters( 'comment_author_says_text', __( 'says', 'theme-textdomain' ) ) );
				?>
		 	</div><!-- .comment-author -->
 
			<div class="comment-metadata">
				<?php
				$pattern = '<time datetime="%s"><a href="%s">%s %s %s</a></time>';
				printf( $pattern, esc_attr( get_comment_time( 'c' ) ), esc_url( get_comment_link( $comment->comment_ID ) ), esc_html( get_comment_date() ), __( 'at', 'theme-textdomain' ), esc_html( get_comment_time() ) );

				edit_comment_link( __( '(Edit)', 'theme-textdomain' ), ' ' );
				?>

			</div><!-- .comment-metadata -->
		</footer><!-- .comment-meta -->
 
		<div class="comment-content">
			<?php if ( ! $comment->comment_approved ) : ?>
				<p class="alert"><?php echo __( 'Your comment is awaiting moderation.', 'theme-textdomain' ); ?></p>
			<?php endif; ?>
 
			<?php comment_text(); ?>
		</div><!-- .comment-content -->
 
		<?php
		comment_reply_link( array_merge( $args, array(
			'depth'  => $depth,
			'before' => '<div class="reply">',
			'after'  => '</div>',
		) ) );
}


/***
 * User settings page 
 *
 */
function add_shadowban_setting( $user )
{
	if ( current_user_can( 'edit_users' ) ) {
		$shadowbanned_meta = esc_attr(get_the_author_meta( 'shadowbanned', $user->ID ));	
		$is_shadowbanned = false;
		if ( !empty($shadowbanned_meta) ){
			if (strcasecmp($shadowbanned_meta, "true") == 0) {
				$is_shadowbanned = true;
			}
		}	
	    ?>
	        <h3>FeenBan</h3>

	        <table class="form-table">
	            <tr>
	                <th><label for="facebook_profile">Shadow ban user</label></th>
	                <td>
						<input type="checkbox" name="shadowbanned" value="true" 
						<?php
						if ($is_shadowbanned) {
							echo "checked=\"checked\"";
						}
						?>
						/><br/><span class="description">If shadowbanned, only the user can see their comments. Shadowbanned users' comments will be invisible to everyone else.</span>
					</td>
	            </tr>
	        </table>
	    <?php
	}
}
add_action( 'show_user_profile', 'add_shadowban_setting' );
add_action( 'edit_user_profile', 'add_shadowban_setting' );


/***
 * 
 */
function save_shadowban_setting( $user_id )
{
	$meta_value = "false";
	if (isset($_POST['shadowbanned'])) {
		if (strcasecmp($_POST['shadowbanned'], "true") == 0) {
			$meta_value = "true";
		}
	}
    update_user_meta( 
		$user_id,
		'shadowbanned',
		$meta_value
	);
}
add_action( 'personal_options_update', 'save_shadowban_setting' );
add_action( 'edit_user_profile_update', 'save_shadowban_setting' );