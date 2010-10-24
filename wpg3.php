<?php
/*
Plugin Name: WPG3
Plugin URI: http://wpg3.digitaldonkey.de
Description: Sucessor of the WPG2 Plugin Compatible to Gallery3 and WP3+ @ ALPHA-DEV
Author URI: http://donkeymedia.eu
Version: 0.2
*/

// Load the Classes before session_start ??
if (isset($_SESSION)){
  die("Too many sessions");
}
// load class before session start in Order to store
if( !class_exists( 'WP_Http' ) ){
  require_once( ABSPATH . WPINC. '/class-http.php' );
} 
session_start();
//wpg3_debug ($_SESSION);

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
	"g3Home" => "/rest/item/1"
	
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
	$item = getItem($url);
  if (! isset($item['type'])){ die ( "ERROR @ getItem ($url)"); }

	// where we start (@todo pager missing!)
  if ( isset($_GET['start']) and $_GET['start'] != "") $firstItemOnPage = $_GET['start'];
  
	/* g3 entity title */
	$html .= wpg3_view_title( $item["title"] );
	
	#############################
	
	// ALBUM OR PHOTO OR Movie
	//$item['type'] = "movie";
	switch ($item['type']) {
	
	
	  /* creating Album Page */
    case "album":
          $html .= '<div class="album">';
          
          $wpg3_settings["lastItemOnPage"] = ($wpg3_settings["firstItemOnPage"] + $wpg3_settings["itemsOnPage"]);
          if ( isset ($item['children']) and $wpg3_settings["lastItemOnPage"] > count($item['children']) ){
            $wpg3_settings["lastItemOnPage"] = count($item['children']);
          }

          /* get all children */          
          for ($child = $wpg3_settings["firstItemOnPage"]; $child < $wpg3_settings["lastItemOnPage"]; $child++) {
            $child_item = getItem($item['children'][$child]);
            $html .= wpg3_view_itemBlock($child_item);           
          }
          
          $html .= "</div>";
          break;
    
    /* creating Photo Page */
    case "photo":
      $html .= "<div class='photo'>";
      $html .= wpg3_view_photopage($item);
      $html .= "</div>"; // end class=photo
      break;

    /* crating movie Page  UNTESTED !!!*/
    case "movie":
      $html .=  "<div class='movie'>";     
      $item['video_url'] = str_replace("thumbs","albums",substr($item['thumbnail'],0,strripos($item['thumbnail'],"/")+1).$item['name']);
      wpg3_view_videoPage($item);
      $html .=  "</div>"; // end class=movie;
     break;
    
    /* We dont know the Entity type */
    default: 
       echo "unknown Entity type";
  }
	
	
	
  #############################
  $html .= '';
  
  echo $html;
  
  $sctipttime =  microtime(true) - $start;
  echo '<div style="border: 1px dotted red;">Execution Time: '.$sctipttime." sec.</div>";
  //wpg3_debug($_SESSION);
}




/**
 *   Object and XHTTP FUNCTIONS
**/

/* 
   BY NOW we have two different ways of caching the g3-HTTP ( get_REST_xml() ) data.
   We'll need to check a good, fast way 
*/

/**
 *  HERE: SESSION BASES CACHE. 
 *  - Needs reload for every user. 
 *     With 4 Albums and 16 items it takes about 4 sec to get the main gallery loaded ... :(
 *  - No "without cockie" implementation yet  @ toDo: What can I do if Cokies are diaabled?
**/
/*
function get_REST_xml($uri) {
 global $wpg3_settings;
 $WP_http_class_cache = $wpg3_settings['g3Url'];
 
 if (!isset($_SESSION['gallery3_cache'][$WP_http_class_cache]) ) {
    echo "INFO: created new Session";
    if( !class_exists( 'WP_Http' ) ){
      require_once( ABSPATH . WPINC. '/class-http.php' );
    } 
    $_SESSION['gallery3_cache'][$WP_http_class_cache] = new WP_Http;
  }
  if (! isset($_SESSION['gallery3_cache'][$uri])){
    $this_req = $_SESSION['gallery3_cache'][$WP_http_class_cache]->request( $uri );
    $_SESSION['gallery3_cache'][$uri] = json_decode($this_req['body']);
  }
  return $_SESSION['gallery3_cache'][$uri];
}
*/

/**
 *  HERE: caching with transients
 *  http://codex.wordpress.org/Transients_API
 *  the when to update is not yet implemented in a good way, 
 *  because we reflect changes in the root only after expiration time
 *  so if you have a large gallery and a lot of visits, you only update the subitems.
 *  Maybe we should give every object its own expire-time too.
**/
function get_REST_xml($uri) {
 global $wpg3_settings;
 $cache_time = 60*15; // 1h=60*60*1
 $WP_http_class_cache = $wpg3_settings['g3Url'];
 $update_cache = false;
 
 //delete_transient($WP_http_class_cache); // --> Unregister Plugin!

 if (false === ( $myCache = get_transient($WP_http_class_cache) ) ) {
    // It wasn't there, so regenerate the data and save the transient
    echo "INFO: created new Cache";    
    if( !class_exists( 'WP_Http' ) ){
      require_once( ABSPATH . WPINC. '/class-http.php' );
    } 
    $request_data['WP_Http'] = new WP_Http;
    $update_cache = true;
  }else{
      $request_data = get_transient($WP_http_class_cache);
  }

  if (! isset($request_data['item'][$uri])){
    $this_req = $request_data['WP_Http'] ->request( $uri );
    $request_data['item'][$uri] = json_decode($this_req['body']);
    $update_cache = true;
  }
  
  if ($update_cache){
    set_transient($WP_http_class_cache, $request_data, $cache_time);
  }
  
  return $request_data['item'][$uri];
}

