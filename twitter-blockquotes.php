<?php
/*
 * Plugin Name: Twitter Blockquotes
 * Plugin URI: http://theme.fm/plugins/twitter-blockquotes/
 * Description: Embed tweets in your website or blog, just like YouTube and Vimeo embeds -- copy and paste a URL to a tweet on a separate line in your editor.
 * Version: 1.0
 * Author: kovshenin
 * Author URI: http://kovshenin.com
 * License: GPL2
 *
 * Inspired by the Blackbird Pie plugin by bradvin: http://wordpress.org/extend/plugins/twitter-blackbird-pie/
 * Kudos for the plugin sources, I actually took some of the regexes from it.
 */

class Twitter_Blockquotes_Plugin {
	
	/*
	 * Class Constructor (fired during WordPress init)
	 */
	function __construct() {
		// Register an embed handler
		wp_embed_register_handler( 'twitter_blockquote', '/^(http|https):\/\/twitter\.com\/(?:#!\/)?(\w+)\/status(es)?\/(\d+)$/', array( &$this, 'embed_handler' ) );
		
		// A few hooks
		add_action( 'admin_init', array( &$this, '_admin_init' ) );
		add_action( 'admin_menu', array( &$this, '_admin_menu' ) );
		add_action( 'wp_head', array( &$this, '_wp_head' ) );
		
		// Populate $this->options array
		$this->_load_options();
	}
	
	/*
	 * Populates the $this->options array from the database.
	 */
 	private function _load_options() {
		$this->options = (array) get_option( 'twitter-blockquotes' );
	}
	
	/*
	 * Embed Handler
	 *
	 * Fired when there's an embed hit with a tweet URL. Look at the
	 * filters inside to see how this can be modified.
	 */
	public function embed_handler( $matches, $attr, $url, $rawattr ) {
		global $post;
		$post_id = $post->ID;
		$tweet_id = $matches[4];
		$meta_key = '_tbq_' . $tweet_id;
		$embed = '';
		$tweet = array();

		// See if there's something cached.
		if ( '' === ( $tweet = get_post_meta( $post_id, $meta_key, true ) ) ) {
			$response = wp_remote_get( "http://api.twitter.com/1/statuses/show.json?id={$tweet_id}" );
			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
				$raw_tweet = json_decode( wp_remote_retrieve_body( $response ) );

				$tweet = array(
					'text' => $raw_tweet->text,
					'screen_name' => $raw_tweet->user->screen_name,
					'url' => $url
				);
				
				// Allows developers to modify the array above before it is cached.
				$tweet = apply_filters( 'twitter_blockquote_tweet_raw', $tweet, $raw_tweet, $tweet_id, $url );
				
				// Cache this for later use.
				update_post_meta( $post_id, $meta_key, $tweet );
			}
		}

		// Developers can filter this.
		$tweet = apply_filters( 'twitter_blockquote_tweet', $tweet, $tweet_id, $url );
		
		if ( ! empty( $tweet ) ) {
			
			// Linkify, mentionify, hashtagify and listify the text.
			$text = $this->_make_clickable( $tweet['text'] );
			
			// Developers make good use of these!
			$cite_attr = apply_filters( 'twitter_blockquote_tweet_cite_attr', $tweet['url'], $tweet, $tweet_id, $url );
			$text = apply_filters( 'twitter_blockquote_tweet_text', $text, $tweet, $tweet_id, $url );
			$cite = apply_filters( 'twitter_blockquote_tweet_cite', "<a href='{$tweet['url']}'>@{$tweet['screen_name']}</a>", $tweet, $tweet_id, $url );
			
			// And these especially.
			$before_cite = apply_filters( 'twitter_blockquote_tweet_before_cite', '', $tweet, $tweet_id, $url );
			$after_cite = apply_filters( 'twitter_blockquote_tweet_after_cite', '', $tweet, $tweet_id, $url );
			$before_blockquote = apply_filters( 'twitter_blockquote_tweet_before_blockquote', '', $tweet, $tweet_id, $url );
			$after_blockquote = apply_filters( 'twitter_blockquote_tweet_after_blockquote', '', $tweet, $tweet_id, $url );
			$blockquote_class = apply_filters( 'twitter_blockquote_tweet_class', 'tweet', $tweet, $tweet_id, $url );
			
			// This will be embedded.
			$embed = "{$before_blockquote}<blockquote class='{$blockquote_class}' cite='{$cite_attr}'><p>{$text}
				{$before_cite}<cite>{$cite}</cite>{$after_cite}
			</p></blockquote>{$after_blockquote}";
			
			// Or override the whole embed HTML completely.
			$embed = apply_filters( 'twitter_blockquote_tweet_embed', $embed, $tweet, $tweet_id, $url );
		}

