<?php
/**
  *  The Template System of WPG3
  *
  *  Reading all files in ... and creating Template Objects of them.
  * 
  *   <b>TEMPLATE FILES</b>
  *
  *   By now we'll Ignore: subdirectorys and files starting with "."
  *
  *   A template filename must NOT contain spaces, dots etc. to be a valid php function name
  * 
  * 
  *   @link http://wpg3.digitaldonkey.de
  *   @author Thorsten Krug <driver@digitaldonkey.de>
  *   @filesource
  *   @package WPG3
 **/
 
 
/**
  *   Manages Template Files
  *   
  *   It will read all template Files in you Template Directory
  *   and create Template Objects and store them in DB.
  *
  *   get_templates() will offer you available Templates.
  *
  *   use_template(templ, items ) will return a HTML String of item(s) using the template
  *
  *   
  *   @package WPG3
  */
class WPG3_Template{
 /**
  *   Basic Information about Template files and classes will be stored in DB.
 **/
  private $templates;
  
 /**
  *   Keeping options per class gives a lot of flexibility.
  *   @internal
 **/
  private $wpg3_options;
 /**
  *   Loads class Data
  *   @package WPG3
  */
  function __construct($wpg3_options) {
    if (! is_array($wpg3_options)){
      wp_die('wpg3_options missing@Wpg3_Xhttp --> __construct');
    }
    $this->wpg3_options = $wpg3_options;
    $this->templates = $this->get_templates_array();    
  }
  
 /**
  *   Enables the use of a certain Template. 
  *   Depending on the Type (single item on collection) it 
  *   may fail if you have chosen the wrong Template.
  *   
  *   This function will wp_die id the template file or the class can't be loaded.
  *
  *   @param array $get_item 
  *   @param object item-object e.g. Album, Photo, Movie. Including member items if there are
  *   @return string html-string of the desired item/template or FALSE on error
  *   @todo automatically sets styles and javascript for THIS TEMPLATE (by now we have to set it on Init for ALL templates)
  *   
 **/
 public function use_template($get_item, $data ){
  
    if (!is_array($this->templates) ) wp_die('Templates undefined @use_template()');
    
    // find the right template id
    //  $get_item['template'] => valid Template ID or false
    $myTemplateId = false;
    $available_Templates = $this->get_templates( $data->entity->type );
    if ( ! $get_item['template'] ){
      foreach ( $available_Templates as $tpl ){
        if ( $tpl['id'] == $get_item['template'] ){
          $myTemplateId = $tpl['id'];
        }
      }
    }
    // Default Template
    if ( ! $myTemplateId ){
      $supported_item_types = array( 'photo', 'album' );
      if ( in_array($data->entity->type , $supported_item_types ) ){
        $myTemplateId = 'defaultTemplate_default_'.$data->entity->type;
      }else{
        wp_die("Unsuported Item type @ defaultTemplate<br /> Currently supported: <em>".implode($supported_item_types, ', ').'</em>' );
      }
    }
    // get the Template
    $myTemplate = false;
    foreach($available_Templates as $tpl){
        if ( $tpl['id'] == $myTemplateId ){
          $myTemplate = $tpl;
        }
    }
 
    $html = '<div class="gallery">';

    /* found a template? is it includable? */
    if(!is_array($myTemplate) or !is_file( $myTemplate['file']) or !is_readable($myTemplate['file']) ){
      wp_die("Couldn't load Template@ WPG3_Template->use_template<br />Template ID: ".$myTemplate['id']."<br />SEE: &lt;file&gt;_&lt;Method&gt;"); 
    }
    require_once($myTemplate['file']);
    if ( !$obj = eval("return new ".$myTemplate['class']."();") ){
      wp_die("Couldn't load Class @ WPG3_Template->use_template<br />Template ID: ".$myTemplate['id']."<br />SEE: &lt;file&gt;_&lt;Method&gt;"); 
    }
    // add Styles
    // we have to add the Scripts and CSS more early --> register_script_and_css ... that's bad :(
    isset($get_item['int_width']) ? $width = $get_item['int_width'] : $width = false;
    $html .= $obj->{$myTemplate['method']}($data, $width );
    
    $html .= '</div>';

    if (empty($html)){
       $return = false;
    }
    return $html;
  }

  
 /**
  *   Give you all available Templates of a type
  *
  *   @param string type: e.g. 'album', 'photo' or whatever you may be defined in the Template Files
  *   @return array Array containing template data
  *                 or false if there no templates available for the chosen type.
 **/
 public function get_templates($type){
    $templates = $this->templates;
    $return = array();
    foreach($templates as $key => $val){
      if($val['type'] === $type){
        array_push($return, $val );
      }
    }
    if (empty($return)){
       $return = false;
    }
    return $return;
  }

