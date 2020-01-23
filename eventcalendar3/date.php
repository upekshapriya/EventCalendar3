<?php
/*
Copyright (c) 2005, Alex Tingle.  $Revision: 287 $

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

/** Date class. Encapsulates all functionality concerning dates - 
 *  how many days in each month, leap years, days of the week, locale
 *  names etc. */
class ec3_Date
{
  var $year_num =0;
  var $month_num=0;
  var $day_num  =1;
  var $_unixdate      =0;
  var $_days_in_month =0;

  function __construct($year_num=0,$month_num=0,$day_num=0)
  {
    global $ec3;
    if($year_num>0)
    {
      $this->year_num =$year_num;
      $this->month_num=$month_num;
      $this->day_num  =$day_num;
    }
    if(0==$this->year_num && is_single())
        $this->_from_single();
    if(0==$this->year_num && $ec3->is_date_range)
        $this->_from_date_range();
    if(0==$this->year_num)
        $this->_from_date(); // Falls back to today.
  }

  /** Helper function, only called by the constructor. Calculates the value of
   *  month/year for the current single post. */
  function _from_single()
  {
    global $ec3;
    if($ec3->query->posts && $ec3->query->posts[0]->ec3_schedule)
    {
      $this->year_num=
        intval(mysql2date('Y',$ec3->query->posts[0]->ec3_schedule[0]->start));
      $this->month_num=
        intval(mysql2date('m',$ec3->query->posts[0]->ec3_schedule[0]->start));
    }
  }

  /** Helper function, only called by the constructor. Calculates the value of
   *  month/year for the current query's 'ec3_before/after'. */
  function _from_date_range()
  {
    global $ec3;
    if(!empty($ec3->range_from))
    {
      $c=explode('-',$ec3->range_from);
      $this->year_num=intval($c[0]);
      $this->month_num=intval($c[1]);
    }
    elseif(!empty($ec3->range_before))
    {
      $c=explode('-',$ec3->range_before);
      $this->year_num=intval($c[0]);
      $this->month_num=intval($c[1]);
    }
  }

  /** Helper function, only called by the constructor. Calculates the value of
   *  month/year for the current page. Code block from
   *  wp-includes/template-functions-general.php (get_calendar function). */
  function _from_date()
  {
    global $wpdb;
    $m = get_query_var('m');
    $year = get_query_var('year');
    $monthnum = get_query_var('monthnum');

    if (isset($_GET['w'])) {
        $w = ''.intval($_GET['w']);
    }

    // Let's figure out when we are
    if (!empty($monthnum) && !empty($year)) {
        $thismonth = ''.zeroise(intval($monthnum), 2);
        $thisyear = ''.intval($year);
    } elseif (!empty($w)) {
        // We need to get the month from MySQL
        $thisyear = ''.intval(substr($m, 0, 4));
        $d = (($w - 1) * 7) + 6; //it seems MySQL's weeks disagree with PHP's
        $thismonth = $wpdb->get_var("SELECT DATE_FORMAT((DATE_ADD('${thisyear}0101', INTERVAL $d DAY) ), '%m')");
    } elseif (!empty($m)) {
//        $calendar = substr($m, 0, 6);
        $thisyear = ''.intval(substr($m, 0, 4));
        if (strlen($m) < 6) {
            $thismonth = '01';
        } else {
            $thismonth = ''.zeroise(intval(substr($m, 4, 2)), 2);
        }
    } else {
        $thisyear=ec3_strftime("%Y");
        $thismonth=ec3_strftime("%m");
    }
    
    $this->year_num =intval($thisyear);
    $this->month_num=intval($thismonth);
    $this->day_num  =1;
  }
  
  /** Month arithmetic. Returns a new date object. */
  function plus_months($month_count)
  {
    $result=new ec3_Date($this->year_num,$this->month_num,$this->day_num);
    $result->month_num += $month_count;
    if($month_count>0)
    {
      while($result->month_num>12)
      {
        $result->month_num -= 12;
        $result->year_num++;
      }
    }
    else
    {
      while($result->month_num<1)
      {
        $result->month_num += 12;
        $result->year_num--;
      }
    }
    return $result;
  }
  /** Convenience function for accessing plus_months(). */
  function prev_month() { return $this->plus_months(-1); }
  function next_month() { return $this->plus_months( 1); }
  
  /** Modifies the current object to be one day in the future. */
  function increment_day()
  {
    $this->day_num++;
    if($this->day_num > $this->days_in_month())
    {
      $this->day_num=1;
      $this->month_num++;
      if($this->month_num>12)
      {
        $this->month_num=1;
        $this->year_num++;
      }
      $this->_days_in_month=0;
    }
    $this->_unixdate=0;
  }
  
  /** Obtain the month ID for this date: e.g. ec3_2005_06 */
  function month_id()
  {
    return 'ec3_' . $this->year_num . '_' . $this->month_num;
  }
  
