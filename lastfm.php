<?php
/*
	Last.fm Album Collage
	David Kernan
	
	Version 0.5 = 26/06/2010
	Version 0.6 = 17/9/2010
	Version 0.7 = 10/6/2011
	Version 0.9 = 02/8/2011
	
	
	0.5
		Minor Bugfixes
	
	0.6
		Invalid Headers being sent, corrected.
		Cache timeout increased to 10 minutes.
	0.7
		Removed invalid images showing up in Result, will now only show the albums tha have a cover art in the Last.fm database
	0.9
		Updated Webpage to include loading
		Included Higher Definition Collages
*/
//Grabs the query included in the URL.
$url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
$url = substr($url, strpos($url, '?')+1);
//Parses the $vars and assigns the values as in the URL. $name and $period expected here.
parse_str($url);
$name = strtolower($name);
$period = strtolower($period);
$m = $width*$length;
//Filepath to the Cached Images.
$path = "i/php/$name$period$m.jpg";
$noimage = "http://cdn.last.fm/flatness/catalogue/noimage/2/default_album_medium.png";
//File Modified date
$time = filemtime($path);
//Checks if a file exists and is older than 2 hours. If so it outputs the image in the cache

if(file_exists($path) && $time > time() - 18000 )
{
	header ("Content-type: image/jpeg");
 	header("Cache-Control: must-revalidate");
	$offset = 60 * 10;
 	$ExpStr = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
 	header($ExpStr);
	readfile($path);	
}
/*else if(file_exists($path) && $time > time() - 604800 && $period != "7day")
{
	header("Content Type: image/jpeg");
	header("Cache-Control: must-revalidate");
	$offset = 60 * 10;
	$ExpStr = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
	header(ExpStr);
	readfile($path);
}*/
else if($time < time() - 18000) //Or else it fetches a new image
{
	/*if($file_exists($path))
	{
		header("Content-type: image/jpeg");
		header("Cache-Control: must-revalidate");
		$offset = 60*10;
		$ExpStr = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
		header($ExpStr);
		readfile($path);
		$output = true;
	}
*/
	include "../../proxy.php";
	//Include a User Agent for the XML Query
	header("User-Agent" . ": Last.fm/Album Collage");
	$user_url = "http://ws.audioscrobbler.com/2.0/?method=user.gettopalbums&user=$name&period=$period&api_key=990bffa4bfec47d7e826740f266d3e75";
	$ch = curl_init();
	//Large Images are defined by this tag.
	$needle = "<image size=\"large\">";
	$endneedle = "</image>";
	$rowcount = 0;
	//Follows the X & Y axis
	$loc = 0;
	$loc2 = 0;
	//Grabs the XML page via a proxy.
	$last_xml = proxy_url($user_url);
	$counter = 0;
	//The Resized image at 300x300 pixels.
	$resize = imagecreatetruecolor(100*$width, 100*$length);
	$myImg = imagecreatetruecolor(126*$width, $length*126);
	$bg = imagecolorallocate($myImg, 255, 255, 255);
	imagefill($myImg, 0, 0, $bg);
	imagefill($resize, 0, 0, $bg);
	//3x3 Albums = 9 Images to be grabbed.
	while($counter != $width*$length && $counter < 37 )
	{
		//Grabs the URL for the Images
		$start = strpos($last_xml, $needle);
		$last_xml = substr($last_xml, $start + 20 );
		$end = strpos($last_xml, $endneedle);
		$imgurl = substr($last_xml, 0, strpos($last_xml, $endneedle));
	//Check is there's an actual image there, if not skip this turn, and get next album
		if(strcmp($noimage, $imgurl) != 0)
		{	
			//Saves the images according to its file type.
			if(strcmp (substr($imgurl, -3), "jpg" ) == 0)
			{
				save_image($imgurl, "topalbum.jpg");
				$image = imagecreatefromjpeg("topalbum.jpg");
			}
			if(strcmp(substr($imgurl, -3), "png" ) == 0)
			{
				save_image($imgurl, "topalbum.png");
				$image = imagecreatefrompng("topalbum.png");
			}
			//If the X axis reaches the end of the 3x3 grid, change Y by one increment of 126 and reset X
			if ($loc == 126*$width)
			{
				$loc = 0;
				$loc2 = $loc2 + 126;
			}
		
			imagecopy($myImg, $image, $loc, $loc2, 0, 0, 126, 126);
			//Gets rid of the Albums processed ready for the next Album.
			$last_xml = substr($last_xml, strpos($last_xml, $endneedle));
			$counter++;
			//Increments X
			$loc = $loc + 126;
		}
	}
	header ("Content-type: image/jpeg");//Send type tobrowser
 	header("Cache-Control: must-revalidate");//Set Cahce
	$offset = 60 * 10;
 	$ExpStr = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";//Expire date
 	header($ExpStr);

	//save the jpg resize to the path!
	imagecopyresampled( $resize, $myImg, 0, 0, 0, 0, 100*$width, 100*$length, 126*$width, 126*$length);
	imagejpeg($resize, $path, 100);
	chmod($path, 0644); //Make it readable!
	readfile($path);//Read the file and show to the request!
	
	//Cleam up tiem!
	imagedestroy($myImg);
	imagedestroy($image);
	imagedestroy($resize);
}
?>