 /**
  *   Give you all valid Template id's
  *
  *   @return array Array Template ID's
  *                 or false if there no templates available.
 **/
 public function get_template_ids(){
    $templates = $this->templates;
    $return = array();
    foreach($templates as $key => $val){
        array_push($return, $val['id'] );
    }
    if (empty($return)){
       $return = false;
    }
    return $return;
  }

 /**
  *   Debug Helper: ECHO Template Objects stored in DB to screen
  *   @package WPG3
 **/
  public function debug_templates(){
    $return  = '<div style="border: 1px dotted red;"><h3>Template Information</h3><p>Template Directory: <strong>'.print_r( $this->wpg3_options['templateDirectory'], true )."</strong></p>";
    $return .=  "<pre>".print_r( $this->templates, true )."</pre></div>";
    return $return;
  }

 /**
  *   Deletes this class options from WP Database
  *   @package WPG3
 **/
  public function delete_db_options(){
    return delete_option('wpg3_templates');
  }

/**
 *    Read all files in directory recursive
**/
private function getAllFiles($directory, $recursive = true) {
     $result = array();
     $handle =  opendir($directory);
     while ($datei = readdir($handle)) {
      if (substr($datei, 0,1) != "." and substr($datei, -3) == "php") {
        $file = $directory.$datei;
          if (is_dir($file)) {
            if ($recursive) { 
              $result = array_merge($result,  $this->getAllFiles($file.'/')); 
            }
          }else{
            $result[] = $file;
          } 
      }
     }
     closedir($handle);
     return $result;
}
/**
 *  get latest change in directory
**/
  private function getHighestFileTimestamp($directory, $recursive = true) {
    $allFiles = $directory;
    if( is_string($directory) ){
      $allFiles = $this->getAllFiles($directory, $recursive);
    }
     $highestKnown = 0;
     foreach ($allFiles as $val) {
          $currentValue = filemtime($val);
          if ($currentValue > $highestKnown) $highestKnown = $currentValue;
     }
     return $highestKnown;
}


/**
 *    Reads all Template Objects from database or updates the files.
 *    Calls update_templates() if the contents of Template directory changed
 *    @return: array Template Objects 
**/
    private function get_templates_array(){ 
      $return = false;
      $update = false;
      // check for updates in tpl dir
      if (isset( $this->wpg3_options['templateDirectory'] ) and trim ($this->wpg3_options['templateDirectory']) ){
        // not yet set up?
        if ( ! isset( $tpl_stored_lastchange )) $this->wpg3_options['template_change'] = 0;
        $tpl_stored_lastchange = $this->wpg3_options['template_change'];
        $tpl_latest_change = $this->getHighestFileTimestamp($this->wpg3_options['templateDirectory']);
        if ( $tpl_stored_lastchange < $tpl_latest_change ){
           $update = true;
           $this->wpg3_options['template_change'] = $tpl_latest_change;
        }
      }else{
        // let's update to get the default Template
        $update = true;
      }
      if ($update){
      $return = $this->update_templates();  
      }
      return $return;
    }

/**
 *  Enque Scripts and CSS via wp_enqueue_script for later use of wp_register_script
 * 
 *  @global $wp_styles $wp_scripts
**/
  public function register_script_and_css(){
    global $wp_styles, $wp_scripts;
    wp_enqueue_script('jquery');

    foreach ($this->templates as $template){
      // enque scripts
      if (isset($template['script']) ){
        $script = array();
        array_push( $script,  array(substr (substr(strrchr($template['script'], '/'),  1) , 0,  -3).'_js', $template['script']) );
        $script = array_unique($script);
        foreach ($script as $script_data){
          wp_register_script($script_data[0], $script_data[1]);
          wp_enqueue_script( $script_data[0] );      
        }        
      }
      // enque css
      if (isset($template['css']) ){
        $css = array();
        array_push( $css,  array(substr (substr(strrchr($template['css'], '/'),  1) , 0,  -4).'_css', $template['css']) );
        $css = array_unique($css); 
        foreach ($css as $css_data){
          wp_register_style($css_data[0], $css_data[1]);
          wp_enqueue_style( $css_data[0] );
        }
      }
    }
  }

/**
 *  Reads all Template files and add template Objects to the database
 *  @todo changeable Template Directory
 *  @todo: we should add some errors when we fail reading the file or classes
 *  @todo return error 
 *  @return: debug-output on Sucess (and an Error @todo)
**/
  public function update_templates()
  {
    $templates = array();
          
    /* makes sure that there is a  wpg3_templates directory */
    if ( isset( $this->wpg3_options['templateDirectory'] ) and !is_dir( $this->wpg3_options['templateDirectory'] ) ){
      if ( !mkdir( $this->wpg3_options['templateDirectory'] ) ){
       wp_die("Please create a Templet Direcoty: $this->wpg3_options['templateDirectory']<br />or chmod 777 the parent folder.");
      }
    }
    $template_files  = $this->getAllFiles( plugin_dir_path(__FILE__).'default_template/');
    if ( isset($this->wpg3_options['templateDirectory']) and trim ( $this->wpg3_options['templateDirectory'] )  ){
      foreach ($this->getAllFiles($this->wpg3_options['templateDirectory']) as $template){
              array_push($template_files, $template );
      }
    }
    
    foreach ($template_files as $file){
      $file = pathinfo($file);
      $file['abspath'] = $file['dirname'] . '/' . $file['basename'];
      if ( is_file( $file['abspath'] ) and is_readable( $file['abspath'] ) ){
        require_once( $file['abspath'] );
        /* init template class of each file found */
        if ($obj = eval("return new ".$file['filename']."();")){
          $template_array = $obj->get_templates();
          foreach($template_array as $key => $val){
            $val['file'] = $file['abspath'];
            $val['class'] = $file['filename'];
            $val['id'] = $file['filename'].'_'.$val['method'];            
            if( is_callable( array($obj, $val['method']) ) ){
              array_push($templates, $val);
            }else{
              wp_die("Can't call Method: <strong>".$val['method']."</strong><br /> at file: <strong>".$val['file']."</strong><br /> @update_templates");
            }
          }
        }
      }
    }
    return  $templates;
  }
    
/**
 *  Register Options
 *
**/
  public function admin_init(){
     $this_module = array(
                // unique section ID
                'unique_name' =>'template_options', 
                // visible Section Title
                'title' => __('Template Options'), 
                // callback_fn displaying the Settings
                'function' => array( $this , 'admin_options_section_display' ), 
                // FIELDS
                'settings_fields' => array(
                                        array(
                                        // unique ID
                                        'field_id' => 'templateDirectory', 
                                        // field TITLE text
                                        'field_title' => __('Template Directory'), 
                                        // function CALLBACK, to display the input box
                                        'field_display' => array( $this , 'admin_options_section_display_templateDirectory'), 
                                        // function CALLBACK validate field
                                        'field_validate' => array( $this , 'admin_options_section_validate_templateDirectory')
                                       ),
                                    )
          );
    return $this_module;
  }
  
