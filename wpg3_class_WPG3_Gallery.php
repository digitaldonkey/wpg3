<?php
/**
  *   Handle Gallery Post Type
  *
  *   There are only GET requests to g3-REST-API
  *
  *   @link http://wpg3.digitaldonkey.de
  *   @author Thorsten Krug <driver@digitaldonkey.de>
  *   @filesource
  *   @package WPG3
 **/
 
/**
  *   Post types
  *
  *   @todo Blocks
  *   @todo Paged Album Requests 
  */
class WPG3_Gallery{

  private $wpg3_options;
  
/**
  *   todo
  *
  *   @param array wpg3_options
 **/
  function __construct($wpg3_options) {
    if (! is_array($wpg3_options)){
      wp_die('g3 Settings missing@WPG3_Gallery --> __construct');
    }
    $wpg3_options["g3Slug"] = 'gallery';
    $this->wpg3_options = $wpg3_options;
}
/**
 *  Register Options
 *
**/
  public function get_module(){

     $main_mudule = array(
                // unique section ID
                'unique_name' =>'wpg3_gallery_options', 
                // visible Section Title
                'title' => __('Gallery Post Type'), 
                // callback_fn displaying the Settings
                'function' => array( $this , 'admin_options_section_display' ), 
                // FIELDS
                'settings_fields' => array(
                                        array(
                                        // unique ID
                                        'field_id' => 'g3GalleryEnabled', 
                                        // field TITLE text
                                        'field_title' => __('Enable Gallery3 Post Type'), 
                                        // function CALLBACK, to display the input box
                                        'field_display' => array( $this , 'admin_options_section_display_g3GalleryEnabled'), 
                                        // function CALLBACK validate field
                                        'field_validate' => array( $this , 'admin_options_section_validate_g3GalleryEnabled')
                                       )
                                    )   
          );
    return $main_mudule;
  }

/**
 *  init_wp_post_types
 *
**/
  public function init_post_types(){
    $return = false;
    if ($this->wpg3_options['g3GalleryEnabled'] == "enabled" ){
      $return = true;
    }
    
    register_post_type( 'wpg3_item',
    array(
      'labels' => array(
          'name' => __( 'Galleries' ),
          'singular_name' => __( 'Gallery' ),
          'add_new' => __( 'Add New' ),
          'add_new_item' => __( 'Add New Item' ),
          'edit' => __( 'Edit' ),
          'edit_item' => __( 'Edit Gallery' ),
          'new_item' => __( 'New Gallery' ),
          'view' => __( 'View Gallery' ),
          'view_item' => __( 'View Galleries' ),
          'search_items' => __( 'Search Galleries' ),
          'not_found' => __( 'No galleries found' ),
          'not_found_in_trash' => __( 'No galleries in Trash' ),
          'parent' => __( 'Parent Gallery' ),
          'can_export' => false,
          //'register_meta_box_cb' => array(&$this, 'wpg3_gallery_admin_meta_box'),


        ),
        'description' => __( 'A Gallery is a type of content that is the most wonderful content in the world. There are no alternatives that match how insanely creative and beautiful it is.' ),
        'public' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'rewrite' => array('slug' => $this->wpg3_options['g3Slug']), 
        'menu_position' => 20,
        'menu_icon' => plugin_dir_url(__FILE__).'/images/menu.png?ver=20100531',
        
      )
    );


  }

/**
 *  Let's check out the querry Filters
 *
**/
public function parse_query_callback($wp_query){

	/*	here is your parsign stuff*/
  echo "<pre>\n";
  print_r ( $wp_query );
  echo "</pre>";
  

	//return $wp_query;

}

/**
 *  Options Page Output for "g3GalleryEnabled"
 *
**/
  public function admin_options_section_display_g3GalleryEnabled()
  { 
    $field_id = 'g3GalleryEnabled';
    $options = $this->wpg3_options; 
    
    echo '<p>Gallery Custom Post type inegration</p>';
    
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
    echo '<input '.$checked.' id="'.$field_id.'_checkbox" name="wpg3_options['.$field_id.'_checkbox]" type="checkbox" /> Enable Integration';
    echo '<input id="'.$field_id.'" name="wpg3_options['.$field_id.']" type="hidden" value="'.$enabled.'" />';

  }
  
/**
 *  Options Page Validation for "g3GalleryEnabled"
 *
 *  
**/
  public function admin_options_section_validate_g3GalleryEnabled($field_val)
  { 
    global $_POST;
    $return = "disabled";
    if (isset($this->wpg3_options['g3GalleryEnabled']) ){
      $return = $this->wpg3_options['g3GalleryEnabled'];
    }
    if ( isset($_POST['wpg3_options']['g3GalleryEnabled_checkbox']) ){
      $return = "enabled";
    }else{
      $return = "disabled";
    }
    return $return;
  }

  /**
   *  Section Header for Options Page
   *
  **/
    public function admin_options_section_display()
    {?>
      <div style="width: 600px; margin-left: 10px;">
      </div>
  
  <?php }

//// add to your plugin deactivation function
//global $wp_rewrite;
//$wp_rewrite->flush_rules();

} // END CLASS
