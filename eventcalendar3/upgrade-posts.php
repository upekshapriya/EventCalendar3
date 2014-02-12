<?php

/** Only include this file if a database upgrade is called for.
 *  Otherwise it can be safely ignored. */
function ec3_upgrade_posts()
{
  if(!function_exists('__ngettext'))
  {
    function __ngettext($single,$plural,$number,$domain='default')
    {
      if($number==1) return __($single,$domain);
      else           return __($plural,$domain);
    }
  }


  global $ec3,$post,$wpdb;
  $ec3->advanced=false;
  $changed = ec3_upgrade_posts_apply();

  $query =& new WP_Query();
  $query->query( 'nopaging=1&cat=' . $ec3->event_category );

  $format = 'Y-m-d H:i:s';
  $now = time();
  $rownum = 0;
  ?>

  <?php if($changed): ?>

   <div id="message" class="updated fade"><p><strong>
   <?php
     $msg = __ngettext('Post upgraded.','%d posts upgraded.',$changed,'ec3');
     echo sprintf($msg,$changed);
   ?>
   </strong></p></div>

  <?php endif ?>

  <div class="wrap">
  <form method="post">
  <h2><?php _e('Upgrade Event Posts (from version 3.0)','ec3'); ?></h2>

  <?php if($query->have_posts()): ?>

  <input type="hidden" name="ec3_action" value="upgrade_posts" />
  <table class="widefat">

        <thead>
        <tr>
           <th scope="col">OK</th>
           <th scope="col">Title</th>
           <th scope="col">Post date</th>
           <th scope="col">Event date</th>
        </tr>
        </thead>

   <?php while($query->have_posts()): $query->the_post(); ?>

     <?php if(empty($post->ec3_schedule)):
       $post_date = get_post_time();
       $post_modified_date = get_post_modified_time();
       $rownum++;
       if($rownum % 2)
       {
         $rowclass='alternate ';
       }
       else
       {
         $rowclass='';
       }

       if($post_modified_date >= $now)
       {
         $errstyle='background-color:#fcc';
       }
       else
       {
         $errstyle='';
       }
     ?>

       <tr class="<?php echo $rowclass ?>">
          <td>
           <input type="checkbox" name="ec3_upgrade_<?php the_ID() ?>"
            value="1" checked="checked" />
          </td>
          <td title="Post ID: <?php the_ID() ?>">
           <a target="_blank" href="<?php the_permalink() ?>">
            <?php the_title() ?>
           </a>
          </td>
          <td>
           <input type="text" name="ec3_postdate_<?php the_ID() ?>"
            style="<?php echo $errstyle ?>"
            value="<?php echo date($format,$post_modified_date) ?>" />
          </td>
          <td>
           <input type="text" name="ec3_eventdate_<?php the_ID() ?>"
            value="<?php echo date($format,$post_date) ?>" />
          </td>
       </tr>

     <?php endif ?>
   <?php endwhile ?>
   <?php if($rownum==0): ?>

       <tr><td>No posts to upgrade.</td></tr>

   <?php endif ?>

  </table>

  <p class="submit">

    <?php if($rownum>0): ?>

       <input type="submit" name="ec3_upgrade_posts"
        value="<?php _e('Upgrade Event Posts','ec3') ?>" />
       <input type="submit" name="ec3_cancel_upgrade"
        value="<?php _e("Don't Upgrade Posts") ?>" />

    <?php else: update_option('ec3_upgrade_posts',0) ?>

       <input type="submit" name="ec3_cancel_upgrade"
        value="<?php _e('OK') ?> &raquo;" />

    <?php endif ?>

  </p>

  <?php endif ?>

  </form>
  </div>

<?php

}


/** Process results from the 'ec3_upgrade_posts' form. */
function ec3_upgrade_posts_apply()
{
  if(!isset($_POST) ||
     !isset($_POST['ec3_action']) ||
     $_POST['ec3_action']!='upgrade_posts')
  {
    return;
  }

  global $ec3,$wpdb;

  $changed_count=0;

  // Find all of our parameters
  $sched_entries=array();
  $fields =array('postdate','eventdate');
  foreach($_POST as $k => $v)
  {
    if(preg_match('/^ec3_(upgrade|'.implode('|',$fields).')_(_?)([0-9]+)$/',$k,$match))
    {
      $pid=intval($match[3]);
      if(!isset( $sched_entries[$pid] ))
          $sched_entries[ $pid ]=array();
      $sched_entries[ $pid ][ $match[1] ] = $v;
    }
  }

  foreach($sched_entries as $pid => $vals)
  {
    if(empty($vals['upgrade']) ||
       empty($vals['postdate']) ||
       empty($vals['eventdate']) )
    {
      continue;
    }
    $postdate = "'".$wpdb->escape($vals['postdate'])."'";
    $eventdate = "'".$wpdb->escape($vals['eventdate'])."'";
    $cnt=$wpdb->get_var(
      "SELECT COUNT(0) FROM $ec3->schedule
       WHERE post_id=$pid");
    if(!empty($cnt))
      continue;
    // Create a schedule record.
    $wpdb->query(
      "INSERT INTO $ec3->schedule (post_id,start,end,allday,rpt)
       VALUES ($pid,$eventdate,$eventdate,0,'')"
    );
    // Modify the post date.
    $wpdb->query(
      "UPDATE $wpdb->posts
       SET post_date=$postdate, post_date_gmt=$postdate
       WHERE ID=$pid"
    );
    $changed_count++;
  }
  return $changed_count;
}

?>
