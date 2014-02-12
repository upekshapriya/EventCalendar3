/* EventCalendar. Copyright (C) 2005, Alex Tingle.  $Revision: 65 $
 * This file is licensed under the GNU GPL. See LICENSE file for details.
 */

/** class ec3_Popup */
function ec3_Popup()
{

  ec3.do_onload( function()
  {
    // Pre-load images.
    ec3.imgShadow0=new Image(8,32);
    ec3.imgShadow1=new Image(500,16);
    ec3.imgShadow2=new Image(8,32);
    ec3.imgShadow0.src=ec3.myfiles+'/shadow0.png';
    ec3.imgShadow1.src=ec3.myfiles+'/shadow1.png';
    ec3.imgShadow2.src=ec3.myfiles+'/shadow2.png';

    // Generate the popup (but keep it hidden).
    var table,tbody,tr,td;
    ec3_Popup.popup=document.createElement('table');
    ec3_Popup.popup.style.display='none';
    ec3_Popup.popup.id='ec3_popup';
    ec3_Popup.popup.className='ec3_popup';
    tbody=ec3_Popup.popup.appendChild( document.createElement('tbody') );
    tr=tbody.appendChild( document.createElement('tr') );
    td=tr.appendChild( document.createElement('td') );
    td.id='ec3_shadow0';
    td.rowSpan=2;
    td.appendChild( document.createElement('div') );
    td=tr.appendChild( document.createElement('td') );
    ec3_Popup.contents=td.appendChild( document.createElement('table') );
    ec3_Popup.contents.style.width=500;
    td=tr.appendChild( document.createElement('td') );
    td.id='ec3_shadow2';
    td.rowSpan=2;
    td.appendChild( document.createElement('div') );
    tr=tbody.appendChild( document.createElement('tr') );
    td=tr.appendChild( document.createElement('td') );
    td.id='ec3_shadow1';

    document.body.appendChild(ec3_Popup.popup);
  } );

  function add_tbody(tbody)
  {
    if(!tbody)
      return;
    var anchor_list=tbody.getElementsByTagName('a');
    if(!anchor_list)
      return;
    for(var i=0; i<anchor_list.length; i++)
    {
      var a=anchor_list[i];
      var td=a.parentNode;
      // 'title' might have become 'nicetitle' if that plugin is being run.
      var titleattr=a.getAttribute('nicetitle');
      if(!titleattr)
          titleattr=a.getAttribute('title');
      if(titleattr && td.nodeName=='TD' && td.className!='pad')
      {
        td.setAttribute('ec3_title',titleattr);
        a.removeAttribute('nicetitle');
        a.removeAttribute('title');
        addEvent(td,'mouseover',show);
        addEvent(td,'mouseout',hide);
        addEvent(td,'focus',show);
        addEvent(td,'blur',hide);
      }
    }
  }
  ec3_Popup.add_tbody=add_tbody;
  
  function show(e)
  {
    var n;
    if(e.currentTarget)
        n=e.currentTarget; // Mozilla/Safari/w3c
    else if(window.event)
        n=window.event.srcElement; // IE
    else
        return;

    // Find the TD element and the calendar node.
    // (IE will sometimes randomly give us a child instead).
    var td,cal;
    while(1)
    {
      if(!n || n==document.body)
      {
        return;
      }
      else if(n.tagName=='TABLE')
      {
        cal=n;
        break;
      }
      else if(n.tagName=='TD')
      {
        td=n;
      }
      n=n.parentNode;
    }
    
    var ec3_title=td.getAttribute('ec3_title');
    if(typeof ec3_title == 'undefined')
      return;
    if(ec3_Popup.just_hidden)
       ec3_Popup.popup.style.display = "block";
    else
       ec3_Popup.show_timer=setTimeout(
         function(){ec3_Popup.popup.style.display = "block";},
         600
       );

    ec3_Popup.contents.style.width=''+(cal.offsetWidth-2)+'px';
    ec3_Popup.popup.style.top=''+(findPosY(cal)+cal.offsetHeight)+'px';
    ec3_Popup.popup.style.left=''+(findPosX(cal)-8)+'px';

    var tbody=ec3.get_child_by_tag_name(ec3_Popup.contents,'tbody');
    if(tbody)
      ec3_Popup.contents.removeChild(tbody);
    tbody=ec3_Popup.contents.appendChild(document.createElement('tbody'));
    
    var titles=ec3_title.split(', ');
    for(var i=0; i<titles.length; i++)
    {
      tr=tbody.appendChild(document.createElement('tr'));
      td=tr.appendChild(document.createElement('td'));
      td.appendChild(document.createTextNode(titles[i]));
      if(titles[i].indexOf('@')!=-1)
        td.className='eventday';
    }

    // Let's put this event to a halt before it starts messing things up
    window.event? window.event.cancelBubble=true: e.stopPropagation();
  }

  function hide()
  {
    if(ec3_Popup.show_timer)
      clearTimeout(ec3_Popup.show_timer);
    if(ec3_Popup.popup.style.display!='none')
    {
     ec3_Popup.just_hidden=1;
     ec3_Popup.hide_timer=setTimeout(function(){ec3_Popup.just_hidden=0;},900);
    }
    ec3_Popup.popup.style.display='none';

  }

  //=====================================================================
  // Event Listener
  // by Scott Andrew - http://scottandrew.com
  // edited by Mark Wubben, <useCapture> is now set to false
  //=====================================================================
  function addEvent(obj, evType, fn){
    if(obj.addEventListener){
      obj.addEventListener(evType, fn, false); 
      return true;
    } else if (obj.attachEvent){
      var r = obj.attachEvent('on'+evType, fn);
      return r;
    } else {
      return false;
    }
  }

  //=====================================================================
  // From http://www.quirksmode.org/
  //=====================================================================
  function findPosX(obj)
  {
    var curleft = 0;
    if (obj.offsetParent)
    {
      while(1)
      {
        curleft += obj.offsetLeft;
	if(!obj.offsetParent) break;
        obj = obj.offsetParent;
      }
    }
    else if (obj.x)
      curleft += obj.x;
    return curleft;
  }

  function findPosY(obj)
  {
    var curtop = 0;
    if (obj.offsetParent)
    {
      while(1)
      {
        curtop += obj.offsetTop;
	if(!obj.offsetParent) break;
        obj = obj.offsetParent;
      }
    }
    else if (obj.y)
      curtop += obj.y;
    return curtop;
  }

} // end namespace ec3_Popup

// Export public functions from ec3_Popup namespace.
ec3_Popup();
