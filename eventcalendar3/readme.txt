=== Event Calendar 3 ===
Tags: calendar, event, events, vcalendar, icalendar, ical, ajax, widget, sidebar
Stable tag: 3.1.4
Contributors: Alex Tingle
Donate link: http://www.amazon.co.uk/gp/registry/1S44DP6XXOFIN
Requires at least: 1.5
Tested up to: 2.6.3

Manage future events as an online calendar. Display upcoming events in a dynamic
calendar, on a listings page, or as a list in the sidebar.


== Description ==

Manage future events as an online calendar. Display upcoming events in a dynamic
calendar, on a listings page, or as a list in the sidebar. You can subscribe to
the calendar from iCal (OSX) or Sunbird.

Choose one WordPress category as the 'event' category, and then add posts for
dates in the future. Add the calendar or event list functions to your template,
or just use the 'Event Category' page to list your forthcoming events.

EventCalendar works fine with WordPress v2. It should also work with WordPress
v1.5. Reports of successes and failures are most welcome.

 [Full Documentation](http://wpcal.firetree.net)


== Installation ==

Before you start, make sure that you have at least MySQL v4.

= 1. Upload to your plugins folder, usually `wp-content/plugins/` =

The plugin is in the form of a directory called 'eventcalendar3'.

= 2. Activate the plugin on the plugin screen. =

Don't try to view your blog yet. First you must...

= 3. Change settings on the "Options > Event Calendar" options screen. =

You must choose which WordPress category to use for events. (Viewing the
options screen for the first time also sets up the database, and upgrades
events from older versions of EventCalendar.)

 [Details](http://wpcal.firetree.net/options)

= 4. Add the Event Calendar or Upcoming Events list to your sidebar. =

If you use the WordPress Widgets, then the Event Calendar is available as an
easy to install widget. In order to use it you must first activate the 'Event
Calendar Widget' plugin.

If you use the K2 template then the Event Calendar is available as a sidebar
module.

Otherwise, you need to make a small adition to your template. Add the
following code to your sidebar.php:

    Event Calendar:
    <li>
      <?php ec3_get_calendar(); ?>
    </li>

    Upcoming Events:
    <li>Events
      <?php ec3_get_events(5); ?>
    </li>

If you are using an older template, then you should check that your HTML
header contains the following tag: `<?php wp_head(); ?>`

Caution: The Event Calendar must be unique. If you try to show more than one
calendar on a page, then only the first will be displayed.

 [Details](http://wpcal.firetree.net/template-functions)


== How to make an Event Post ==

An event post is a normal blog post, with one or more attached events. On the
'Write Post' page, scroll down and you will see the 'Event Editor'. You might
need to click the little '+' in its blue bar, to see the controls.

To start with, you will only see the column headings (Start, End and All Day)
and the '+' - add event button. Click '+' to add a new event.

The event will start and end on the next full hour. To set the start date, click
on the '...' button, next to the start time. A popup calendar will appear.

Select the new start date by clicking on the calendar. Optionally you can change
the time by clicking on the popup's time and dragging. Click on the 'X' to
dismiss the popup.

You can also edit the date and time in the normal way, by clicking on the
numbers and changing them with the keyboard. If you edit the date manually like
this, make sure that you keep to the correct format:
  YEAR-MONTH-DATE 24-HOURS:MINUTES

Example: An event is scheduled from 2-4pm on 14th August, 2006.

   Start: 2006-08-14 14:00:00
   End:   2006-08-14 16:00:00

When you change the 'Start' field, the 'End' field updates automatically, so
that the event's duration remains the same. If you want to change the duration,
then edit the 'End' field, just as you did for the 'Start'.

If you tick the 'All Day' checkbox, then the times are ignored - the event goes
on through the whole day. You can make the event span over more than one day if
you wish. You can also add more scheduled times for the same post. Just click
the '+' button to add more lines.

To remove an event, click on the '-' button.

When you've finished editing your events, just Save the post in the normal way.


== New Features in v3.1 ==

This is a significant re-write. Event dates are now kept in their own table,
so they are separate from the post date. There is a new Ajax interface on the
post edit screen that allows you to set the event date.

 o Event dates are shown in their own little box at the beginning of event
   posts.

 o Multi-day events are now supported.

 o Timezones are properly supported, so everything works properly for those
   unfortunate people who don't live in the GMT timezone.
   
 o Translations are now available in the following languages:

     ca_ES  Catalan             (by Vicent Cubells)
     cs_CZ  Czech               (by Michal Franek)
     de_DE  German              (by Marc Schumann)
     dk_DK  Danish              (by Simon Bognolo)
     es_ES  Spanish             (by Maira Belmonte)
     fi_FI  Finnish             (by Ralf Strandell)
     fr_FR  French              (by Davy Morel & Jérôme aka Comme une image)
     hu_HU  Hungarian           (by Elbandi)
     it_IT  Italian             (by Jimmi)
     mk_MK  Macedonian          (by Vanco Ordanoski)
     nb_NO  Norwegian           (by Realf Ording Helgesen)
     nl_NL  Dutch               (by Gerjan Boer & P. Mathijssen)
     pt_BR  Portuguese (Brazil) (by DJ Spark)
     ro_RO  Romanian            (by Sushkov Stanislav)
     ru_RU  Russian             (by Ivan Matveyev)
     sl_SI  Slovenian           (by Damjan Gerli)
     sv_SE  Swedish             (by Anders Laurin)
     tr_TR  Turkish             (by Firat Cem Tezer & Roman Neumüller)

   If you would like to make a new translation, then just make a copy of the
   file getttext/ec3.pot, and add your translated text. You can use the existing
   .po files as an example. Post your file to the
   [mailing list](http://penguin.firetree.net/eventcalendar) and we'll add it
   into the project.


== Screenshots ==

1. The (AJAX) Event Calendar and Upcoming Events widgets.

2. The AJAX Event Calendar has nicely formatted popups.

3. The Event Editor on the Write Post page.

4. Event Calendar options page.


== Planned features ==

Some enhancements haven't made it into this release, but they are planned for
the future. Look in the file TODO.txt for details.


== Note: ==

Copyright (c) 2005-2008  Alex Tingle and others.
License: GPL
Some of this code was developed with the financial support of Stephen Hinton.
