<div class="wrap">
  <h2><?php _e('WPG3 Options', 'wpg3') ?></h2>
  <form name="form1" method="post" action="">
	  <input type="hidden" name="stage" value="process" />
    
    <?php
    
    /*Lets add some default options if they don't exist*/
    add_option('wpg3_g3Url', 'wpg3.local', 'wpg3');

    /*check form submission and update options*/
    if ('process' == $_POST['stage'])
    {
      update_option('wpg3_g3Url', $_POST['wpg3_g3Url']);
    }
    
    /*Get options for form fields*/
    $wpg3_g3Url = stripslashes(get_option('wpg3_g3Url'));
    ?>   
    
    
    
<table width="100%" cellspacing="2" cellpadding="5" class="editform">
      <tr valign="top">
        <th scope="row"><?php _e('Gallery3 Url', 'wpg3') ?></th>
        <td><input name="wpg3_g3Url" type="text" id="wpg3_g3Url" value="<?php echo $wpg3_g3Url; ?>" size="40" />
        <br />
<?php _e('The REST Uri of the G3 Installation<br />The g3-Rest-module need to be enabled and (by now) you need to set enable_guest_access', 'wpg3') ?></td>
      </tr>
     </table>
     
     
   <p class="submit">
      <input type="submit" name="Submit" value="<?php _e('Update Options', 'mlcf') ?> &raquo;" />
    </p>
  </form>
</div>