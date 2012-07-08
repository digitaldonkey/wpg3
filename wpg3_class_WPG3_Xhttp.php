<?php
/**
  *   Handle XHTTP Requests and cache data in WP_transients 
  *
  *   There are only GET requests to g3-REST-API
  *
  *   @link http://wpg3.digitaldonkey.de
  *   @author Thorsten Krug <driver@digitaldonkey.de>
  *   @global class class-http.php
  *   @package WPG3
  *   @filesource
 **/
 
/**
  *   Handle XHTTP Requests and cache data in WP_transients 
  *
  *   @todo Paged Album Requests
  */
class WPG3_Xhttp{
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
  *   @staticvar integer $staticvar is returned
  *   @param array wpg3_options
  *   @source
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
 *   {@source}
 *  @todo paged Items
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
     *    'parents'  array ( 0 => array ( 0 => Abs_url (str) , 1 => Title (int)  ) // array of parent_urls
     *    'g3Page' => true
    **/
    
  // we use Permalinks or GET ( ...url...?itemid=66) 
  if( isset($item['parents']) and  isset($item['id']) and trim ($item['id']) ){      

      $return = $this->getItemWithChildren( $this->wpg3_options['g3Url'].'/rest/item/'.$item['id'], $item['parents'] );

  }else{

    if( isset($item['id']) and trim ($item['id'])){
      $return = $this->getItemWithChildren( $this->wpg3_options['g3Url'].'/rest/item/'.$item['id']);
    }    
    if( isset($item['rest_uri']) ){
      $count = strlen($this->wpg3_options['g3Url'].'/rest/item/');
      if ( substr($item['rest_uri'], 0, $count) === $this->wpg3_options['g3Url'].'/rest/item/' ){
        $return = $this->getItemWithChildren($item['rest_uri']);
      }
    }

  }  
    return $return;
  }
  
/**
  *   get cached XML from REST_URI
  *
  *   @param string g3_rest_uri or array ( (str)"slug", (str) "id", (array) "members")
  *   @todo much too slow ???
  *   @return object Object containing Child-Objects in Object->members
 **/
  public function get_slugs( $item )
  { 
    $return = false;
    
    /*
    echo "<pre>FIREST ITEMn";
    print_r ( $item  );
    echo "</pre>";
    */
    
    
    if ( is_string($item) ){
      $first_item = $this->getObject($item);
      $slugs = array(
                    "slug" => $first_item->entity->name,
                    "id" => $first_item->entity->id,
                    );
      if (!empty( $first_item->members ) ){
        $slugs["members"] = $first_item->members;
        $slugs["members"] = $this->get_slugs( $slugs );
      }      
      $return =  $slugs;
    }
    // recursion
    if ( is_array($item) ){
      $return = array();
      foreach ( $item["members"] as $child){
        
        $child = $this->getObject($child);      
      
        $child_array = array(
                    "slug" => $child->entity->name,
                    "id" => $child->entity->id,
                    );
        if (!empty( $child->members ) ){
          $child_array["members"] = $child->members;
          $child_array["members"] = $this->get_slugs( $child_array );          
        }else{
          $child_array["members"] = false;
        }
        array_push ( $return, $child_array );
      }
    }
     return $return;
  }
  
  
/**
  *   get cached XML from REST_URI
  *
  *   @param string g3_rest_uri
  *   @param array Optional, Rewrite Urls
  *   @return object Object containing Child-Objects in Object->members
 **/
  public function getItemWithChildren($uri, $slugs = false ){
    $start = microtime(true);
    $return = clone $this->getObject($uri);

  if( $slugs ){
  
    if ( ! empty($return->entity->parent) ){
      $parent_slugs = array_slice( $slugs , 0 ,  -1 );
      $item_slugs  =  array_slice( $slugs ,  -1 );            
      $return->links->parents = $parent_slugs;
      $return->links->item = $item_slugs[0];
    }
    // children?
    if ( ! empty($return->members) ){
      $return->members = $this->getMultipleObjects($return->members, $slugs);
    }
    
  }else{
    if ( ! empty($return->entity->parent) ){
      $return->links->parents = $this->getObjectParent($return->entity->parent);
      // script Url??
      $return->links->item = array( '?itemid='.$return->entity->id , $return->entity->title);            
     }
    // children?
    if ( ! empty($return->members) ){
        $return->members = $this->getMultipleObjects($return->members);
    }
  }



    if (! $return ){
      wp_die("ERROR @getItemWithChildren");
    }
    $this->update_cache(); //just in case we loaded some new XML
    
    $sctipttime =  microtime(true) - $start;
    // echo '<div style="border: 1px dotted red;">getItemWithChildren Execution Time: '.$sctipttime." sec.</div>";

    return $return;
  }


/**
  *   get cached XML from REST_URI
  *
  *   @param string g3_rest_uri
  *   @return object Object cached or new Item Object
 **/  
  private function getObject($uri, $nocache = false ){    
    $return = false;
    
    if ( isset($this->cache['items'][$uri]) and !$nocache ){
      
      $return = $this->cache['items'][$uri];
      //echo '<div style="width: 40px; height:10px; background:green;">loaded cache</div>';
    }else{
      //echo '<div style="width: 40px; height:10px; background:red;">get XML</div>';
     
      $this_req = $this->cache['WP_Http']->request( $uri, $this->get_rest_header('GET', $this->wpg3_options['restReqestKey']) );
      if ( $this_req['response']['code'] != 200 ){
        // echo "Couldn't connect by Rest @ getObject<br /><pre>".print_r($this_req, true)."</pre>";
        $return = array(false, $this_req['response']);
      }
      
      $this->cache['items'][$uri] = json_decode($this_req['body']);
      $return = $this->cache['items'][$uri];
      $this->update_cache = true;
       $this->update_cache();
    }    
   return $return;
  }
  
  
/**
  *   get cached XML from REST_URI
  *
  *   @param array of Rest Uri strings
  *   @return array of Object cached or new Item Objects
 **/ 
  private function getMultipleObjects($array, $slugs=false){
    if (! is_array($array)){
      die('g3: no Array@getMultipleObjects in wpg3_class_xhttp.php');
    }
    
    $load_items = array();
    foreach ($array as $item){
      if ( ! isset($this->cache['items'][$item])){
          //echo "<div style='color: red;'>NOTin Chache: $item</div>";
          array_push( $load_items, $item ); 
      }      
    }
    // load from cache
    if(!empty($load_items)){
      $uri = $this->wpg3_options['g3Url'].'/rest/items?urls=["'.implode( '","' , $load_items ).'"]';
      //echo "<br />getMultipleObjects URI : ".$uri."<br />";
      
      $this_req = $this->cache['WP_Http']->request( $uri, $this->get_rest_header() );
      if ( $this_req['response']['code'] != 200 ){
        wp_die("Couldn't connect by Rest @ getMultipleObjects<br /><pre>".print_r($this_req, true)."</pre>");
      }

      $items = json_decode($this_req['body']);
      if (empty ( $items )){
        wp_die("Couldn't get multiple items @WPG3_Xhttp->getMultipleObjects()");
      }

      foreach ($items as $item){
        $this->cache['items'][$item->url] = $item;
      }
    $this->update_cache = true;   
    }
    
    $return = array();
    foreach($array as $item){
      //echo "ITEM: ".$item."<br />";
      $my_item = $this->cache['items'][$item];
        
        // base for the Item Url
        $slugs  ? $item_url = end($slugs) : $item_url= '' ;
        
        if ($slugs){
          $my_item->links->item = array( $item_url[0].'/'.$my_item->entity->name , $my_item->entity->title);
        // GET Link
        }else{
          $my_item->links->item = array( '?itemid='.$my_item->entity->id , $my_item->entity->title);
        
        
        }
        array_push($return, $my_item );
    }
   return $return;
  }


/**
 *  REST Header
 *
**/
public function get_rest_header($method = "GET", $key = false ){
  $return = false;
    
  if ( $key ) $this->wpg3_options['restReqestKey'] = $key;
  
  if(isset($this->wpg3_options['restReqestKey'])){
     $return = array(
            'method' => $method,
            'headers' =>  array( 'X-Gallery-Request-Key' => $this->wpg3_options['restReqestKey'])
             );
  }
  return $return;
}

/**
 *  Register Options
 *
**/
  public function admin_init(){
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
                                       ),
                                        array(
                                        // unique ID
                                        'field_id' => 'restReqestKey', 
                                        // field TITLE text
                                        'field_title' => __('Rest API Key'), 
                                        // function CALLBACK, to display the input box
                                        'field_display' => array( $this , 'admin_options_section_display_restReqestKey'), 
                                        // function CALLBACK validate field
                                        'field_validate' => array( $this , 'admin_options_section_validate_restReqestKey')
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
    $val = isset($options[$field_id])? 'value="'.$options[$field_id].'"' : 'style="color: red;" value="/rest/item/1"';
    echo '<p>Default g3 Album/Item to display. e.g. <strong>/rest/item/1</strong></p>';
    echo '<input id="'.$field_id.'" name="wpg3_options['.$field_id.']" size="30" type="text" '.$val.' />'."\n";
    echo '<p>This Item will be you the root Album of you G3-Page.';
  }

/**
 *  Options Page Validation for "g3Home"
 *
 *  @todo validate g3Home against REST
**/
  public function admin_options_section_validate_g3Home($field_val)
  { 
    global $wpg3, $_POST;
    $return = false;
    $valid = false;
    $error = "A valid Item looks '/rest/item/#'   Where # is the ID of a G3-Album (int > 0)<br />You entered: '".$field_val."'";
    
    if (isset($_POST['wpg3_options']['restReqestKey'])){
      $this->wpg3_options['restReqestKey'] = $_POST['wpg3_options']['restReqestKey'] ;      
    }
    
    // validate input
    $count = strlen('/rest/item/');
    if ( substr($field_val, 0, $count) === '/rest/item/' and intval( substr($field_val, $count) ) > 0 ){

        //Validate g3Url and g3Home
        $xhttp = $wpg3->get_module_instance('WPG3_Xhttp', $this->wpg3_options);
        $result = $xhttp->getObject($this->wpg3_options['g3Url'].$field_val, true);        
                
        if ( $result->entity->type == 'album'){
          $return = $field_val;
          $valid = true;
        }else{
           $error = 'No Gallery Album here! <br />Maybe you need to enable Guest Access or set a REST Api Key.<br />URL: '.$this->wpg3_options['g3Url'].$field_val;
        }
    }
    if(!$valid){
      // create a nice Error including you field_id
      add_settings_error('g3Home', 
                         'settings_updated', 
                         __('A valid Gallery3 REST-Album Url is rqeuired @ g3Home<br />'.$error ));
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
    echo '<input id="'.$field_id.'" name="wpg3_options['.$field_id.']" size="10" type="text" value="'.$val.'" />'."\n";  
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

/**
 *  Options Page Output for "restReqestKey"
 *
**/
  public function admin_options_section_display_restReqestKey()
  { $field_id = 'restReqestKey';
    $options = $this->wpg3_options; 
    $val = isset($options[$field_id])?$options[$field_id]:'';
    echo '<p>There is a single Key used for all get requests.</p>';
    echo '<input id="'.$field_id.'" name="wpg3_options['.$field_id.']" size="30" type="text" value="'.$val.'" />'."\n";  
  }

/**
 *  Options Page Validation for "restReqestKey"
 *
**/
  public function admin_options_section_validate_restReqestKey($field_val)
  {
    $return = false;
    // validate input
     if ( $field_val == '' ){
     $return = ' '; //blank, not empty or you'll get trouble emptying the value!
    }else{
      if ( $this->test_restReqestKey($field_val) ){
            return $field_val;
      }else{
        // create a nice Error including you field_id
        add_settings_error('restReqestKey', 
                           'settings_updated', 
                           __('A valid Rest-Api Key to your Gallery is rqeuired @ restReqestKey<br /> You entered: "'.$field_val.'"'));
      }
    }
    return $return;
  }

/**
 *  Test restReqestKey
 *
**/
  private function test_restReqestKey($restReqestKey)
  {
    $return = false;
    
    $home = isset($this->wpg3_options['g3Home']) ? $this->wpg3_options['g3Home'] : '/rest/item/1';
    $url = $this->wpg3_options['g3Url'].$home;
    
    $request = array(
        'method' => 'GET',
        'headers' =>  array( 'X-Gallery-Request-Key' => $restReqestKey)
        );
    
    $response = $this->cache['WP_Http']->request($url, $request) ;
    
    if ($response['response']['code'] == 200){
      $return = true;
    }
    return $return;
  }




  /**
    *   Recoursivly getting Parents
    *
    *   @return array with links to Element Parents
   **/
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
