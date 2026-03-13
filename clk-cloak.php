<?php
/**
 * Plugin Name: CLK Cloak - User Agent Content Cloaking
 * Plugin URI:
 * Description: Cloaking shortcodes based on user agent. [clk]...[/clk] shows content only to search/crawler bots (e.g. Googlebot) and hides it from human visitors; [clk_user]...[/clk_user] hides content from bots and shows it only to real users (useful for heavy scripts/counters).
 * Version: 1.0.2
 * Author: Vlad
 * Text Domain: clk-cloak
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Auto-updates via Plugin Update Checker (GitHub).
if ( file_exists( plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php' ) ) {
	require plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';

	if ( class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
		$clk_cloak_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://github.com/CruentoVulpes/clk-cloak/',
			__FILE__,
			'clk-cloak'
		);

		$clk_cloak_update_checker->setBranch( 'main' );
	}
}

/**
 * Get current user agent in a safe and extensible way.
 *
 * @return string
 */
function clk_get_user_agent() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

	if ( function_exists( 'wp_unslash' ) ) {
		$ua = wp_unslash( $ua );
	}

	if ( strlen( $ua ) > 1024 ) {
		$ua = substr( $ua, 0, 1024 );
	}

	/**
	 * Filter the user agent string used by CLK Cloak.
	 *
	 * @param string $ua Raw user agent string (max 1024 chars).
	 */
	$ua = apply_filters( 'clk_cloak_user_agent', $ua );

	return strtolower( $ua );
}

/**
 * Detect search bot (show content to crawlers).
 */
function clk_is_search_bot() {
	$ua = clk_get_user_agent();

	$bots = array(
		'googlebot',
		'bingbot',
		'yandexbot',
		'baiduspider',
		'duckduckbot',
		'slurp',           // Yahoo
		'facebookexternalhit',
		'twitterbot',
		'linkedinbot',
		'whatsapp',
		'telegrambot',
		'applebot',
		'bytespider',      // TikTok
		'petalbot',        // Huawei
		'semrushbot',
		'ahrefsbot',
		'mj12bot',
		'dotbot',
	);

	/**
	 * Filter the list of known bot signatures used by CLK Cloak.
	 *
	 * @param string[] $bots List of substrings to search for in the user agent.
	 */
	$bots = apply_filters( 'clk_cloak_bot_user_agents', $bots );
	
	foreach ( $bots as $bot ) {
		if ( strpos( $ua, $bot ) !== false ) {
			return true;
		}
	}
	
	return false;
}

/**
 * Detect human browser (hide content from users).
 */
function clk_is_human_browser() {
	$ua = clk_get_user_agent();

	$browsers = array(
		'chrome',
		'firefox',
		'safari',
		'opera',
		'edge',
		'msie',
		'trident',
		'android',
		'mobile',
		'samsung',
		'ucbrowser',
		'silk',            // Kindle
	);

	/**
	 * Filter the list of user agent fragments treated as "human browsers".
	 *
	 * @param string[] $browsers List of substrings to search for in the user agent.
	 */
	$browsers = apply_filters( 'clk_cloak_human_browser_user_agents', $browsers );
	
	foreach ( $browsers as $browser ) {
		if ( strpos( $ua, $browser ) !== false ) {
			return true;
		}
	}
	
	return false;
}

/**
 * Shortcode [clk][/clk]: show content to bots, hide from humans.
 */
function clk_cloak_shortcode( $atts, $content = null ) {
	if ( empty( $content ) ) {
		return '';
	}
	
	// Bots: show content
	if ( clk_is_search_bot() ) {
		return do_shortcode( $content );
	}
	
	// Human browsers: hide
	if ( clk_is_human_browser() ) {
		return '';
	}
	
	// Unknown user-agent: hide by default (safer)
	return '';
}

add_shortcode( 'clk', 'clk_cloak_shortcode' );

/**
 * Shortcode [clk_user][/clk_user]:
 * hide content from search bots (Googlebot, etc.), show to users.
 * Useful for heavy scripts/counters you don't want to serve to bots.
 */
function clk_reverse_cloak_shortcode( $atts, $content = null ) {
	if ( empty( $content ) ) {
		return '';
	}

	// Bots: hide content completely
	if ( clk_is_search_bot() ) {
		return '';
	}

	// Humans and unknown agents: show content
	return do_shortcode( $content );
}

add_shortcode( 'clk_user', 'clk_reverse_cloak_shortcode' );

/**
 * Ensure [clk] works inside theme option output.
 * Theme `skybet` outputs footer copyright via:
 * apply_filters('theme_copyright_footer', ... get_option('copyright') ...)
 */
function clk_apply_shortcodes_in_theme_copyright_footer( $html ) {
	if ( ! is_string( $html ) || $html === '' ) {
		return $html;
	}

	// Some option UIs may store HTML as entities; decode to real markup before shortcode parsing.
	$html = wp_specialchars_decode( $html, ENT_QUOTES );

	return do_shortcode( $html );
}

add_filter( 'theme_copyright_footer', 'clk_apply_shortcodes_in_theme_copyright_footer', 20 );
