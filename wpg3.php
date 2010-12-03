<?php
/*
  Plugin Name: WPG3
  Plugin URI: http://wpg3.digitaldonkey.de
  Description: Sucessor of the WPG2 Plugin Compatible to Gallery3 and WP3+ @ ALPHA-DEV
  Author URI: http://donkeymedia.eu
  Version: 0.85
*/
/**
  *   WPG3 Main
  *
  *   @link http://wpg3.digitaldonkey.de
  *   @author Thorsten Krug <driver@digitaldonkey.de>
  *   @version 0.82
  *   @filesource
  *   @package WPG3
 **/


//error_reporting (1);  

  global $wpg3;
  $wpg3 = new WPG3_Main();

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
  **/
class WPG3_Main
{ 
 /**
  *
  * @var bool true/false
 **/
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
 *  @param [array] Optional array Item 
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
 **/
 // that's what we'll pass to get_item()
  $get_item = array('id' => false,
                    'rest_uri' => $this->wpg3_options['g3Url'].$this->wpg3_options['g3Home'],
                    'width' => false,
                    'template' => false );
  
  // There was a <WPGX>-Tag
  if ( is_array($g3_tag) ){
    $get_item = $this->check_wpg3_tag($g3_tag);
  }  
  
  // $_GET Requests
  if ( isset($_GET['itemid']) and intval($_GET['itemid']) > 0 ){
    $get_item['rest_uri'] = $this->wpg3_options['g3Url'].'/rest/item/'. $_GET['itemid'];
  }
  
  // Debug REST $url
  if ($this->debug) echo '<pre style="padding: 5px;border: 1px dotted red;"><strong>REST request array:</strong><br />'.print_r( $get_item ,true).'</pre>';
	
	// Getting Items
	$xhttp = $this->get_module_instance('WPG3_Xhttp');
  //$xhttp->clear_cache();

	$items = $xhttp->get_item( $get_item );

  $templates = $this->get_module_instance('WPG3_Template');
  //echo $templates->debug_templates();

  echo $templates->use_template( $get_item, $items );
	
