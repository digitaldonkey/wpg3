<?php
/*
  Plugin Name: WPG3 0.7
  Plugin URI: http://wpg3.digitaldonkey.de
  Description: Sucessor of the WPG2 Plugin Compatible to Gallery3 and WP3+ @ ALPHA-DEV
  Author URI: http://donkeymedia.eu
  Version: 0.7
*/
/**
  *   WPG3 Main
  *
  *   @link http://wpg3.digitaldonkey.de
  *   @author Thorsten Krug <driver@digitaldonkey.de>
  *   @version 1.0
  *   @filesource
  *   @package WPG3
 **/


//error_reporting (1);  

  global $wpg3;
  $wpg3 = new Wpg3_Main();

 /**
   *   MAIN CLASS 
   *
   *  MODULES in WPG3
   *
   *  WPG3 is modularized
   *
   *  Every Mudule can have ONE OPTINS SECTION and a number of SETTINGS-FIELDS
   *  - The options Section should provide:
   *    - a unique section ID
   *    - a Section Title
   *    - callback_fn displaying the Settings
   *  - Every Setting-Field need to provide:
   *    - a unique ID
   *    - a TITLE
   *    - a callback function to display the input box
   *    - a validate function including and an error message if it dosn't validate
   *  
   *  
   *   @package WPG3
  **/
class Wpg3_Main
{ 
  public $is_enabled = false;
  private $wpg3_options;
  /**
    *  Storing module instances
    *  use: get_module($classname);
   **/
  private $modules;
  private $debug = false;
  /**
   *   Load wpg3_options 
   *  
  **/
  function __construct() {
    $wpg3_options = get_option('wpg3_options');
    if ( is_array($wpg3_options) ){
      $this->wpg3_options = $wpg3_options;
      $this->is_enabled = true;
    }else{
      add_action('admin_notices', array( $this , 'no_settings_yet') );
    }
}

/**
 *  WPG3 post/page content
 *
 *  Here goes everything that replaces the <WPG>-Tag in a Post.
 *
 *  @todo DEFINE PARAM??? --> CONTENT FILTER REGEXP
 *  @param [array] Optional array Item (
**/
public function wpg3_content($g3_tag=false)
{ 
    if($this->debug) $start = microtime(true);

 /**
  *   @global object $wp_query
  *   needed to get the Url of the page or post the <WPG3>-Tag is placed
  *   to enable linking within the gallery
 **/
  global $wp_query;
  if (isset($wp_query->post->ID)) $this->wpg3_options['scriptUrl'] = get_permalink( $wp_query->post->ID );

 /**
  *  current REST request Uri
  *
  *  Default is the Value chosen on the WPG3-Options Page
  *  Here we handle which REST-Item-Url we will use.
  *  e.g. $_GET['itemid'], <WPG3>-Tags, Permalinks ...
  *  @todo Permalinks
 **/
 // that's what we'll pass to get_item()
  $get_item = array('id' => false,
                    'rest_uri' => $this->wpg3_options['g3Url'].$this->wpg3_options['g3Home'],
                    'width' => false,
                    'template' => false );
                    
  /**
   *  There was a <WPGX>-Tag
   *  
   *  @internal
   *  We might have here : 
   *  $g3_tag[0] => all of the following merged
   *  $g3_tag[1] => int.id|str.rel.path|str.REST.path
   *  $g3_tag[2] => int.width|str.width
   *  $g3_tag[3] => str.template.id
  **/

  // Debug REST $url
  if ( is_array($g3_tag) ){
    if ($this->debug){
      echo '<div style="padding: 5px;border: 1px dotted red;"><strong>&lt;WPGX&gt;'.$g3_tag[0].'&lt;/WPGX&gt;</strong>';
      echo '<pre style="font-size: 12px;">'."\n";
      echo '<wpg3> [int.id|str.rel.path|str.REST.path] [ | [int.width|str.width] ] [ | [str.template]</wpg3>';
      echo "</pre>";
      echo "</div>";
    }
  }
 /**
  * $_GET Requests
  *
 **/
  if ( isset($_GET['itemid']) and is_int( intval($_GET['itemid'])) ){
    $get_item = array( 'id' => $_GET['itemid'] );
  }
  
  // Debug REST $url
  if ($this->debug) echo '<pre style="padding: 5px;border: 1px dotted red;"><strong>REST request array:</strong><br />'.print_r( $get_item ,true).'</pre>';
	
	// Getting Items
	$xhttp = $this->get_module_instance('WPG3_Xhttp');
  //$xhttp->printSettings();
  //$xhttp->clear_cache();

	$items = $xhttp->get_item( $get_item );

  $templates = $this->get_module_instance('WPG3_Template');
  //echo $templates->debug_templates();
  
  if ( $items->entity->type == "album" ){
    echo ($templates->use_template('defaultTemplate_default_album', $items));
  }
  if ( $items->entity->type == "photo" ){
    echo ($templates->use_template('defaultTemplate_default_photo', $items));
  }	
	
  if($this->debug) echo '<div style="border: 1px dotted red;">WPG3 main script time: '.round( (microtime(true) - $start) , 4)." sec.</div>";
}

/**
 *    WPG3 Content Callback
 *   
 *   We filter post/page Content and look for WPGX Tags
 *
 *   <b>The WPG3-Tag</b>
 *   <wpg3> [id|rel.path|REST.path] [ | [int.width|str.[] ] ]</wpg3> 
 * 
 *   The second Value (sepparated by "|") is optional. if missing we will
 *   get the default size from wpg3-Options-Page.
 *
 *   The sizes correspond to g3: Values are:  'thumb', 'custom' and 'full'
 *   e.g: <wpg3>id</wpg3>
 *   or: <wpg3>REST.path|thumb</wpg3>
 *   
 *   
 *   <b>WPG2 Compatibility:</b>
 *   
 *   You can use equivalently the WPG2-Tag which will render the sam way WPG3-Tag does.
 *  
 *   No CUSTOM RESIZE by now :(
 *   We will work around the missing g3-custom-resize function
 *   by using trashold-values you can set on the Options page.
 *   You can choose to which of the available g3-images-sizes you want to
 *   switch for you int.width
 *
 *   For your Memory: The (latest available) WPG2 Tag was:
 *   <wpg2> [id] [ | [int.width] ]</wpg2> e.g: <wpg2>6570|200</wpg2>
 *   By now I didn't implement <wpg2id>.
 *   
 *  @param string $content
 *  @param bool $templateTag=false
 *  @todo implement Template Tag Support
**/
  public function wpg3_content_callback( $content , $templateTag=false) {
    $return = false;
    /* Run the input check. */		
    if(false === strpos($content, '<wpg') and !$templateTag) {
      $return = $content;
    }else{
      $return = preg_replace_callback('/<wpg[23]>([^\|]*)[\|]?([^\|]*)[\|]?(.*)<\/wpg[23]>/i', array( $this, 'wpg3_content' ), $content );
      
      /*
      echo "<pre>\n";
      if (! is_array($return)) echo 'NO ARRAY';
      print_r ( $return );
      echo "</pre>";
      */
   }
    return $return;
  }

