(function() {
	var DOM = tinymce.DOM;

	tinymce.create('tinymce.plugins.Wpg3Tags', {
		init : function(ed, url) {
      
      var valid_tags = new Array('wpg3','wpg3','wpgdId');
      
      var t = this;

			ed.onBeforeSetContent.add(function(ed, o) {
					////console.log ('wpg2tohtml');
					if (o.set){
            //o.content = t._htmlToWysiwyg(o.content, url);
					}
      });
			ed.onPostProcess.add(function(ed, o) {
				if (o.set){
					//alert ('wpg2tohtml');
					o.content = t._htmlToWysiwyg(o.content, url);
        }
				if (o.get){
					//console.log ('htmltowpg2: load -> _wysiwygToHtml()');
					//alert (o.content);
					o.content = t._wysiwygToHtml(o.content, t);
			  }
			});

		},

		getInfo : function() {
			return {
				longname : 'WPG3 Plugin',
				author : 'Thorsten Krug', // add Moxiecode?
				authorurl : 'http://donkeymedia.eu',
				infourl : 'http://wpg3.digitaldonkey.de',
				version : '0.85'
			};
		},



  /**
   *  Turn html content into TinyMCE Wysiwyg image-Tag
   *  ans store the wpg3-Tag into img-rel attribute
  **/
  _htmlToWysiwyg : function(content, pluginURL) {
      
      //alert (content);
      
      var t = this;
      var replaceContent = jQuery(content);
      
      replaceContent.children('wpg3, wpg2, wpg2id').each(function(){
          //console.debug(this);
          var tag = jQuery(this).text();
          //alert ('_htmlToWysiwyg: '+ tag );
          jQuery(this).replaceWith( t._getWysiwygImage( tag ) );
      });
      
     //alert ('CONTENT: '+replaceContent.html());
     return replaceContent.html();
  },

  
  _wysiwygToHtml : function(content, t) {
    // Parse all WPG2 placeholder img tags and replace them with <wpg2>
      
      //console.log ('_wysiwygToHtml: ->content '+content);
      var return_obj = jQuery(content);

      return_obj.children('img').each( function(i, val){
        var myImage = jQuery(val);
        
        //console.log('_wysiwygToHtml');
        //console.debug(myImage);

        if ( myImage.hasClass('wpg3Image') && myImage.attr('alt') != 'undefined' )
        {
          myImage.replaceWith('<wpg3>'+myImage.attr('alt')+'</wpg3>');
        
        }else{
          //console.log ('_wysiwygToHtml: Where is ALT tag?');
        }
      });
    return return_obj.html();
  },
  
  /**
   *  Insert a Image for a tag 
   *
   *  Here we will handle the tag-to-prieview-image Issue.
   *  htmltowpg2() will just take take the @param??? of the image-Tag to get back the Tag.
   *  @return html string Image for the WYSIWYG-View
   *  @param  jQuery img-Object tit attr: rel containing the wpg3 Tag???
  **/
  _getWysiwygImage : function( wpg3_tag_values ) {
    
    //console.log ('_getWysiwygImage: '+ wpg3_tag_values);

    //@todo we net to process the tag and get the right Url and Size!
    
    var image_path ="http://blog.digitaldonkey.de/gallery2/d/2122-17/hochzeitography.jpg";
    var img_obj = jQuery('<img id="'+GetRandom(0,30000)+'" src="' + image_path + '" alt="'+wpg3_tag_values+'" title="" class="mceItem wpg3Image" />');
    ////console.debug(img_obj);
   
    //alert ('image: '+ img_obj.class );
    return img_obj;
   }

});

/* DEBUG till we have a propper ID*/
function GetRandom( min, max ) {
	if( min > max ) {
		return( -1 );
	}
	if( min == max ) {
		return( min );
	}
 
        return( min + parseInt( Math.random() * ( max-min+1 ) ) );
}

	// Register plugin
	tinymce.PluginManager.add('Wpg3Tags', tinymce.plugins.Wpg3Tags);
})();