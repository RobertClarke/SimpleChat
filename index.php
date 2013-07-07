
<!DOCTYPE html>
<html>
<head>
<meta name="description" content="Chatroom" />
<meta charset=utf-8 />
<link href="assets/style/default.css" rel="stylesheet" media="screen">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="http://cdn.pubnub.com/pubnub-3.5.3.min.js" ></script>
<script>
var $_GET = {};

document.location.search.replace(/\??(?:([^=]+)=([^&]*)&?)/g, function () {
    function decode(s) {
        return decodeURIComponent(s.split("+").join(" "));
    }

    $_GET[decode(arguments[1])] = decode(arguments[2]);
});
function getCookie(c_name)
{
var i,x,y,ARRcookies=document.cookie.split(";");
for (i=0;i<ARRcookies.length;i++)
  {
  x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
  y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
  x=x.replace(/^\s+|\s+$/g,"");
  if (x==c_name)
	{
	return unescape(y);
	}
  }
}

function setCookie(c_name,value,exdays)
{
var exdate=new Date();
exdate.setDate(exdate.getDate() + exdays);
var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
document.cookie=c_name + "=" + c_value;
}
(function($) {
	$.fn.writeText = function(text, func) {
		var elem = this,
		content=text,
		contentArray = text.split(""),
		current = 0;
		setInterval(function() {
			if(current < contentArray.length) {
				elem.text(elem.text() + contentArray[current++]);
			}else if (current==contentArray.length) {
				clearInterval(this);
			}
		}, 10);
	};

})(jQuery);
if (getCookie("zchatname")==null) {
	setCookie("zchatname", "<? echo $_SERVER['REMOTE_ADDR']; ?>", 10000)
}
if (getCookie("zchatmuted")==null) {
	setCookie("zchatmuted", 0, 10000)
}
var channel=(""+document.location).split("#")[1];
if (channel==null||channel=="undefined"||channel=="") { channel="main"; }else{ channel=channel.toLowerCase(); }
var online = new Array();
var onlineprevious = new Array();
var name = getCookie("zchatname");
var muted = getCookie("zchatmuted");
var focus = false;
var splash = true;
var warning = 0;
var pubnub = PUBNUB.init({
		publish_key   : 'pub-c-9a75484a-ae88-4a31-aa88-f3ccb7993e7f',
		subscribe_key : 'sub-c-df977d90-4a10-11e2-adec-12313f022c90'
	});;

function writeVideo(id, name) {
	$("<span class='img'><div class='img'>"+name+"<br><iframe src='http://www.youtube.com/embed/"+id+"?autoplay=1&modestbranding=1&controls=0&showinfo=0&rel=0&enablejsapi=1&version=3&playerapiid=mbYTP_bgndVideo&origin=http%3A%2F%2Fgaben.tv&allowfullscreen=true&wmode=transparent&iv_load_policy=3&html5=1'></iframe><br><a href='http://youtube.com/watch?v="+id+"'>Watch on youtube</a></div></span>").appendTo('div.content');
}
function writeImage(src, name) {
	$("<span class='img'><div class='img'>"+name+"<br><img class='image' src='"+src+"' alt='"+src+"'></div></span>").appendTo('div.content');
}
function write(text, type) {
	if (type==1) {
		$("<span style='color: #629FCB'></span>").appendTo('div.content').writeText(text);
	}else
	if (type==2) {
		$("<span style='color: #F3C36B'></span>").appendTo('div.content').writeText(text);
	}else
	{
		$("<span class='margin"+type+"'></span>").appendTo('div.content').writeText(text);
	}
}
jQuery(document).ready(function(){
	pubnub.subscribe({
		channel : "zewlcouk#"+channel,
		message : function(m){ 
			var message=m.split("#/#");
			if (message[0]=="join") {
				write(message[1]+" has joined", 1);
				if (muted==0) {
					document.getElementById('join').play();
				}
			}
			if (message[0]=="leave") {
				write(message[1]+" has left", 1);
				if (muted==0) {
					document.getElementById('leave').play();
				}
			}
			if (message[0]=="say") {
				if (message[2].charAt(0)!="/") {
					//Attempt to parse as youtube video
					var matches = message[2].match(/watch\?v=([a-zA-Z0-9\-_]+)/);
					if (matches)
					{
						writeVideo(message[2].split("?v=")[1], message[1])
						return;
					}
					write(message[1]+": "+message[2], 0);
					if (muted==0) {
						document.getElementById('ping').play();
					}
				}else{
					command(message[2].substring(1), message[1]);
				}
			}
			if (message[0]=="name") {
				write(message[1]+" has changed their name to "+message[2]+".", 1);
			}
			if (message[0]=="ping") {
				var ping=message[1];
				var ip=message[2];
				pubnub.publish({
					channel : "zewlcouk#"+channel,
					message : "pong#/#"+name+"#/#<? echo $_SERVER['REMOTE_ADDR']; ?>#/#"+ip+"#/#"+message[3]
				})
			}
			if (message[0]=="pong") {
				if (message[3]=="<? echo $_SERVER['REMOTE_ADDR']; ?>") {
					for (var i=0;i<online.length;i++) {
						if (online[i].split("/")[1]==message[2]) {
							if (online[i].split("/")[0]!=message[1]) {
								online.splice(i,1);
							}
						}
					}
					if (jQuery.inArray(message[1], online.join("/").split("/"))==-1) {
						var ping=(new Date).getTime()-message[4];
						online.push(message[1]+"/"+message[2]+"/"+ping);
					}
				}
				updateOnlineList();
			}
		}
	});
});
var html="";
var beats=0;
var deadbeats=0;
function updateOnlineList() {
	online.sort();
	html="";
	if (online.length>0) {
		deadbeats=0;
		warning_close();
		for (var i=0;i<online.length;i++) {
			html+=online[i].split("/")[0]+" <span class='ping'>("+online[i].split("/")[2]+"ms)</span><br>";
		}
	}else{
		if (beats>2&&splash==false) {
			html+="<span class='ping'>[Not connected]</span>";
			deadbeats++;
			if (deadbeats>4) {
				warning_open();
			}
		}else{
			html+="<span class='ping'>[Connecting]</span>";
		}
	}
}

