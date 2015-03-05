<?php
/**
 * Plugin Name: Nova's Mobile Authenticator
 * Plugin URI: http://plugins.svn.wordpress.org/wp-mobile-authenticator/
 * Description: Addes an authetnication code step to login
 * Version: 1.0.1
 * Author: elrond1369
 * Author URI: http://profiles.wordpress.org/elrond1369/
 * License: GPL2
 */ 
function getCode($username) {
	date_default_timezone_set("UTC");
	if($username!=false) {
		$username = get_user_by('login', $username);
		$user_id=$username->id;
	} else {
		$user_id = get_current_user_id();
	}
$key1 = intval(get_user_meta($user_id, 'auth-key1', true));
$key2 = intval(get_user_meta($user_id, 'auth-key2', true));
if(!get_user_meta($user_id, 'auth-enable', true) && strpos($_SERVER['REQUEST_URI'], "wp-login.php")==1) {
	return false;
}
$testseconds = intval(date("s"));
if(!get_user_meta($user_id, 'auth-relax', true)) {
	if($testseconds<15) {
		$seconds=15;
	}
	if($testseconds>14 && $testseconds<30) {
		$seconds=30;
	}
	if($testseconds<45 && $testseconds>29) {
		$seconds=45;
	}
	if($testseconds>44) {
		$seconds=60;
	}
} else {
	if($testseconds<30) {
		$seconds=30;
	}
	if($testseconds>29) {
		$seconds=60;
	}
}
$seconds = $seconds * $key1;
$minutes = intval(date("i")) * $key2;
$hours = intval(date("H"))+ $key1;
$day = intval(date("d")) + $key2;
$month = $key1 - intval(date("m"));
$year = $key2 - intval(date("Y"));
$output = $seconds + $minutes + $hours + $day + $month + $year +1;
if($output<10000000) {
	$output = $output * 10;
}
return $output;
}
 function auth_make_field() {
	 echo '<label for="auth">Authentication Code</label><br><input type="text" name="auth" id="auth" autocomplete="off"><br>';
 }
 add_action('login_form', 'auth_make_field');
 function addProfileFields() {
	 $user_id = get_current_user_id();
	 if(get_user_meta($user_id, 'auth-enable', true)=="true") {
		 $enable = "checked";
	 } else {
		 $enable = "";
	 }
	 if(get_user_meta($user_id, 'auth-relax', true)=="true") {
		 $relax = "checked";
	 } else {
		 $relax = "";
	 }
	 echo '<div style="border-top:1px solid;border-bottom:1px solid; padding-bottom:10px;"><h3>WordPress Mobile Authenticator</h3>Enable: <input type="checkbox" name="auth-enable" value="true" '.$enable.'><br>Relaxed Mode: <input type="checkbox" name="auth-relax" value="true" '.$relax.'><br><input type="button" value="Show Keys" class="button button-primary" onclick="document.getElementById(\'auth-keys\').style.display=\'\'"><div id="auth-keys" style="display:none;padding-top:10px"><label for="auth-key1">Key 1</label><input type="text" id="auth-key1" name="auth-key1" value="'.get_user_meta($user_id, 'auth-key1', true).'" autocomplete="off" maxlength="6"><br><label for="auth-key2">Key 2</label><input type="text" name="auth-key2" name="auth-key2" value="'.get_user_meta($user_id, 'auth-key2', true).'" autocomplete="off" maxlength="6"><br>Current Code: '.getCode(false).'</div></div>';
 }
 add_action('show_user_profile', 'addProfileFields');
  add_action('edit_user_profile', 'setProfileFields');
 function check_fields($errors, $update, $user) {
	$key1 = intval($_POST['auth-key1']);
	$key2 = intval($_POST['auth-key2']);
	if($key1>0 && $key2>0) {
		if($key1<100000 || $key2<100000 || $key1>999999 || $key2>999999) {
			$errors->add('demo_error',__('Invalid Authentication Keys'));
			return;
		}
	}
}
add_filter('user_profile_update_errors', 'check_fields', 10, 3);
 function getProfileFields($user_id) {
	$key1 = intval($_POST['auth-key1']);
	$key2 = intval($_POST['auth-key2']);
	if($key1>0 && $key2>0) {
		if($key1<100000 || $key2<100000 || $key1>999999 || $key2>999999) {
			return;
		}
	}
	update_user_meta($user_id, 'auth-key1', $_POST['auth-key1']);
	update_user_meta($user_id, 'auth-key2', $_POST['auth-key2']);
	update_user_meta($user_id, 'auth-enable', $_POST['auth-enable']);
	update_user_meta($user_id, 'auth-relax', $_POST['auth-relax']);
 }
 add_action('personal_options_update', 'getProfileFields');
 add_filter( 'authenticate', 'authCheckCode', 30, 3 );
function authCheckCode($user, $username, $password) {
	if(getCode($username)!=intval($_POST['auth']) && getCode($username)!=false) {
		$errors= new WP_Error('demo_error',__('Invalid Authentication Code'));
		return $errors;
	} else {
		return $user;
	}
}

