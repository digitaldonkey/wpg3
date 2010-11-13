<?php
/**
  *   Example Template
  *
  *
 **/


/** 
 *  classname must be the same than the Filename without Extension.
 *  e.g. if the file name is "myFile.php" the flile should contain a class named "myfile"
 *
 *  Javascript&CSS Files should redide in the Template Folder
**/
class defaultTemplate
{
  private $templates = array(
    array(
    'name'  => 'My Photo Template',
    'type'  => 'photo',
    'method'=> 'photo_template_07',
    'script'=> 'myPluginTemplate.js',
    'css'=> 'myPluginCSS.css'
    ),
    array(
    'name'  => 'My Album Template',
    'type'  => 'album',
    'method'=> 'album_template_01',
    'script'=> 'myPluginTemplate.js',
    'css'=> 'myPluginCSS.css'
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
  public function photo_template_01($item){
    $html ='';
    $html .= '<pre> HERE IS MY PHOTO TEMPLATE</pre>';
    return $$html;
  }
  public function album_template_01($items){
    $html ='';
    $html .= '<pre> HERE IS MY ALBUM TEMPLATE</pre>';
    return $$html;
  }
}

/*
$mySearchResult=new SearchResult();
$mySearchResult->search("nach einer guten Idee");
echo $mySearchResult->numResults;  //Fehler: Cannot access private property
$mySearchResult->numResults=5;
// Hier auch. Ihn das Ã„ndern zu lassen wäre gefährlich, weil getResult
// Fehler machen wrde. Der Programmierer kann getNumResults() verwenden.
echo $mySearchResult->getResult();
*/
?>