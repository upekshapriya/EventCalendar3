//=====================================================================
// Event Listener
// by Scott Andrew - http://scottandrew.com
// edited by Mark Wubben, <useCapture> is now set to false
// Now allows callback lists for objects with an Id - Alex Tingle
//=====================================================================
function firetree_addEvent(obj, evType, fn)
{
  if(obj.id){
    if(!document._callbacks_by_id)
        document._callbacks_by_id=new Array();
    if(!document._callbacks_by_id[obj.id])
        document._callbacks_by_id[obj.id]=new Array();
    document._callbacks_by_id[obj.id].push(fn);
    fn=callback;
  }

  if(obj.addEventListener){
    obj.addEventListener(evType, fn, false); 
    return true;
  } else if (obj.attachEvent){
    var r = obj.attachEvent('on'+evType, fn);
    return r;
  } else {
    return false;
  }

  function callback(e)
  {
    var n;
    if(e.currentTarget)    n=e.currentTarget; // Mozilla/Safari/w3c
    else if(window.event)  n=window.event.srcElement; // IE
    else                   return;

    if(n.id && document._callbacks_by_id[n.id])
      for(var i=0; i<document._callbacks_by_id[n.id].length; i++)
        document._callbacks_by_id[n.id][i](e);
  }
}
