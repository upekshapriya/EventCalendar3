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

require_once(dirname(__FILE__).'/calendar-basic.php');

class ec3_ec3xml extends ec3_BasicCalendar
{
  var $details = array();

  function ec3_ec3xml($options=false,$datetime=0)
  {
    $this->ec3_BasicCalendar($options,$datetime);
  }

  function wrap_month($monthstr)
  {
    return "<month id='".$this->dateobj->month_id()."'>\n"
           . $monthstr
           . "</month>\n";
  }
  
  function wrap_week($weekstr)
  {
    return $weekstr;
  }
  
  function wrap_day($dayarr)
  {
    if(empty($this->dayobj))
      return '';

    $day_id   = $this->dateobj->day_id();
    $date     = $this->dateobj->to_mysqldate();
    $day_link = $this->dateobj->day_link($this->show_only_events);
    $result ="<day id='$day_id' date='$date' link='$day_link'";
    if(!empty($this->dayobj->titles))
      $result.=" titles='".implode(', ',$this->dayobj->titles)."'";
    if($this->dayobj->has_events())
      $result.=" is_event='1'";
    if(empty($dayarr))
      $result .= "/>\n";
    else
      $result .= ">\n".implode("\n",$dayarr)."</day>\n";
    return $result;
  }

  function make_pad($num_days,$is_start_of_month)
  {
    return '';
  }

  /** Helper function, makes a <start> or <end> element. */
  function _datetime_element($datetime,$tagname)
  {
    $datestr = mysql2date(get_option('date_format'),$datetime);
    $timestr = mysql2date(get_option('time_format'),$datetime);
    return "  <$tagname date='$datestr' time='$timestr'>$datetime</$tagname>\n";
  }

  function make_event(&$event)
  {
    global $id;
    $this->_add_detail($event);
    $result = " <event post_id='pid_$id' sched_id='sid_$event->sched_id'";
    if($event->allday)
    {
      $result .= " allday='1'>\n";
    }
    else
    {
      $result .= ">\n";
      if(substr($event->start,0,10) == $this->dayobj->date)
        $result.= $this->_datetime_element($event->start,'start');
      if(substr($event->end,0,10) == $this->dayobj->date)
        $result.= $this->_datetime_element($event->end,'end');
    }
    $result .= " </event>\n";
    return $result;
  }

  function make_post(&$post)
  {
    global $id;
    $this->_add_detail();
    $result = " <post post_id='pid_$id' />\n";
    return $result;
  }
    
  function _add_detail($event=FALSE)
  {
    global $id, $post, $ec3_htmlspecialchars;

    // Record the post's title for today.
    $title=get_the_title();
    if(empty($this->dayobj->titles))
      $this->dayobj->titles = array();
    $safe_title=strip_tags($title);
    $safe_title=
      str_replace(
        array(',','@'),
        ' ',
        $ec3_htmlspecialchars(
          stripslashes($safe_title),
          ENT_QUOTES,
          get_option('blog_charset'),
          FALSE // double_encode
        )
      );
    if(!empty($event))
    {
      if($this->dayobj->date == substr($event->start,0,10))
        $safe_title .= ' @'.ec3_get_start_time();
      else
        $safe_title = '...'.$safe_title;
    }
    $this->dayobj->titles[] = $safe_title;

    // Make a unique <detail> element.
    if(array_key_exists($id,$this->details))
      return;

    $link=get_permalink(); 
    $d = " <detail id='pid_$id' title='$title' link='$link'";
    $excerpt = get_the_excerpt();
    if(empty($excerpt))
      $d .= " />\n";
    else
      $d .= "><excerpt><![CDATA[$excerpt]]></excerpt></detail>\n";
    $this->details[$id] = $d;
  }
}; // end class ec3_ec3xml


@header('Content-type: text/xml; charset=' . get_option('blog_charset'));
echo '<?xml version="1.0" encoding="'.get_option('blog_charset')
.    '" standalone="yes"?>'."\n";

// Turn off EC's content filtering.
remove_filter('the_content','ec3_filter_the_content',20);
remove_filter('get_the_excerpt', 'ec3_get_the_excerpt');
add_filter('get_the_excerpt', 'wp_trim_excerpt');

global $ec3,$wp_query;
$options=array();
if($wp_query->is_month)
  $options['num_months']=1;
$calobj = new ec3_ec3xml($options);
switch(ec3_get_listing_q($wp_query))
{
  case 'E':
    $calobj->add_events($wp_query);
    break;
  case 'P':
    $calobj->add_posts($wp_query,!$ec3->advanced);
    break;
  default:
    $calobj->add_events($wp_query);
    $calobj->add_posts($wp_query,!$ec3->advanced);
}

?>
<calendar><?php echo $calobj->generate() ?>
<details id="details">
<?php echo implode('',$calobj->details) ?>
</details>
</calendar>