  if($this->debug) echo '<div style="border: 1px dotted red;">WPG3 main script time: '.round( (microtime(true) - $start) , 4)." sec.</div>";
}

  /**
   * <WPG3>-Tag Tester
   *
  **/  
  private function testTags()
  { if (!$this->debug) return;
    
    global $_POST, $_SERVER;
    
    $testtags = array(
      // [id|rel.path|REST.path] 
      '<wpg3>13</wpg3>',
      '<wpg3>item/1</wpg3>',
      '<wpg3>item/10</wpg3>',
      '<wpg3>http://wpg3.local/gallery3/index.php/rest/item/63</wpg3>',
      
      // [id|rel.path|REST.path] [ | [int.width|str.[] ] ]
      '<wpg3>13|200</wpg3>',
      '<wpg3>item/1|300</wpg3>',
      '<wpg3>item/10|400</wpg3>',
      '<wpg3>http://wpg3.local/gallery3/index.php/rest/item/6|600</wpg3>',
      '<wpg3>item/1|thumb</wpg3>',
      '<wpg3>item/1|thumbnail</wpg3>',
      '<wpg3>item/10|resize</wpg3>',
      '<wpg3>http://wpg3.local/gallery3/index.php/rest/item/63|medium</wpg3>',
      '<wpg3>item/1|full</wpg3>',
      '<wpg3>item/10|large</wpg3>',
      
      // [id|rel.path|REST.path]  [ | [int.width|str.[] ] [ | [int.width|str.[] ] ]]
      '<wpg3>13|200|defaultTemplate_default_photo</wpg3>',
      '<wpg3>item/1|300|defaultTemplate_default_album</wpg3>',
      '<wpg3>item/1|300|defaultTemplate_default_photo</wpg3>'
    );

    echo '<form action="'.$_SERVER['PHP_SELF'].'?tagtester" method="post" onchange="this.submit()">'."\n";    
    echo '<select name="tags">'."\n";
    foreach ($testtags as $tag)
      { 
        $sel ="";
        if ($_POST['tags'] == $tag) $sel = ' selected="selected" ';
        echo '<option '.$sel.' value="'.$tag.'">'.htmlentities($tag)."</option>\n";
      }
    echo '</select>'."\n";
    echo '</form>'."\n";
  
  }
  
 /**
  *   Validate the <WPGX> Tag
  *
  *   @param array $g3_tag preg_replace
  *   @return array $get_item the santized request
 **/  
  private function check_wpg3_tag($g3_tag)
  { 
    $get_item = array('id' => false,
                    'rest_uri' => $this->wpg3_options['g3Url'].$this->wpg3_options['g3Home'],
                    'width' => false,
                    'template' => false );
   /**
     *  If there was a <WPGX>-Tag
     *  
     *  @internal
     *  We might have here : 
     *  $g3_tag[0] => all of the following merged
     *  $g3_tag[1] => int.id|str.rel.path|str.REST.path
     *  $g3_tag[2] => int.width|str.width
     *  $g3_tag[3] => str.template.id
    **/

  // Debug REST $url
    // DEBUG
    if ($this->debug){
      echo '<div style="padding: 5px;border: 1px dotted red;"><strong>&lt;WPGX&gt;'.$g3_tag[0].'&lt;/WPGX&gt;</strong>';
      echo '<pre style="font-size: 12px;">'."\n";
      echo '<wpg3> [int.id|str.rel.path|str.REST.path] [ | [int.width|str.width] ] [ | [str.template]</wpg3>';
      echo "</pre>";
      echo "</div>";
    }
    
    // check for int.id|str.rel.path|str.REST.path
    if (isset($g3_tag[1])){
    
      // check for int ID
      if (intval($g3_tag[1])>0){
        $get_item['rest_uri'] = $this->wpg3_options['g3Url'].'/rest/item/'.$g3_tag[1];
      }
      // str.rel.path
      $count = strlen('item/');
      if ( intval (substr($g3_tag[1], $count) ) > 0 ){
        $get_item['rest_uri'] = $this->wpg3_options['g3Url'].'/rest/item/'.substr($g3_tag[1], $count);
      }    
      // str.REST.path
      $count = strlen($this->wpg3_options['g3Url'].'/rest/item/');
      if ( substr($g3_tag[1], 0, $count) === $this->wpg3_options['g3Url'].'/rest/item/' ){
        $get_item['rest_uri'] = $g3_tag[1];
      }
          
    }
      
    // check for int.width|str.width
    if (isset($g3_tag[2])){
      // for int.width
      if(intval ($g3_tag[2]) > 0){
        if ($g3_tag[2] <= $this->wpg3_options['g3Resize']['max_thumb'] ){
          $get_item ['width'] = 'thumb';
        }
        if ($g3_tag[2] >=  $this->wpg3_options['g3Resize']['max_thumb'] and $g3_tag[2] < $this->wpg3_options['g3Resize']['max_resize'] ){
          $get_item ['width'] = 'resize';
        }
        if ($g3_tag[2] >= $this->wpg3_options['g3Resize']['max_resize'] ){
          $get_item ['width'] = 'full';
        }
        $get_item ['int_width'] = $g3_tag[2];
      }
      // str.width
      $valid_sizes = array('thumb', 'thumbnail', 'resize', 'medium', 'custom', 'full', 'large');
      if (in_array ( $g3_tag[2], $valid_sizes, true) ){
        if($g3_tag[2] == 'thumb' or $g3_tag[2] == 'thumbnail'){
          $get_item ['width'] = 'thumb';
        }
        if($g3_tag[2] == 'resize' or $g3_tag[2] == 'medium' or $g3_tag[2] == 'custom'){
          $get_item ['width'] = 'resize';
        }
        if($g3_tag[2] == 'full' or $g3_tag[2] == 'large' or $g3_tag[2] == 'large'){
          $get_item ['width'] = 'full';
        }
      }
    }
    
    // check for  str.template.id
    if ( isset($g3_tag[3]) and is_string($g3_tag[3]) ){
      $tpl = $this->get_module_instance('WPG3_Template');
      if (in_array ( $g3_tag[3] , $tpl->get_template_ids() , true ) ){
        $get_item['template'] = $g3_tag[3];
      }
    }
    return $get_item;
  }

