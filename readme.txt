=== GWPG3 ===
Tags: gallery, gallery3, wpg3, wpg2
Contributors: digitaldonkey
Requires at least: 3.1
Tested up to: 3.1
Stable tag: Trunk
Donate link: http://donkeymedia.eu/2010/08/26/worpress-multilingual-contactform/

== Description ==
Alpha State development Version of Wordpress Plugin connecting Gallery3 and Wordpress3+ maintaining compatibility to WPG2.

Call for Templates: 
<b>WPG3 has a template System integrated which let you chose the way you display Gallery-Items or Albums. 
Please submit your template Ideas!</b>

For now we miss image-chooser. But it's on the way ;)

Features
 - XHTTP-caching
 - Themeable output (use ANY REST data in your Template)
 - &lt;WPG3&gt; and &lt;/WPG2&gt; tag Support

- Requires PHP5 (like Gallery3)
- Requires Gallery3 REST Module to be enabled and (for now) the REST Option allow_guest_access enabled.

== Installation ==

1. Upload to your plugins folder, usually `wp-content/plugins/`
2. Activate the plugin on the plugin screen.
3. Configure the plugin on it's settings screen. Settings -> WPG3
4. Add the '&lt;WPG3&gt;item/1&lt;/WPG2&gt;' to the body of the post/page in the editors HTML mode to get a full gallery.


== Frequently Asked Questions ==

= How do I get my G3 Homepage =

Make sure you entered /rest/item/1 at "Default Gallery Album" in Options Page.
Add &lt;WPG3&gt;item/1&lt;/WPG2&gt; to the body of the post/page in the editors HTML mode.

== Screenshots ==
1. Gallery3 Page
2. Gallery3 Options

== Changelog ==
= 0.85 =
Integrated Gallery3 Page with Rewrite Suppport (make sure Rewrites are enabled)
= 0.82 =
WPG3-Tag support. Compatible to WPG2-Tags

== Upgrade Notice ==
= 0.85 =
We're getting closer. Please give me some feedback!
= 0.82 =
Template System - enables Templating
