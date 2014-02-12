<?php
/*
Copyright (c) 2008, Alex Tingle.

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

require_once(dirname(__FILE__).'/calendar-sidebar.php');

/** Renders a big calendar. */
class ec3_BigCalendar extends ec3_SidebarCalendar
{
  function ec3_BigCalendar($options=false,$datetime=0)
  {
    // Initialise the parent class.
    $this->ec3_SidebarCalendar($options,$datetime);
  }
  
  /** dayobj - ec3_CalendarDay object, may be empty. */
  function wrap_day($dayarr)
  {
    $day_id = $this->dateobj->day_id();

    $td_classes = array();
    if($day_id=='today')
      $td_classes[] = 'ec3_today';
    if(!empty($this->dayobj))
    {
      $td_classes[] = 'ec3_postday';
      if($this->dayobj->has_events())
        $td_classes[] = 'ec3_eventday';
    }

    $td_id = $this->id.'-'.$day_id;
    if(empty($td_classes))
      $result = "\n\t  <td id='$td_id'>\n\t    ";
    else
      $result = "\n\t  <td id='$td_id' class='".implode(' ',$td_classes)
                . "'>\n\t    ";

    if(empty($this->dayobj))
      $result .= '<span class="ec3_daynum">'.$this->dateobj->day_num.'</span>';
    else
      $result .= '<a class="ec3_daynum" href="'
                 . $this->dateobj->day_link($this->show_only_events)
		 . '">'.$this->dateobj->day_num.'</a>';

    $result .= '<div>'.implode("\n",$dayarr).'</div>';
    $result .= "</td>";
    return $result;
  }

  function make_event(&$event)
  {
    if($this->dayobj->date == substr($event->start,0,10))
    {
      $title = ec3_get_start_time(); // same day
      if($this->dayobj->date == substr($event->end,0,10))
        $title .= ' - ' . ec3_get_end_time();
      else
        $title .= '...';
    }
    else
    {
      if($this->dayobj->date == substr($event->end,0,10))
        $title = '...' . ec3_get_end_time();
      else
        $title .= '...'.__('all day','ec3').'...';
    }

    return "\n\t    "
      . '<p class="ec3_event"><a title="'.$title
      . '" href="'.get_permalink().'">'.get_the_title().'</a></p>';
  }

  function make_post(&$post)
  {
    return "\n\t    "
      . '<p class="ec3_post"><a href="'.get_permalink().'">'
      . get_the_title().'</a></p>';
  }

  function generate()
  {
    global $ec3;
    $result = parent::generate();

    if(empty($ec3->done_bigcal_javascript))
    {
      $ec3->done_bigcal_javascript=true;
      $result .= "\t<script type='text/javascript' src='"
      .    $ec3->myfiles . "/calendar-big.js'></script>\n";
    }
    $result .=
        "\t<script type='text/javascript'><!--\n"
      . "\t  ec3.calendars['$this->id'].new_day = ec3.big_cal.new_day;\n"
      . "\t  ec3.calendars['$this->id'].update_day = ec3.big_cal.update_day;\n"
      . "\t--></script>\n";
    return $result;
  }

};

?>
