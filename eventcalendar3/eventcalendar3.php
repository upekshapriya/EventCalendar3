<?php
/*
Plugin Name: Event Calendar
Version: 3.2.beta3
Plugin URI: http://wpcal.firetree.net
Description: Manage future events as an online calendar. Display upcoming events in a dynamic calendar, on a listings page, or as a list in the sidebar. You can subscribe to the calendar from iCal (OSX) or Sunbird. Change settings on the <a href="options-general.php?page=ec3_admin">Event Calendar Options</a> screen.
Author: Alex Tingle
Author URI: http://blog.firetree.net/
*/

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

require_once(dirname(__FILE__).'/options.php');
require_once(dirname(__FILE__).'/date.php');
require_once(dirname(__FILE__).'/template-functions.php');
require_once(dirname(__FILE__).'/template-functions-new.php');
require_once(dirname(__FILE__).'/admin.php');
require_once(dirname(__FILE__).'/tz.php');
require_once(dirname(__FILE__).'/widget-calendar.php');
require_once(dirname(__FILE__).'/widget-list.php');


$ec3_today_id=str_replace('_0','_',ec3_strftime("ec3_%Y_%m_%d"));


function ec3_action_init()
{
  add_feed('ical','ec3_do_feed_ical');
  add_feed('ec3xml','ec3_do_feed_ec3xml');
}


function ec3_do_feed_ical()
{
  load_template( dirname(__FILE__).'/feed-ical.php' );
}


function ec3_do_feed_ec3xml()
{
  load_template( dirname(__FILE__).'/feed-ec3xml.php' );
}


/** Read the schedule table for the posts, and add an ec3_schedule array
 * to each post. */
function ec3_filter_the_posts($posts)
{
  if('array'!=gettype($posts) || 0==count($posts))
    return $posts;

  $post_ids=array();
  // Can't use foreach, because it gets *copies* (in PHP<5)
  for($i=0; $i<count($posts); $i++)
  {
    $post_ids[]=intval($posts[$i]->ID);
    $posts[$i]->ec3_schedule=array();
  }
  global $ec3,$wpdb;
  $sql="SELECT *,IF(end>='$ec3->today',1,0) AS active
        FROM $ec3->schedule
        WHERE post_id IN (".implode(',',$post_ids).")
        ORDER BY start";
  $key = md5($sql);
  $schedule = wp_cache_get($key,'ec3');
  if($schedule===FALSE)
  {
    $schedule = $wpdb->get_results($sql);
    wp_cache_add($key,$schedule,'ec3');
  }
  // Flip $post_ids so that it maps post ID to position in the $posts array.
  $post_ids=array_flip($post_ids);
  if($post_ids && $schedule)
      foreach($schedule as $s)
      {
        $i=$post_ids[$s->post_id];
        $posts[$i]->ec3_schedule[]=$s;
      }
  return $posts;
}


function ec3_action_wp_head()
{
  require(dirname(__FILE__).'/wp-head.php');
}


/** Turn OFF advanced mode when we're in the admin screens. */
function ec3_action_admin_head()
{
  global $ec3;
  $ec3->advanced=false;
}


/** In advanced mode, exclude events from the archive. */
function ec3_filter_getarchives_where($where)
{
  global $ec3,$wpdb;
  if($ec3->advanced)
    $where.=" AND ec3_sch.post_id IS NULL ";
  return $where;
}


/** In advanced mode, exclude events from the archive. */
function ec3_filter_getarchives_join($join)
{
  global $ec3;
  if($ec3->advanced)
    $join.=" LEFT JOIN $ec3->schedule ec3_sch ON ec3_sch.post_id=id ";
  return $join;
}


/** In advanced mode, exclude events from the archive,
 *  Otherwise, disbale EC's normal query filtering, for archive links. */
function ec3_filter_get_archives_link($link_html)
{
  global $ec3;
  $re='/(<a[^>]* href=[\'"]|<option[^>]* value=[\'"])([^\'"]+)([\'"])/';
  if(preg_match($re,$link_html,$match))
  {
    if($ec3->advanced)
      $listing = 'posts';
    else
      $listing = 'disable';

    if(strpos($match[2],'?')===FALSE)
      $replace="\\1\\2?ec3_listing=$listing\\3";
    else
      $replace="\\1\\2&amp;ec3_listing=$listing\\3";
    $link_html=preg_replace($re,$replace,$link_html); 
  }
  return $link_html;
}


