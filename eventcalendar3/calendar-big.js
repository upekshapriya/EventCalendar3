ec3.big_cal = {

  new_day : function(td)
    {
      td.innerHTML='<span class="ec3_daynum">'+td.ec3_daynum+'</span><div></div>';
    },
    
  update_day : function(td,day)
    {
      ec3.add_class(td,'ec3_postday');
      // Clear out the TD.
      td.innerHTML='';
      // Make an A element
      var a=document.createElement('a');
      a.href=day.link();
      if(day.is_event())
      {
        ec3.add_class(td,'ec3_eventday');
        a.className='eventday';
      }
      a.innerHTML=td.ec3_daynum;
      a.className='ec3_daynum';
      // Put the A into the TD.
      td.appendChild(a);
      // Now, make a DIV for the event details.
      var div=document.createElement('div');
      var posts = day.posts_and_events();
      for(var i=0, len=posts.length; i<len; i++)
      {
        ec3.add_class(td,'ec3_'+posts[i].kind+'day');
	var p=document.createElement('p');
	ec3.add_class(p,'ec3_'+posts[i].kind);
	div.appendChild(p);
        var a=document.createElement('a');
        a.href=posts[i].link();
        a.title=posts[i].brief();
        a.innerHTML=posts[i].title();
        p.appendChild(a);
      }
      td.appendChild(div);
    }

}