  /** Obtain the day ID for this date: e.g. ec3_2005_06_25 */
  function day_id()
  {
    $result='ec3_'.$this->year_num.'_'.$this->month_num.'_'.$this->day_num;
    global $ec3_today_id;
    if($result==$ec3_today_id)
      return 'today';
    else
      return $result;
  }

  /** Obtain a blog link for this date. */
  function day_link($show_only_events)
  {
    global $ec3,$wp_rewrite;
    $year  = $this->year_num;
    $month = $this->month_num;
    $day   = $this->day_num;
    // Nabbed from get_day_link()...
//    $daylink = $wp_rewrite->get_day_permastruct();
//    if ( !empty($daylink) ) {
//      $daylink = str_replace('%year%', $year, $daylink);
//      $daylink = str_replace('%monthnum%', zeroise(intval($month), 2), $daylink);
//      $daylink = str_replace('%day%', zeroise(intval($day), 2), $daylink);
//      $daylink = apply_filters('day_link', get_option('home') . user_trailingslashit($daylink, 'day'), $year, $month, $day);
//      if($show_only_events)
//        $daylink .= '?ec3_listing=events';
//    } else {
      $daylink=apply_filters('day_link', get_option('home') . '/?m=' . $year . zeroise($month, 2) . zeroise($day, 2), $year, $month, $day);
      if($show_only_events)
        $daylink .= '&amp;ec3_listing=events';
//    }
    return $daylink;
  }

  /** e.g. June */
  function month_name()
  {
    global $month;
    return $month[zeroise($this->month_num,2)];
  }

  /** e.g. Jun */
  function month_abbrev()
  {
    global $month_abbrev;
    return $month_abbrev[ $this->month_name() ];
  }

  /** Obtain a blog link for this month. */
  function month_link($show_only_events)
  {
    global $ec3,$wp_rewrite;
    $year  = $this->year_num;
    $month = $this->month_num;
    // Nabbed from get_month_link()...
//    $monthlink = $wp_rewrite->get_month_permastruct();
//    if ( !empty($monthlink) ) {
//      $monthlink = str_replace('%year%', $year, $monthlink);
//      $monthlink = str_replace('%monthnum%', zeroise(intval($month), 2), $monthlink);
//      $monthlink = apply_filters('month_link', get_option('home') . user_trailingslashit($monthlink, 'month'), $year, $month);
//      if($show_only_events)
//        $monthlink .= '?ec3_listing=events';
//    } else {
      $monthlink = apply_filters('month_link', get_option('home') . '/?m=' . $year . zeroise($month, 2), $year, $month);
      if($show_only_events)
        $monthlink .= '&amp;ec3_listing=events';
//    }
    return $monthlink;
  }


  function days_in_month()
  {
    if(0==$this->_days_in_month)
      $this->_days_in_month=intval(date('t', $this->to_unixdate()));
    return $this->_days_in_month;
  }
  function week_day()
  {
    return intval(date('w', $this->to_unixdate()));
  }
  function to_unixdate()
  {
    if(empty($this->_unixdate))
    {
      $this->_unixdate =
        mktime(0,0,0, $this->month_num,$this->day_num,$this->year_num);
    }
    return $this->_unixdate;
  }
  function to_mysqldate()
  {
    return "$this->year_num-"
     . zeroise($this->month_num,2) . '-'
     . zeroise($this->day_num,2);
  }
  /** Returns TRUE if $this and $dateobj refer to the same date. */
  function equals($dateobj)
  {
    return(
      $this->day_num   == $dateobj->day_num &&
      $this->month_num == $dateobj->month_num &&
      $this->year_num  == $dateobj->year_num
    );
  }
  /** Returns TRUE if $this is earlier than $dateobj. */
  function less_than($dateobj)
  {
    if( $this->year_num != $dateobj->year_num )
        return( $this->year_num < $dateobj->year_num );
    elseif( $this->month_num != $dateobj->month_num )
        return( $this->month_num < $dateobj->month_num );
    else
        return( $this->day_num < $dateobj->day_num );
  }
} // end class ec3_Date


/** Converts a MySQL date object into an EC3 date object. */
function ec3_mysql2date(&$mysqldate)
{
  $as_arr=explode( '-', substr($mysqldate,0,10) );
  return new ec3_Date(intval($as_arr[0]),intval($as_arr[1]),intval($as_arr[2]));
}

/** Converts a day or month Id to a PHP date (Unix timestamp). */
function ec3_dayid2php(&$id)
{
  $parts=explode('_',$id);
  $year =intval($parts[1]);
  $month=intval($parts[2]);
  $day  =(count($parts)>=4? intval($parts[3]): 1);
  return mktime( 0,0,0, $month,$day,$year);
}

?>
