<?php
/*
Plugin Name: WPG3
Plugin URI: http://wpg3.digitaldonkey.de
Description: Sucessor of the WPG2 Plugin Compatible to Gallery3 and WP3+ @ ALPHA-DEV
Author URI: http://donkeymedia.eu
Version: 0.1
*/


/* OPTIONS PAGE */
function wpg3_add_options_page() {
		add_options_page(__('WPG3 Options', 'wpg3'), __('WPG3', 'wpg3'), 'manage_options', 'wpg3_plugin_options' ,'wpg3_plugin_options');
	}
function wpg3_plugin_options() {

  if (!current_user_can('manage_options'))  {
    wp_die( __('You do not have sufficient permissions to access this page ???') );
  }
  require_once('wpg3-options.php');
}


/* Create a working Page */
function wpg3_callback( $content , $templateTag=false) {

	/* Run the input check. */		
	if(false === strpos($content, '<!--wpg3-->') and !$templateTag) {
		return $content;
	}else{
    return str_replace('<!--wpg3-->', wpg3_work(), $content);
  }
}

/* Get started :) */
function wpg3_work(){

  //check the time
  $start = microtime(true);

  $html = "";
  
  // get Options
  $wpg3_g3Url = stripslashes(get_option('wpg3_g3Url'));
  
  // Example 1 get all Members of a resource (in a very long-winded way I think)
  $html .= wpg3_getAllMembers($wpg3_g3Url);
  
  // Example 2 get a random Child 
  //$html .= wpg3_getRandomChild($wpg3_g3Url);
  

  // Example 3 get ul-list of Elements 
  //$html .= wpg3_getElementsList($wpg3_g3Url);
  
  
  echo $html;
  
  $sctipttime =  microtime(true) - $start;
  echo '<div style="border: 1px dotted red;">Execution Time: '.$sctipttime." sec.</div>";
}


/* EXAMPLE get items list */ 
function wpg3_getElementsList($url){
  $html = "";
  
  // create a querry list for all members
  $get =  $url.'?scope=all&type=album';

  $result = wpg3_getRequestObject($get);
  
  wpg3_debug($result);
  
  //$html.= wpg3_makeThumb( $result->entity);
  
  return $html;
}

/* EXAMPLE get random item */ 
function wpg3_getRandomChild($url){
  
  $html = "";
  
  // create a querry list for all members
  $get =  $url.'?random=true';

  $result = wpg3_getRequestObject($get);
  
  // get the random Member 
  $result = wpg3_getRequestObject($result->members[0]);
    
  $html.= wpg3_makeThumb( $result->entity);
  
  return $html;
}


/* EXAMPLE get all members */ 
function wpg3_getAllMembers($url){
  $html = "";
  // get the root Item
  $result = wpg3_getRequestObject($url);

  //find all members
  $myMembers = array();
  foreach ($result->members as $url ){
    array_push( $myMembers, $url);
  }

  // create a querry list for all members
  $urls = 'http://wpg3.local/gallery3/index.php/rest/items?urls=["'.implode($myMembers, '","').'"]';
    
  // GETTING Multiple Objects
  //$all = 'http://wpg3.local/gallery3/index.php/rest/items?urls=["http://wpg3.local/gallery3/index.php/rest/item/2","http://wpg3.local/gallery3/index.php/rest/item/4"]';
  
  $result = wpg3_getRequestObject($urls);
  
  foreach ($result as $obj){
    $html.= wpg3_makeThumb( $obj->entity);
  }

  return $html;
}

/* Start a session or return it */
function get_REST($url) {
  //$start = microtime(true);
  
  if (!isset($_SESSION['gallery3_cache'][$url])) {
  
  if( !class_exists( 'WP_Http' ) ){
    require_once( ABSPATH . WPINC. '/class-http.php' );
  } 
    $_SESSION['gallery3_cache'][$url] = new WP_Http;
  }
  
  //$sctipttime =  microtime(true) - $start;
  //echo '<div style="border: 1px dotted green;">get_REST Time: '.$sctipttime." sec.</div>";
  
  return $_SESSION['gallery3_cache'][$url];

}

/* create a request */
function wpg3_getRequestObject($url){
  $start = microtime(true);

  $xhttp_req = get_REST($url);
    
  $result = $xhttp_req->request( $url );

  if( is_wp_error( $result ) ){
    echo "Uuups... There is a wp_error thrown while getting $url<br />";
    echo $result->get_error_message()."<br />";
  }else{
    // if you get a 403 check if the REST module is enabled and allow_guest_access=1 in Extended Options
    
    // fetch result into a PHP array
    $result = json_decode ( $result['body']);
    
   //wpg3_debug($result);
   
  $sctipttime =  microtime(true) - $start;
  echo '<div style="border: 1px dotted red;">wpg3_getRequestObject Time: '.$sctipttime." sec.</div>";

    return $result;
  }

}




/* create a Thumb output*/
function wpg3_makeThumb($entity){
  //wpg3_debug($entity);
  $html = '<img src ="'.$entity->thumb_url.'" alt="'.$entity->description.'" height="'.$entity->thumb_height.'" width="'.$entity->thumb_width.'" />';
  return $html;
}

function wpg3_debug($array){
      echo  "<pre>";    
      print_r( $array );
      echo "</pre>";
}

/* Action calls for all functions */
add_action('admin_menu', 'wpg3_add_options_page');
add_filter('the_content', 'wpg3_callback');

?>