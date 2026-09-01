<?php
/**
 * Plugin Name: ACFW Demo Auto-login
 * Description: Demo-site only. Signs every visitor in as a locked-down demo customer so the My Account page can be viewed without credentials.
 * Version:     1.0.0
 * Author:      Rcube
 *
 * NOT PART OF THE PLUGIN. Never ship this, and never install it on a real store.
 *
 * Install, either way:
 *   - Upload submission/acfw-demo-autologin.zip through Plugins > Add New >
 *     Upload Plugin, then activate it. Deactivate to turn the demo off.
 *   - Or drop this file straight into wp-content/mu-plugins/ (create the folder
 *     if it does not exist). Delete the file to turn the demo off.
 *
 * Requires demo-seed.php to have run first — that is what creates the customer
 * this signs visitors in as.
 *
 * What it does: a logged-out visitor who opens the My Account page — or any URL
 * with ?acfw_demo=1 — is signed in as the demo customer. The account is checked
 * first: it must hold the customer role and nothing that can edit content, and
 * while the demo session is active its email and password cannot be changed, so
 * no visitor can take the account over and lock the next one out.
 *
 * @package AccountCustomizerForWooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Login of the demo customer ( created by demo-seed.php ).
 */
define( 'ACFW_DEMO_USER', 'reviewer' );

/**
 * Why the last acfw_demo_user() call refused, for the ?acfw_demo_debug=1 report.
 *
 * @var string
 */
$GLOBALS['acfw_demo_reason'] = '';

/**
 * The demo account, but only when it is safe to hand to the public.
 *
 * @return WP_User|null
 */
function acfw_demo_user() {

	$user = get_user_by( 'login', ACFW_DEMO_USER );

	if ( ! $user instanceof WP_User ) {
		$GLOBALS['acfw_demo_reason'] = 'No user with the login "' . ACFW_DEMO_USER . '" exists. Run demo-seed.php first — that is what creates it.';
		return null;
	}

	// Hard refusal: anything that can touch content or settings is never
	// auto-logged-in, however this file is configured.
	$forbidden = array( 'edit_posts', 'manage_options', 'manage_woocommerce', 'upload_files', 'edit_users', 'install_plugins' );
	foreach ( $forbidden as $cap ) {
		if ( user_can( $user, $cap ) ) {
			$GLOBALS['acfw_demo_reason'] = 'The "' . ACFW_DEMO_USER . '" account can "' . $cap . '". Auto-login refuses any account that can edit content or settings — give it the plain Customer role.';
			return null;
		}
	}

	if ( ! in_array( 'customer', (array) $user->roles, true ) ) {
		$GLOBALS['acfw_demo_reason'] = 'The "' . ACFW_DEMO_USER . '" account does not hold the Customer role ( roles: ' . implode( ', ', (array) $user->roles ) . ' ).';
		return null;
	}

	$GLOBALS['acfw_demo_reason'] = '';
	return $user;
}

/**
 * Sign a logged-out visitor in as the demo customer.
 */
function acfw_demo_autologin() {

	if ( is_user_logged_in() || is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	$requested  = isset( $_GET['acfw_demo'] ) && '1' === $_GET['acfw_demo']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$on_account = function_exists( 'is_account_page' ) && is_account_page();

	if ( ! $requested && ! $on_account ) {
		return;
	}

	$user  = acfw_demo_user();
	$debug = isset( $_GET['acfw_demo_debug'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! $user ) {
		// Silence is unhelpful when the demo "does nothing", so let the site
		// owner ask why: /my-account/?acfw_demo=1&acfw_demo_debug=1
		if ( $debug ) {
			wp_die(
				'<h1>ACFW demo auto-login</h1><p><strong>Active, but standing down.</strong></p><p>' . esc_html( $GLOBALS['acfw_demo_reason'] ) . '</p>',
				'ACFW demo auto-login',
				array( 'response' => 200 )
			);
		}
		return;
	}

	if ( $debug ) {
		wp_die(
			'<h1>ACFW demo auto-login</h1><p><strong>Ready.</strong> Signing visitors in as "' . esc_html( ACFW_DEMO_USER ) . '" (user ID ' . (int) $user->ID . ').</p><p>Remove acfw_demo_debug from the URL to use the demo.</p>',
			'ACFW demo auto-login',
			array( 'response' => 200 )
		);
	}

	// A browser that refuses cookies would otherwise bounce between the redirect
	// and this hook forever, so only ever attempt the round trip once.
	if ( isset( $_GET['acfw_demo_try'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, false );

	$target = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );

	wp_safe_redirect( add_query_arg( 'acfw_demo_try', '1', $target ) );
	exit;
}
add_action( 'template_redirect', 'acfw_demo_autologin', 1 );

/**
 * Is the current visitor inside the shared demo session?
 *
 * @return bool
 */
function acfw_demo_is_demo_session() {
	$user = wp_get_current_user();
	return $user instanceof WP_User && $user->exists() && ACFW_DEMO_USER === $user->user_login;
}

/**
 * Refuse the account-details form for the shared demo account.
 *
 * @param WP_Error $errors Validation errors.
 * @return WP_Error
 */
function acfw_demo_block_account_changes( $errors ) {
	if ( acfw_demo_is_demo_session() ) {
		$errors->add( 'acfw_demo', 'This is a shared demo account, so its login details cannot be changed. Everything else on this page is fully interactive.' );
	}
	return $errors;
}
add_filter( 'woocommerce_save_account_details_errors', 'acfw_demo_block_account_changes' );

/**
 * Belt and braces: block any programmatic edit of the demo user's credentials.
 *
 * @param array $data    Sanitised user data heading for the database.
 * @param bool  $update  Whether this is an update.
 * @param int   $user_id User being updated.
 * @return array
 */
function acfw_demo_preserve_credentials( $data, $update, $user_id ) {

	if ( ! $update || ! $user_id ) {
		return $data;
	}

	$user = get_user_by( 'id', $user_id );
	if ( $user instanceof WP_User && ACFW_DEMO_USER === $user->user_login ) {
		$data['user_email'] = $user->user_email;
		$data['user_pass']  = $user->user_pass;
	}

	return $data;
}
add_filter( 'wp_pre_insert_user_data', 'acfw_demo_preserve_credentials', 10, 3 );

/**
 * Tell the visitor what they are looking at.
 */
function acfw_demo_badge() {

	if ( ! acfw_demo_is_demo_session() ) {
		return;
	}
	?>
	<div style="position:fixed;left:50%;bottom:16px;transform:translateX(-50%);z-index:99999;background:#111827;color:#fff;font:500 13px/1.4 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;padding:9px 16px;border-radius:999px;box-shadow:0 6px 20px rgba(0,0,0,0.25);">
		Demo mode — you are signed in as a sample customer. Explore freely.
	</div>
	<?php
}
add_action( 'wp_footer', 'acfw_demo_badge', 99 );
