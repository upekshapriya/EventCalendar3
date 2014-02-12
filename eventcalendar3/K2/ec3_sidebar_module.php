<?php
/*
Copyright (c) 2006, Darrell Schulte.  $Revision: 240 $

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


/** Event Calendar module. */
function ec3_k2mod_cal($args)
{
  extract($args);
  echo $before_module . $before_title . $title . $after_title;
  ec3_get_calendar();
  echo $after_module;
}

register_sidebar_module(
  __('Event Calendar','ec3'),
  'ec3_k2mod_cal', // callback
  'sb-calendar' // class
);


/** Upcoming events module. */
function ec3_k2mod_list($args)
{
  extract($args);
  echo $before_module . $before_title . $title . $after_title;
  ec3_get_events(sbm_get_option('limit'));
  echo $after_module;
}

function ec3_k2mod_list_control()
{
  if(isset($_POST['ec3_module_limit']))
  {
    sbm_update_option('limit', $_POST['ec3_module_limit']);
  }

  ?>
  <p>
   <label for="ec3-module-num-events">
    <?php _e('Number of events:', 'ec3'); ?>
   </label>
   <input id="ec3-module-num-events" name="ec3_module_limit"
    type="text" value="<?php
    echo(sbm_get_option('limit')); ?>" size="4" />
  </p>
  <p>
   <a href="options-general.php?page=ec3_admin">
    <?php _e('Go to Event Calendar Options','ec3') ?>.
   </a>
  </p>
  <?php
}

register_sidebar_module(
  __('Upcoming Events','ec3'),
  'ec3_k2mod_list', // callback
  '', // class
  array('limit' => 5)
);
register_sidebar_module_control(
  __('Upcoming Events','ec3'),
  'ec3_k2mod_list_control'
);

?>
