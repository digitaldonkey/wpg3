<?php
/**
  *   Handle XHTTP Requests and cache data in WP_transients 
  *
  *   There are only GET requests to g3-REST-API
  *
  *   @link http://wpg3.digitaldonkey.de
  *   @author Thorsten Krug <driver@digitaldonkey.de>
  *   @filesource
  *   @package WPG3
 **/
 
/**
  *   Handle XHTTP Requests and cache data in WP_transients 
  *
  *   @todo Blocks
  *   @todo Paged Album Requests 
  */
class WPG3_Xhttp{
    public $slugs;
   /**
    *   Keeping options per class gives a lot of flexibility.
    *   @internal
   **/
    private $wpg3_options;
    private $cache;
    private $update_cache = false;

/**
  *   Create a cache with a WP_transient
  *
  *   {@source}
  *   @staticvar integer $staticvar is returned
  *   @param array wpg3_options
 **/
  function __construct($wpg3_options) {
    if (! is_array($wpg3_options)){
      wp_die('g3 Settings missing@WPG3_Xhttp --> __construct');
    }
    $this->wpg3_options = $wpg3_options;
    
    /* check the cache */
    if (false === ( $this->cache = get_transient($this->wpg3_options['g3Url']) ) ) {
        // It wasn't there, so regenerate the data and save the transient
        if( !class_exists( 'WP_Http' ) ){
          require_once( ABSPATH . WPINC. '/class-http.php' );
        } 
        $this->cache['WP_Http'] = new WP_Http;
        $this->update_cache = true;
      }else{
          $this->cache = get_transient($this->wpg3_options['g3Url']);
      }
  }
  
  
/**
 *  Get item(s) by REST-Uri
 *
 *  Wrapper Class to crate WPG3-Objects by g3_rest_url or (later) slugs
 *
 *  The XHTTP-Stuff is in getItemWithChildren($item_rest_uri)
 *
 *  <b>The WPG3-Object:</b>
 *
 *    -item
 *    -members
 *    -slug
 *    --paged?
 *    --next, previous slug
 *    -parent slug
 *
 *  @todo get Items by slug?
 *  @todo paged?? Where to do?
 *  @param array [item-tag-array]
 *  @return object item(s) with children if there are --> getItemWithChildren()
 *
**/  
  public function get_item( $item = false ){
    $return = false;
    // fallback: if we got a bad request
    if (! is_array( $item) ){
      echo "Something went wrong. I'll go 'home'";
      $item = $this->wpg3_options['g3Url'].$this->wpg3_options['g3Home'];
    }
    
    /**
     *  DEF: $item Array
     *   
     *   'id' => false,
     *   'rest_uri' => $this->wpg3_options['g3Url'].$this->wpg3_options['g3Home'],
     *    'width' => false,
     *    'template' => false 
     *  
    **/
    
    if( isset($item['id']) and trim ($item['id'])){
        // unset others??
        $return = $this->getItemWithChildren( $this->wpg3_options['g3Url'].'/rest/item/'.$item['id']);
    }
    if( isset($item['rest_uri']) ){
      $count = strlen($this->wpg3_options['g3Url'].'/rest/item/');
      if ( substr($item['rest_uri'], 0, $count) === $this->wpg3_options['g3Url'].'/rest/item/' ){
        $return = $this->getItemWithChildren($item['rest_uri']);
      }
    }
 
    return $return;
  }
  
/**
  *   get cached XML from REST_URI
  *
  *   @param string g3_rest_uri
  *   @return object Object containing Child-Objects in Object->members
 **/
  private function getItemWithChildren($uri){
    $start = microtime(true);
    $return = clone $this->getObject($uri);

    // children?
    if ( ! empty($return->members) ){
        $return->members = $this->getMultipleObjects($return->members);
    }
    // parents??
    if ( ! empty($return->entity->parent) ){
        $return->wpg3->parents = $this->getObjectParent($return->entity->parent);
    }

    if (! $return ){
      wp_die("ERROR @getItemWithChildren");
    }
    $this->update_cache(); //just in case we loaded some new XML
    
    $sctipttime =  microtime(true) - $start;
    // echo '<div style="border: 1px dotted red;">getItemWithChildren Execution Time: '.$sctipttime." sec.</div>";

    return $return;
  }
  
  private function getObject($uri){    
    $return = false;
    if ( isset($this->cache['items'][$uri])){
      $return = $this->cache['items'][$uri];
      //echo '<div style="width: 40px; height:10px; background:green;">loaded cache</div>';
    }else{
      //echo '<div style="width: 40px; height:10px; background:red;">get XML</div>';
      $this_req = $this->cache['WP_Http']->request( $uri );
      $this->cache['items'][$uri] = json_decode($this_req['body']);
      $return = $this->cache['items'][$uri];
      $this->update_cache = true;
    }    
   return $return;
  }
  
