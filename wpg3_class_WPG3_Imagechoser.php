<?php
 /**
   *   IMAGE GALLERY (Image Choser) CLASS 
   *
   *   Adds Gallery3 Media Tab to the Wordpress Image-Choser.
   *
   *  @event admin_init 
   *  @package WPG3
   *  @filesource
  **/
class WPG3_Imagechoser
{ 
  private $wpg3_options;
  
 /**
  *   Loads class Data
  *   @package WPG3
  */
  function __construct($wpg3_options) {
    if (! is_array($wpg3_options)){
      wp_die('wpg3_options missing@WPG3_Imagechoser --> __construct');
    }
    $this->wpg3_options = $wpg3_options;
  }


/**
 *  Register Options and Admin Init filter
 *
**/
public function main_init()
{


}


/**
 *  Register Options and Admin Init filter
 *
**/
public function admin_init()
{
    /* Add gallery3 Tab to Media Library */
    add_action('media_upload_tabs', array(&$this, 'imagechoser_tab') );
    
    /* Add gallery3 Media Library Form */
    add_action   ('media_upload_wpg3',  array(&$this,'imagechoser_media_browser') );

   /* Options */
   $this_module = array(
              // unique section ID
              'unique_name' =>'imagechoser_options', 
              // visible Section Title
              'title' => __('Image Choser Options'), 
              // callback_fn displaying the Settings
              'function' => array( $this , 'admin_options_section_display' ), 
              // FIELDS
              'settings_fields' => array(
                                      array(
                                      // unique ID
                                      'field_id' => '', 
                                      // field TITLE text
                                      'field_title' => __(''), 
                                      // function CALLBACK, to display the input box
                                      'field_display' => array( $this , 'admin_options_section_display_'), 
                                      // function CALLBACK validate field
                                      'field_validate' => array( $this , 'admin_options_section_validate_')
                                     ),
                                  )
        );
  return $this_module;
}


/**
  *   Add gallery3 Media Tab
  *
 **/
public function imagechoser_tab($x){
	$x['wpg3'] = __('Gallery3');
  return $x;
}

/**
 *  Gallery3 Media Library Form
 * 
**/
public function imagechoser_media_browser($x){
	$errors = array();
	$id = 0;
/* some stuff I didn't understand yet */
	if ( !empty($_POST['insertonlybutton']) ) {
    //echo '$_POST[ insertonlybutton ] = '.$_POST['html-upload']; 
	}

	if ( !empty($_POST) ) {
		//$return = media_upload_form_handler();

		if ( is_string($return) )
			return $return;
		if ( is_array($return) )
			$errors = $return;
	}

	if ( isset($_POST['save']) ) {
    $errors['upload_notice'] = __('Saved.');
		return media_upload_gallery();
	}

/* Its g3! So lets do something */
	if ( isset($_GET['tab']) && $_GET['tab'] == 'wpg3' )
		return wp_iframe( array(&$this, 'media_upload_type_wpg3_form') , 'wpg3', $errors, $id );

	return wp_iframe( 'media_upload_type_form', 'wpg3', $errors, $id );
}


/**
  *   The form for the Iframe 
  *
  *   @global $wpg3-instance to call get_templates()
 **/
  public function media_upload_type_wpg3_form($type = 'wpg3', $errors = null, $id = null)
  {
  global $wpg3;
  $plugin_dir_url =  trailingslashit( plugins_url( '', __FILE__ ) );

  media_upload_header();
  
  $post_id = isset( $_REQUEST['post_id'] )? intval( $_REQUEST['post_id'] ) : 0;
  
  $form_action_url = admin_url("media-upload.php?type=$type&tab=type&post_id=$post_id");
  $form_action_url = apply_filters('media_upload_form_url', $form_action_url, $type);
  
    $wpg3_date_format = get_option('date_format').' - '.get_option('time_format'); 
    //echo $date_format;
  
  echo '<form enctype="multipart/form-data" method="post" action="' . $form_action_url .'" class="media-upload-form type-form validate" id="wpg3-form">';
  echo '<input type="submit" class="hidden" name="save" value="" />';
  echo '<input type="hidden" name="post_id" id="post_id" value="' .  (int) $post_id .'" />';
  wp_nonce_field('media-form');
  
  echo '<h3 class="media-title">' .  _e('Add media files from Gallery3') . '</h3>';
  echo '<div id="media-album-info">Can not load XHTTP</div>';
  echo '<div id="media-items"></div>';
  
  // preload
  echo '<img src="'.$plugin_dir_url.'images/ajax-loading.gif" alt=""  style="display: none;" />';
  
  // add javascript
  echo '<script src="'.$plugin_dir_url.'js/media-upload-form.js" type="text/javascript"></script>';
  
  
    // set JS Options
  $js_options = array(
                       'wpg3_date_format' => $wpg3_date_format,
                       'album_icon'       => $plugin_dir_url . 'images/ico-album.png',
                       'url'              => $this->wpg3_options['g3Url']. '/rest/item/',
                       'image_dir_uri'         => $plugin_dir_url . 'images/',
                    );

  if (isset($this->wpg3_options['restReqestKey'])){
    $js_options['restReqestKey'] = $this->wpg3_options['restReqestKey'];
  }
  

/**
    *   get available templates
    *
    *   @todo we need a propper value for 'available template types: $types '
    *
   **/
  $template = $wpg3->get_module_instance('WPG3_Template');
  $types = array ('album', 'photo');
  foreach ( $types as &$type ){
    $js_options['templates'][$type] =  $template->get_templates( $type );
  }
 
  
  // so maybe we can use a json options array???
  //echo json_encode ( $js_options );
  
  
  echo "
  <script type='text/javascript'>
  //<![CDATA[
  
    var imagechoser_options = ".json_encode ( $js_options )."
  
     var imagchoser_json = ".json_encode ( $js_options )."
     var wpg3_date_format = '" . $wpg3_date_format . "';
     var image_dir_uri =  '" . $plugin_dir_url . "images/';
     var album_icon =  '" . $plugin_dir_url . "images/ico-album.png';

     var url = '" .$this->wpg3_options['g3Url']. "/rest/item/';
     
  //]]>
  </script>";
  
  // CSS-STYLES
  echo "<link rel='stylesheet' href='".$plugin_dir_url . "css/imagechoser.css' type='text/css' media='all' />";
  
  // ????
  echo '<div id="gallery-items">';
  if ( $id ) {
    if ( !is_wp_error($id) ) {
      add_filter('attachment_fields_to_edit', 'media_post_single_attachment_fields_to_edit', 10, 2);
      echo get_media_items( $id, $errors );
    } else {
      echo '<div id="media-upload-error">'.esc_html($id->get_error_message()).'</div>';
      exit;
    }
  }
  echo '</div>';
  
  // disabled by now
  echo '<p class="savebutton wpg3-submit">';
  echo '<input disabled="disabled" type="submit" class="button" name="save" value="Save all changes" />';
  echo '</p>';
  echo '</form>';  
  
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
         No Image Choser Options yet
      </p>
   </div>';
}
  
/**
 *  Options Page Output for "templateDirectory"
**/
  public function admin_options_section_display_()
  { $field_id = '';
    $options = $this->wpg3_options; 
    $val = isset($options[$field_id])?$options[$field_id]: '';
    //echo '<p>Absolute path to template directory. Leave empty to disable.<br />e.g. <strong>'.ABSPATH.'wp-content/wpg3-templates/</strong></p>';
    //echo '<input id="'.$field_id.'" name="wpg3_options['.$field_id.']" size="60" type="text" value="'.$val.'" />'."\n";  
  }

  /**
 *  Options Page Validation for "templateDirectory"
**/
  public function admin_options_section_validate_($field_val)
  { 
    $return = false;
    $field_val = trim($field_val);
    // validate input
    if ( $field_val == '' ){
     $return = ' '; //blank, not empty or you'll get trouble emptying the value!
    }
    /*
    else{
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
    */
    return $return;
  }


}