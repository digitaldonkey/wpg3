<?php
 /**
   *   Main Gallery with Rewrite Support 
   *
   *  You can chose a page in options to display
   *  a full gallery with Url rewrite enabled. 
   *  This class handles rewriting and displaying
   *  the Gallery Items using the Template Class.
   *
   *  @package WPG3
   *
   *  @global class $wpg3
   *  @global class $xhttp
   *  @filesource
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
 *  Add Rewrite and handling of it
 *
**/
public function main_init()
{
  if ( isset($this->wpg3_options['g3Page']) and $this->wpg3_options['g3Page'] == "enabled"){    
    add_filter('query_vars', array(&$this, 'page_querry_vars'));
    add_filter('rewrite_rules_array', array(&$this, 'page_add_rewrites'));
    add_filter( "the_content", array(&$this, 'page_content_filter') );
  }
}


/**
 *  create rewrites 
 *
 *  This very Expensive function should ONLY be calles at backend.
 *  It loads ALL child Elements to generate Rewrites 
 *  
 *  @global class WPG3_Xhttp
 *  @global object wpg3;
 *  @param string root element for Rewrites
 *
**/
  public function update_rewrites( $item ){
    global $wpg3;
    
    $xhttp = $wpg3->get_module_instance('WPG3_Xhttp');
    $rewrite_array = $xhttp->get_slugs( $item );
    
    if ( isset($this->wpg3_options['g3PageId']) ){
      $rewrite_array['slug'] = $this->wpg3_options['g3PageId']['post_name'];    
    }
    $this->wpg3_options['g3RewriteArray'] = $rewrite_array;
    $new_options = get_option('wpg3_options');
    $new_options['g3RewriteArray'] = $rewrite_array;
    update_option('wpg3_options', $new_options);
  }

  
/**
 *  Register Options and Admin Init filter
 *
**/
public function admin_init()
{ 
  global $wpg3, $_POST;  
  
/**
 *  make sure we don't update if g3Url or g3Home
 *  are not validated yet. 
**/
  if (  $wpg3->is_enabled 
        and !isset($_POST['g3Url'])
        and  !isset($_POST['g3Home'])
        and  isset($this->wpg3_options['g3Page'])
        and $this->wpg3_options['g3Page'] == "enabled" )
   {    
        $this->update_rewrites( $this->wpg3_options['g3Url'].$this->wpg3_options['g3Home'] );
   }
   if ( $wpg3->is_enabled ){
      flush_rewrite_rules();
   }
   
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
 *  create a querry Var
 *  for the g3-Page if there is one.
 *  @param array WP querry_vars
 *  @return array WP querry_vars
**/
  public function page_querry_vars($querry_vars)
  { 
    if (isset($this->wpg3_options['g3PageId']) ){
      $querry_vars[] = $this->wpg3_options['g3PageId']['post_name']; 
    }
    return $querry_vars;
  }


/**
 *  Output G3 Page content
 *
 *  @global object wpg3
 *  @global object wp_query
 *  @global object WPG3_Template
 *  @param string Page content
 *  @return string WPG3-Page Content
**/
  public function page_content_filter($content)
  { 
    // only handle output on g3 Page
    if (get_the_ID() != $this->wpg3_options['g3PageId']['ID'])
    return $content;
    global $wpg3, $wp_query;

    $pagename = $this->wpg3_options['g3PageId']['post_name'];
    if(isset($wp_query->query_vars[$pagename])) {
        $gallery = urldecode($wp_query->query_vars[$pagename]);
    }
    
    // generate querry vars array and validate??
    // @todo : we should validate against the g3_rewrites!!!
    $myQuerryVars[0] = $pagename;
    if (isset ($wp_query->query_vars[$pagename]) ){
      $slugs =  explode ( '/', $wp_query->query_vars[$pagename],  100 );
      foreach ($slugs as $slug){
        if (! eregi("^[a-zA-Z0-9_\.-]*$", $slug ) ) return "Invalid Url @ ".__CLASS__.'::'. __FUNCTION__ .'()';
        array_push( $myQuerryVars , $slug );
      }
    } 
        
    $itemId = false;
    $parent_urls = array();
    $rewrites[0] = $this->wpg3_options['g3RewriteArray'];    
    
    while ( $slug = array_shift ( &$myQuerryVars ) ){
      if (!is_array($rewrites)){
        // we should change back to the parent url because THIS sub-slug is wrong!
        break;
      }
      foreach ($rewrites as $the_rewrite_obj){
        if ($slug == $the_rewrite_obj['slug']){
          $itemId = $the_rewrite_obj['id'];
          $rewrites = $the_rewrite_obj['members'];
          array_push ( $parent_urls , array('/'.$the_rewrite_obj['slug'] , $the_rewrite_obj['slug'] ));
        }
      } 
      if ( $rewrites[0]['slug'] == $slug ){
        $itemId = $rewrites[0]['id'];
        $rewrites = $rewrites[0]['members'];
      }
        
    }
    
  	// Getting Items
  	if ($itemId){
  	  
  	  // url with path
  	  $script_url =  get_bloginfo('wpurl');
  	  foreach ( $parent_urls as &$url ){
  	      $url[0] = $script_url.$url[0];
  	      $script_url = $url[0];
  	  }
	    $xhttp = $wpg3->get_module_instance('WPG3_Xhttp');
	    $get_item = array('id' => $itemId , 'template' => false, 'parents' => $parent_urls, 'g3Page'=> true);
	    $items = $xhttp->get_item( $get_item );
    }
    // get template    
    
    $templates = $wpg3->get_module_instance('WPG3_Template');
    return $templates->use_template( $get_item, $items );
  }
  
  
/**
 *  Add rewrites
 *
 *  @param array WP_rewrites
 *  @return array WP_rewrites
**/
public function page_add_rewrites($rules)
{   
    if ( isset($this->wpg3_options['g3PageId'] ) ){
      $pagename = $this->wpg3_options['g3PageId']['post_name'];
      $newrules = array($pagename.'/(.*)?$' => 'index.php?pagename='.$pagename.'&'.$pagename.'=$matches[1]');
      $rules = $newrules + $rules;
    }
    return $rules;
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
         Permalinks need to be enabled!
      </p>
   </div>';
}
  
/**
 *  Options Page Output for "g3Page"
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
 *  Options Page Validation for "g3Page"
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
 *  Options Page Output for "g3PageId"
**/
  public function admin_options_section_display_g3PageId()
  { $field_id = 'g3PageId';
    $options = $this->wpg3_options; 
    
    // default
    $selected = array('echo' => 0, 'name' => 'g3PageIdSelect'); // name = #id
    $slug = 'undefined';
    $disabled = 'disabled="disabled" ';
    $pageSelector = 'Select a page ';
    
    // default values if there are saved ones
    if (isset($options['g3PageId']) ){      
      $selected['selected'] = $options['g3PageId']['ID'];
      $slug = $options['g3PageId']['post_name'];
    }
    
    // enabled
    if( isset($options['g3Page'] ) and $options['g3Page'] == "enabled"){
      $pageSelector .= wp_dropdown_pages($selected);
      $disabled = '';

    }else{
      // disable fields if not active     
      $count = count('<select');
      $pageSelector .= '<select '. $disabled . substr(wp_dropdown_pages($selected), $count);
    }
    
    echo $pageSelector.' or create a NEW PAGE by entering a unique page-slug ';  
    echo '<input id="g3PageSlug" '.$disabled.' name="g3PageSlug" size="10" type="text" value="'.$slug.'" />'."\n";  
    echo '<input id="'.$field_id.'" name="wpg3_options['.$field_id.']" type="hidden" value="true" />'."\n";  
    echo '<p>Saving the settings will update your permalinks!</p>'."\n";  

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
            jQuery('#g3PageIdSelect').change(function(){
                jQuery('#g3PageSlug').val('');
            });
        });\n"
        ."</script>";
  }