  private function getMultipleObjects($array){
    if (! is_array($array)){
      die('g3: no Array@getMultipleObjects in wpg3_class_xhttp.php');
    }
    $load_items = array();
    foreach ($array as $item){
      if ( ! isset($this->cache['items'][$item])){
          array_push( $load_items, $item ); 
      }      
    }
    if(!empty($load_items)){
      $uri = $this->wpg3_options['g3Url'].'/rest/items?urls=["'.implode( '","' , $load_items ).'"]';
      //echo "<br />getMultipleObjects URI : ".$uri."<br />";
      $this_req = $this->cache['WP_Http']->request( $uri );
      $items = json_decode($this_req['body']);
      foreach ($items as $item){
        $this->cache['items'][$item->url] = $item;
      }
    $this->update_cache = true;   
    }
    $return = array();
    foreach($array as $item){
        array_push($return, $this->cache['items'][$item]);
    }
   return $return;
  }

/**
 *  Register Options
 *
**/
  public function get_module(){
     $main_mudule = array(
                // unique section ID
                'unique_name' =>'xhttp_options', 
                // visible Section Title
                'title' => __('XHTTP Options'), 
                // callback_fn displaying the Settings
                'function' => array( $this , 'admin_options_section_display' ), 
                // FIELDS
                'settings_fields' => array(
                                        array(
                                        // unique ID
                                        'field_id' => 'g3Home', 
                                        // field TITLE text
                                        'field_title' => __('Default Gallery Album'), 
                                        // function CALLBACK, to display the input box
                                        'field_display' => array( $this , 'admin_options_section_display_g3Home'), 
                                        // function CALLBACK validate field
                                        'field_validate' => array( $this , 'admin_options_section_validate_g3Home')
                                       ),
                                       array(
                                        // unique ID
                                        'field_id' => 'cacheTime', 
                                        // field TITLE text
                                        'field_title' => __('Local Cache Time'), 
                                        // function CALLBACK, to display the input box
                                        'field_display' => array( $this , 'admin_options_section_display_cacheTime'), 
                                        // function CALLBACK validate field
                                        'field_validate' => array( $this , 'admin_options_section_validate_cacheTime')
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
    </div>

<?php }
  
/**
 *  Options Page Output for "g3Home"
 *
**/
  public function admin_options_section_display_g3Home()
  { $field_id = 'g3Home';
    $options = $this->wpg3_options; 
    $val = isset($options[$field_id])?$options[$field_id]:'/rest/item/1';
    echo '<p>Default g3 Album/Item to display. e.g. <strong>/rest/item/1</strong></p>';
    echo '<input id="'.$field_id.'" name="wpg3_options['.$field_id.']" size="60" type="text" value="'.$val.'" />'."\n";  
  }

/**
 *  Options Page Validation for "g3Home"
 *
 *  @todo validate g3Home against REST
**/
  public function admin_options_section_validate_g3Home($field_val)
  {
    $return = false;
    // validate input
    $count = strlen('/rest/item/');
    if ( substr($field_val, 0, $count) === '/rest/item/' ){
        $return = $field_val;
    }else{
      // create a nice Error including you field_id
      add_settings_error('g3Home', 
                         'settings_updated', 
                         __('A valid Gallery3 REST-Item is rqeuired @ g3Home<br /> You entered: "'.$field_val.'"'));
    }
    return $return;
  }

/**
 *  Options Page Output for "cacheTime"
 *
**/
  public function admin_options_section_display_cacheTime()
  { $field_id = 'cacheTime';
    $options = $this->wpg3_options; 
    $val = isset($options[$field_id])?$options[$field_id]:900;
    echo '<p>Time to cache Gallery Items in Seconds e.g. 15 minutes = 60*15 => <strong>900</strong></p>';
    echo '<input id="'.$field_id.'" name="wpg3_options['.$field_id.']" size="60" type="text" value="'.$val.'" />'."\n";  
  }

/**
 *  Options Page Validation for "cacheTime"
 *
**/
  public function admin_options_section_validate_cacheTime($field_val)
  {
    $return = false;
    // validate input
    if ( intval($field_val)){
        $return = intval($field_val);
    }else{
      // create a nice Error including you field_id
      add_settings_error('cacheTime', 
                         'settings_updated', 
                         __('A number (soconds) is rqeuired @ cacheTime<br /> You entered: "'.$field_val.'"'));
    }
    return $return;
  }






  /* returns array of links to Element Parents */
  private function getObjectParent($parent){
    $parent = $this->getObject($parent);
    
    $urls = $this->getParents($parent);
    return $urls;
  }
  
  private function getParents($item){
    $urls = array();
    do {
      $url = $this->wpg3_options['scriptUrl']; 
      if (! $item->entity->id == 0){
        $url .= "?itemid=".$item->entity->id;
      }
      array_unshift($urls,  array( $url, $item->entity->name)  );
      if (isset($item->entity->parent)){
        $item = $this->getObject($item->entity->parent);
      }else{
        $item = false;
      }
   } while ($item);
    return $urls;
  }   
  
/**
  *   Debug
 **/
  public function printSettings(){
    echo "<pre>";
    print_r($this->wpg3_options);
    echo "</pre>";
  }
  /* update cache if necessary */
  private function update_cache(){
    if ($this->update_cache){
      set_transient(
              $this->wpg3_options['g3Url'],
              $this->cache,
              $this->wpg3_options['cacheTime']
              );
      $this->update_cache = false;
    }
  }

/**
 *   delete all cached stuff
 *   @package WPG3
**/
  public function clear_cache(){
    delete_transient($this->wpg3_options['g3Url']); // --> Unregister Plugin!
  }
  
}
?>