/**
 *   WPG3 Content Callback
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
      
    /* Tag Tester */
    if ( isset($_GET['tagtester']) ){
      $this->testTags();
      if ( isset($_POST['tags']) ){
        $content =  '<p style="color: red;">Used <WPGX>-Tag: '.htmlentities($_POST['tags']).'</p>';
        $content .=  $_POST['tags'] ;
        
      }
    }

    if(false === stripos($content, '<wpg') and !$templateTag) {
      $return = $content;
    }else{
      $return = preg_replace_callback('/<wpg[23]>([^\|]*)[\|]?([^\|]*)[\|]?(.*)<\/wpg[23]>/i', array( $this, 'wpg3_content' ), $content );
   }   
    return $return;
  }

  /**
   *  Admin Error Message: Missing Settings
   *  
  **/
  public function no_settings_yet(){
     echo '<div class="error"><p>Please check WPG3 Options in in order to enable the Plugin.</p></div>';
  }

  /**
   *   Redirection init Scripts
   *
   *  {@source}
  **/
  public function wpg3_init(){
    if ($this->is_enabled){
      $template = $this->get_module_instance('WPG3_Template');
      $template->register_script_and_css();

      if ( isset( $this->wpg3_options['g3PageId'] ) and intval ($this->wpg3_options['g3PageId']) > 0){
        $gallerypage = $this->get_module_instance('WPG3_Rewrite');
        $gallerypage->main_init();
      }
    }

  }
  /**
   *   Options Page and Input Validation for WPG3-Modules 
   *
   *   Depends on WP-Settings Api
   *
   *    {@source}
   *
  **/
  public function wpg3_admin_init()
  { 
    global $wp_rewrite;
    
    // MODULES register their options here
    $modules = array($this->get_module() );
    if($this->is_enabled){

      $xhttp = $this->get_module_instance('WPG3_Xhttp');
      array_push($modules, $xhttp->admin_init() );

      $template = $this->get_module_instance('WPG3_Template');
      array_push($modules, $template->admin_init() );
      /*
      $imagechoser = $this->get_module_instance('WPG3_Imagechoser');
      array_push($modules, $imagechoser->admin_init() );
      */
      if ( $wp_rewrite->using_permalinks() ){
        $gallerypage = $this->get_module_instance('WPG3_Rewrite');
        array_push($modules, $gallerypage->admin_init() );
      }
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
    /* stores validation functions */
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
  *
  *   {@source}
  *
  *   @param string Classname
  *   @return object instance
 **/ 
  public function get_module_instance($module, $override_options = false){
    $return = false;
    if (isset($this->modules[$module]) and !$override_options){
      $return = $this->modules[$module];
    }else{
      if (! isset($this->modules[$module])) $this->__autoload( $module );
      if ( $override_options){
        $this->wpg3_options = $override_options;
      }
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
                                       ),
                                        array(
                                        // unique ID
                                        'field_id' => 'g3Resize', 
                                        // field TITLE text
                                        'field_title' => __('Resize Options'), 
                                        // function CALLBACK, to display the input box
                                        'field_display' => array( $this , 'admin_options_section_display_g3Resize'), 
                                        // function CALLBACK validate field
                                        'field_validate' => array( $this , 'admin_options_section_validate_g3Resize')
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
    </p>
    </div>

<?php }



/**
 *  Options Page Output for "g3Resize"
 *
 *  @todo Pass the Width-param from WPG3-Tag to Template
**/
  public function admin_options_section_display_g3Resize()
  { 
    $options = $this->wpg3_options; 
    
    $field_id = 'g3Resize';
    $val = isset($options[$field_id])? $options[$field_id] : array('max_thumb' => 100, 'max_resize' => 300) ;
    ?>
    <p style = "width:600px;">Gallery 2 <strong>offered custom Resizes</strong>. Gallery3 not yet.<br />
       You can chose which reize to use in WPG3 for the width parameter in WPGX-Tags<br />
       <strong>Note:</strong><em> The Numbered width paramater is available in the Template to adjust width by CSS.</em>
    </p>
    <table >
      <tr>
        <td style = "text-align: right;">Max pixel width<br />to use Thumb</td>
      <?php
        echo '<td><input id="'.$field_id.'[max_thumb]" name="wpg3_options['.$field_id.'][max_thumb]" size="10" type="text" value="'.$val['max_thumb'].'" /> px</td>'."\n"; 
        echo '<td style = "text-align: center;">Max pixel width<br />to use Medium</td>';
        echo '<td><input id="'.$field_id.'[max_resize]" name="wpg3_options['.$field_id.'][max_resize]" size="10" type="text" value="'.$val['max_resize'].'" /> px</td>'."\n"; 
      ?>
      <td>For higher width values<br />we use the Fullsize Image</td>
    </tr>
   </table>
   <p>According to this settings WPG3 will chose the
   'thumb', 'resize' or 'full' G3-Image to be inserted for a width parameter</p>
<?php
  }
  

/**
 *  Validation Page Validation for "g3Resize"
 *
**/
  public function admin_options_section_validate_g3Resize($field_val)
  { 
   $return = isset($this->wpg3_options['g3Resize']) ? $this->wpg3_options['g3Resize'] : array('max_thumb' => 60, 'max_resize' => 500 ); 
    if ( is_array($field_val)){
      
      if ( intval( $field_val['max_thumb']) > 0  and intval( $field_val['max_resize'] ) > $field_val['max_thumb']){
        $return['max_thumb'] = $field_val['max_thumb'];        
      }else{
      add_settings_error('g3Resize', 
                         'settings_updated', 
                         __('The Thumb width must be lower than the Medium width @ g3Resize<br /> You entered:<br /> max_thumb = '.$field_val['max_thumb'].'px<br />max_resize = '.$field_val['max_resize'].'px'));
      }
      if ( intval( $field_val['max_resize']) > 0 and $field_val['max_resize'] > $return['max_thumb'] ){
       $return['max_resize'] = $field_val['max_resize'];
      }
    }
    return $return;
  }
  
/**
 *  Options Page Output for "g3Url"
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
    !$this->is_enabled ? $enabled = ' style="color: red;" ': $enabled ='';
    echo '<input id="'.$field_id.'" name="wpg3_options['.$field_id.']" '.$enabled.'size="60" type="text" value="'.$val.'" />'."\n";  
  }

/**
 *  Validate field "restReqestKey"
 *
 *  @todo validate g3Url against REST
**/
  public function admin_options_section_validate_g3Url($field_val)
  {
    $return = false;
    // validate input
    if ( preg_match('#^http\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $field_val)){
        if( substr( $field_val, -1 ) === "/" ) $field_val = substr ( $field_val, 0 , -1 );
        // unset REST API Key
        unset($this->wpg3_options['restReqestKey']);
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
/**
 *  Get WPG3 Options
 *  @return array wpg3_options
**/
	public function get_options() {
    return $this->wpg3_options;
  }
/**
 *  WPG3 enabled?
 *  @return bool true/false
**/
  public function is_enabled($type){
    $return = false;
    if (isset($this->wpg3_options[$type]) and $this->wpg3_options[$type] == "enabled") $return = true;
    return $return;
  }

}// END class WPG3_Main

	add_action('init', array(&$wpg3, 'wpg3_init') );
	add_action('admin_init', array(&$wpg3, 'wpg3_admin_init') );
   /* Add options Page */
    add_action('admin_menu', array(&$wpg3, 'admin_add_page') );
  if($wpg3->is_enabled){
    add_filter('the_content', array(&$wpg3, 'wpg3_content_callback') ); 
 }
?>