<?php
/**
* Plugin Name: FeenBan
* Plugin URI: http://anothercoffee.net/feenban
* Description: A plugin for shadowbanning commenters.
* Version: 0.2
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
define( 'FEENBAN_VERSION', '0.2' );
define( 'FEENBAN_REQUIRED_WP_VERSION', '3.9' );
define( 'FEENBAN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FEENBAN_PLUGIN_NAME', trim( dirname( FEENBAN_PLUGIN_BASENAME ), '/' ) );
define( 'FEENBAN_PLUGIN_URL', WP_PLUGIN_URL."/".dirname( plugin_basename( __FILE__ ) ) );
define( 'FEENBAN_PLUGIN_DIR', WP_PLUGIN_DIR."/".dirname( plugin_basename( __FILE__ ) ) );
define( 'FEENBAN_PLUGIN_MODULES_DIR', FEENBAN_PLUGIN_DIR . '/modules' );
define( 'FEENBAN_TEMPLATE_COMMENTS', '/feenban-comments.php' );
define( 'FEENBAN_AUTHOR_METAKEY_BANNED', 'acc_feenbanned');
define( 'FEENBAN_DEFAULT_NOTIFICATION_MSG', 'Comment not displayed due to shadowban.');

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
	
	$shadowbanned_meta = get_the_author_meta( FEENBAN_AUTHOR_METAKEY_BANNED, $comment_author_id );	
	$author_is_shadowbanned	= false;
	if ( !empty($shadowbanned_meta) ){
		if (strcasecmp($shadowbanned_meta, "true") == 0) {
			$author_is_shadowbanned = true;
		}
	}
	
	$user_is_shadowbanned = false;
	$shadowbanned_meta = get_the_author_meta( FEENBAN_AUTHOR_METAKEY_BANNED, $comment_author_id );
	if ( !empty($shadowbanned_meta) ){
		if (strcasecmp($shadowbanned_meta, "true") == 0) {
			$user_is_shadowbanned = true;
		}
	}	

	if ( ($user_owns_comment == false) && $author_is_shadowbanned  ) {
		$options = get_option('feenban_option_name');		
		$status = $options['show_status'];		
		if (strcasecmp($status, "true") == 0) {		
		?>		
			<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
	        	<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">	
		<?php
			$options = get_option('feenban_option_name');
			$notification_string = $options['notification_string'];
			echo "<div class=\"shadowban\"><p>".esc_html($notification_string)."</p></div>";			
		}
	} else {
		?>
		<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
	        <article id="div-comment-<?php comment_ID(); ?>" class="comment-body">	
		<?php		
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
		$shadowbanned_meta = esc_attr(get_the_author_meta( FEENBAN_AUTHOR_METAKEY_BANNED, $user->ID ));	
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
						<input type="checkbox" name="acc_feenbanned" value="true" 
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
	if (isset($_POST['acc_feenbanned'])) {
		if (strcasecmp($_POST['acc_feenbanned'], "true") == 0) {
			$meta_value = "true";
		}
	}
    update_user_meta( 
		$user_id,
		FEENBAN_AUTHOR_METAKEY_BANNED,
		$meta_value
	);
}
add_action( 'personal_options_update', 'save_shadowban_setting' );
add_action( 'edit_user_profile_update', 'save_shadowban_setting' );



/***
 * Use Settings API to handle plugin settings
 */
add_action('admin_menu', 'feenban_admin_add_page');
function feenban_admin_add_page() {
	add_options_page(
		'FeenBan Settings',
		'FeenBan',
		'manage_options',
		'feenban_settings',
		'feenban_options_page'
		);
}

function feenban_options_page() {
	if(!current_user_can('manage_options')) {
		die('You do not have access to this page');
	}
	
	?>
	<div>
	<h2>FeenBan</h2>
	<form action="options.php" method="post">
	<?php settings_fields('feenban_options_group'); ?>
	<?php do_settings_sections('feenban_settings'); ?>
 
	<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
	</form></div>
 
	<?php
}

add_action('admin_init', 'feenban_admin_init');
function feenban_admin_init() {
	register_setting( 
		'feenban_options_group',
		'feenban_option_name',
		'feenban_options_validate'
	);
	add_settings_section(
		'feenban_main',
		'Display settings',
		'feenban_section_text',
		'feenban_settings'
	);
	add_settings_field(
		'feenban_show_status',
		'Show shadowban status',
		'feenban_setting_show_status',
		'feenban_settings',
		'feenban_main'
	);
	add_settings_field(
		'feenban_notification_string',
		'Banned message',
		'feenban_notification_string',
		'feenban_settings',
		'feenban_main'
	);	
}

function feenban_section_text() {
	echo '<p>Do you want to let readers know that you have shadowbanning moderation in place?</p>';
}

function feenban_notification_string() {
	$options = get_option('feenban_option_name');
	echo "<input id='feenban_notification_string' name='feenban_option_name[notification_string]' size='40' type='text' value='{$options['notification_string']}' />";
	echo "<p class=\"description\">Message shown to comment readers when a comment author has been shadowbanned. Only displayed when you have <strong>Show shadowban status</strong> turned on.</p>";
}


/*
 * Option to show notice that a comment author has been shadowbanned
 */
function feenban_setting_show_status() {
	$options = get_option('feenban_option_name');
	$value = $options['show_status'];

	echo "<input type=\"checkbox\" name=\"feenban_option_name[show_status]\" value=\"true\""; 
	if (strcasecmp($value, "true") == 0) {
		echo "checked=\"checked\"";
	}
	echo "/ > Notify reader in comments section that a comment author has been shadowbanned";
	echo "<p class=\"description\">Turn this off if you don't want to make it obvious that you are shadow banning comments.</p>";
}


function feenban_options_validate($input) {
	$options = get_option('feenban_option_name');	
	$options['notification_string'] = FEENBAN_DEFAULT_NOTIFICATION_MSG;
	$message = trim($input['notification_string']);
	
	if(!empty($message))
	{
		$options['notification_string'] = sanitize_text_field($message);
	}
	
	$options['show_status'] = trim($input['show_status']);
	if (strcasecmp($options['show_status'], "true") == 0) {
		$options['show_status'] = 'true';
	} else {
		$options['show_status'] = 'false';		
	}
	
	return $options;
}