/* get the object and fetch a item array */
function getItem($uri) {
  $format = get_option('date_format');
  $jsonObj = get_REST_xml($uri);  
  if (! is_object($jsonObj )) die ("ERROR @ get_REST_xml($uri)");
  $item =  array(
    "thumbnail" => $jsonObj->entity->thumb_url,
    "description" => $jsonObj->entity->description,
    "name" => $jsonObj->entity->name,
    "title" => $jsonObj->entity->title,
    "id" => $jsonObj->entity->id,
    "viewcount" => $jsonObj->entity->view_count,
    "type" => $jsonObj->entity->type,
    "url" => $jsonObj->url,
    "date_photo" => date($format,$jsonObj->entity->captured-25200),
    "date_updated" => date($format,$jsonObj->entity->updated+25200),
    "real_width" => $jsonObj->entity->width,
    "real_height" => $jsonObj->entity->height
   );
   if($item["type"] != 'photo'){
      $item["children"] = $jsonObj->members;
   }
   if($item["type"] != 'album'){
      $item["resized"] = $jsonObj->entity->resize_url;
      $item["full"] = $jsonObj->entity->file_url;
   }   
   if( isset ($jsonObj->entity->parent)){
    $item["parent"] = $jsonObj->entity->parent;
   }   
   return $item;
}



/**
 *   View Functions
**/

/* get item as block (for the Album page) */
function wpg3_view_itemBlock($item){
  global $wpg3_settings;
  $html = '';
  $html .= "<div class='block' style='display: inline-block; width: 150px; margin: 3px; background: #efefef; text-align: center; padding-top: 6px;'>";
  if ($item['type'] == "album"){
    $html .= '<a href="'.$wpg3_settings["scriptUrl"].'?itemid='.$item['id'].'"';            
  }else{             
    $html .= "<a href='".$item['full']."' rel='lightbox[photos]' class='lightbox-enabled' title='".$item["title"]."'>";
  }
  $html .= "<img src='".$item["thumbnail"]."'/>";
  $html .= "</a>";
  $html .= '<h4><a href="'.$wpg3_settings["scriptUrl"].'?itemid='.$item['id'].'">'.$item["title"]."</a></h4>";
  /* META */
  $html .= "<div class='meta'>";
  if ($item['type'] == "album"){
    $html .= count($item['children'])." Items";
  }
  if ($item['description'] != ""){
    $html .= "<p>".$item['description']."</p>";
  }
  $html .= "</div>"; // END class=meta  
  $html .= "</div>"; // END class=block	
  return $html;
}

/*  create a photo Page */
function wpg3_view_photopage($item){
  $html = '';
  $html .= "<a href='".$item['full']."'><img src='".$item['resized']."'/></a>";
  $desc = $item['description'];
  if ($desc == "") $desc = "Not provided.";
	$html .=  wpg3_view_get_desc($item);
  return $html;
}

/* create a video Page */
function wpg3_view_videoPage($item){
  $html = '';
  $html .= "<object id='pla123123sadgasdfsdafasdfasdffdfddfadsf1yer' 
						classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' 
						name='pl123123asdfasdfasdfayer' 
						width='640' height='352'>
					<param name='movie' value='". plugins_url().'/wpg3/G2flv.swf'."'>
					<param name='allowfullscreen' value='true'>
					<param name='allowscriptaccess' value='always'>
					<param name='flashvars' value='type=video&amp;file=".urlencode($item['video_url'])."'>
					<embed type='application/x-shockwave-flash' 
							id='pla1212asdfahlkljklsdfdasfsd3123yer2' 
							name='ghjghdgplay123asfdasdf124242523er2' 
							src='". plugins_url().'/wpg3/G2flv.swf'."' 
							width='640' height='352' 
							allowscriptaccess='always' 
							allowfullscreen='true' 
							flashvars='type=video&amp;file=".urlencode($item['video_url'])."'>
					</object>";
	$html .=  wpg3_view_get_desc($item);
  return $html;
}			

function wpg3_view_get_desc($item){
  $html = '';
  $desc = $item['description'];
	if ($desc == ""){
	  $desc = "Not provided.";
	}
	$html .=  "<table class='datatable'>
					<tr><th colspan=2>Photo Information</th></tr>
					<tr><td>Description</td><td>".$desc."</td></tr>
					<tr><td>Date Photographed</td><td>".$item['date_photo']."</td></tr>
					<tr><td>Date Updated</td><td>".$item['date_updated']."</td></tr>
					<tr><td>Dimensions</td><td>".$item['real_width']."x".$item['real_height']."</td></tr>";
  if (isset ($item['full'])){
    $html .= '<tr><td>Link to full photo</td><td><a href="'.$item['full'].'">Here</a></td></tr>';
  }
  $html .="</table>";
  return $html;
}
      
function wpg3_view_title($title="hallo"){
	return "<h2>".$title."</h2>";	
}



/* create a Thumb output*/
function wpg3_view_makeThumb($entity){
  //wpg3_debug($entity);
  $html = '<img src ="'.$entity->thumb_url.'" alt="'.$entity->description.'" height="'.$entity->thumb_height.'" width="'.$entity->thumb_width.'" />';
  return $html;
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