<?php
/*
Plugin Name: Wordpress FireEagle plugin
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the plugin.
Version: The plugin's Version Number, e.g.: 1.0
Author: Name Of The Plugin Author
Author URI: http://URI_Of_The_Plugin_Author
*/

require_once dirname(__FILE__)."/lib/fireeagle.php";
session_start();

$fe_key = 'DZmIaMapfOuH';
$fe_secret = 'yzSKJkHDxWOA6PA0fKG57xiLz07WZCNR';

function wpfe_display_best_guess_name(){
  global $fe_key; global $fe_secret;
  
  $best_guess_name = wp_cache_get('wpfe_best_guess_name');

  if ($best_guess_name == false){
    $access_token = get_option('wpfe_access_token');
    $access_secret = get_option('wpfe_access_secret');

    $fe = new FireEagle($fe_key, $fe_secret, $access_token, $access_secret);
    $loc = $fe->user();
    
    $best_guess_name = htmlspecialchars($loc->user->best_guess->name);
    wp_cache_set('wpfe_best_guess_name', $best_guess_name);
    
    $best_guess_name = $best_guess_name;
  };
  
  return $best_guess_name;
};


function wpfe_wp_admin(){
  global $fe_key; global $fe_secret;
  
  $access_token = get_option('wpfe_access_token');
  $access_secret = get_option('wpfe_access_secret');
  
  echo "<h2>Test Manage</h2>";
  
  if ($_GET['step'] == '3') {
  
  echo "<h1>Step 3</h1>";
  $fe = new FireEagle($fe_key, $fe_secret, $access_token, $access_secret);
  
  $loc = $fe->user(); // equivalent to $fe->call("user")
  ?><h2>Where you are<?php if ($loc->user->best_guess) echo ": ".htmlspecialchars($loc->user->best_guess->name) ?></h2><?php
  if (empty($loc->user->location_hierarchy)) {
   ?><p>Fire Eagle doesn't know where you are yet.</p><?php // '
  };
  
  } elseif ($_GET['step'] == '2') {
    echo "<h1>Step 2</h1>";
         
    $fe = new FireEagle($fe_key, $fe_secret, $_SESSION['request_token'], $_SESSION['request_secret']);
    $tok = $fe->getAccessToken();
    if (!isset($tok['oauth_token']) || !is_string($tok['oauth_token'])
        || !isset($tok['oauth_token_secret']) || !is_string($tok['oauth_token_secret'])) {
     error_log("Bad token from FireEagle::getAccessToken(): ".var_export($tok, TRUE));
     echo "ERROR! FireEagle::getAccessToken() returned an invalid response. Giving up.";
     exit;
    };
    
    $_SESSION['auth_state'] = "done";
    
    update_option('wpfe_access_token', $tok['oauth_token']);
    update_option('wpfe_access_secret', $tok['oauth_token_secret']);
    
    echo('<p>Great. Now go to <a href="'.$_SERVER["REQUEST_URI"].'&step=3">final step</a></p>');
    
        
  } else {
  
  $fe = new FireEagle($fe_key, $fe_secret);
  $tok = $fe->getRequestToken();
  
  if (!isset($tok['oauth_token'])
      || !is_string($tok['oauth_token'])
      || !isset($tok['oauth_token_secret'])
      || !is_string($tok['oauth_token_secret'])) {
   echo "ERROR! FireEagle::getRequestToken() returned an invalid response Giving up.";
   exit;
  };
  
  $_SESSION['auth_state'] = "start";
  $_SESSION['request_token'] = $token = $tok['oauth_token'];
  $_SESSION['request_secret'] = $tok['oauth_token_secret'];
  echo('Location: <a href="'.$fe->getAuthorizeURL($token).'" target="_new">Authorize FireEagle</a>');
  
  echo('<p>Afterwards. Please proceed to <a href="'.$_SERVER["REQUEST_URI"].'&step=2">Step 2</a></p>');

  };
};

function wpfe_config_page($value='') {
  if ( function_exists( 'add_submenu_page' ) )
  add_submenu_page( 'plugins.php', __('FireEagle Configuration'), __('FireEagle'), 'manage_options', __FILE__, 'wpfe_wp_admin' );
}

add_action( 'admin_menu', 'wpfe_config_page' );

?>