/** Rewrite date restrictions if the query is day- or category- specific. */
function ec3_filter_posts_where($where)
{
  global $ec3,$wpdb;

  if($ec3->query->is_page || $ec3->query->is_single || $ec3->query->is_admin)
      return $where;

  $listing = ec3_get_listing();
  
  if($listing=='D'): // disable event calendar's query filtering.

      return $where;

  elseif($listing=='P'): // posts-only

     // Hide all events (same as last branch)
     $where.=" AND ec3_sch.post_id IS NULL ";
     $ec3->join_ec3_sch=true;

  elseif($ec3->query->is_date):

     // Transfer events' 'post_date' restrictions to 'start'
     $df='YEAR|MONTH|DAYOFMONTH|HOUR|MINUTE|SECOND|WEEK'; // date fields
     $re="/ AND (($df)\($wpdb->posts\.post_date(,[^\)]+)?\) *= *('[^']+'|\d+\b))/i";
     if(preg_match_all($re,$where,$matches)):
       $where_post_date = implode(' AND ',$matches[1]);

       // rdate/rtime should be between start..end:
       $year_num = intval(date('Y'));
       $sdateobj = new ec3_Date($year_num,1,1);
       $edateobj = new ec3_Date($year_num,12,0);
       $stime = array('00','00','00');
       $etime = array('23','59','59');
       for($i=0; $i<count($matches[1]); $i++)
       {
         $num = intval( str_replace("'",'',$matches[4][$i]) );
         if(          'YEAR'==$matches[2][$i])
           $sdateobj->year_num = $edateobj->year_num = $num;
         elseif(     'MONTH'==$matches[2][$i])
           $sdateobj->month_num = $edateobj->month_num = $num;
         elseif('DAYOFMONTH'==$matches[2][$i])
           $sdateobj->day_num = $edateobj->day_num = $num;
         elseif(      'HOUR'==$matches[2][$i])
           $stime[0] = $etime[0] = zeroise($num,2);
         elseif(    'MINUTE'==$matches[2][$i])
           $stime[1] = $etime[1] = zeroise($num,2);
         elseif(    'SECOND'==$matches[2][$i])
           $stime[2] = $etime[2] = zeroise($num,2);
       }

       // If the end day num has not been set, then choose the month's last day.
       if($edateobj->day_num<1)
       {
         $edateobj->day_num = 1;
         $edateobj->day_num = $edateobj->days_in_month();
       }

       $where_start=
         sprintf("start<='%1\$s' AND end>='%2\$s'",
           $edateobj->to_mysqldate().' '.implode(':',$etime),
           $sdateobj->to_mysqldate().' '.implode(':',$stime)
         );

       $where=preg_replace($re,'',$where);
       if($listing=='E'):                                       // EVENTS only
         $where.=" AND ($where_start) ";
       else:                                                    // ALL
         $is_post='ec3_sch.post_id IS NULL';
         $where.=" AND (($where_post_date AND $is_post) OR "
                     . "($where_start AND NOT $is_post)) ";
       endif;
       $ec3->order_by_start=true;
       $ec3->join_ec3_sch=true;
     endif;

  elseif($ec3->is_date_range):

     $w=array();
     if( !empty($ec3->range_from) )
       $w[] = '%2$s' . ">='$ec3->range_from'";
     if( !empty($ec3->range_before) )
       $w[] = '%1$s' . "<='$ec3->range_before'";

     if(!empty($w)):
       $ws = implode(' AND ',$w);
       $where_start = sprintf($ws,'ec3_sch.start','ec3_sch.end');
       if($listing=='E'):                                       // EVENTS only
         $where.=" AND ($where_start) ";
       else:                                                    // ALL
         $pd = "$wpdb->posts.post_date";
         $where_post_date = sprintf($ws,$pd,$pd);
         $is_post = 'ec3_sch.post_id IS NULL';
         $where.=" AND (($where_post_date AND $is_post) OR "
                     . "($where_start AND NOT $is_post)) ";
       endif;
       $ec3->order_by_start=true;
       $ec3->join_ec3_sch=true;
     endif;

  elseif($ec3->advanced):

      if($listing=='E'):                                       // EVENTS only

          // Hide inactive events
          $where.=" AND ec3_sch.post_id IS NOT NULL ";
          $ec3->join_ec3_sch=true;
          $ec3->join_only_active_events=true;
          $ec3->order_by_start=true;
          global $wp;
          $wp->did_permalink=false; // Allows zero results without -> 404

      elseif($ec3->query->is_search):

          $where.=' AND (ec3_sch.post_id IS NULL OR '
                       ."ec3_sch.end>='$ec3->today')";
          $ec3->join_ec3_sch=true;

      else:

          // Hide all events
          $where.=" AND ec3_sch.post_id IS NULL ";
          $ec3->join_ec3_sch=true;

      endif;
  endif;

  return $where;
}