  /**
   *  Error Message: Missing Settings
  **/
  public function no_settings_yet(){
     echo '<div class="error"><p>Please check WPG3 Options in in order to enable the Plugin.</p></div>';
  }

  /**
   *   Redirection init Scripts
   *
  **/
  public function wpg3_init(){
    if ($this->is_enabled){
      $template = $this->get_module_instance('WPG3_Template');
      $template->register_script_and_css();
    }
  }


  /**
   *   Options Page and Input Validation for WPG3-Modules 
   *
   *   Depends on WP-Settings Api
  **/
  public function wpg3_admin_init()
  {
    
    // MODULES register their options here
    $modules = array($this->get_module() );
    if($this->is_enabled){

      $xhttp = $this->get_module_instance('WPG3_Xhttp');
      array_push($modules, $xhttp->get_module() );

      $template = $this->get_module_instance('WPG3_Template');
      array_push($modules, $template->get_module() );
    }
    
    register_setting(
      'wpg3_options_grp', // settings group
      'wpg3_options', // settings
      array(&$this,'admin_validate_options') // fn to validate input
      );
    $page = 'wpg3_options_page';
        
    // load Settings API
    if (!function_exists('add_settings_section')){
      require_once(ABSPATH.'wp-admin/includes/template.php');
    }
    /**
      * stores validation functions
     **/
    $this->validate_fields = array();
    //  setting Option sections and fields
    foreach( $modules as $module){
      if ( $module ){
        add_settings_section( $module['unique_name'], $module['title'], $module['function'], $page);
        if( isset($module['settings_fields']) ){
          foreach($module['settings_fields'] as $field){
           add_settings_field($field['field_id'], $field['field_title'], $field['field_display'], $page, $module['unique_name']);
           if( isset($field['field_validate']) )$this->validate_fields[ $field['field_id'] ] = $field['field_validate'];
          }
        }
      }
    }  
  }

/**
  *   get a module instance, load if required
  *   @param string Classname
  *   @return object instance
 **/ 
  public function get_module_instance($module){
    $return = false;
    if (isset($this->modules[$module]) ){
      $return = $this->modules[$module];
    }else{
      $this->__autoload( $module );
      $instance = new $module( $this->wpg3_options );
      $this->modules[$module] = $instance;
      $return = $instance;
    }
   return $return;
  }

/**
  *   Create Options Page for WPG3
  *   @internal
 **/  
  public function admin_add_page()
  {                     
    add_options_page(__('WPG3 Settings'),
                     __('WPG3'),
                     'manage_options',
                     __('WPG3'),
                     array(&$this,'admin_optins_page_fn')
                     );
  }
  
/**
  *   Echo Option Page
  *   @internal
 **/ 
  public function admin_optins_page_fn()
  { 
    //echo "This is the admin_optins_page() ";
    echo "\n<div>\n";    
    echo '<h2>'.__('WPG3 Settings')."</h2>\n";
    echo '<form action="options.php" method="post">'."\n";
    settings_fields('wpg3_options_grp'); // Group Name
    echo "\n";
    do_settings_sections('wpg3_options_page'); 
    echo "\n";
    echo '<p><input name="Submit" type="submit" value="'. __('Save Changes').'" /></p>'."\n";
    echo "</form>\n</div>\n";
  }
  
/**
  *   Validate Option Fields
  *
  *   @param array WP-secured Input from $_POST
  *   @return array Input validated 
 **/
  public function admin_validate_options($input)
  { 
    $wpg3_options = $this->wpg3_options;
    
    $validatable = array_intersect_key( $this->validate_fields,  $input);

    foreach ($validatable as $field => $function){
      $val = call_user_func ($function, $input[$field]);
      if( $val) $wpg3_options[$field] = $val;
   }
    return $wpg3_options;
  }		

/**
 *  Every Module can provide its own Options or return "false"
 *
**/
  private function get_module(){
     $main_mudule = array(
                // unique section ID
                'unique_name' =>'main_options', 
                // visible Section Title
                'title' => __('Basic WPG3 Options'), 
                // callback_fn displaying the Settings
                'function' => array( $this , 'admin_options_section_display' ), 
                // FIELDS
                'settings_fields' => array(
                                        array(
                                        // unique ID
                                        'field_id' => 'g3Url', 
                                        // field TITLE text
                                        'field_title' => __('Gallery3 Url'), 
                                        // function CALLBACK, to display the input box
                                        'field_display' => array( $this , 'admin_options_section_display_g3Url'), 
                                        // function CALLBACK validate field
                                        'field_validate' => array( $this , 'admin_options_section_validate_g3Url')
                                       )
                                     )
          );
    return $main_mudule;
  }
  
/**
 *  Section Header for Options Page
 *
**/
  public function admin_options_section_display()
  {?>
    <div style="width: 600px; margin-left: 10px;">
    
    <p>  
       You must enable REST-Module in Gallery3 in Order to use WPG3.
       <br /> For now it is also necessary to set <strong>allow_guest_access = 1</strong> in the 
       extended settings of your Gallery.
    </p>
    <p>       
       To check if you can use G3-Rest by placing e.g. <strong><pre>http://yourdomain.com/gallery3/index.php/rest/item/1?output=html</pre></strong>
       in you browser Adress Field. It should show you some json data.<br /> In this example your Gallery3 Url would be: <strong><pre>http://yourdomain.com/gallery3/index.php</pre></strong>.
    </p>
    </div>

<?php }
  
/**
 *  Options Page Output for "field1"
 *
**/
  public function admin_options_section_display_g3Url()
  { $field_id = 'g3Url';
    $options = $this->wpg3_options; // we should use data of $this !! ?>
    <p>The Url of you Gallery3 installation 
       e.g. <strong>http://wpg3.local/gallery3/index.php</strong>
    </p>
    <?php
    $val = isset($options[$field_id])?$options[$field_id]:get_bloginfo('url').'/gallery3/index.php';
    !$this->is_enabled ? $enabled = ' style="color: red;" ':'';
    echo '<input id="'.$field_id.'" name="wpg3_options['.$field_id.']" '.$enabled.'size="60" type="text" value="'.$val.'" />'."\n";  
  }

/**
 *  Options Page Validation for "field1"
 *
 *  @todo validate g3Url against REST
**/
  public function admin_options_section_validate_g3Url($field_val)
  {
    $return = false;
    // validate input
    if ( preg_match('#^http\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $field_val)){
        if( substr( $field_val, -1 ) === "/" ) $field_val = substr ( $field_val, 0 , -1 );
        $return = $field_val;
    }else{
      // create a nice Error including you field_id
      add_settings_error('g3Url', 
                         'settings_updated', 
                         __('A valid Gallery3 Url is required @ g3Url<br /> You entered: "'.$field_val.'"'));
    }
    return $return;
  }
	
	private function __autoload($class_name) {
    include 'wpg3_class_'.$class_name . '.php';    
  }
	public function get_options() {
    return $this->wpg3_options;
  }
  public function is_enabled($type){
    $return = false;
    if ($this->wpg3_options[$type] == "enabled") $return = true;
    return $return;
  }

}// END class Wpg3_Main

	add_action('init', array(&$wpg3, 'wpg3_init') );
	add_action('admin_init', array(&$wpg3, 'wpg3_admin_init') );
  add_action('admin_menu', array(&$wpg3, 'admin_add_page') );
  if($wpg3->is_enabled){
    add_filter('the_content', array(&$wpg3, 'wpg3_content_callback') ); 
 }
?>