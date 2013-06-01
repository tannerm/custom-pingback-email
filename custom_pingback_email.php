<?php
/**
 * Plugin Name: Custom Pingback Email
 * Plugin URI:  http://wordpress.org/extend/plugins
 * Description: Add the option for a pingback and trackback notification to go to an email other than the site admin email.
 * Version:     0.1.0
 * Author:      Tanner Moushey
 * Author URI:  tannermoushey.com
 * License:     GPLv2+
 * Text Domain: cpe
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2013 Tanner Moushey (email : tanner@moushey.us)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using grunt-wp-plugin
 * Copyright (c) 2013 10up, LLC
 * https://github.com/10up/grunt-wp-plugin
 */

// Useful global constants
define( 'CPE_VERSION', '0.1.0' );
define( 'CPE_URL',     plugin_dir_url( __FILE__ ) );
define( 'CPE_PATH',    dirname( __FILE__ ) . '/' );

/**
 * Default initialization for the plugin:
 * - Registers the default textdomain.
 */
function cpe_init() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'cpe' );
	load_textdomain( 'cpe', WP_LANG_DIR . '/cpe/cpe-' . $locale . '.mo' );
	load_plugin_textdomain( 'cpe', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	include( CPE_PATH . '/includes/cpe_settings_field.php' );
}

/**
 * Activate the plugin
 */
function cpe_activate() {
	// First load the init scripts in case any rewrite functionality is being loaded
	cpe_init();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'cpe_activate' );

/**
 * Deactivate the plugin
 * Uninstall routines should be in uninstall.php
 */
function cpe_deactivate() {

}
register_deactivation_hook( __FILE__, 'cpe_deactivate' );

// Wireup actions
add_action( 'init', 'cpe_init' );

