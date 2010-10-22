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

function wpg3_work(){
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

  // g3-home ?
  if ( ! isset($_GET['itemid']) ){
		$url = $wpg3_settings['g3Url'].$wpg3_settings['g3Home'];
	} else {
	  $url = $wpg3_settings['g3Url']."/rest/item/" . $_GET['itemid'];
	}
	
	// get g3 Data
	$xml = getItem($url);
  if (! isset($xml['type'])){ die ( "ERROR @ getItem ($url)"); }

	// where we start (@todo pager missing!)
  if ( isset($_GET['start']) and $_GET['start'] != "") $firstItemOnPage = $_GET['start'];
  
	/* g3 entity title */
	$html .= wpg3_view_title( $xml["title"] );
	
	#############################
	
	// ALBUM OR PHOTO OR Movie
	switch ($xml['type']) {
	
	
	  /* creating Album Page */
    case "album":
          echo '<div class="albums">';
          
          $wpg3_settings["lastItemOnPage"] = ($wpg3_settings["firstItemOnPage"] + $wpg3_settings["itemsOnPage"]);
          if ( isset ($xml['children']) and $wpg3_settings["lastItemOnPage"] > count($xml['children']) ){
            $wpg3_settings["lastItemOnPage"] = count($xml['children']);
          }

          /* get all children */          
          for ($child = $wpg3_settings["firstItemOnPage"]; $child < $wpg3_settings["lastItemOnPage"]; $child++) {
            $item = getItem($xml['children'][$child]);
            wpg3_view_itemBlock($item);           
          }
          
          echo "</div>";
          break;
    
    /* creating Photo Page */
    case "photo":
      echo "<div class='gallery-photo'>";
			echo "<a href='".$xml['full']."'><img src='".$xml['resized']."'/></a>";
  		echo "</div><br/><br/>";
      $desc = $xml['description'];
      if ($desc == "") $desc = "Not provided.";
      echo "<table class='datatable'>
            <tr><th colspan=2>Photo Information</th></tr>
            <tr><td>Description</td><td>".$desc."</td></tr>
            <tr><td>Date Photographed</td><td>".$xml['date_photo']."</td></tr>
            <tr><td>Date Updated</td><td>".$xml['date_updated']."</td></tr>
            <tr><td>Dimensions</td><td>".$xml['real_width']."x".$xml['real_height']."</td></tr>
            <tr><td>Link to full photo</td><td><a href='".$xml['full']."'>Here</a></td></tr>
          </table>";
      break;

    /* crating movie Page  UNTESTED !!!*/
    case 2:
      echo '<h5 style="color: red; border-top: 1px dashed red; padding-left: 1em;">'.$xml['type']."</h5>";
      echo "<div class='gallery-photo'>";
      $url = str_replace("thumbs","albums",substr($xml['thumbnail'],0,strripos($xml['thumbnail'],"/")+1).$xml['name']);
			echo "<object id='pla123123sadgasdfsdafasdfasdffdfddfadsf1yer' 
						classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' 
						name='pl123123asdfasdfasdfayer' 
						width='640' height='352'>
					<param name='movie' value='http://www.otenko.com/gallery2/modules/flashvideo/lib/G2flv.swf'>
					<param name='allowfullscreen' value='true'>
					<param name='allowscriptaccess' value='always'>
					<param name='flashvars' value='type=video&amp;file=".urlencode($url)."'>
					<embed type='application/x-shockwave-flash' 
							id='pla1212asdfahlkljklsdfdasfsd3123yer2' 
							name='ghjghdgplay123asfdasdf124242523er2' 
							src='http://www.otenko.com/gallery2/modules/flashvideo/lib/G2flv.swf' 
							width='640' height='352' 
							allowscriptaccess='always' 
							allowfullscreen='true' 
							flashvars='type=video&amp;file=".urlencode($url)."'>
					</object>";
		   echo "</div><br/><br/>";
		   $desc = $xml['description'];
		    if ($desc == "") $desc = "Not provided.";
		    echo "<table class='datatable'>
					<tr><th colspan=2>Photo Information</th></tr>
					<tr><td>Description</td><td>".$desc."</td></tr>
					<tr><td>Date Photographed</td><td>".$xml['date_photo']."</td></tr>
					<tr><td>Date Updated</td><td>".$xml['date_updated']."</td></tr>
					<tr><td>Dimensions</td><td>".$xml['real_width']."x".$xml['real_height']."</td></tr>
					<tr><td>Link to full photo</td><td><a href='".$xml['full']."'>Here</a></td></tr>
				</table>";
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

// @ toDo: What can I do if Cokies are diaabled?
/*
  if ! isset ($_SESSION) and isset ($_GET(SSID) get back my Session data???
*/

// set up XHTTP trough Wordpress WP_Http Class
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

function wpg3_view_itemBlock($item){
  global $wpg3_settings;
  echo "<div class='block' style='display: inline-block; width: 150px; margin: 3px; background: #efefef; text-align: center; padding-top: 6px;'>";
            if ($item['type'] == "album"){
              echo '<a href="'.$wpg3_settings["scriptUrl"].'?itemid='.$item['id'].'"';            
            }else{             
              echo "<a href='".$item['full']."' rel='lightbox[photos]' class='lightbox-enabled' title='".$item["title"]."'>";
            }
              echo "<img src='".$item["thumbnail"]."'/>";
              echo "</a>";
              echo '<h4><a href="'.$wpg3_settings["scriptUrl"].'?itemid='.$item['id'].'">'.$item["title"]."</a></h4>";
            
            /* META */
            echo "<div class='meta'>";
            if ($item['type'] == "album"){
              echo count($item['children'])." Items";
            }
            if ($item['description'] != ""){
              echo "<p>".$item['description']."</p>";
            }
            echo "</div>"; // END class=meta
            
            echo "</div>"; // END class=block	
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

	/* Run the input check. */		
	if(false === strpos($content, '<!--wpg3-->') and !$templateTag) {
		return $content;
	}else{
    return str_replace('<!--wpg3-->', wpg3_work(), $content);
  }
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