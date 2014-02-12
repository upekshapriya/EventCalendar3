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

if(version_compare(phpversion(),'5.0')<0)
  require_once(dirname(__FILE__).'/clone.php');

/**

Glossary:

 date         - string in the form "YYYY-MM-DD" (same as MySQL)
 datetime, m  - string in the form "YYYY-MM-DD HH-mm-ss" (same as MySQL)
 unixtime, u  - integer, seconds since the epoch.
 day_id       - string in the form "ec3_YYYY_MM_DD"
 month_id     - string in the form "ec3_YYYY_MM"
 dateobj, dob - ec3_Date object.

 */


class ec3_CalendarDay
{
  var $date; // MySQL date "YYYY-MM-DD"
  var $_posts;
  var $_events;
  var $_events_allday;

  function ec3_CalendarDay($date)
  {
    $this->date = $date;
    $this->_posts = array();
    $this->_events = array();
    $this->_events_allday = array();
  }
  
  function add_post(&$post)
  {
    $this->_posts[] = $post;
  }
  
  function add_event(&$event)
  {
    if(empty($event->allday))
      $this->_events[] = $event;
    else
      $this->_events_allday[] = $event;
  }
  
  function has_events()
  {
    return !( empty($this->_events_allday) && empty($this->_events) );
  }

  function has_posts()
  {
    return !empty($this->_posts);
  }

  function is_empty()
  {
    return !( $this->has_posts() || $this->has_events() );
  }

  function iter_events_allday()
  {
    global $ec3;
    $ec3->events = $this->_events_allday;
    return new ec3_EventIterator();
  }

  function iter_events()
  {
    global $ec3;
    $ec3->events = $this->_events;
    return new ec3_EventIterator();
  }

  // ... More goes here

};


/** Calendar class, used for rendering calendars. */
class ec3_BasicCalendar
{
  // OPTIONS

  /** The unique ID for this calendar. DEFAULT='ec3_basic_calendar' */
  var $id;
  /** Number to months displayed by get_calendar(). DEFAULT=1 */
  var $num_months;
  /** Show only events in calendar. DEFAULT=false */
  var $show_only_events;

  // MEMBER VARIABLES

  /** First date covered by this calendar (always 1st of the month).
   *  An ec3_Date object. */
  var $begin_dateobj;
  /** Next date AFTER the range covered by this calendar (always 1st of the
   *  month). An ec3_Date object. */
  var $limit_dateobj;

  /** Map of ec3_CalendarDay objects. */
  var $_days = false;
  
  /** Status variable used during calendar generation. Points to the
   *  current ec3_CalendarDay object (if any). */
  var $dayobj;

  /** Status variable used during calendar generation. Points to the
   *  current ec3_Date object (if any). */
  var $dateobj;

  /** $month_date is a string of the form "YYYY-MM..." */
  function ec3_BasicCalendar($options=false,$month_date=false)
  {
    // Set options from the $options array, if it's been provided.
    // Otherwise set the defaults from the old, global WP options.
    if(empty($options))
      $options=array();

    if(array_key_exists('id',$options))
      $this->id = strip_tags(stripslashes($options['id']));
    else
      $this->id ='ec3_basic_calendar';

    if(array_key_exists('num_months',$options))
      $this->num_months = $options['num_months'];
    else
      $this->num_months =max(1,abs(intval(get_option('ec3_num_months'))));

    if(array_key_exists('show_only_events',$options))
      $this->show_only_events = $options['show_only_events'];
    else
      $this->show_only_events=intval(get_option('ec3_show_only_events'));
    // END OPTIONS

    if(empty($month_date))
    {
      $this->begin_dateobj = new ec3_Date(); // defaults to the current month
    }
    else
    {
      $parts=explode('-',$month_date);
      $year_num =intval($parts[0]);
      $month_num=intval($parts[1]);
      $this->begin_dateobj = new ec3_Date($year_num,$month_num,1);
    }
    $this->limit_dateobj =$this->begin_dateobj->plus_months($this->num_months);
    $this->_days = array();
  }
  
  function &_get_day($mysqldate)
  {
    if(!isset($this->_days[$mysqldate]))
        $this->_days[$mysqldate] = new ec3_CalendarDay($mysqldate);;
    return $this->_days[$mysqldate];
  }
  
