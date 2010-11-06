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

    $return = clone $this->getObject($uri);
    //wpg3_debug($return);

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
    //echo "<h2>Cache after update_cache: </h2>";
    //wpg3_debug ( $this->cache);
    
    $sctipttime =  microtime(true) - $start;
    echo '<div style="border: 1px dotted red;">getItemWithChildren Execution Time: '.$sctipttime." sec.</div>";

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
      $uri = $this->wpg3_settings['g3Url'].'/rest/items?urls=["'.implode( '","' , $load_items ).'"]';
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
  
  /* returns array of links to Element Parents */
  private function getObjectParent($parent){
    $parent = $this->getObject($parent);
    
    $urls = $this->getParents($parent);
    return $urls;
  }
  
  private function getParents($item){
    $urls = array();
    do {
      $url = $this->wpg3_settings['scriptUrl']; 
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