//Misc functions

function splash_status(status) {
	splash=true;
	$("div#loading h2").animate({"marginTop": '20px', opacity: 0}, 1000, function() {
		$("div#loading h2").html(status).css({"marginTop": '0px', opacity: 0});
		$("div#loading h2").animate({"marginTop": '10px', opacity: 1}, 1000);
	});
}

function splash_close() {
	splash=false;
	$("div#loading h2").animate({"marginTop": '20px', opacity: 0}, 1000, function() {
		$("div#loading").fadeOut();
		write("Now chatting in "+channel+". For a list of commands type /help.", 1);
		focus=true;
		$("#commandinput").focus();
	});
	pubnub.publish({
		channel : "zewlcouk#"+channel,
		message : "join#/#"+name
	})
}

function warning_open() {
	if (warning==0) {
		warning=1;
		$("div#warning").fadeIn();
	}
}

function warning_close() {
	if (warning==1) {
		warning=0;
		$("div#warning").fadeOut();
	}
}

function ping() {
	pubnub.publish({
		channel : "zewlcouk#"+channel,
		message : "ping#/#"+name+"#/#<? echo $_SERVER['REMOTE_ADDR']; ?>#/#"+(new Date).getTime()
	})
	updateOnlineList();
}
function heartbeat() {
	if (beats==1) {
		splash_status("waiting for response...");
	}
	if (beats>=2&&online.length>0&&deadbeats==0&&splash==true) {
		splash_close();
	}
	beats++;
	$('.onlinelist').html(html);
	onlineprevious=online;
	online.splice(0,online.length);
	ping();
	setTimeout("heartbeat()",3000)
}
function command(command, runner) {
	if (command.substring(0, 2)=="me"&&command.substring(3)!="") {
		write(runner+" "+command.substring(3), 2);
	}
	if (command=="poptart") {
		writeImage("nyan.gif", runner);
	}
	if (command=="wat") {
		writeImage("wat.jpg", runner);
	}
	if (command=="heman") {
		writeVideo("36h7XHMJls0", runner);
	}
	if (command=="gaben") {
		writeVideo("rP2MDtWu5t0", runner);
	}
}
$(document).ready(function() {
	heartbeat();
	$('#nick').val(name);
	if (muted==1) {
		$('#mute').attr("src","assets/images/muted.png");
	}
	$('#mute').click(function() {
		if (muted==0) {
			muted=1;
			$(this).attr("src","assets/images/muted.png");
		}else{
			muted=0;
			$(this).attr("src","assets/images/unmuted.png");
		}
		setCookie("zchatmuted", muted, 1000);
	});
	$("*").click(function(event) { if (event.target.id!="nick"&&focus==true) { $('#commandinput').focus(); }});
	$('#commandinput').keydown(function(event) {
		if (event.which == 13) {
			var text=$('#commandinput').val();
			if (text!="") {
				pubnub.publish({
					channel : "zewlcouk#"+channel,
					message : "say#/#"+name+"#/#"+text
				})
				$('#commandinput').val("");
			}
		}
	});
	$('#nick').keydown(function(event) {
		if (event.which == 13) {
			var newname=$('#nick').val().replace(/ /g,'');
			if (newname!=name) {
				if (newname!="") {
					pubnub.publish({
						channel : "zewlcouk#"+channel,
						message : "name#/#"+name+"#/#"+newname
					})
					setCookie("zchatname", newname, 10000)
					name=newname;
					$('#nick').val(name);
				}
			}
		}
	});
	window.onunload=function() {
		pubnub.publish({
			channel : "zewlcouk#"+channel,
			message : "leave#/#<? echo $_SERVER['REMOTE_ADDR']; ?>"
		})
	}
	$("title").html("#"+channel);
	$("div#loading h2").animate({"marginTop": '10px', opacity: 1}, 1000);
	$("div#loading h1").html("SimpleChat - #"+channel);
});
</script>
<title></title>
</head>
<body onunload="unload()">
<audio id='join' src='assets/sounds/join.mp3' preload='auto'></audio>
<audio id='leave' src='assets/sounds/leave.mp3' preload='auto'></audio>
<audio id='ping' src='assets/sounds/talk.mp3' preload='auto'></audio>
<img class='preload' src='nyan.gif'>
<img class='preload' src='wat.jpg'>

<div id="loading"><h1>SimpleChat - #</h1><h2>sending heartbeat...</h2></div>
<div id="warning"><h1>Error</h1><h2>connection lost</h2></div>

<div class="menu">
	<img src="assets/images/unmuted.png" id="mute">
	<input type="text" id="nick" class="nick" placeholder="Nickname">
</div>

<div class="onlinelist">
	<span class='ping'>[Connecting]</span>
</div>

<div class="content">

</div>
<div class="input">> <input type="text" id="commandinput" class="input"></div>
</body>
</html>