  function add_posts(&$query,$include_event_posts=false)
  {
    foreach($query->posts as $p)
    {
      if($include_event_posts || empty($p->ec3_schedule))
      {
        $day =& $this->_get_day( substr($p->post_date,0,10) );
        $day->add_post($p);
      }
    }
  }

  function add_events(&$query)
  {
    foreach($query->posts as $p)
    {
      if(empty($p->ec3_schedule))
        continue;
      $begin_datetime = $this->begin_dateobj->to_mysqldate();
      foreach($p->ec3_schedule as $event)
      {
        $dob       = ec3_mysql2date( max($event->start,$begin_datetime) );
        // Find $limit_dob - the day after the end of this event.
        $limit_dob = ec3_mysql2date( $event->end );
        $limit_dob->increment_day();
        if($this->limit_dateobj->less_than($limit_dob))
          $limit_dob = $this->limit_dateobj;
        // Loop over all the days of this event.
        for( ; $dob->less_than($limit_dob); $dob->increment_day() )
        {
          $day =& $this->_get_day($dob->to_mysqldate());
          $day->add_event($event);
        }
      }
    }
  }

  function wrap_month($monthstr)
  {
    return $this->dateobj->month_name().' '.$this->dateobj->year_num."\n"
           . $monthstr."\n";
  }
  
  function wrap_week($weekstr)
  {
    return $weekstr."\n";
  }
  
  function make_pad($num_days,$is_start_of_month)
  {
    return substr('                              ',0,$num_days*3);
  }
  
  /** dayobj - ec3_CalendarDay object, may be empty. */
  function wrap_day($dayarr)
  {
    return implode(', ',$dayarr).' ';
  }

  /** dayobj - ec3_CalendarDay object, may be empty.
   *  This function returns an array of strings, one for each event or post. */
  function make_day()
  {
    global $ec3;
    $result = array();
    if(!empty($this->dayobj))
    {
      for($evt=$this->dayobj->iter_events_allday(); $evt->valid(); $evt->next())
          $result[] = $this->make_event_allday($ec3->event);

      for($evt=$this->dayobj->iter_events(); $evt->valid(); $evt->next())
          $result[] = $this->make_event($ec3->event);

      foreach($this->dayobj->_posts as $p)
      {
        global $post;
        $post = get_post($p->ID);
        setup_postdata($post);
        $result[] = $this->make_post($post);
      }
    }
    return $result;
  }

  function make_event_allday(&$event)
  {
    return $this->make_event($event);
  }

  function make_event(&$event)
  {
    return get_the_title();
  }

  function make_post(&$post)
  {
    return get_the_title();
  }

  function generate()
  {
    $result='';
    $curr_dateobj = $this->begin_dateobj;
    while($curr_dateobj->less_than($this->limit_dateobj))
    {
      $days_in_month =$curr_dateobj->days_in_month();
      $week_day=( $curr_dateobj->week_day() + 7 - intval(get_option('start_of_week')) ) % 7;
      $col =0;

      $monthstr= '';
      $weekstr = '';

      while(True)
      {
        $this->dateobj = clone($curr_dateobj);
        if($col>6)
        {
          $monthstr .= $this->wrap_week($weekstr,$curr_dateobj);
          $weekstr = '';
          $col=0;
        }
        if($col<$week_day)
        {
          // insert padding
          $weekstr .= $this->make_pad( $week_day - $col, true );
          $col=$week_day;
        }
        // insert day
        $datetime    =  $curr_dateobj->to_mysqldate();
        $this->dayobj=  $this->_days[$datetime]; // might be empty
        $dayarr      =  $this->make_day();
        $weekstr     .= $this->wrap_day($dayarr);

        $col++;
        $curr_dateobj->increment_day();
        if(1==$curr_dateobj->day_num)
            break;
        $week_day=($week_day+1) % 7;
      }
      // insert padding
      $weekstr .= $this->make_pad( 7 - $col, false );
      $monthstr .= $this->wrap_week($weekstr);
      $result .= $this->wrap_month($monthstr);
    }
    ec3_reset_wp_query();
    return $result;
  }

};

