<?php
/*
Plugin Name: WPG3 0.5
Plugin URI: http://wpg3.digitaldonkey.de
Description: Sucessor of the WPG2 Plugin Compatible to Gallery3 and WP3+ @ ALPHA-DEV
Author URI: http://donkeymedia.eu
Version: 0.5
*/


// load class before session start in Order to store

/* Get started :) */
$wpg3_settings;

function wpg3_work($g3_item=false){
  global $wpg3_settings;
  //check the time
  $start = microtime(true);

  global $wp_query;
  $html = "";

// SETTINGS
  $wpg3_settings = array(
  "g3Url" => stripslashes(get_option('wpg3_g3Url')),
  "scriptUrl" => get_permalink( $wp_query->post->ID ),
  //page display
	"itemsOnPage" => 30, // if you load a lot of Elements check loading Speed!
	"firstItemOnPage" => 0,
	"g3Home" => "/rest/item/1",
	"cache_time" => 60*15, // 1h=60*60*1	
	);

  // g3-home or do we start on a different Element?
  if ($g3_item){
    // so we found a <!--wpg3="$g3_item[2]"-->  
    $wpg3_settings['g3Home'] = '/rest/'.$g3_item[2];
  }
  if ( ! isset($_GET['itemid']) ){
		$url = $wpg3_settings['g3Url'].$wpg3_settings['g3Home'];
	} else {
	  $url = $wpg3_settings['g3Url']."/rest/item/" . $_GET['itemid'];
	}
	
	// get g3 Data
	
	require_once('wpg3_class_xhttp.php');
	$xhttp=new Wpg3_Xhttp($wpg3_settings);
  //$xhttp->printSettings();
  
  //$xhttp->clear_cache();
	$items = $xhttp->get_item( $url );

	require_once('wpg3_class_template.php');
  $templates=new WPG3_Template();
  //echo $myClass->debug_templates();
  
  if ( $items->entity->type == "album" ){
    echo ($templates->use_template('template2_album_template_01', $items));
  }
  if ( $items->entity->type == "photo" ){
    echo ($templates->use_template('template2_photo_template_01', $items));
  }
  $sctipttime =  microtime(true) - $start;
  echo '<div style="border: 1px dotted red;">WPG3 Main Work Time: '.$sctipttime." sec.</div>";

}




/**
 *   Plugin Functions
**/

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


/* Set a tag where to put the gallery */
function wpg3_callback( $content , $templateTag=false) {
  $return = false;
	/* Run the input check. */		
	if(false === strpos($content, '<!--wpg3') and !$templateTag) {
		$return = $content;
	}else{
	  if (strpos($content, '<!--wpg3-->')){
	    // only the Tag -> Starting at base level
	    $return =  str_replace('<!--wpg3-->', wpg3_work(), $content);
	  }else{
    $return = preg_replace_callback('/(.*)\<!--wpg3="(.*)"--\>(.*)/is', "wpg3_work" , $content );
	  }
  }
  return $return;
}


/**
 *   Helper Functions
**/

function wpg3_debug($array){
      echo  "<pre>";    
      print_r( $array );
      echo "</pre>";
}

/* Action calls for all functions */
add_action('admin_menu', 'wpg3_add_options_page');
add_filter('the_content', 'wpg3_callback');

?>