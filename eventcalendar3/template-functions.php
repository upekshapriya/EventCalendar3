<?php
/*
Copyright (c) 2005-2008, Alex Tingle.

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


/** Report an error if EventCalendar not yet installed. */
function ec3_check_installed($title)
{
  global $ec3;
  if(!$ec3->event_category)
  {?>
    <div style="background-color:black; color:red; border:2px solid red; padding:1em">
     <div style="font-size:large"><?php echo $title; ?></div>
     <?php _e('You must choose an event category.','ec3'); ?>
     <a style="color:red; text-decoration:underline" href="<?php echo
       get_option('home');?>/wp-admin/options-general.php?page=ec3_admin">
      <?php _e('Go to Event Calendar Options','ec3'); ?>
     </a>
    </div>
   <?php
  }
  return $ec3->event_category;
}


/** Substitutes placeholders like '%key%' in $format with 'value' from $data
 *  array. */
function ec3_format_str($format,$data)
{
  foreach($data as $k=>$v)
      $format=str_replace("%$k%",$v,$format);
  return $format;
}


define('EC3_DEFAULT_TEMPLATE_EVENT','<a href="%LINK%">%TITLE% (%TIME%)</a>');
define('EC3_DEFAULT_TEMPLATE_DAY',  '%DATE%:');
define('EC3_DEFAULT_DATE_FORMAT',   'j F');
define('EC3_DEFAULT_TEMPLATE_MONTH','');
define('EC3_DEFAULT_MONTH_FORMAT',  'F Y');


define('EC3_DEFAULT_FORMAT_SINGLE',
       '<tr class="%2$s"><td colspan="3">%1$s</td></tr>');
define('EC3_DEFAULT_FORMAT_RANGE',
       '<tr class="%4$s"><td class="ec3_start">%1$s</td>'
         . '<td class="ec3_to">%3$s</td><td class="ec3_end">%2$s</td></tr>');
define('EC3_DEFAULT_FORMAT_WRAPPER','<table class="ec3_schedule">%s</table>');

/** Echos the schedule for the current post. */
function ec3_the_schedule(
  $format_single =EC3_DEFAULT_FORMAT_SINGLE,
  $format_range  =EC3_DEFAULT_FORMAT_RANGE,
  $format_wrapper=EC3_DEFAULT_FORMAT_WRAPPER
)
{
  echo ec3_get_schedule($format_single,$format_range,$format_wrapper);
}

?>
