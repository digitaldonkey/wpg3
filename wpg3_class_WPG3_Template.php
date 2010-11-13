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
  *   @param string template id (is: classname_methodname of the template)
  *   @param array item-object e.g. Album, Photo, Movie. Including member items if there are
  *   @return string html-string of the desired item/template or FALSE on error
  *   @todo automatically sets styles and javascript
  *   
 **/
 public function use_template($template_id, $data ){
    $myTemplate = false;
    $html = '';
    foreach($this->templates as $key => $val){
      if($val['id'] === $template_id){
        $myTemplate = $val;
      }
    }
    /* found a template? is it includable? */
    if(!is_array($myTemplate) or !is_file( $myTemplate['file']) or !is_readable($myTemplate['file']) ){
      wp_die("Couldn't load Template@ WPG3_Template->use_template<br />Template ID: $template_id<br />SEE: &lt;file&gt;_&lt;Method&gt;"); 
    }
    require_once($myTemplate['file']);
    if ( !$obj = eval("return new ".$myTemplate['class']."();") ){
      wp_die("Couldn't load Class @ WPG3_Template->use_template<br />Template ID: $template_id<br />SEE: &lt;file&gt;_&lt;Method&gt;"); 
    }
    $html .= $obj->{$myTemplate['method']}($data);

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
  *   @package WPG3
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
  *   Debug Helper: ECHO Template Objects stored in DB to screen
  *   @package WPG3
 **/
  public function debug_templates(){
    return "<pre>".print_r( $this->wpg3_options->templateDirectory, true )."</pre>";
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
      if (substr($datei, 0,1) != ".") {
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
      //int timestamp
      $lastChange = $this->getHighestFileTimestamp($this->wpg3_options['templateDirectory']);
      $stored_lastChange = get_transient('templateChanged');
      
      if ( $stored_lastChange ){
        set_transient('templateChanged', $lastChange, $this->cacheTime);
      }
      if ($lastChange > $stored_lastChange){
      $return = $this->update_templates();
      }
      return $return;
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
    
    $template_files = $this->getAllFiles($this->wpg3_options['templateDirectory']);
    
    foreach ($template_files as $file){
      $file = pathinfo($file);
      $file['abspath'] = $file['dirname'] . '/' . $file['basename'];
      if ( is_file( $file['abspath'] ) and is_readable( $file['abspath'] ) ){
        //echo  'File: '.$file."<br />";
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
              /* @todo: we should add some errors when we fail with this */
            }
          }
        }
      }
    }
   /* safe all to the database */
    return  $templates;
  }
    
/**
 *  Register Options
 *
**/
  public function get_module(){
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
    $options = get_option('wpg3_options'); // we should use data of $this !!
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