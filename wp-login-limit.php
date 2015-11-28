<?php
/*
Plugin Name: WP Login Limit
Plugin URI: https://github.com/tungvn/wp-login-limit
Description: Limit Wordpress login failed attemps
Author: Vũ Ngọc Tùng
Version: 0.3
Author URI: http://tungvn.info/
*/

/*
 * Plugin settings
 */
add_action( 'admin_init', 'tvn_login_limit_register_theme_cb' );
function tvn_login_limit_register_theme_cb() {
    register_setting( 'wp-login-theme-option', 'login_failed_attemps' );
    register_setting( 'wp-login-theme-option', 'banned_time' );
}

/*
 * Plugin settings page
 */
add_action( 'admin_menu', 'tvn_wp_login_limit_settings' );
function tvn_wp_login_limit_settings(){
	add_options_page(
		'Login Limit Settings',
		'WP Login Limit Settings',
		'manage_options',
		'wp-login-limit-settings',
		'tvn_wp_login_limit_settings_cb'
	);

	function tvn_wp_login_limit_settings_cb(){
		$banned_time = intval( get_option( 'banned_time', 600 ) );
		$login_failed_attemps = intval( get_option( 'login_failed_attemps', 3 ) ); ?>
		<h2>WP Login Limit Settings</h2>
		<div class="wrap">
			<form action="options.php" method="POST">
				<?php settings_fields( 'wp-login-theme-option' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="login_failed_attemps">Login failed attemps</label></th>
						<td><input type="text" name="login_failed_attemps" id="login_failed_attemps" value="<?php echo $login_failed_attemps; ?>"> time(s)</td>
					</tr>
					<tr>
						<th><label for="banned_time">Banned Time</label></th>
						<td><input type="text" name="banned_time" id="banned_time" value="<?php echo $banned_time; ?>"> (ms)</td>
					</tr>
				</table>
				<?php submit_button( 'Save Settings', 'primary' ); ?>
			</form>
		</div>
	<?php }
}

/*
 * Adds a 'settings' link in the plugins table
 */
add_filter( 'plugin_action_links_'. plugin_basename(__FILE__), 'tvn_plugin_action_links_cb' );
function tvn_plugin_action_links_cb( $links ) {
	array_unshift( $links, '<a href="options-general.php?page=wp-login-limit-settings">'. __( 'Settings', 'login-limit' ) .'</a>' );
	return $links;
}

/*
 * Load language files for translating strings.
 */
add_action( 'init', 'tvn_wp_login_limit_load_language' );
function tvn_wp_login_limit_load_language() {
	$plugin_dir = basename( dirname( __FILE__ ) );
	load_plugin_textdomain( 'login-limit', false, dirname( plugin_basename( __FILE__ ) ) .'/languages/' );
}

/*
 * Check conditions after each login failed
 */
add_action( 'wp_login_failed', 'tvn_limit_failed_login_attemps_cb' );
function tvn_limit_failed_login_attemps_cb(){
	tvn_check_login_attemps();
}

/*
 * Change Wordpress login form follow login failed attemps
 */
add_action( 'login_form', 'tvn_check_login_attemps' );
if( !function_exists( 'tvn_check_login_attemps' ) ){
	function tvn_check_login_attemps(){
		// Banned time in milisecond, default is 600 (ms)
		$banned_time = intval( get_option( 'banned_time', 600 ) );

		// Max login failed attemps, default is 3
		$login_failed_attemps = intval( get_option( 'login_failed_attemps', 3 ) );

		if( isset( $_COOKIE['login_attemps'] ) ){
			$login_attemps = intval( $_COOKIE['login_attemps'] );

			if( $login_attemps >= $login_failed_attemps ){
				add_action( 'login_form', 'tvn_lock_login_form_cb' );
			}
			else{
				$login_attemps++;
				setcookie( 'login_attemps', $login_attemps, time() + $banned_time, '/' );
				$login_attemps_exist = $login_failed_attemps - $login_attemps; ?>
				<script type="text/javascript">
				if( document.getElementById('login_error') )
					document.getElementById('login_error').innerHTML += '<br><?php echo esc_js( __( "You have $login_attemps_exist attemps to login" , "email-login" ) ); ?>';
				</script>
			<?php }
		}
		else{
			setcookie( 'login_attemps', 1, time() + $banned_time, '/' );
		}
	}
}

/*
 * Lock Wordpress login form
 */
if( !function_exists( 'tvn_lock_login_form_cb' ) ){
	function tvn_lock_login_form_cb(){
		if( 'wp-login.php' != basename( $_SERVER['SCRIPT_NAME'] ) )
			return;
		?>
		<script type="text/javascript">
		// Disable input user_login
		if( document.getElementById('user_login') )
			document.getElementById('user_login').disabled = true;

		// Disable input user_pass
		if( document.getElementById('user_pass') )
			document.getElementById('user_pass').disabled = true;

		// Disable input rememberme
		if( document.getElementById('rememberme') )
			document.getElementById('rememberme').disabled = true;

		// Error Messages
		if( document.getElementById('login_error') )
			document.getElementById('login_error').innerHTML = '<strong><?php echo esc_js( __( 'You just have login failed 3 times. Wait for 10 minutes to continue!' , 'email-login' ) ); ?></strong>';
		else{
			var el = document.createElement('div').setAttribute('id', 'login_error');
			el.innerHTML = '<strong><?php echo esc_js( __( 'You just have login failed 3 times. Wait for 10 minutes to continue!' , 'email-login' ) ); ?></strong>';
			var login = document.getElementById('loginform');
			login.parentNode.insertBefore( login, el );
		}
		</script><?php
	}
}
