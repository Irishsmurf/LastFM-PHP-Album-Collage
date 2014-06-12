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
	<title>Paddez</title>
   	<link rel="stylesheet" href="/~paddez/style.css" type="text/css" media="screen" />
    <link rel="SHORTCUT ICON" href="/~paddez/images/faviocon.ico">
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
<li><a href="../../index.html">Home</a></li>
<li><a href="../../blog/">Blog</a></li>
<li><a href="/~paddez/projects/">Projects</a></li>
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
<?php 
			if(!class_exists('S3')) 
				require_once 'S3.php';
			
			//Require AWS Keys for S3 storage
			if(!defined('awsAccessKey')) 
				define('awsAccessKey', $config['accessKey']);//'AKIAIK7MYGVKHI3BRRGA');
			if(!defined('awsSecretKey')) 
				define('awsSecretKey', $config['secretKey']); //'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');
		
			
			function uploadS3($path, $key)
			{
				$bucketName = 'lastfm-img-paddez';
				if(!file_exists($path) || !is_file($path))
				{
					exit("\nERROR: NO FILE");
				}

				$s3 = new S3(awsAccessKey, awsSecretKey);
				$s3->putObjectFile($path, $bucketName, $key, S3::ACL_PUBLIC_READ, array('Cache-Control' => 'must-revalidate'));
			}
			$time = microtime(true);
			$avg = 0;
			$name = $_POST['name'];
			$period = $_POST['period'];
			$len = $_POST['len'];
			$width = $_POST['width'];
			$bigImage = $_POST['hiDef'];
			$valid = false;
			if($period <= 48 and $len <= 6 and $width <= 8)
			{
				$valid = true;
			}
			
			if($bigImage == TRUE)
			{
				$fontSize = 12;
				$contentSize = 300;
			}
			else
			{
				$fontSize = 6;
				$contentSize = 126;
			}
			$m = $len*$width;
			if(strlen($name) > 1 and $valid == true)
			{
				$aValid = array('-', '_');
				//if( !preg_match("/^[-a-z0-9_]/i", $name))
				if(!ctype_alnum(str_replace($aValid, '', $name)))
				{
					echo "<p> Username contains Illegal Characters, Try again</p>";
				}
				else
				{
					include "../../proxy.php";
					header("User-Agent" . ": Last.fm/Album Collage");
					$user_url = "http://ws.audioscrobbler.com/2.0/?method=user.gettopalbums&user=$name&period=$period&api_key=990bffa4bfec47d7e826740f266d3e75";
					$ch = curl_init();
					$needle = "<image size=\"large\">";
					$endneedle = "</image>";
					$loc = 0;
					$loc2 = 0;
					$last_xml = proxy_url($user_url);
					$counter = 0;
					$i = 0;
					$total = substr($last_xml, strpos($last_xml, "total=\"")+7, 10);
					$total = intval(substr($total, 0, strpos($total, "\">")));
					$resize = imagecreatetruecolor(100*$width, 100*$len);
					$myImg = imagecreatetruecolor($contentSize*$width, $contentSize*$len);
//					$white = ImageColorAllocate($myImg, 0, 0, 0);
					$white = ImageColorAllocate($myImg, 255, 255, 255);
					$name = strtolower($name);
					$noimage = "http://cdn.last.fm/flatness/catalogue/noimage/2/default_album_medium.png";
					if($total != 0 and $total >= $m)
					{
						while($counter != $m and $i != 150)
						{
							$artTime = microtime(true);
							$start = strpos($last_xml, $needle);
							$last_xml = substr($last_xml, $start + 20 );
							$end = strpos($last_xml, $endneedle);
							
							$imgurl = substr($last_xml, 0, $end);
							if(strcmp($noimage, $imgurl) != 0)
							{
								//Is it High Def?
								if($bigImage == TRUE)
								{
									//replace 126x126 to the extra large, 300x300
									$imgurl = str_replace("/126/", "/300x300/", $imgurl);
								}
								//check to see extension, create a jpg template if its jpeg
								if(strcmp (substr($imgurl, -3), "jpg" ) == 0)
								{
									save_image($imgurl, "i/topalbum.jpg");
									$image = imagecreatefromjpeg("i/topalbum.jpg");
								}
								//else png if png
								if(strcmp(substr($imgurl, -3), "png" ) == 0)
								{
									save_image($imgurl, "i/topalbum.png");
									$image = imagecreatefrompng("i/topalbum.png");
								}
								//is it at the end?
								if ($loc == $contentSize*$width)
								{
									$loc = 0;
									$loc2 = $loc2 + $contentSize;
								}

								if(imagesx($image) >= $contentSize)
								{
									imagecopy($myImg, $image, $loc, $loc2, 0, 0, $contentSize, $contentSize);
									$last_xml = substr($last_xml, strpos($last_xml, $endneedle));
									$counter = $counter + 1;
									$loc = $loc + $contentSize;
									$avg = (microtime(true) - $artTime) + $avg;
								}
							}
							
							$i = $i + 1;
						}
						

						
						#for($i = 0; $i <= $width; $i += 1)
						#{
						#	imageline($myImg, $contentSize * $i , 0 , $contentSize * $i, $contentSize * $len,$white);
						#}
						#for($i = 0; $i <= $len; $i += 1)
						#{
						#	imageline($myImg, 0, $contentSize * $i, $contentSize*$width, $contentSize*$i, $white);
						#}
							
						$avg = $avg/$m;
						$path = "i/$name$period$m.jpg";

						imagejpeg($myImg, $path, 100);
						chmod($path, 0644);
						uploadS3($path, 'lastfm/'.basename($path));
						imagecopyresampled( $resize, $myImg, 0, 0, 0, 0, 100*$width, 100*$len, $contentSize*$width, $contentSize*$len);
						imagejpeg($resize, "i/php/$name$period$m.jpg", 100);
						uploadS3("i/php/$name$period$m.jpg", "lastfm/thumb/$name$period$m.jpg");
						chmod("i/php/$name$period$m.jpg", 0644);
						


						if(strlen($name) > 1)
						{
						$bbcode = "<textarea rows = \"3\" cols = \"120\"  readonly>[url=http://www.redbrick.dcu.ie/~paddez/projects/lastfm/][img]http://www.redbrick.dcu.ie/~paddez/projects/lastfm/lastfm.php?name=$name&period=$period&width=$width&length=$len [/img][/url]</textarea>";
						}
						if($bigImage and $width > 4)
						{
							$time = round(microtime(true) - $time, 3);
							echo "Click the image for full size";
							echo "<a href = \"http://cdn.paddez.com/lastfm/".basename($path)."\"><img src=\"http://cdn.paddez.com/lastfm/thumb/$name$period$m.jpg\" name=\"place\"></img></a><br />";
							$outText = "Generated in ".$time . " seconds (Average ".round($avg, 3)."s per album)";
							echo $outText;					
							$fp = fopen($genTimes, 'a');
							fwrite($fp, $outText."\n");
							fclose($fp);
						}
						else
						{
							$time = round(microtime(true) - $time, 3);
							echo "<img src=\"http://cdn.paddez.com/lastfm/".basename($path)."\" name=\"place\"></img><br />";
//							echo "Generated in ".$time . " seconds (Average ".round($avg, 3)."s per album)";
							$outText = "Generated in ".$time . " seconds (Average ".round($avg, 3)."s per album)";
							echo $outText;
							$fp = fopen($genTimes, 'a');
							fwrite($fp, $outText."\n");
							fclose($fp);
						}
						
						imagedestroy($resize);
						imagedestroy($myImg);
						imagedestroy($image);
					}
					else
						echo "Not enough albums recorded on this profile for that period. ($total found, $m needed)";
				}
			}
			else 
			{
				echo "<img src=\"http://www.redbrick.dcu.ie/~paddez/projects/lastfm/i/notload.gif\" name=\"place\"></img>\n";
			}
			?>
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
<p>Create an album collage from your Last.fm scrobbles</p></section>
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
