/* EventCalendar. Copyright (C) 2005-2008, Alex Tingle.
 * This file is licensed under the GNU GPL. See LICENSE file for details.
 */
 
 
// mod to work with WP 3.7 and above

// Set in HTML file:
//   var ec3.start_of_week
//   var ec3.month_of_year
//   var ec3.month_abbrev
//   var ec3.myfiles
//   var ec3.home
//   var ec3.viewpostsfor

///////////////////////////////////////////////////////////////////////////////
// namespace ec3
///////////////////////////////////////////////////////////////////////////////

var ec3 = {
  version:'3.2.beta3',

  /** Get today's date.
   *  Note - DO THIS ONCE, so that the value of today never changes! */
  today : new Date(),

  /** Global store for ec3.Calendar objects. */
  calendars : [],
  
  allday : 'all day',

  ELEMENT_NODE: 1,
  TEXT_NODE:    3,

  init : function()
    {
      // Set-up calculated stuff about today.
      ec3.today_day_num  = ec3.today.getDate();
      ec3.today_month_num= ec3.today.getMonth() + 1;
      ec3.today_year_num = ec3.today.getFullYear();

      // Pre-load image.
      ec3.imgwait=new Image(14,14);
      ec3.imgwait.src=ec3.myfiles+'/ec_load.gif';

      // Convert strings from PHP into Unicode
      ec3.viewpostsfor=ec3.unencode(ec3.viewpostsfor);
      for(var i=0; i<ec3.month_of_year.length; i++)
        ec3.month_of_year[i]=ec3.unencode(ec3.month_of_year[i]);
      for(var j=0; j<ec3.month_abbrev.length; j++)
        ec3.month_abbrev[j]=ec3.unencode(ec3.month_abbrev[j]);
    },

  /** Register an onload function. */
  do_onload : function(fn)
    {
      var prev=window.onload;
      window.onload=function(){ if(prev)prev(); fn(); }
    },

  /** Register a new calendar with id: cal_id.
   *  Valid options, set as an object are:
   *    hide_logo = true|false
   *  Eg. ec3.new_calendar('wp-calendar',{hide_logo:false});
   */
  new_calendar : function(cal_id,options)
    {
      var cal = new ec3.Calendar(cal_id);
      if(options && options.hasOwnProperty('hide_logo'))
        cal.hide_logo = options.hide_logo;
      ec3.do_onload( function(){cal.init();} );
      ec3.calendars[cal_id] = cal;
      return cal;
    },

  /** Converts HTML encoded text (e.g. "&copy Copyright") into Unicode. */
  unencode : function(text)
    {
      if(!ec3.unencodeDiv)
        ec3.unencodeDiv=document.createElement('div');
      ec3.unencodeDiv.innerHTML=text;
      return(ec3.unencodeDiv.innerText || ec3.unencodeDiv.firstChild.nodeValue);
    },

  get_child_by_tag_name : function(element,tag_name)
    {
      var results=element.getElementsByTagName(tag_name);
      if(results)
        for(var i=0; i<results.length; i++)
          if(results[i].parentNode==element)
            return results[i];
      return 0;
    },

  calc_day_id : function(day_num,month_num,year_num)
    {
      if(ec3.today_day_num==day_num &&
         ec3.today_month_num==month_num &&
         ec3.today_year_num==year_num)
      {
        return 'today';
      }
      else
      {
        return 'ec3_'+year_num+'_'+month_num+'_'+day_num;
      }
    },

  /** Add new_class_name to element.className, but only if it's not
   *  already listed there. */    
  add_class : function(element,new_class_name)
    {
      if(element.className.length==0) // no current class
      {
        element.className=new_class_name;
      }
      else if(-1 == element.className.indexOf(new_class_name)) // no match
      {
        element.className+=' '+new_class_name;
      }
      else // Possible match, check in more detail.
      {
        var classes=element.className.split(' ');
        for(var i=0, len=classes.length; i<len; i++)
          if(classes[len-i-1]==new_class_name)
            return; // positive match.
        element.className+=' '+new_class_name;
      }
    },

  /** Converts an ISO datetime (YYY-MM-DD hh:mm:ss) into a Date object. */
  parse_datetime : function(s)
    {
      if(s && s.length)
      {
        var dt=s.split(' ');
        var ymd=dt[0].split('-');
        var hms=dt[1].split(':');
        return new Date(
            parseInt(ymd[0],10),parseInt(ymd[1],10)-1,parseInt(ymd[2],10),
            parseInt(hms[0],10),parseInt(hms[1],10),  parseInt(hms[2],10)
          );
      }
      return null;
    },

  /** Tests an XML attribute for value=='1'. */
  attr2bool : function(element,attrname)
    {
      var val;
      if(typeof element.getAttributeNode == 'function')
      {
        var n=element.getAttributeNode(attrname);
        return (n && n.specified && n.value=='1')? true: false;
      }
      else
      {
        var a=element.getAttribute(attrname);
        return (a && a=='1')? true: false;
      }
    },

  extend : function(dest,src)
    {
      for(k in src)
        dest[k] = src[k];
    }

} // end namespace ec3
ec3.do_onload( function(){ec3.init();} );