/** */
function ec3_filter_posts_join($join)
{
  global $ec3,$wpdb;
  // The necessary joins are decided upon in ec3_filter_posts_where().
  if($ec3->join_ec3_sch || $ec3->order_by_start)
  {
    $join.=" LEFT JOIN $ec3->schedule ec3_sch ON ec3_sch.post_id=id ";
    if($ec3->join_only_active_events)
        $join.="AND ec3_sch.end>='$ec3->today' ";
  }
  return $join;
}

/** Change the order of event listings (only advanced mode). */
function ec3_filter_posts_orderby($orderby)
{
  global $ec3, $wpdb;
  if($ec3->order_by_start)
  {
    $regexp="/(?<!DATE_FORMAT[(])\b$wpdb->posts\.post_date\b( DESC\b| ASC\b)?/i";
    if(preg_match($regexp,$orderby,$match))
    {
      if($match[1] && $match[1]==' DESC')
        $orderby=preg_replace($regexp,'ec3_start',$orderby);
      else
        $orderby=preg_replace($regexp,'ec3_start DESC',$orderby);
    }
    else
    {
      // Someone's been playing around with the orderby - just overwrite it.
      $orderby='ec3_start';
    }
  }
  return $orderby;
}


/** Eliminate double-listings for posts with >1 scheduled event. */
function ec3_filter_posts_groupby($groupby)
{
  global $ec3,$wpdb;
  if($ec3->join_ec3_sch || $ec3->order_by_start)
  {
    if(empty($groupby))
        $groupby="{$wpdb->posts}.ID";
  }
  return $groupby;
}


/** Add a sched_id field, if we want a listing. */
function ec3_filter_posts_fields($fields)
{
  global $ec3,$wpdb;
  if($ec3->join_ec3_sch || $ec3->order_by_start)
  {
    $fields .=
      ", IF(ec3_sch.post_id IS NULL,$wpdb->posts.post_date,"
      .                            "MIN(ec3_sch.start)) AS ec3_start ";
  }
  return $fields;
}


/** Remove limts when we are making an ec3xml feed. */
function ec3_filter_post_limits($limits)
{
  global $ec3;
  if( $ec3->query->is_feed &&
      $ec3->query->query['feed']=='ec3xml' &&
      $ec3->query->is_date )
  {
    // No limits!! Might be a but risky if the date has many many many posts...
    return '';
  }
  return $limits;
}


function ec3_filter_query_vars($wpvarstoreset)
{
  global $ec3;
  // Backwards compatibility with URLs from old versions of EC.
  if(isset($_GET['ec3_ical']) || isset($_GET['ec3_vcal']))
  {
    ec3_do_feed_ical();
    exit(0);
  }
  if(isset($_GET['ec3_dump']))
    ec3_filter_query_vars_dump();
  // else...
  $wpvarstoreset[]='ec3_today';
  $wpvarstoreset[]='ec3_days';
  $wpvarstoreset[]='ec3_from'; // ?? Deprecated
  $wpvarstoreset[]='ec3_after';
  $wpvarstoreset[]='ec3_before';
  $wpvarstoreset[]='ec3_listing';
  // Turn-off broken canonical redirection when both m= & cat= are set.
  if(isset($_GET['m']) && isset($_GET['cat']))
    remove_action('template_redirect','redirect_canonical');
  return $wpvarstoreset;
}


/** Test function. Helps to diagnose problems.
 * The output from this feature has been chosen to NOT reveal any private
 * information, yet be of real use for debugging.
 */
