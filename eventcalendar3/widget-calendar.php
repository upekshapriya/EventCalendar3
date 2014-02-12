<?php
/*
Copyright (c) 2006 Darrell Schulte, 2008 Alex Tingle.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


/** Event Calendar widget. */
function ec3_widget_cal($args) 
{
  extract($args);
  $options = get_option('ec3_widget_cal');
  echo $before_widget . $before_title;
  echo ec3_widget_title($options['title'],'Event Calendar');
  echo $after_title;
  if(ec3_check_installed('Event-Calendar'))
  {
    require_once(dirname(__FILE__).'/calendar-sidebar.php');
    global $ec3;
    $calobj = new ec3_SidebarCalendar($options);
    echo $calobj->generate();
  }
  echo $after_widget;
}


/** Event Calendar widget - control. */
function ec3_widget_cal_control() 
{
  $options = $newopts = get_option('ec3_widget_cal');
  if( $_POST["ec3_cal_submit"] ) 
  {
    $newopts['title']=strip_tags(stripslashes($_POST["ec3_cal_title"]));
    $newopts['num_months']      =max(1,intval($_POST["ec3_cal_num_months"]));
    $newopts['day_length']      =abs(intval($_POST["ec3_cal_day_length"]));
    $newopts['navigation']      =intval($_POST["ec3_cal_navigation"]);
    $newopts['show_only_events']=!empty($_POST["ec3_cal_show_only_events"]);
    $newopts['hide_logo']       =empty($_POST["ec3_cal_show_logo"]);
    $newopts['disable_popups']  =empty($_POST["ec3_cal_show_popups"]);
  }
  if( $options != $newopts ) 
  {
    $options = $newopts;
    update_option('ec3_widget_cal', $options);
  }
  require_once(dirname(__FILE__).'/calendar-sidebar.php');
  $title = ec3_widget_title($options['title'],'Event Calendar');
  $cal = new ec3_SidebarCalendar($options); // Use this to get defaults.
  ?>
  <p>
   <label for="ec3_cal_title">
    <?php _e('Title:') ?><br />
    <input class="widefat" id="ec3_cal_title" name="ec3_cal_title"
     type="text" value="<?php echo htmlspecialchars($title,ENT_QUOTES); ?>" />
   </label>
  </p>
  <p>
   <label for="ec3_cal_num_months">
    <?php _e('Number of months','ec3') ?>:<br />
    <input class="widefat" id="ec3_cal_num_months" name="ec3_cal_num_months"
     type="text" value="<?php echo $cal->num_months ?>" />
   </label>
  </p>
  <p>
   <label for="ec3_cal_day_length">
    <?php _e('Show day names as','ec3') ?>:<br />
    <select name="ec3_cal_day_length">
     <option value='1'<?php if($cal->day_length<3) echo " selected='selected'" ?> >
      <?php _e('Single Letter','ec3'); ?>
     </option>
     <option value='3'<?php if(3==$cal->day_length) echo " selected='selected'" ?> >
      <?php _e('3-Letter Abbreviation','ec3'); ?>
     </option>
     <option value='9'<?php if($cal->day_length>3) echo " selected='selected'" ?> >
      <?php _e('Full Day Name','ec3'); ?>
     </option>
    </select>
   </label>
  </p>
  <p>
   <label for="ec3_cal_navigation"
    title="<?php _e('The navigation links are more usable when they are above the calendar, but you might prefer them below or hidden for aesthetic reasons.','ec3') ?>">
    <?php _e('Position of navigation links','ec3') ?>:<br />
    <select name="ec3_navigation">
     <option value='0'<?php if(0==!$cal->navigation) echo " selected='selected'" ?> >
      <?php _e('Above Calendar','ec3'); ?>
     </option>
     <option value='1'<?php if(1==$cal->navigation) echo " selected='selected'" ?> >
      <?php _e('Below Calendar','ec3'); ?>
     </option>
     <option value='2'<?php if(2==$cal->navigation) echo " selected='selected'" ?> >
      <?php _e('Hidden','ec3'); ?>
     </option>
    </select>
   </label>
  </p>
  <p>
   <label for="ec3_cal_show_only_events">
    <input type="checkbox" value="1" id="ec3_cal_show_only_events" name="ec3_cal_show_only_events"
     <?php if($cal->show_only_events) echo " checked='checked'" ?>  />
    <?php _e('Only Show Events','ec3') ?>.
   </label>
  </p>
  <p>
   <label for="ec3_cal_show_logo">
    <input type="checkbox" value="1" id="ec3_cal_show_logo" name="ec3_cal_show_logo"
     <?php if(!$cal->hide_logo) echo " checked='checked'" ?>  />
    <?php echo sprintf(__('Show %s logo','ec3'),'Event-Calendar') ?>.
   </label>
  </p>
  <p>
   <label for="ec3_cal_show_popups"
    title="<?php _e('You might want to disable popups if you use Nicetitles.','ec3'); ?>">
    <input type="checkbox" value="1" id="ec3_cal_show_popups" name="ec3_cal_show_popups"
     <?php if(!$cal->disable_popups) echo " checked='checked'" ?>  />
    <?php _e('Popup event lists','ec3') ?>.
   </label>
  </p>

  <input type="hidden" name="ec3_cal_submit" value="1" />
  <?php
}


function ec3_action_widgets_init_cal() 
{
  if(!function_exists('wp_register_sidebar_widget'))
    return;

  // Event Calendar widget
  wp_register_sidebar_widget(
    'event-calendar',
    __('Event Calendar','ec3'),
    'ec3_widget_cal', 
    array('description' =>
          __( 'Display upcoming events in a dynamic calendar.','ec3')
              . ' (Event-Calendar '. __('Plugin') .')' ) 
  );
  register_widget_control('event-calendar','ec3_widget_cal_control');
}


add_action('widgets_init', 'ec3_action_widgets_init_cal');

?>
