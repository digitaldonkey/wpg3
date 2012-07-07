/* wpg3_default.js */
//  alert ('wpg3_default.js');
jQuery(window).load(function() {
  
  var height = 0;
  var width = 0;
  var blocks = jQuery('.block');
  blocks.each(function(){
    if ( jQuery(this).height() > height ){
      height = jQuery(this).height();
    }
    if ( jQuery(this).width() > width ){
      width = jQuery(this).width();
    }
  });
  blocks.height(height);
  blocks.width(width);

});