///////////////////////////////////////////////////////////////////////////////
/** Calendar class. */
ec3.Calendar = function(cal_id)
{
  this.cal_id = cal_id;
}

ec3.Calendar.prototype = {

  hide_logo : false,

  full_id : function(short_id)
    {
      return this.cal_id+'-'+short_id;
    },

  short_id : function(full_id)
    {
      return full_id.substr(this.cal_id.length);
    },

  getElementById : function(short_id)
    {
      return document.getElementById(this.full_id(short_id));
    },

  init : function()
    {
      // Holds ongoing XmlHttp requests.
      this.reqs =  new Array();

      // Get the calendar's root div element.
      this.div = document.getElementById(this.cal_id);

      // Overwrite the href links in ec3_prev & ec3_next to activate EC3.
      var prev=this.getElementById('ec3_prev');
      var next=this.getElementById('ec3_next');
      if(prev && next)
      {
        // Check for cat limit in month link
        var xCat=new RegExp('[&?]ec3_listing=[eE].*$');
        var match=xCat.exec(prev.href);
        if(match)
          this.is_listing=true;
        // Replace links
        var self = this;
        prev.onclick = function(){self.go_prev(); return false;}
        next.onclick = function(){self.go_next(); return false;}
        prev.href='#';
        next.href='#';
      }

      if(typeof ec3_Popup != 'undefined')
      {
        // Set-up popup.
        var cals=this.get_calendars();
        if(cals)
        {
          // Add event handlers to the calendars.
          for(var i=0,len=cals.length; i<len; i++)
            ec3_Popup.add_tbody( ec3.get_child_by_tag_name(cals[i],'tbody') );
        }
      }
    },

  /** Replaces the caption and tbody in table to be the specified year/month. */
  create_calendar : function(table_cal,month_num,year_num)
    {
      // Take a deep copy of the current calendar.
      var table=table_cal.cloneNode(1);

      // Calculate the zero-based month_num
      var month_num0=month_num-1;

      // Set the new caption
      var caption=ec3.get_child_by_tag_name(table,'caption');
      if(caption)
      {
        var c=ec3.get_child_by_tag_name(caption,'a');
        var caption_text=ec3.month_of_year[month_num0] + ' ' + year_num;
        if(c && c.firstChild && c.firstChild.nodeType==ec3.TEXT_NODE )
        {
        //var url=ec3.home+'/?feed=ec3xml&year='+year_num+'&monthnum='+month_num;
	  if(month_num<10) 
	  {
	    c.href=ec3.home+'/?m='+year_num+'0'+month_num;
	  }
	  else
	  {
	    c.href=ec3.home+'/?m='+year_num+month_num;
	  }
          if(this.is_listing)
             c.href+='&ec3_listing=events';
          c.title=ec3.viewpostsfor;
          c.title=c.title.replace(/%1\$s/,ec3.month_of_year[month_num0]);
          c.title=c.title.replace(/%2\$s/,year_num);
          c.firstChild.data=caption_text;
        }
      }

      if(caption &&
         caption.firstChild &&
         caption.firstChild.nodeType==ec3.TEXT_NODE)
      {
        caption.firstChild.data=ec3.month_of_year[month_num0] + ' ' + year_num;
      }

      var tbody=ec3.get_child_by_tag_name(table,'tbody');

      // Remove all children from the table body
      while(tbody.lastChild)
        tbody.removeChild(tbody.lastChild);

      // Make a new calendar.
      var date=new Date(year_num,month_num0,1, 12,00,00);

      var tr=document.createElement('tr');
      var td,div;
      tbody.appendChild(tr);
      var day_count=0
      var col=0;
      while(date.getMonth()==month_num0 && day_count<40)
      {
        var day=(date.getDay()+7-ec3.start_of_week)%7;
        if(col>6)
        {
          tr=document.createElement('tr');
          tbody.appendChild(tr);
          col=0;
        }
        if(col<day)
        {
          // insert padding
          td=document.createElement('td');
          td.colSpan=day-col;
          td.className='pad';
          tr.appendChild(td);
          col=day;
        }
        // insert day
        td=document.createElement('td');
        td.ec3_daynum=date.getDate();
        var short_id=ec3.calc_day_id(date.getDate(),month_num,year_num);
        td.id=this.full_id(short_id);
        if(short_id=='today')
          td.className='ec3_today';
        this.new_day(td); // Extensions may over-ride this function.
        tr.appendChild(td);
        col++;
        day_count++;
        date.setDate(date.getDate()+1);
      }
      // insert padding
      if(col<7)
      {
        td=document.createElement('td');
        td.colSpan=7-col;
        td.className='pad';
        tr.appendChild(td);
      }

      // add the 'dog'
      if((7-col)>1 && !this.hide_logo)
      {
        a=document.createElement('a');
        a.href='http://blog.firetree.net/?ec3_version='+ec3.version;
        a.title='Event Calendar '+ec3.version;
        td.style.verticalAlign='bottom';
        td.appendChild(a);
        div=document.createElement('div');
        div.className='ec3_ec';
        div.align='right'; // keeps IE happy
        a.appendChild(div);
      }

      // set table's element id
      table.id=this.full_id('ec3_'+year_num+'_'+month_num);

      return table;
    }, // end create_calendar()

  /** Dispatch an XMLHttpRequest for a month of calendar entries. */
  loadDates : function(month_num,year_num)
    {
      var req=new XMLHttpRequest();
      if(req)
      {
        this.reqs.push(req);
        var self = this;
        req.onreadystatechange = function(){self.process_xml();};
        if(month_num<10) {
        	var url=ec3.home+'/?feed=ec3xml&m='+year_num+'0'+month_num;
        }
        else {
			var url=ec3.home+'/?feed=ec3xml&m='+year_num+month_num;
		}
        if(this.is_listing)
           url+='&ec3_listing=events';
        req.open("GET",url,true);
        this.set_spinner(1);
        req.send(null);
      }
    },
  

  /** Obtain an array of all the calendar tables. */
  get_calendars : function()
    {
      var result=new Array();
      for(var i=0; i<this.div.childNodes.length; i++)
      {
        var c=this.div.childNodes[i];
        if(c.id &&
           c.id.search(this.full_id('ec3_[0-9]'))==0 &&
           c.style.display!='none')
        {
          result.push(this.div.childNodes[i]);
        }
      }
      if(result.length>0)
        return result;
      else
        return 0;
    },


  /** Changes the link text in the forward and backwards buttons.
   *  Parameters are the 0-based month numbers. */
  rewrite_controls : function(prev_month0,next_month0)
    {
      var prev=this.getElementById('ec3_prev');
      if(prev && prev.firstChild && prev.firstChild.nodeType==ec3.TEXT_NODE)
        prev.firstChild.data='\u00ab\u00a0'+ec3.month_abbrev[prev_month0%12];
      var next=this.getElementById('ec3_next');
      if(next && next.firstChild && next.firstChild.nodeType==ec3.TEXT_NODE)
        next.firstChild.data=ec3.month_abbrev[next_month0%12]+'\u00a0\u00bb';
    },


  /** Turn the busy spinner on or off. */
  set_spinner : function(on)
    {
      var spinner=this.getElementById('ec3_spinner');
      var publish=this.getElementById('ec3_publish');
      if(spinner)
      {
        if(on)
        {
          spinner.style.display='inline';
          if(publish)
            publish.style.display='none';
        }
        else
        {
          spinner.style.display='none';
          if(publish)
            publish.style.display='inline';
        }
      }
    },


  /** Called when the user clicks the 'previous month' button. */
  go_prev : function()
    {
      var calendars=this.get_calendars();
      if(!calendars)
        return;
      var pn=calendars[0].parentNode;

      // calculate date of new calendar
      var id_array=this.short_id(calendars[0].id).split('_');
      if(id_array.length<3)
        return;
      var year_num=parseInt(id_array[1]);
      var month_num=parseInt(id_array[2])-1;
      if(month_num==0)
      {
        month_num=12;
        year_num--;
      }
      // Get new calendar
      var newcal=this.getElementById('ec3_'+year_num+'_'+month_num);
      if(newcal)
      {
        // Add in the new first calendar
        newcal.style.display=this.calendar_display;
      }
      else
      {
        newcal=this.create_calendar(calendars[0],month_num,year_num);
        pn.insertBefore( newcal, calendars[0] );
        this.loadDates(month_num,year_num);
      }
      // Hide the last calendar
      this.calendar_display=calendars[calendars.length-1].style.display;
      calendars[calendars.length-1].style.display='none';

      // Re-write the forward & back buttons.
      this.rewrite_controls(month_num+10,month_num+calendars.length-1);
    },


  /** Called when the user clicks the 'next month' button. */
  go_next : function()
    {
      var calendars=this.get_calendars();
      if(!calendars)
        return;
      var pn=calendars[0].parentNode;
      var last_cal=calendars[calendars.length-1];

      // calculate date of new calendar
      var id_array=this.short_id(last_cal.id).split('_');
      if(id_array.length<3)
        return;
      var year_num=parseInt(id_array[1]);
      var month_num=1+parseInt(id_array[2]);
      if(month_num==13)
      {
        month_num=1;
        year_num++;
      }
      // Get new calendar
      var newcal=this.getElementById('ec3_'+year_num+'_'+month_num);
      if(newcal)
      {
        // Add in the new last calendar
        newcal.style.display=this.calendar_display;
      }
      else
      {
        newcal=this.create_calendar(calendars[0],month_num,year_num);
        if(last_cal.nextSibling)
          pn.insertBefore(newcal,last_cal.nextSibling);
        else
          pn.appendChild(newcal);
        this.loadDates(month_num,year_num);
      }
      // Hide the first calendar
      this.calendar_display=calendars[0].style.display;
      calendars[0].style.display='none';

      // Re-write the forward & back buttons.
      this.rewrite_controls(month_num-calendars.length+11,month_num);
    },


  /** Triggered when the XML load is complete. Checks that load is OK, and then
   *  updates calendar days. */
  process_xml : function()
    {
      var busy=0;
      for(var i=0; i<this.reqs.length; i++)
      {
        var req=this.reqs[i];
        if(req)
        {
          if(req.readyState==4)
          {
            this.reqs[i]=0;
            if(req.status==200)
              this.update_days( new ec3.xml.Calendar(req.responseXML) );
          }
          else
            busy=1;
        }
      }
      if(!busy)
      {
        // Remove old requests.
        while(this.reqs.shift && this.reqs.length && !this.reqs[0])
          this.reqs.shift();
        this.set_spinner(0);
      }
    },


  /** Adds links to the calendar for each day listed in the XML. */
  update_days : function(xcal)
    {
      for(var i=0, len=xcal.day.length; i<len; i++)
      {
        var td=this.getElementById(xcal.day[i].id());
        if(td && td.ec3_daynum)
        {
          this.update_day(td,xcal.day[i]);
        }
      }
      if(typeof ec3_Popup != 'undefined')
      {
        var month=this.getElementById(xcal.id());
        if(month)
          ec3_Popup.add_tbody( ec3.get_child_by_tag_name(month,'tbody') );
      }
    },

  /** Makes a new day inside the given TD.
   *  The day number is stored in td.ec3_daynum.
   *  This member function may be over-ridden to change the way the day cell
   *  is rendered.
   */
  new_day : function(td)
    {
      td.appendChild(document.createTextNode( td.ec3_daynum ));
    },

  /** Add events & posts to a single day into a TD element. This member function
   *  may be over-ridden to change the way the day cell is rendered.
   *  Parameters:
   *
   *   td - the TD element into which the day should be written.
   *        The day number is stored in td.ec3_daynum.
   *
   *   day - an ec3.xml.Day object containing the day's posts and events.
   *         See below for documentation.
   */
  update_day : function(td,day)
    {
      ec3.add_class(td,'ec3_postday');
      // Save the TD's text node for later.
      var txt=td.removeChild(td.firstChild);
      // Make an A element
      var a=document.createElement('a');
      a.href=day.link();
      a.title=day.titles();
      if(day.is_event())
      {
        ec3.add_class(td,'ec3_eventday');
        a.className='eventday';
      }
      // Put the saved text node into the A.
      a.appendChild(txt);
      // Finally, put the A into the TD.
      td.appendChild(a);
    }

} // end ec3.Calendar.prototype