function ec3_filter_query_vars_dump($wpvarstoreset=NULL)
{
  global $ec3, $wpdb;
  echo "<pre>\n";
  echo "POSTS:\n";
  print_r( $wpdb->get_results(
    "SELECT ID,post_date,post_date_gmt,post_status,post_name,post_modified,
       post_modified_gmt,post_type
     FROM $wpdb->posts ORDER BY ID"
  ));
  if($ec3->wp_have_categories)
  {
    echo "POST2CAT:\n";
    print_r($wpdb->get_results("SELECT * FROM $wpdb->post2cat ORDER BY post_id"));
  }
  echo "EC3_SCHEDULE:\n";
  print_r($wpdb->get_results("SELECT * FROM $ec3->schedule ORDER BY post_id"));
  echo "EC3 OPTIONS:\n";
  print_r($wpdb->get_results(
    "SELECT option_name,option_value
     FROM $wpdb->options WHERE option_name LIKE 'ec3_%'"
  ));
  echo "ACTIVE PLUGINS:\n";
  print_r( $wpdb->get_var(
    "SELECT option_value
     FROM $wpdb->options WHERE option_name='active_plugins'"
  ));
  echo "</pre>\n";
  exit(0);
}


/** Add support for new query vars:
 *
 *  - ec3_today : sets date to today.
 *  - ec3_days=N : Finds events for the next N days.
 *  - ec3_after=YYYY-MM-DD : limits search to events on or after YYYY-MM-DD.
 *  - ec3_before=YYYY-MM-DD : limits search to events on or before YYYY-MM-DD.
 */
function ec3_filter_parse_query($wp_query)
{
  global $ec3;
  // query_posts() can be called multiple times. So reset all our variables.
  $ec3->reset_query($wp_query);

  // Deal with EC3-specific parameters.
  if( !empty($wp_query->query_vars['ec3_today']) )
  {
    // Force the value of 'm' to today's date.
    $wp_query->query_vars['m']=ec3_strftime('%Y%m%d');
    $wp_query->is_date=true;
    $wp_query->is_day=true;
    $wp_query->is_month=true;
    $wp_query->is_year=true;
    $ec3->is_today=true;
  }
  else
  {
    if( !empty($wp_query->query_vars['ec3_days']) )
    {
      // Show the next N days.
      $ec3->days=intval($wp_query->query_vars['ec3_days']);
      $secs=$ec3->days*24*3600;
      $wp_query->query_vars['ec3_after' ]=ec3_strftime('%Y-%m-%d');
      $wp_query->query_vars['ec3_before']=ec3_strftime('%Y-%m-%d',time()+$secs);
    }

    // Get values (if any) for after ($a) & before ($b).
    if( !empty($wp_query->query_vars['ec3_after']) )
        $a=$wp_query->query_vars['ec3_after'];
    else if( !empty($wp_query->query_vars['ec3_from']) )
        $a=$wp_query->query_vars['ec3_from'];
    else
        $a=NULL;

    if( !empty($wp_query->query_vars['ec3_before']) )
        $b=$wp_query->query_vars['ec3_before'];
    else
        $b=NULL;

    if( $a=='today' )
        $a=ec3_strftime('%Y-%m-%d');
    if( $b=='today' )
        $b=ec3_strftime('%Y-%m-%d');

    $re='/\d\d\d\d[-_]\d?\d[-_]\d?\d/';
    if( !empty($a) && preg_match($re,$a) ||
        !empty($b) && preg_match($re,$b) )
    {
      // Kill any other date parameters.
      foreach(array('m','second','minute','hour','day','monthnum','year','w')
              as $param)
      {
        unset($wp_query->query_vars[$param]);
      }
      $wp_query->is_date=false;
      $wp_query->is_time=false;
      $wp_query->is_day=false;
      $wp_query->is_month=false;
      $wp_query->is_year=false;
      $ec3->is_date_range=true;
      $ec3->range_from  =$a;
      $ec3->range_before=$b;
      $wp_query->ec3_listing = 'EVENTS';
    }
  } // end if (today)

  if( !empty($wp_query->query_vars['ec3_listing']) )
  {
    // Over-ride the default $wp_query->ec3_listing.
    $wp_query->ec3_listing = strtoupper($wp_query->query_vars['ec3_listing']);
  }
}


