<?php
/**
 *
 *    WPG3 XHTTP 
 *
 *  Stores g3-items locally into transients
 *
 *  
 *
 *
**/

class Wpg3_Xhttp{
    public $slugs;
    private $wpg3_settings;
    private $cache;
    private $update_cache = false;
  
  function __construct($settings) {
    if (! is_array($settings)){
      die('g3 Settings missing@Wpg3_Xhttp --> __construct');
    }
    $this->wpg3_settings = $settings;
    /* check the cache */
    if (false === ( $this->cache = get_transient($this->wpg3_settings['g3Url']) ) ) {
        // It wasn't there, so regenerate the data and save the transient
        if( !class_exists( 'WP_Http' ) ){
          require_once( ABSPATH . WPINC. '/class-http.php' );
        } 
        $this->cache['WP_Http'] = new WP_Http;
        $this->update_cache = true;
        $this->update_cache();
      }else{
          $this->cache = get_transient($this->wpg3_settings['g3Url']);
      }
    //wpg3_debug($this->cache);

  }
  
  
  /**
   *  Get item(s) by REST-Uri
   *
   *  by slug ????
   *  @return
   *    -item
   *    -members
   *    -slug
   *    --paged?
   *    --next, previous slug
   *    -parent slug
   *
   *   @return
  **/  
  public function get_item( $item = false ){
    /* by default we pass g3Home */
    if (! $item ){
      $item = $this->wpg3_settings['g3Url'].$this->wpg3_settings['g3Home'];
    }
    
    /* is it a slug or REST url */
    $count = strlen($this->wpg3_settings['g3Url'].'/rest/item/');
    if ( substr($item, 0, $count) === $this->wpg3_settings['g3Url'].'/rest/item/' ){

      //echo "REST url detected";
      
      return $this->getItemWithChildren($item);
    
    }
    
    /*
    else{
    
      echo "SLUG path detected";
     // this should be a slug-path
     // remove leading and trailing Slash
      if(substr($item, 0,1) === "/" ){
        $item = substr($item, 1);
      }
      $item = untrailingslashit($item);
      // path ARRAY
      $path =  explode ("/", $item); 
    }
    */
    //echo $item;
  }
  
  /* get cached XML from REST URI */
  private function getItemWithChildren($uri){
    $start = microtime(true);

    $return = false;
    
    /* load item from cache or get it*/
    if (isset($this->cache['items'][$uri])){
      $return = $this->cache['items'][$uri];
      echo "Found root in Cache<br />";
    }else{
      $this_req = $this->cache['WP_Http']->request( $uri );
      $this->cache['items'][$uri] = json_decode($this_req['body']);
      $return = $this->cache['items'][$uri];
      $this->update_cache = true;
      $this->update_cache(); // somehow strangely necessary otherwise 2 of three reloads fail ??

    }  
      // children?
      if ( ! empty($this->cache['items'][$uri]->members) ){
        echo "Found members<br />";
        // if not in cache -> add to cache
        $load_items = array();
        foreach($this->cache['items'][$uri]->members as $child_uri){
          echo '<h4 style="color: red;">'.$child_uri."</h4>";
          //wpg3_debug($this->cache['items']);
          if (! isset($this->cache['items'][$child_uri])){
            echo "Didn't find Child in cache: $child_uri <br />";
            array_push($load_items, '"'.$child_uri.'"'); 
          }
        
        }
        if (!empty($load_items)){
          echo "Loading items ...<br />";
          $children_uri = $this->wpg3_settings['g3Url'].'/rest/items?urls=['.implode( ',' , $load_items).']';
            //echo "<p style='color: green;'>Child Url".$children_uri."</p>";
          $this_req = $this->cache['WP_Http'] ->request( $children_uri );
          if (!$this_req){
            wp_die($this_req);
          }
          foreach(json_decode($this_req['body']) as $child){
            echo '<p style="color:green;">Child Url'.$child->url."</p>";
            $this->cache['items'][$child->url] = $child;
          }
          $this->update_cache = true;
        }
        
        // add items to item[members]
        $child_items = array();
        //wpg3_debug($this->cache['items'][$uri]->members);
        foreach ($this->cache['items'][$uri]->members as $item){
          array_push($child_items, $this->cache['items'][$item]);
        }
        $return->members = $child_items;
      }
    if (! $return ){
      wp_die("ERROR @getItemWithChildren");
    }
    //$this->update_cache(); //just in case we loaded some new XML
    
    $sctipttime =  microtime(true) - $start;
    echo '<div style="border: 1px dotted red;">getItemWithChildren Execution Time: '.$sctipttime." sec.</div>";

    return $return;
  }
  
  private function getObject($uri){
    $start = microtime(true);  
    $return = false;    
    if (isset($this->cache['items'][$uri])){
      $return = $this->cache['items'][$uri];
    }else{
      $this_req = $this->cache['WP_Http']->request( $uri );
      $this->cache['items'][$uri] = json_decode($this_req['body']);
      $return = $this->cache['items'][$uri];
      $this->update_cache = true;
    }    
  
  }
  
  /**
   *  DEBUG
  **/
  public function printSettings(){
    echo "<pre>";
    print_r($this->wpg3_settings);
    echo "</pre>";
  }
  /* update cache if necessary */
  private function update_cache(){
    if ($this->update_cache){
      set_transient(
              $this->wpg3_settings['g3Url'],
              $this->cache,
              $this->wpg3_settings['cache_time']
              );
      $this->update_cache = false;
      echo "Cache Updated<br />";
    }
  }
  /**
   *  delete all cached stuff
  **/
  public function clear_cache(){
    delete_transient($this->wpg3_settings['g3Url']); // --> Unregister Plugin!
  }
  
}
?>
