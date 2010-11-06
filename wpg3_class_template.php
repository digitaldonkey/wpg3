<?php
/** 
 *  WPG3 Template Class
 *  
**/
class WPG3_Template{
  private $templates;
  
  function __construct() {
    $this->templates = $this->get_templates_array();
  }
  
 /**
  *   use_template
  *
  *   @param template id  (is: classname_methodname )
  *   @param data
  *         item-array on single like type: photo, movie ...
  *         item array including member item array's multiple (album)
  *   @return html of the desired item/template
  *         automatically sets styles and javascript
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
      wp_die("Couldn't load Template@ WPG3_Template->use_template<br />".
             "Template ID: $template_id<br />SEE: &lt;file&gt;_&lt;Method&gt;"); 
    }
    require_once($myTemplate['file']);
    if ( !$obj = eval("return new ".$myTemplate['class']."();") ){
      wp_die("Couldn't load Class @ WPG3_Template->use_template<br />".
             "Template ID: $template_id<br />SEE: &lt;file&gt;_&lt;Method&gt;"); 
    }
    $html .= $obj->{$myTemplate['method']}($data);

    if (empty($html)){
       $return = false;
    }
    return $html;
  }

  
 /**
  *   get_templates
  *
  *   @param type: e.g. 'album', 'photo'
  *   @return array Templates or false if there aren't any
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
    *   debug_templates
    *
    *   print out Template data stored in DB
   **/
  public function debug_templates(){
     $return =  "<pre>";
     $return .= print_r( get_option('wpg3_templates'), true );
     $return .= "</pre>";
    return $return;
  }

   /**
    *   delete_db_options
    *
    *   deletes this class options from WP Database
   **/
  public function delete_db_options(){
    return delete_option('wpg3_templates');
  }
  /**
   *  function get_templates_array
   *
   *  Reads all Template from databas or updates the files,
   *  if there no Templates storred in DB
   *  @return: array templates 
  **/
    private function get_templates_array(){  
      if ( ! get_option('wpg3_templates')) {
         $this->update_templates();
      }
      return get_option('wpg3_templates');
    }
  
  /**
   *  function update_templates
   *
   *  Reads all Template files and add template Objects to the databas
   *  @return: debug-output on Sucess (and an Error @todo)
  **/
    public function update_templates(){
      $wpg3_template_dir = ABSPATH.'wp-content/wpg3_templates/';  // can we put it into wp-content/templates ??
      $templates = array();
      $return = '';
      
      /* makes sure that there is a  wpg3_templates directory */
      if ( !is_dir( $wpg3_template_dir ) ){
        if ( !mkdir( $wpg3_template_dir ) ){
          wp_die("pleas create a Templet Direcoty:".
          "$wpg3_template_dir or chmod 777 you wp-content Folder.");
        }
      }
      /**
       *  get the Files if 
       *  IGNORE: subdirectorys and files starting with "."
       *  Filename must NOT contain spaces, dots etc. to be a valid php function name
      **/
      if ( $directory_handle = opendir( $wpg3_template_dir )){
        
        $template_files = array();
        while (false !== ($file = readdir($directory_handle))) {
          if (substr($file, 0,1) != ".") {
             array_push($template_files, $file);
          }
        }
        foreach ($template_files as $file){
          $file_templates['abspath'] = $wpg3_template_dir.$file;
          if ( is_file( $file_templates['abspath'] ) and is_readable( $file_templates['abspath'] ) ){
             $file_templates['class'] = substr($file, 0, -4);
            //echo  'ABSPATH: '.$file_templates['abspath']."<br />";
            //echo  'File: '.$file."<br />";
            require_once( $file_templates['abspath'] );
            /* init template class of each file found */
            if ($obj = eval("return new ".$file_templates['class']."();")){
              $template_array = $obj->get_templates();
              foreach($template_array as $key => $val){
                $val['file'] = $file_templates['abspath'];
                $val['class'] = $file_templates['class'];
                $val['id'] = $file_templates['class'].'_'.$val['method'];
                if( is_callable( array($obj, $val['method']) ) ){
                  array_push($templates, $val);
                  /* @todo: we should add some errors when we fail wit this */
                }
              }
            }
          }
        }
    
       /* safe all to the database */
        if ( get_option('wpg3_templates')  != $templates) {
          if ( update_option('wpg3_templates' , $templates)){
            $return .=  "Updated Template Options";
          }
        }
         $return .=  "<pre>";
         $return .= print_r( get_option('wpg3_templates'), true );
         $return .= "</pre>";
      }
      return  $return;
    }
  
  
}

?>