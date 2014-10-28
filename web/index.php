<?php

include_once('config.inc.php');
$file = "refers.txt";
$genTimes = "genTime.txt";
$log_ip = 1;
$url = parse_url($_SERVER['HTTP_REFERER']);
$referer = (!isset($_SERVER['HTTP_REFERER']) || $_SERVER['HTTP_REFERER'] == '') ? 'an unknown url/direct access (typing in URL)' : $_SERVER['HTTP_REFERER'];
$ip = ($log_ip == 1) ? $_SERVER['REMOTE_ADDR'] : false;
$time = gmdate("Y-m-d\T H:i:s\Z");
$user_text  = ($log_ip == 1) ? "On {$time} {$ip}" : "On {$time} a user";
$refer_text = "{$referer}";
$fw = fopen("hits.txt", 'a');
fwrite($fw, $user_text."\n");
fclose($fw);

if ($url['host'] !== $_SERVER['HTTP_HOST'] && $referer != 'an unknown url/direct access (typing in URL)')
{

	$fp = fopen($file, 'a');
	fwrite($fp, $user_text." hit from referrer: "."{$refer_text} \n");
	fclose($fp);

}

?>
<!doctype html>
<html lang="en">

<head>
<meta name="twitter:card" content="photo">
<meta name="twitter:site" content="irishsmurf">
	<meta name="twitter:creator" content="">
	<meta name="twitter:title" content="">
	<meta name="twitter:image:src" content="">
	<meta name="twitter:domain" content="">
	<meta name="twitter:app:name:iphone" content="">
	<meta name="twitter:app:name:ipad" content="">
	<meta name="twitter:app:name:googleplay" content="">
	<meta name="twitter:app:url:iphone" content="">
	<meta name="twitter:app:url:ipad" content="">
	<meta name="twitter:app:url:googleplay" content="">
	<meta name="twitter:app:id:iphone" content="">
	<meta name="twitter:app:id:ipad" content="">
	<meta name="twitter:app:id:googleplay" content="">
	<title>Paddez</title>
   	<link rel="stylesheet" href="https://www.paddez.com/style.css" type="text/css" media="screen" />
    <link rel="SHORTCUT ICON" href="https://www.paddez.com/images/faviocon.ico">
	<script>
	if(document.images)
	{
		var load = new Image();
		load.src = "http://www.redbrick.dcu.ie/~paddez/projects/lastfm/i/load.gif";
	}
	function changeImage()
	{
		if(document.images)
		{				
			document.place.src = "http://www.redbrick.dcu.ie/~paddez/projects/lastfm/i/load.gif";
		}
		
	}
	</script>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-48104767-1', 'paddez.com');
  ga('send', 'pageview');

</script>
</head>


<body>
<div class="topbanner">
<h1>~/paddez/projects/lastfm</h1>
</div>
<br />
<br />
<nav>
<ul>
<li><a href="https://www.paddez.com/index.html">Home</a></li>
<li><a href="https://www.paddez.com/blog/">Blog</a></li>
<li><a href="https://www.paddez.com/projects/">Projects</a></li>
</ul>
</nav>
<div id="content">
<div id="mainContent">	
<section id="intro">
<header>
<center>
<h2>Last.fm Album Collage Generator</h2>
</header><center>
<p>
</p>
</section>
<section>
<article class="main">
<center>
<table cellpadding="0" cellspacing="0">
<tr>
<form action="<?php echo $PHP_SELF;?>" method=post>
<td class="label"> Username: </td>
<td>
<input type="text" size="40" name="name" placeholder="Username" <?php if(strlen($name) > 1) echo " value=\"$name\"";  ?>></td>
<tr>
<td class="label"> Rows: </td>
<td>
<select name="len">
<option value="3" selected>3</option>
<option value="4">4</option>
<option value="5">5</option>
<option value="6">6</option>

</select>
</tr>
<td class="label"> Columns: </td>
<td>
<select name="width">
<option value="3" selected>3</option>
<option value="4">4</option>
<option value="5">5</option>
<option value="6">6</option>

</select>
</td>
<tr>               
<td class="label"> Period: </td>
<td>
<select name="period">
<option value="overall">Overall</option>
<option value="7day" selected>Last 7 Days</option>
<option value="3month">Last 3 Months</option>
<option value="6month">Last 6 Months</option>
<option value="12month">Last 12 Months</option>
</select>
</td>
<tr>	
<td>Hi-res</td>
<td>
<input type="checkbox" name="hiDef" value="true" />
</td>
</tr>
</table>
<br />
<input type=submit value="Submit" name="submit" onClick="changeImage();">

</form>
<?php if(!$bigImage) echo "<p>$bbcode</p>" ?></center>
</article>
</section>
</div>
<!--<aside>
<center>
Follow Me At
<br />
<a href="http://www.twitter.com/Irishsmurf" class="rollover1" title="Twitter"  target="_BLANK"><span class="displace">Twitter</span></a>
<a href="http://www.facebook.com/theIrishsmurf" class="rollover2" title="Facebook" target="_BLANK"><span class="displace">Facebook</span></a>
<a href="http://www.last.fm/user/Irishsmurf" class="rollover3" title="Last.fm" target="_BLANK"><span class="displace">Last.fm</span></a>      
<a href="http://www.reddit.com/user/Irishsmurf" target="_BLANK" ><img src="../../images/reddit2.png" alt="Reddit" width="36" height="38" border="0"></a>

</center>
</aside>-->
</div>
<br />
<br />
<br />
<br />
<br />
<br />
<footer>
<div>
<section id="about">
<header>
<h3>About</h3>
</header>
<p>Create an album collage from your Last.fm scrobbles</p>
<p>If you run into any issues or have any suggestions for the LastFM tool- please drop me a mail at dave@paddez.com. Thanks!</p></section>
<section id ="links">
<header>
<h3> Links </h3>
</header>
<ul>
<li id="fast1"><a href="http://redbrick.dcu.ie/">Redbrick</a></li>
<li id="fast1"><a href="http://games.dcu.ie/">DCU's Gaming Society</a></li>
<li id="fast1"><a href="http://www.codekettl.com">CodeKettl</a></li>
<li id="fast1"><a href="http://pastebin.com">Pastebin</a></li>
<li id="fast1"><a href="http://www.dcu.ie"> My College!</a></li>

 </ul>
</div>
</footer>
</body>
</html>
