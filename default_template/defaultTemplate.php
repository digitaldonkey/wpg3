<?php 
 /**
  *   WPG3 default/example Template
  *
  *
  *  classname must be the same than the Filename without Extension.
  *  e.g. if the file name is "myFile.php" the flile should contain a class named "myfile"
  *
  *  Javascript&CSS Files should redide in the Template Folder
  *
  *  @todo We load ANY CSS/Script at init even if a template is not in use at the page :(
  *  @todo script_url_dir
  *  @link http://wpg3.digitaldonkey.de
  *  @author Thorsten Krug <driver@digitaldonkey.de>
  *  @filesource
  *  @package WPG3
 **/
 
 /**
  *   Default Template
  *
  *   {@source}
  *
  *
  *   @link http://wpg3.digitaldonkey.de
  *   @author Thorsten Krug <driver@digitaldonkey.de>
  *   @version 0.82
  *   @filesource
  *   @package WPG3
 **/
class defaultTemplate
{
  private $show_available_data = false;
  
  private $templates = array(
    array(
    'name'  => 'Full Photo Template',
    'type'  => 'photo',
    'method'=> 'full_photo',
    ),
    array(
    'name'  => 'Full Album Template',
    'type'  => 'album',
    'method'=> 'full_album',
    ),
    array(
    'name'  => 'Inline Photo Template',
    'type'  => 'photo',
    'method'=> 'inline_photo',
    'features' => array('link', 'align','size', 'caption'),
    ),
    array(
    'name'  => 'Inline Album Template',
    'type'  => 'album',
    'method'=> 'inline_album',
    'features' => array('link', 'align','size', 'caption'),
    )
  );
 /**
  *   register the templates
  *
  *   @return array available Templates
 **/
 public function get_templates(){
    foreach ($this->templates as &$template){
      $template['css'] = get_bloginfo('wpurl').'/wp-content/plugins/wpg3/default_template/wpg3_default.css';
      $template['script'] = get_bloginfo('wpurl').'/wp-content/plugins/wpg3/default_template/wpg3_default.js';
    }
    return $this->templates;
  }
  
  
/**
 *  Full Photo Template
 *
 *  Any Template should return the HTML output
 *  Templates for single elements (e.g. photo, movie) will get one Item
 *
 *   {@source}
 *
 *  @param object item 
 *  @param int Width. This is passed by the WPG3-Tag
**/
  public function full_photo($item, $width=false){
    $html ='<div class="gallery">';
    // $width = WPGX-Tag Width value or false if not set
    //$html .= $width;
    if ($this->show_available_data){
      $html .= '<pre>ITEM:<br />'.print_r($item, true).'<br/>WIDTH:<br />'.print_r($width, true).'</pre>';
    }
    //
    if(isset($item->links->parents)){
          $html .= $this->create_bradcrump($item->links->parents , $item->entity->title);
    }

    $html .="<h2>".$item->entity->title."</h2>";
    $html .= "<a href='".$item->entity->file_url_public."'><img src='".$item->entity->resize_url_public."' /></a>";
	  $html .=  $this->wpg3_view_get_desc($item);
	  $html .=  '</div><div class="clear"></div>';

    return $html;
  }

/**
 *   Inline Photo Template
 *
 *  Any Template should return the HTML output
 *  Templates for single elements (e.g. photo, movie) for Inline/float Block
 *
 *   {@source}
 *
 *  @param object item 
 *  @param array extras (e.g. array('link' => 'http: //...', 'width' => int, 'align' => string )
**/
  public function inline_photo($item, $width=false){
    $html ='<div class="gallery">';
    // $width['width'] = WPGX-Tag Width value or false if not set
    if ($this->show_available_data){
      $html .= '<pre>ITEM:<br />'.print_r($item, true).'<br/>WIDTH:<br />'.print_r($width, true).'</pre>';
    }
    //
    if(isset($item->links->parents)){
          $html .= $this->create_bradcrump($item->links->parents , $item->entity->title);
    }

    $html .="<h2>".$item->entity->title."</h2>";
    $html .= "<a href='".$item->entity->file_url_public."'><img src='".$item->entity->resize_url_public."' /></a>";
	  $html .=  $this->wpg3_view_get_desc($item);
	  $html .=  "</div>";
    return $html;
  }  


/**
 *   Inline Album Template
 *
 *  Any Template should return the HTML output
 *  Templates for single elements (e.g. photo, movie) for Inline/float Block
 *
 *   {@source}
 *
 *  @param object item 
 *  @param array extras (e.g. array('link' => 'http: //...', 'width' => int, 'align' => string )
**/
  public function inline_album($item, $width=false){
    $html ='<div class="gallery">';
    // $width = WPGX-Tag Width value or false if not set
    //$html .= $width;
    if ($this->show_available_data){
      $html .= '<pre>ITEM:<br />'.print_r($item, true).'<br/>WIDTH:<br />'.print_r($width, true).'</pre>';
    }
    //
    if(isset($item->links->parents)){
          $html .= $this->create_bradcrump($item->links->parents , $item->entity->title);
    }

    $html .="<h2>".$item->entity->title."</h2>";
    $html .= "<a href='".$item->entity->file_url_public."'><img src='".$item->entity->resize_url_public."' /></a>";
	  $html .=  $this->wpg3_view_get_desc($item);
	  $html .=  "</div>";
    return $html;
  } 
  
/**
 *  Full Album Template 
 *
 *  Any Template should return the HTML output
 *  Album Templates will will get a array withe the template and Subitems at Members
 *
 *   {@source}
 *   
 *  @param object items with children
 *  @param int Width. This is passed by the WPG3-Tag
**/
  public function full_album($items, $width=false){    
    $html ='<div class="gallery">';
    if ($this->show_available_data){
      $html .= '<pre>ITEM:<br />'.print_r($items, true).'<br/>WIDTH:<br />'.print_r($width, true).'</pre>';
    }
    if(isset($items->links->parents)){
          $html .= $this->create_bradcrump($items->links->parents, $items->entity->title);
    }

    $html .="<h2>".$items->entity->title."</h2>";
    $html .= '<div class="album">';
    
    if ( $items->members ){
      foreach ($items->members as $child => $item) {
        $html .= $this->view_itemBlock($item);           
      }
    }
    $html .= "</div>";
	  $html .=  '</div><div class="clear"></div>';
    
    return $html;
   }

