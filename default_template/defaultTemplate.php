<?php 
 /**
  *   WPG3 default/example Template
  *
  *   @link http://wpg3.digitaldonkey.de
  *   @author Thorsten Krug <driver@digitaldonkey.de>
  *   @version 1.0
  *   @filesource
  *   @package WPG3
  *  classname must be the same than the Filename without Extension.
  *  e.g. if the file name is "myFile.php" the flile should contain a class named "myfile"
  *
  *  Javascript&CSS Files should redide in the Template Folder
  *  @todo We load ANY CSS/Script at init even if a template is not in use at the page :(
  *  @todo script_url_dir
 **/
class defaultTemplate
{
  private $show_available_data = false;
  private $templates = array(
    array(
    'name'  => 'Default Photo Template',
    'type'  => 'photo',
    'method'=> 'default_photo',
    'script'=> 'http://wpg3.local/wp-content/plugins/wpg3/default_template/wpg3_default.js',
    'css'=>    'http://wpg3.local/wp-content/plugins/wpg3/default_template/wpg3_default.css'
    ),
    array(
    'name'  => 'Default Album Template',
    'type'  => 'album',
    'method'=> 'default_album',
    'script'=> 'http://wpg3.local/wp-content/plugins/wpg3/default_template/wpg3_default.js',
    'css'=>    'http://wpg3.local/wp-content/plugins/wpg3/default_template/wpg3_default.css'
    ),
  );
 /* register the templates */
 public function get_templates(){
    return $this->templates;
  }
  /**
   *  Any Template should return the HTML output
   *  Templates for single elements (e.g. photo, movie) will get one Item
   *  Album Templates will will get a array withe the template and Subitems at Members
  **/


  /* PHOTO TEMPLATE */
  public function default_photo($item, $width=false){
    $html ='';
    // $width = WPGX-Tag Width value or false if not set
    //$html .= $width;
    if ($this->show_available_data){
      $html .= '<pre>'.print_r($item, true).'</pre>';
    }
    //
    if(isset($item->wpg3->parents)){
          $html .= $this->create_bradcrump($item->wpg3->parents , $item->entity->title);
    }

    $html .="<h2>".$item->entity->title."</h2>";
    $html .= "<a href='".$item->entity->file_url_public."'><img src='".$item->entity->resize_url_public."' /></a>";
	  $html .=  $this->wpg3_view_get_desc($item);
    return $html;
  }
  
  
  /* ALBUM TEMPLATE */
  public function default_album($items, $width=false){    
    $html ='';
    if ($this->show_available_data){
      $html .= '<pre style="font-size: small; line-height: 85%;"> Available for Photo Template:<br />'.print_r($items, true).'</pre>';
    }
    if(isset($items->wpg3->parents)){
          $html .= $this->create_bradcrump($items->wpg3->parents, $items->entity->title);
    }

    $html .="<h2>".$items->entity->title."</h2>";
    $html .= '<div class="album">';
    
    if ( $items->members ){
      foreach ($items->members as $child => $item) {
        $html .= $this->view_itemBlock($item);           
      }
    }
    $html .= "</div>";
    
    return $html;
   }





  /* HELPER: get item as block (for the Album page) */
  private function view_itemBlock($item){
    global $wpg3_settings;
    
    $html = '';
    $html .= "<div class='block' style='display: inline-block; width: 150px; margin: 3px; background: #efefef; text-align: center; padding-top: 6px;'>";
    //wpg3_debug($item);
    if ($item->entity->type == "album"){
      $html .= '<a href="'.$wpg3_settings["scriptUrl"].'?itemid='.$item->entity->id.'">';            
    }else{             
      $html .= "<a href='".$item->entity->file_url_public."' rel='lightbox[photos]' class='lightbox-enabled' title='".$item->entity->name."'>";
    }
    $html .= "<img src='".$item->entity->thumb_url_public."' />";
    $html .= "</a>";
    $html .= '<h4><a href="'.$wpg3_settings["scriptUrl"].'?itemid='.$item->entity->id.'">'.$item->entity->name."</a></h4>";
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