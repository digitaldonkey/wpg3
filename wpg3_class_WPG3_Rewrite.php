<?php
 /**
   *   Main Gallery with Rewrite Support 
   *
   *
   *  
   *
   *  @package wpg3
   *  wpg3_class_WPG3_Rewrite
  **/
class WPG3_Rewrite
{ 
  private $wpg3_options;
  
 /**
  *   Loads class Data
  *
  */
  function __construct($wpg3_options) {
    if (! is_array($wpg3_options)){
      wp_die('wpg3_options missing@WPG3_Rewrite --> __construct');
    }
    $this->wpg3_options = $wpg3_options;
  }


/**
 *  Register Options and Admin Init filter
 *
**/
public function admin_init()
{
    /* Add gallery3 Tab to Media Library */
    //add_action('media_upload_tabs', array(&$this, 'imagechoser_tab') );
    
    /* Add gallery3 Media Library Form */
    //add_action   ('media_upload_wpg3',  array(&$this,'imagechoser_media_browser') );

   /* Options */
   $this_module = array(
              // unique section ID
              'unique_name' =>'rewrite_options', 
              // visible Section Title
              'title' => __('Gallery Page Options'), 
              // callback_fn displaying the Settings
              'function' => array( $this , 'admin_options_section_display' ), 
              // FIELDS
              'settings_fields' => array(
                                      array(
                                      // unique ID
                                      'field_id' => 'g3Page', 
                                      // field TITLE text
                                      'field_title' => __('Enable G3 Page'), 
                                      // function CALLBACK, to display the input box
                                      'field_display' => array( $this , 'admin_options_section_display_g3Page'), 
                                      // function CALLBACK validate field
                                      'field_validate' => array( $this , 'admin_options_section_validate_g3Page')
                                     ),
                                   array(
                                      // unique ID
                                      'field_id' => 'g3PageId', 
                                      // field TITLE text
                                      'field_title' => __('Set Gallery3 Page'), 
                                      // function CALLBACK, to display the input box
                                      'field_display' => array( $this , 'admin_options_section_display_g3PageId'), 
                                      // function CALLBACK validate field
                                      'field_validate' => array( $this , 'admin_options_section_validate_g3PageId')
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
  {
    echo '
    <div style="width: 600px; margin-left: 10px;">
      <p>
         Main Gallery with rewrite Support. After setting a g3Page you can use slugs provided by Gallery3.
      </p>
   </div>';
}
  
/**
 *  Options Page Output for "templateDirectory"
**/
  public function admin_options_section_display_g3Page()
  { $field_id = 'g3Page';
    $options = $this->wpg3_options; 
     
     // set default
     if (! isset ( $options[$field_id]) ) $options[$field_id] = "disabled";

     if ( $options[$field_id] == "enabled" ){
       $checked = ' checked="checked" ';
       $enabled = "enabled";
     }else{ 
      $checked = ''; 
      $enabled = "disabled";
     }
     // if the checkbox is not checked validation will not be triggered, so we make sure to always have a value
     echo '<input '.$checked.' id="'.$field_id.'_checkbox" name="wpg3_options['.$field_id.'_checkbox]" type="checkbox" /> Enable G3 Page';
     echo '<input id="'.$field_id.'" name="wpg3_options['.$field_id.']" type="hidden" value="'.$enabled.'" />';    
  }

  /**
 *  Options Page Validation for "templateDirectory"
**/
  public function admin_options_section_validate_g3Page($field_val)
  { 
   global $_POST;
     $return = "disabled";
     if (isset($this->wpg3_options['g3Page']) ){
       $return = $this->wpg3_options['g3Page'];
     }
     if ( isset($_POST['wpg3_options']['g3Page_checkbox']) ){
       $return = "enabled";
     }else{
       $return = "disabled";
     }
    return $return;
  }

/**
 *  Options Page Output for "templateDirectory"
**/
  public function admin_options_section_display_g3PageId()
  { $field_id = 'g3PageId';
    $options = $this->wpg3_options; 
    
    // default
    
    $selected = array('echo' => 0, 'name' => 'g3PageIdSelect'); // name = #id
    $slug = 'undefined';
    $disabled = 'disabled="disabled" ';
    $pageSelector = 'Select a page ';
    
    // default val for Select
    if (isset($options['g3PageId']) )  $selected['selected'] = $options['g3PageId'];
    
    // enabled
    if( $options['g3Page'] == "enabled" and isset($options['g3Page'] )){
            
      $pageSelector .= wp_dropdown_pages($selected);
      $disabled = '';

      // slug of the selected post
      if (isset($options['g3PageId']) ){
        $count = strlen (get_bloginfo('url').'/');
        $slug = substr ( get_permalink($options['g3PageId']), $count );
      }
    }else{
      // disable fields if not active     
      $count = count('<select');
      $pageSelector .= '<select '. $disabled . substr(wp_dropdown_pages($selected), $count);
    }
    
    echo $pageSelector.' or create a NEW PAGE by entering a unique page-slug ';  
    echo '<input id="g3PageSlug" '.$disabled.' name="g3PageSlug" size="10" type="text" value="'.$slug.'" />'."\n";  
    echo '<input id="'.$field_id.'" name="wpg3_options['.$field_id.']" type="hidden" value="true" />'."\n";  

   // toggle enabled/disabled by javascript
   echo  "<script type='text/javascript'>\n"
        ."jQuery(document).ready(function(){ 
            var toggle_it = jQuery('#g3Page_checkbox');
            toggle_it.click(function(){
                if (toggle_it.attr('checked')){
                  jQuery('#g3PageSlug, #g3PageIdSelect').removeAttr('disabled');
                  jQuery('#g3PageSlug').val('')
                }else{
                  jQuery('#g3PageSlug, #g3PageIdSelect').attr('disabled','disabled');
                  jQuery('#g3PageSlug').val('undefined')
                }
            });          
        });\n"
        ."</script>";
  }

/**
 *  Options Page Validation for "templateDirectory"
**/
  public function admin_options_section_validate_g3PageId($field_val)
  { 
    // $field_val will always return 'true'.
    global $_POST;
    
    $return = false;    
    
    if ( isset($_POST['g3PageIdSelect']) and intval($_POST['g3PageIdSelect']) > 0){
      $return = $_POST['g3PageIdSelect'];
    }
    
    /**
     *  check for new slug
     *
    **/
    if (isset($_POST['g3PageSlug'])){
      $mySlug = $_POST['g3PageSlug'];
      if (substr($mySlug, 0,1) != '/'){
        $mySlug = '/'.$mySlug;
      }
      
      /**
       *  @todo propper preg replace. Maybe we need to check for categories also?
      **/
      $x = wp_list_pages(array('echo'=>0, 'title_li'=>'') );      
      $y = preg_split('/.*page-item-().*?/i', $x);
      
      $page_id_array = array();
      
      foreach ( $y as $part){
       $myId = substr($part, 0, strpos($part, '"'));
       if (intval($myId)>0){
         $count = strlen (get_bloginfo('url'));
         $slug = substr ( get_permalink($myId) , $count );
         array_push( $page_id_array, array($myId, $slug ) );
       }
      }
      $exists = false;
      foreach ($page_id_array as $val){
        if ($val[1] == $mySlug){
          $exists = true;
        }
      }
      // so we have a non existing page-slug
      if (!$exists){
        // create new page
        $return = $this->create_page("Gallery", $mySlug );
      }
    }
    return $return;
  }
  
  /**
  * Creates a Wordpress Page
  *
  * @param array string $WPG2 Page Title, string $WPG2 Page Name
  * @return integer $createdpageid
  */
  private function create_page($wpg2_pagetitle='Gallery',$wpg2_pagename='wpg3') {
  
    global $user_ID;
  
    // Create New Gallery2 Output Page
    $wpg2_createdpageid = wp_insert_post(array(
          'post_author'		=> $user_ID,
          'post_title'		=> $wpg2_pagetitle,
          'post_name'		 	=> $wpg2_pagename,
          'post_status'		=> "publish",
          'post_type'         => "page",
          'comment_status'	=> "closed",
          'ping_status'		=> "closed",
          'post_content'      => "WPG3 Internal Page used for displaying your Gallery3 Content."
        ));
  
    // Add BTEV Event Message
    if (function_exists('btev_trigger_error')) {
      btev_trigger_error('WPG3 ADD TEMPLATE WORDPRESS PAGE ('.$wpg2_createdpageid.')', E_USER_NOTICE, __FILE__);
    }
  
    return $wpg2_createdpageid;
  
  }

}// End Class