if ( !function_exists('wp_notify_moderator') ) :
	/**
	 * Notifies the moderator of the blog about a new comment that is awaiting approval.
	 *
	 * @since 1.0
	 * @uses $wpdb
	 *
	 * @param int $comment_id Comment ID
	 * @return bool Always returns true
	 */
	function wp_notify_moderator($comment_id) {
		global $wpdb;

		if ( 0 == get_option( 'moderation_notify' ) )
			return true;

		$comment = get_comment($comment_id);
		$post = get_post($comment->comment_post_ID);
		$user = get_userdata( $post->post_author );
		$ping_email = get_option( 'cpe_pingback_email' );
		// Send to the administration and to the post author if the author can modify the comment.
		$email_to = array( get_option('admin_email') );
		if ( user_can($user->ID, 'edit_comment', $comment_id) && !empty($user->user_email) && ( get_option('admin_email') != $user->user_email) )
			$email_to[] = $user->user_email;

		$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);
		$comments_waiting = $wpdb->get_var("SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'");

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		switch ($comment->comment_type)
		{
			case 'trackback':
				$notify_message  = sprintf( __('A new trackback on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
				$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
				$notify_message .= sprintf( __('Website : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
				$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
				$notify_message .= __('Trackback excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
				// if ping email is set, overwrite admin and author email
				if ( $ping_email )
					$email_to = $ping_email;
				break;
			case 'pingback':
				$notify_message  = sprintf( __('A new pingback on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
				$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
				$notify_message .= sprintf( __('Website : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
				$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
				$notify_message .= __('Pingback excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
				// if ping email is set, overwrite admin and author email
				if ( $ping_email )
					$email_to = $ping_email;
				break;
			default: //Comments
				$notify_message  = sprintf( __('A new comment on the post "%s" is waiting for your approval'), $post->post_title ) . "\r\n";
				$notify_message .= get_permalink($comment->comment_post_ID) . "\r\n\r\n";
				$notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
				$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
				$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
				$notify_message .= sprintf( __('Whois  : http://whois.arin.net/rest/ip/%s'), $comment->comment_author_IP ) . "\r\n";
				$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
				break;
		}

		$notify_message .= sprintf( __('Approve it: %s'),  admin_url("comment.php?action=approve&c=$comment_id") ) . "\r\n";
		if ( EMPTY_TRASH_DAYS )
			$notify_message .= sprintf( __('Trash it: %s'), admin_url("comment.php?action=trash&c=$comment_id") ) . "\r\n";
		else
			$notify_message .= sprintf( __('Delete it: %s'), admin_url("comment.php?action=delete&c=$comment_id") ) . "\r\n";
		$notify_message .= sprintf( __('Spam it: %s'), admin_url("comment.php?action=spam&c=$comment_id") ) . "\r\n";

		$notify_message .= sprintf( _n('Currently %s comment is waiting for approval. Please visit the moderation panel:',
			'Currently %s comments are waiting for approval. Please visit the moderation panel:', $comments_waiting), number_format_i18n($comments_waiting) ) . "\r\n";
		$notify_message .= admin_url("edit-comments.php?comment_status=moderated") . "\r\n";

		$subject = sprintf( __('[%1$s] Please moderate: "%2$s"'), $blogname, $post->post_title );
		$message_headers = '';

		$notify_message = apply_filters('comment_moderation_text', $notify_message, $comment_id);
		$subject = apply_filters('comment_moderation_subject', $subject, $comment_id);
		$message_headers = apply_filters('comment_moderation_headers', $message_headers);

		foreach ( $email_to as $email )
			@wp_mail($email, $subject, $notify_message, $message_headers);

		return true;
	}
endif;

if ( ! function_exists('wp_notify_postauthor') ) :
	/**
	 * Notify an author of a comment/trackback/pingback to one of their posts.
	 *
	 * @since 1.0.0
	 *
	 * @param int $comment_id Comment ID
	 * @param string $comment_type Optional. The comment type either 'comment' (default), 'trackback', or 'pingback'
	 * @return bool False if user email does not exist. True on completion.
	 */
	function wp_notify_postauthor( $comment_id, $comment_type = '' ) {

		$comment = get_comment( $comment_id );
		$post    = get_post( $comment->comment_post_ID );
		$author  = get_userdata( $post->post_author );
		$ping_email = get_option( 'cpe_pingback_email' );

		// The comment was left by the author
		if ( $comment->user_id == $post->post_author )
			return false;

		// The author moderated a comment on his own post
		if ( $post->post_author == get_current_user_id() )
			return false;

		// If there's no email to send the comment to
		if ( '' == $author->user_email )
			return false;

		$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		if ( empty( $comment_type ) ) $comment_type = 'comment';

		// Do not send email to author if ping_email is set
		if ( 'comment' != $comment_type && $ping_email )
			return false;

		if ('comment' == $comment_type) {
			$notify_message  = sprintf( __( 'New comment on your post "%s"' ), $post->post_title ) . "\r\n";
			/* translators: 1: comment author, 2: author IP, 3: author domain */
			$notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
			$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
			$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
			$notify_message .= sprintf( __('Whois  : http://whois.arin.net/rest/ip/%s'), $comment->comment_author_IP ) . "\r\n";
			$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
			$notify_message .= __('You can see all comments on this post here: ') . "\r\n";
			/* translators: 1: blog name, 2: post title */
			$subject = sprintf( __('[%1$s] Comment: "%2$s"'), $blogname, $post->post_title );
		} elseif ('trackback' == $comment_type) {
			$notify_message  = sprintf( __( 'New trackback on your post "%s"' ), $post->post_title ) . "\r\n";
			/* translators: 1: website name, 2: author IP, 3: author domain */
			$notify_message .= sprintf( __('Website: %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
			$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
			$notify_message .= __('Excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
			$notify_message .= __('You can see all trackbacks on this post here: ') . "\r\n";
			/* translators: 1: blog name, 2: post title */
			$subject = sprintf( __('[%1$s] Trackback: "%2$s"'), $blogname, $post->post_title );
		} elseif ('pingback' == $comment_type) {
			$notify_message  = sprintf( __( 'New pingback on your post "%s"' ), $post->post_title ) . "\r\n";
			/* translators: 1: comment author, 2: author IP, 3: author domain */
			$notify_message .= sprintf( __('Website: %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
			$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
			$notify_message .= __('Excerpt: ') . "\r\n" . sprintf('[...] %s [...]', $comment->comment_content ) . "\r\n\r\n";
			$notify_message .= __('You can see all pingbacks on this post here: ') . "\r\n";
			/* translators: 1: blog name, 2: post title */
			$subject = sprintf( __('[%1$s] Pingback: "%2$s"'), $blogname, $post->post_title );
		}
		$notify_message .= get_permalink($comment->comment_post_ID) . "#comments\r\n\r\n";
		$notify_message .= sprintf( __('Permalink: %s'), get_permalink( $comment->comment_post_ID ) . '#comment-' . $comment_id ) . "\r\n";
		if ( EMPTY_TRASH_DAYS )
			$notify_message .= sprintf( __('Trash it: %s'), admin_url("comment.php?action=trash&c=$comment_id") ) . "\r\n";
		else
			$notify_message .= sprintf( __('Delete it: %s'), admin_url("comment.php?action=delete&c=$comment_id") ) . "\r\n";
		$notify_message .= sprintf( __('Spam it: %s'), admin_url("comment.php?action=spam&c=$comment_id") ) . "\r\n";

		$wp_email = 'wordpress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));

		if ( '' == $comment->comment_author ) {
			$from = "From: \"$blogname\" <$wp_email>";
			if ( '' != $comment->comment_author_email )
				$reply_to = "Reply-To: $comment->comment_author_email";
		} else {
			$from = "From: \"$comment->comment_author\" <$wp_email>";
			if ( '' != $comment->comment_author_email )
				$reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
		}

		$message_headers = "$from\n"
				. "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";

		if ( isset($reply_to) )
			$message_headers .= $reply_to . "\n";

		$notify_message = apply_filters('comment_notification_text', $notify_message, $comment_id);
		$subject = apply_filters('comment_notification_subject', $subject, $comment_id);
		$message_headers = apply_filters('comment_notification_headers', $message_headers, $comment_id);

		@wp_mail( $author->user_email, $subject, $notify_message, $message_headers );

		return true;
	}
endif;