///////////////////////////////////////////////////////////////////////////////
// namespace ec3.xml
///////////////////////////////////////////////////////////////////////////////

ec3.xml = {

  /** Global store from XML <detail> objects. */
  details : []
}


///////////////////////////////////////////////////////////////////////////////
/** xml.Calendar class - provides an easy to use interface to read the
 *  XML loaded from ec3xml feeds. */
ec3.xml.Calendar = function(element)
{
  this.element = element;
  this.init();
}
ec3.xml.Calendar.prototype = {

  day : [],

  init : function()
    {
      var days=this.element.getElementsByTagName('day');
      for(var i=0, len=days.length; i<len; i++)
      {
        this.day[i] = new ec3.xml.Day(this,days[i]);
      }
    },

  /** Gets the /calendar/month@id */
  id : function()
    {
      var months=this.element.getElementsByTagName('month');
      if(months)
        return months[0].getAttribute('id');
      else
        return '';
    },

  /** Utility function, not used in the default Javascript.
   *  Given an XML document, it finds the details for post 'post_id'.
   *  We need to do this because getElementById() doesn't work reliably
   *  in XML documents loaded by XMLHttpRequest.
   */
  _detail : function(post_id)
    {
      if(!ec3.xml.details[post_id])
      {
        // Cache the details.
        var details=this.element.getElementsByTagName('detail');
        for(var i=0, len=details.length; i<len; i++)
        {
          var pid=details[i].getAttribute('id');
          if(pid)
            ec3.xml.details[pid] = details[i];
        }
      }
      return ec3.xml.details[post_id];
    }
}


