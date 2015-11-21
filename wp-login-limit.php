<?php
/*
Plugin Name: WP Login Limit
Plugin URI: http://tungvn.info/
Description: Limit 
Author: TungVN
Version: 1.0
Author URI: http://tungvn.info/
*/

/**
 * Load language files for translating strings. Props andykillen.
 */
add_action( 'init', 'tvn_email_load_language', 1 );
function tvn_email_load_language() {
	$plugin_dir = basename( dirname( __FILE__ ) );
	load_plugin_textdomain( 'login-limit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/**
 * 
 */
add_action( 'wp_login_failed', 'tvn_limit_failed_login_attemps_cb' );
function tvn_limit_failed_login_attemps_cb(){
	tvn_check_login_attemps();
}

add_action( 'login_form', 'tvn_check_login_attemps' );
if( !function_exists( 'tvn_check_login_attemps' ) ){
	function tvn_check_login_attemps(){
		if( isset( $_COOKIE['login_attemps'] ) ){
			$login_attemps = intval( $_COOKIE['login_attemps'] );

			if( $login_attemps >= 3 ){
				add_action( 'login_form', 'tvn_lock_login_form_cb' );
			}
			else{
				$login_attemps++;
				setcookie( 'login_attemps', $login_attemps, time() + 600, '/' ); ?>
				<script type="text/javascript">
				if( document.getElementById('login_error') )
					document.getElementById('login_error').innerHTML += '<br><?php echo esc_js( __( 'You have '. 3 - $login_attemps .' attemps to login' , 'email-login' ) ); ?>';
				</script>
			<?php }
		}
		else{
			setcookie( 'login_attemps', 1, time() + 600, '/' );
		}
	}
}

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