		return apply_filters( 'embed_twitter_blockquote', $embed, $matches, $attr, $url, $rawattr );
	}
	
	/*
	 * Outputs the custom CSS provided via the plugin options.
	 */
	public function _wp_head() {
		if ( isset( $this->options['custom-css'] ) && ! empty( $this->options['custom-css'] ) )
			echo "<style>{$this->options['custom-css']}</style>";
	}
	
	/*
	 * Fired during admin_init (doh!), registers settings, sections and fields.
	 */
	public function _admin_init() {
		register_setting( 'twitter-blockquotes', 'twitter-blockquotes', array( &$this, '_validate_options' ) );
		add_settings_section( 'twitter_blockquotes_general', 'General Settings', array( &$this, '_settings_section_general' ), 'twitter-blockquotes' );
		
		// General section
		add_settings_field( 'custom-css', 'Custom CSS', array( &$this, '_settings_custom_css' ), 'twitter-blockquotes', 'twitter_blockquotes_general' );
		add_settings_field( 'clear-cache', 'Clear Cache', array( &$this, '_settings_clear_cache'), 'twitter-blockquotes', 'twitter_blockquotes_general' );
		
		// Anything else?
		do_action( 'twitter_blockquotes_settings' );
		
		// Clearing caches?
		if ( isset( $_GET['twitter_blockquote_clear_caches'], $_GET['_wpnonce'] ) && current_user_can( 'manage_options' ) && check_admin_referer( 'twitter-blockquotes-clear-caches' ) ) {
			
			// Call that dangerous function and add an updated message.
			$this->_clear_post_meta_caches();
			add_settings_error( 'twitter-blockquotes', 100, 'Caches have been cleared!', 'updated' );
		}
	}
	
	public function _settings_section_general() {}
	
	/*
	 * Settings Field: Custom CSS
	 */
	public function _settings_custom_css() {
	?>
		<textarea class="code large-text" rows="5" name="twitter-blockquotes[custom-css]"><?php echo esc_textarea( @$this->options['custom-css'] ); ?></textarea><br />
		<span class="description">Use any CSS you like to customize your Twitter Blockquotes. The blockquote element selector is <code>blockquote.tweet</code></span>
	<?php
	}
	
	/*
	 * Settings Field: Clear Cache
	 */
	public function _settings_clear_cache() {
		$nonce = wp_create_nonce( 'twitter-blockquotes-clear-caches' );
		$url = admin_url( 'options-general.php?page=twitter-blockquotes&twitter_blockquote_clear_caches=1&_wpnonce=' . $nonce );
	?>
		<a href="<?php echo $url; ?>" class="button">Clear All Caches</a>
		<span class="description">Hit this button if you'd like to clear all the caches.</span>
	<?php
	}
	
	/*
	 * Validates the twitter-blockquotes options when saved.
	 */
	public function _validate_options( $options ) {
		$options = apply_filters( 'twitter_blockquotes_validate_options', $options );
		return $options;
	}
	
	/*
	 * Adds a new options page called Twitter Blockquotes under Settings.
	 */
	public function _admin_menu() {
		add_options_page( 'Twitter Blockquotes Options', 'Twitter Blockquotes', 'manage_options', 'twitter-blockquotes', array( &$this, '_admin_menu_content' ) );
	}
	
	/*
	 * Renders the contents of the Twitter Blockquotes options page. Uses the Settings API.
	 */
	public function _admin_menu_content() {
	?>
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br></div>
		<h2>Twitter Blockquotes Settings</h2>

		<form method="post" action="options.php">
				<!-- Provide some help, eh? -->
				<p><strong>Hi there friend!</strong> Thank you for using Twitter Blockquotes. We don't provide too many options yet, Twitter Blockquotes is designed to be as clean and simple as possible. It's up to you how you want your tweets to appear, whether you want avatars and retweet capabilities, perhaps a Twitter bird on the left? Most of the styling is done through the Custom CSS field below, rest is up to a few actions and filters. Read <a href="http://theme.fm/2011/08/embedding-tweets-in-wordpress-with-twitter-blockquotes-1548/">this guide</a> to learn more.</p>
				<p>Twitter Blockquotes usage is quite simple, just copy and paste a URL to any tweet in your posts or pages on a separate line, similar to how you embed YouTube or Vimeo videos. We'll transform that into a nice-looking blockquote for you!</p>

			<?php wp_nonce_field( 'update-options' ); ?>
			<?php settings_fields( 'twitter-blockquotes' ); ?>
			<?php do_settings_sections( 'twitter-blockquotes' ); ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
	}
	
	/*
	 * Clear Caches (internal)
	 *
	 * Quite a dangerous approach at clearing all the Twitter Blockquotes caches,
	 * queries the database directly with a non-limited DELETE.
	 */
	private function _clear_post_meta_caches() {
		global $wpdb;
		
		do_action( 'twitter_blockquotes_clear_caches' );
		
		// This is dangerous, seriously..
		return $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_tbq_%'" );
	}
	
	/*
	 * Make Clickable (internal)
	 *
	 * Makes use of WordPress' make_clickable function as well as adds
	 * some extra replacements to linkify hashtags, mentions and lists.
	 */
	private function _make_clickable( $text ) {
		$text = make_clickable( $text );
		
		// Hashtags
		$text = preg_replace( 
			'/(^|[^0-9A-Z&\/]+)(#|\xef\xbc\x83)([0-9A-Z_]*[A-Z_]+[a-z0-9_\xc0-\xd6\xd8-\xf6\xf8\xff]*)/iu', 
			'${1}<a href="http://twitter.com/search?q=%23${3}" title="#${3}">${2}${3}</a>', 
			$text 
		);
		
		// Mentions
		$text = preg_replace( 
			'/([^a-zA-Z0-9_]|^)([@\xef\xbc\xa0]+)([a-zA-Z0-9_]{1,20})(\/[a-zA-Z][a-zA-Z0-9\x80-\xff-]{0,79})?/u', 
			'${1}@<a href="http://twitter.com/intent/user?screen_name=${3}" class="twitter-action">${3}</a>', 
			$text 
		);
		
		// Lists
		$text = preg_replace(
			'$([@|＠])([a-z0-9_]{1,20})(/[a-z][a-z0-9\x80-\xFF-]{0,79})?$i',
			'${1}<a href="http://twitter.com/${2}${3}">${2}${3}</a>',
			$text
        );

		return $text;
	}
};

// Initialize the plugin object.
add_action( 'init', create_function( '', 'global $twitter_blockquotes_plugin; $twitter_blockquotes_plugin = new Twitter_Blockquotes_Plugin();' ) );