///////////////////////////////////////////////////////////////////////////////
/** xml.Day class - provides an easy to use interface to read <day> elements. */
ec3.xml.Day = function(calendar,element)
{
  this.calendar = calendar;
  this.element = element;
}
ec3.xml.Day.prototype = {

  id      : function(){ return this.element.getAttribute('id');   },
  link    : function(){ return this.element.getAttribute('link'); },
  titles  : function(){ return this.element.getAttribute('titles'); },
  is_event: function(){ return ec3.attr2bool(this.element,'is_event'); },

  /** Obtains the day's date as a Javascript Date object. */
  date : function()
    {
      var d=this.element.getAttribute('date').split('-');
      return new Date(parseInt(d[0],10),parseInt(d[1],10)-1,parseInt(d[2],10));
    },

  _events : function(result)
    {
      var all=this.element.getElementsByTagName('event');
      if(all)
      {
        for(var i=0, len=all.length; i<len; i++)
          result.push( new ec3.xml.Event(this,all[i]) );
      }
      return result;
    },

  _posts : function(result)
    {
      var all=this.element.getElementsByTagName('post');
      if(all)
      {
        for(var i=0, len=all.length; i<len; i++)
          result.push( new ec3.xml.Post(this,all[i]) );
      }
      return result;
    },

  /** Obtains an array of ec3.xml.Event objects. */
  events : function(result){ return this._events([]); },

  /** Obtains an array of ec3.xml.Post objects. */
  posts  : function(result){ return this._posts([]); },

  /** Obtains an array of mixed ec3.xml.Post and ec3.xml.Event objects. */
  posts_and_events : function()
    {
      var result = [];
      result = this._posts(result);
      result = this._events(result);
      return result;
    }
}