/**
 *  Options Page Validation for "g3PageId"
**/
  public function admin_options_section_validate_g3PageId($field_val)
  { 
    // $field_val will always return 'true'.
    global $_POST;
    
    $return = false;    
    
    if ( isset($_POST['g3PageIdSelect']) and intval($_POST['g3PageIdSelect']) > 0 ){
      $return = $this->get_page_array($_POST['g3PageIdSelect']);
    }    

    // check the slug
    if( isset($_POST['g3PageSlug']) and !empty($_POST['g3PageSlug']) and $_POST['g3PageSlug'] != 'undefined' ){
      $mySlug = $_POST['g3PageSlug'];
      if (substr($mySlug, 0,1) == '/'){
        $mySlug = substr($mySlug, 1);
      }
      if (substr($mySlug, -1 ) == '/'){
        $mySlug = substr($mySlug, 0, -1);
      }      
      if ($mySlug != $return['post_name'] and $mySlug != 'undefined'){
                        
            $return = $this->get_page_array($mySlug);

            // so we have a non existing page-slug
            if (!$return){
               $return = $this->get_page_array($this->create_page("Gallery", $mySlug ));
            }
      }
    }    
    return $return;
  }
 
 /**
  *   Get the Page Name and Slug
  *
  * @param int Post-ID or slug
  * @return array array('id'=>$val->ID, 'post_name' =>  $val->post_name); or False if not exists
  */
  private function get_page_array($id_or_slug){
    $return = false;
    $all_pages = get_pages();      

    if (intval($id_or_slug)>0){
      foreach ($all_pages as $val){
        if ( $val->ID == $id_or_slug ){
          $return = array('ID'=>$val->ID, 'post_name' =>  $val->post_name);
        }
      }
    }  
    if (is_string($id_or_slug)){
      foreach ($all_pages as $val){
        if ( $val->post_name == $id_or_slug ){
          $return = array('ID'=>$val->ID, 'post_name' =>  $val->post_name);
        }
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