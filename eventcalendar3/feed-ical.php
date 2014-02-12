<?php
/*
Copyright (c) 2008, Alex Tingle.  $Revision$

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


  /** Quotes iCalendar special characters. */
  function ec3_ical_quote($string)
  {
    $s = preg_replace('/([\\,;])/','\\\\$1',$string);
    $s = preg_replace('/\r?\n/','\\\\n',$s);
    $s = preg_replace('/\r/',' ',$s);
    return $s;
  }


  /** Folds an iCalendar content-line so that it does not exceed
   *  75 characters. Appends CRLF to the end of the line. */
  function ec3_ical_fold($string, $max = 75)
  {
    $result = '';
    $s = $string;
    while(strlen($s)>$max)
    {
      $len = $max;
      while( $len>($max-10) && substr($s,$len-1,1) == '\\' )
        $len --;
      $result .= substr($s,0,$len) . "\r\n\t";
      $s = substr($s,$len);
    }
    $result .= $s;
    return $result."\r\n";
  }


  /** Folds an iCalendar content-line and echos it to stdout */
  function ec3_ical_echo($string, $max = 75)
  {
    echo ec3_ical_fold($string, $max);
  }


  //
  // Generate the iCalendar

  $name=ec3_ical_quote(get_bloginfo_rss('name'));
  $filename=preg_replace('/[^0-9a-zA-Z]/','',$name).'.ics';

  header('Content-Type: text/calendar; charset=' . get_option('blog_charset'));
  header('Content-Disposition: inline; filename=' . $filename);
  header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
  header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
  header('Cache-Control: no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');

  ec3_ical_echo('BEGIN:VCALENDAR');
  ec3_ical_echo('VERSION:2.0');
  ec3_ical_echo("X-WR-CALNAME:$name");

  global $ec3,$wpdb;

  remove_filter('the_content','ec3_filter_the_content',20);
  remove_filter('get_the_excerpt', 'ec3_get_the_excerpt');
  add_filter('get_the_excerpt', 'wp_trim_excerpt');

  $month_ago = ec3_strftime('%Y-%m-%d',time()-(3600*24*31));
  query_posts('ec3_after='.$month_ago.'&nopaging=1');
  if(have_posts())
  {
    for($evt=ec3_iter_all_events(); $evt->valid(); $evt->next())
    {
      $permalink=ec3_ical_quote(get_permalink());
      $entry =& $ec3->event;

      ec3_ical_echo('BEGIN:VEVENT');
      ec3_ical_echo('SUMMARY:'.ec3_ical_quote(get_the_title()));
      ec3_ical_echo("URL;VALUE=URI:$permalink");
      ec3_ical_echo("SEQUENCE:$entry->sequence");
      ec3_ical_echo('UID:'.ec3_ical_quote("$entry->sched_id-$permalink"));
      if (function_exists('first_para_excerpt_no_headings'))//
		{$description = first_para_excerpt_no_headings();} 
		else {$description = get_the_excerpt();}
	  $description .= '\n\n';
	  $description .= $permalink;
      //$description.= ' ['.sprintf(__('by: %s'),get_the_author_nickname()).']';
      ec3_ical_echo('DESCRIPTION:'.ec3_ical_quote($description));
      if($entry->allday)
      {
        $dt_start=mysql2date('Ymd',$entry->start);
        $dt_end=date('Ymd', mysql2date('U',$entry->end)+(3600*24) );
        ec3_ical_echo("TRANSP:TRANSPARENT"); // for availability.
        ec3_ical_echo("DTSTART;VALUE=DATE:$dt_start");
        ec3_ical_echo("DTEND;VALUE=DATE:$dt_end");
      }
      else
      {
        ec3_ical_echo('TRANSP:OPAQUE'); // for availability.
        // Convert timestamps to UTC
        ec3_ical_echo('DTSTART;VALUE=DATE-TIME:'.ec3_to_utc($entry->start));
        ec3_ical_echo('DTEND;VALUE=DATE-TIME:'.ec3_to_utc($entry->end));
      }

      // Location
      $location=get_post_meta($entry->post_id,'location',true);
      $location=apply_filters('ical_location',$location);
      if(!empty($location))
        ec3_ical_echo('LOCATION:'.ec3_ical_quote($location));

      // GEO
      $geo=get_post_meta($entry->post_id,'geo',true);
      $geo=apply_filters('ical_geo',$geo);
      if(!empty($geo))
        ec3_ical_echo('GEO:'.ec3_ical_quote($geo));

      do_action('ical_item');
      ec3_ical_echo('END:VEVENT');
    }
  }
  ec3_ical_echo('END:VCALENDAR');

?>