///////////////////////////////////////////////////////////////////////////////
/** xml.Post class - provides an easy to use interface to read <post>
 *  elements. */
ec3.xml.Post = function(day,element)
{
  this.day = day;
  this.element = element;
}
ec3.xml.Post.prototype = {

  kind : 'post',

  /** Returns a string intended to briefly summarise the post. */
  brief : function() { return ''; },

  // details
 
  link   : function(){ return this._detail().getAttribute('link'); },
  title  : function(){ return this._detail().getAttribute('title'); },
  excerpt: function()
    {
      var excerpts=this._detail().getElementsByTagName('excerpt');
      if(excerpts)
        return excerpts[0].firstChild.data;
      else
        return '';
    },
  
  _detail : function()
    {
      return this.day.calendar._detail( this.element.getAttribute('post_id') );
    }
}


///////////////////////////////////////////////////////////////////////////////
/** xml.Event class - provides an easy to use interface to read <event>
 *  elements. */
ec3.xml.Event = function(day,element)
{
  this.day = day;
  this.element = element;
}
ec3.extend( ec3.xml.Event.prototype, ec3.xml.Post.prototype );
ec3.extend( ec3.xml.Event.prototype, {

  kind : 'event',

  allday : function(){ return ec3.attr2bool(this.element,'allday'); },

  /** Returns a string intended to briefly summarise the event. */
  brief : function()
    {
      if(this.allday())
        return ec3.allday;
      var result = '';
      var starts=this.element.getElementsByTagName('start');
      var ends  =this.element.getElementsByTagName('end');
      if(starts && starts.length)
      {
        result = starts[0].getAttribute('time');
        if(ends && ends.length)
          result += ' - ' + ends[0].getAttribute('time');
        else
          result += '...';
      }
      else
      {
        if(ends && ends.length)
          result = '...' + ends[0].getAttribute('time');
        else
          result = '...'+ec3.allday+'...';
      }
      return result;
    }
});