function ec3_filter_redirect_canonical($redirect_url, $requested_url)
{
  if(is_feed() || preg_match('/[?](.*&(amp;)?)?ec3_/',$requested_url))
    return FALSE; // cancel the redirection.
  else
    return $redirect_url;
}


function ec3_filter_the_content($post_content)
{
  global $ec3;
  switch($ec3->show_event_box)
  {
    case 1:  return ec3_get_schedule() . $post_content;
    case 2:  return ec3_get_iconlets() . $post_content;
    default: return $post_content;
  }
}


/** Page (not post) content can contain a tag like [EC3BigCalendar:<options>]
 *  which gets replaced by a full-page calendar. */
function ec3_filter_the_content_bigcal($post_content)
{
  if(is_page())
  {
    $placeholder = '[EC3BigCalendar';
    $pos=strpos($post_content,$placeholder);
    if($pos!==FALSE)
    {
      // Only load the Big Calendar code now that we know we need it.
      require_once(dirname(__FILE__).'/calendar-big.php');
      // Process options.
      $options=array();
      $options['id']='ec3_big_cal';
      $options['num_months']=1;
      $options['day_length']=9;
      $options['show_only_events']=1;
      $re='/\[EC3BigCalendar(:(\w+=\w+(&|&amp;))*(\w+=\w+))?]/i';
      if(preg_match($re,$post_content,$m))
      {
        if(!empty($m[1]))
        {
          $args = strtolower(html_entity_decode($m[1]));
          $options = wp_parse_args(substr($args,1),$options);
        }
        $placeholder = $m[0];
      }
      $calobj = new ec3_BigCalendar($options);
      $calcode = $calobj->generate();
      $post_content = str_replace($placeholder,$calcode,$post_content);
    }
  }
  return $post_content;
}


/** Replaces default wp_trim_excerpt filter. Fakes an excerpt if needed.
 *  Adds a textual summary of the schedule to the excerpt.*/
function ec3_get_the_excerpt($text)
{
  global $post;

  if(empty($text))
  {
    $text=$post->post_content;
    if(!$post->ec3_schedule)
        $text=apply_filters('the_content', $text);
    $text=str_replace(']]>', ']]&gt;', $text);
    $text=strip_tags($text);
    $excerpt_length=55;
    $words=explode(' ', $text, $excerpt_length + 1);
    if(count($words) > $excerpt_length)
    {
      array_pop($words);
      array_push($words, '[...]');
      $text=implode(' ', $words);
    }
  }

  if($post->ec3_schedule)
  {
    $schedule=ec3_get_schedule('%s; ',"%1\$s %3\$s %2\$s. ",'[ %s] ');
    $text=$schedule.$text;
  }
  
  return $text;
}


//
// Hook in...
if($ec3->event_category)
{
  add_action('init',              'ec3_action_init');
  add_action('wp_head',           'ec3_action_wp_head',13);
  add_action('admin_head',        'ec3_action_admin_head');
  add_filter('query_vars',        'ec3_filter_query_vars');
  add_filter('parse_query',       'ec3_filter_parse_query');
  add_filter('posts_where',       'ec3_filter_posts_where',11);
  add_filter('posts_join',        'ec3_filter_posts_join');
  add_filter('posts_groupby',     'ec3_filter_posts_groupby');
  add_filter('posts_fields',      'ec3_filter_posts_fields');
  add_filter('post_limits',       'ec3_filter_post_limits');
  add_filter('the_posts',         'ec3_filter_the_posts');
  add_filter('get_archives_link', 'ec3_filter_get_archives_link');
  add_filter('the_content',       'ec3_filter_the_content_bigcal');
  add_filter('redirect_canonical','ec3_filter_redirect_canonical',10,2);
  
  if($ec3->show_event_box)
    add_filter('the_content','ec3_filter_the_content',20);
  
  remove_filter('get_the_excerpt', 'wp_trim_excerpt');
  add_filter('get_the_excerpt', 'ec3_get_the_excerpt');
  
  if($ec3->advanced)
  {
    add_filter('posts_orderby','ec3_filter_posts_orderby',11);
    // In advanced mode, exclude events from the archive.
    add_filter('getarchives_join', 'ec3_filter_getarchives_join');
    add_filter('getarchives_where','ec3_filter_getarchives_where');
  }
}

?>