  /**
   *  Section Header for Options Page
   *
  **/
  public function admin_options_section_display()
  {?>
    <div style="width: 600px; margin-left: 10px;">
      <p>
         WPG3 has a Template system integrated. It enables
         the Blogger to chose a different Template for
         every different Item inserted! There are different Item-types
         available. By now there is a template for "album" and"photo" 
         available.
      </p>
      <p>
         The default Templates reside in <em>wp-content/plugins/wpg3</em>.<br/>
         Chose an <strong>additional directory</strong> here for your Templates.
     </p>
      <p>
         If you copy the default Files into your directory you <strong>have to rename
         them</strong> or use different class Names. Please consider to contribute your
         templates to the Community at the <a href="http://wpg3.digitaldonkey.de/wpg3Templates">WPG3 Template page</a>.
      </p>
   </div>

<?php }
  
/**
 *  Options Page Output for "templateDirectory"
**/
  public function admin_options_section_display_templateDirectory()
  { $field_id = 'templateDirectory';
    $options = $this->wpg3_options; 
    $val = isset($options[$field_id])?$options[$field_id]: '';
    echo '<p>Absolute path to template directory. Leave empty to disable.<br />e.g. <strong>'.ABSPATH.'wp-content/wpg3-templates/</strong></p>';
    echo '<input id="'.$field_id.'" name="wpg3_options['.$field_id.']" size="60" type="text" value="'.$val.'" />'."\n";  
  }

/**
 *  Options Page Validation for "templateDirectory"
**/
  public function admin_options_section_validate_templateDirectory($field_val)
  { 
    $return = false;
    $field_val = trim($field_val);
    // validate input
    if ( $field_val == '' ){
     $return = ' '; //blank, not empty or you'll get trouble emptying the value!
    }else{
      if( substr( $field_val, -1 ) != "/" ) $field_val = $field_val."/";
      if ( is_dir( $field_val ) and is_readable( $field_val ) ){
          $return = $field_val;
      }else{
        // create a nice Error including you field_id
        add_settings_error(
            'templateDirectory', 
            'settings_updated', 
            'Enter a existing and readable Template Directory Path
             @ templateDirectory<br />Path must be absolute and readable.
             <br /> You entered: "'.$field_val.'"'
        );
      }
    }
    return $return;
  }
}// END CLASS WPG3_Template
?>