  /* HELPER: get item as block (for the Album page) */
  private function view_itemBlock($item){
    global $wpg3_settings;
    
    $html = '';
    if ($item->entity->type == "album"){
      $html .= "<div class='block item-album'>";
      $html .= '<a href="'.$item->links->item[0].'">';            
    }else{
      $html .= "<div class='block item'>";
      $html .= "<a href='".$item->entity->file_url_public."' rel='lightbox[photos]' class='lightbox-enabled' title='".$item->entity->name."'>";
    }
    $html .= "<img src='".$item->entity->thumb_url_public."' />";
    $html .= "</a>";
    $html .= '<h4><a href="'.$item->links->item[0].'">'.$item->entity->name."</a></h4>";
    /* META */
    $html .= "<div class='meta'>";
    if ($item->entity->type == "album"){
      $html .= count($item->members)." Items";
    }
    if ($item->entity->description != ""){
      $html .= "<p>".$item->entity->description."</p>";
    }
    $html .= "</div>"; // END class=meta  
    $html .= "</div>"; // END class=block	
    return $html;
  }
  
  
  /* HELPER: get item description (for the Photo page) */
  private function wpg3_view_get_desc($item){
  $html = '';
	if ($item->entity->description == ""){
	  $desc = "Not provided.";
	}
	$html .= '<table class="datatable">
					<tr><th colspan=2>Photo Information</th></tr>
					<tr><td>Description</td><td>'.$desc.'</td></tr>
					<tr><td>Date Photographed</td><td>'.$item->entity->created.'</td></tr>
					<tr><td>Date Updated</td><td>'.$item->entity->updated.'</td></tr>
					<tr><td>Dimensions</td><td>'.$item->entity->height.'x'.$item->entity->width.'</td></tr>
					<tr><td>Link to full photo</td><td><a href="'.$item->entity->file_url_public.'">Here</a></td></tr>
          </table>';
  return $html;
  }


  /* HELPER: create breadcrump */
  private function create_bradcrump($ancestors, $title){
    $html = '<div class="breadcrump">';
    foreach ($ancestors as $ancestor){
      if ($ancestor[1]==''){ $ancestor[1] = 'Gallery Home'; }
      $html.= '<a href="'.$ancestor[0].'" >'.$ancestor[1].'</a> ';
    }
    //$html .= $title;
    $html .= '</div>';

  return $html;
  }
}
?>