<?php
/*
Plugin Name: Wordpress FireEagle plugin
Plugin URI: http://www.jurecuhalev.com/blog/wp-fireeagle
Description: FireEagle Integration for Wordpress
Version: 0.1
Author: Jure Cuhalev <jure@zemanta.com>
Author URI: http://www.jurecuhalev.com/blog

Copyright 2008  Jure Cuhalev

Parts of Authorization Code and styling taken from Flickr Manager 2.0.2 
plugin written by Trent Gardner - http://tgardner.net/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

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
    if (empty($loc->user->location_hierarchy)) {
     ?>Fire Eagle doesn't know where you are yet.<?php
    };
    
    wp_cache_set('wpfe_best_guess_name', $best_guess_name);
  };
  
  ?><?php echo $best_guess_name?><?php
};


function wpfe_wp_admin(){
  global $fe_key; global $fe_secret;
  
  $access_token = get_option('wpfe_access_token');
  $access_secret = get_option('wpfe_access_secret');
  
  ?>
  <div class="wrap">
  <h2>FireEagle Configuration</h2>
  
  <?php
  
  if(!empty($_REQUEST['action'])) : 
    switch ($_REQUEST['action']) :
      case 'token':
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

        $access_token = get_option('wpfe_access_token');
        $access_secret = get_option('wpfe_access_secret');
      break;
    endswitch;
  endif;
  
  
  // check if we have tokens and be happy about it then
  if (($access_token != false) && ($access_secret != false)){
    $fe = new FireEagle($fe_key, $fe_secret, $access_token, $access_secret);
    
    ?>
    <h3>Authentication</h3>
    <p>Congratulations, your FireEagle account is sucesfully authorized with this Wordpress installation.</p>
    <p>If you want, you can <a href="#">revoke it</a> (to be implemented).</p>
    
    <h3>Location</h3>
    <p>FireEagle's best guess about your current location is: <b><?php wpfe_display_best_guess_name() ?></b>.</p>
    <p><b>Note:</b> plugin checkes FireEagle for updated location status every 15 minutes.</p>
    
    <?php

  } else {
  // otherwise start authentication process
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
  
  ?>
  <div align="center">
    <h3>Step 1:</h3>
    <form>
      <input type="button" value="Authenticate" onclick="window.open('<?php echo $fe->getAuthorizeURL($token); ?>')" style="background: url( images/fade-butt.png ); border: 3px double #999; border-left-color: #ccc; border-top-color: #ccc; color: #333; padding: 0.25em; font-size: 1.5em;" />
    </form>

    <h3>Step 2:</h3>
    <form method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
      <input type="hidden" name="action" value="token" />
      <input type="submit" name="Submit" value="<?php _e('Finish &raquo;') ?>" style="background: url( images/fade-butt.png ); border: 3px double #999; border-left-color: #ccc; border-top-color: #ccc; color: #333; padding: 0.25em; font-size: 1.5em;" />
    </form>
  </div>
  
  <?php
  };
  ?>
  </div><?php
};

function wpfe_config_page($value='') {
  if ( function_exists( 'add_submenu_page' ) )
  add_submenu_page( 'plugins.php', __('FireEagle Configuration'), __('FireEagle'), 'manage_options', __FILE__, 'wpfe_wp_admin' );
}

add_action( 'admin_menu', 'wpfe_config_page' );

?>