<?php
/*

   Last.fm Album Collage
   Runs on Elastic Beanstalk

   David Kernan

   Version 0.5 = 26/06/2010
   Version 0.6 = 17/9/2010
   Version 0.7 = 10/6/2011
   Version 0.9 = 02/8/2011
   Version 1.0 = 26/10/2014
   Version 1.1 = 28/10/2014
   Version 1.2 = 10/02/2015
   Version 1.3 = 18/07/2015

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

   1.0
   Elastic Beanstalk Support
   Amazon S3 Support
   Total code refiguration to make a bit more sense

   1.1
   Implemented Composer for managing dependancies.

   1.2
   Album information captions (Artist, Album)

   1.3
   Hangul & Japense Support
   Play count included
   §
 */
//Grabs the query included in the URL.

include('config.inc.php');
include('vendor/autoload.php');

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Sns\SnsClient;
use Aws\Sqs\SqsClient;

use Doctrine\Common\Cache\FilesystemCache;
use Guzzle\Cache\DoctrineCacheAdapter;

mb_internal_encoding("UTF-8");

function getJson($url)
{
	/*
		Method for downloading JSON from LastFM using cURL.
		Must set User-Agent, as per LastFM's API policy.
	*/
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_USERAGENT, 'www.paddez.com/lastfm/');
  curl_setopt($curl, CURLOPT_FAILONERROR, true);
  $response = curl_exec($curl);

  if($response == false || curl_errno($curl))
  {
  	$info = curl_getinfo($curl);
  	curl_close($curl);
	imagepng(errorImage(curl_error($curl)));
	error_log("Error: ".curl_error($curl));
  	die('Error: '.curl_error($curl));

  }

  curl_close($curl);
  return ($response);
}

function getImages($coverUrls)
{
	/*
		This method uses parallel cURL's to speed up downloads.
	*/
	//Create array to hold cURL's
	$chs = array();
	//Array for HTTP responses
	$responses = array();
	//Boolean to note if the downloads are still progressing.
	$running = null;
	$mh = curl_multi_init();
	$i = 0;
	foreach($coverUrls as $url)
	{
		$chs[$i] = curl_init($url['url']);
		curl_setopt($chs[$i], CURLOPT_RETURNTRANSFER, true);
		curl_setopt($chs[$i], CURLOPT_USERAGENT, 'www.paddez.com/lastfm/');

		curl_multi_add_handle($mh, $chs[$i]);
		$i++;
	}
	do
	{
		curl_multi_exec($mh, $running);
		curl_multi_select($mh);
	} while($running > 0);

	$i = 0;
	foreach($chs as $ch)
	{
		$images[$i]['data'] = curl_multi_getcontent($ch);
		$images[$i]['artist'] = $coverUrls[$i]['artist'];
		$images[$i]['album'] = $coverUrls[$i]['album'];
		$images[$i]['playcount'] = $coverUrls[$i]['playcount'];
		curl_multi_remove_handle($mh, $ch);
		$i++;
	}

	curl_multi_close($mh);
	return $images;
}

function createCollage($covers, $quality ,$totalSize, $cols, $rows, $albumInfo, $playcount)
{
	switch ($quality)
	{
		case 0:
			$pixels = 34;
			break;
		case 1:
			$pixels = 64;
			break;
		case 2:
			$pixels = 126;
			break;
		case 3:
			$pixels = 300;
			break;
	}

	//Create blank image
	$canvas = imagecreatetruecolor($pixels * $cols, $pixels * $rows);
	//Set black colour.
	$backgroundColor = imagecolorallocate($canvas, 0, 0, 0);
	//Fill with black
	imagefill($canvas, 0, 0, $backgroundColor);
	//Note where cursor is.
	$coords['x'] = 0;
	$coords['y'] = 0;

	$i = 1;
	//Grab images with cURL method.
	$images = getImages($covers);

	//For each image returned, create image object and write text
	foreach($images as $rawdata)
	{
		error_log("Album Processing: ".$rawdata['artist']." - ".$rawdata['album']);
		$image = imagecreatefromstring($rawdata['data']);
		if($albumInfo || $playcount)
		{
			$font = "resources/NotoSansCJK-Regular.ttc";
			$white = imagecolorallocate($image, 255, 255, 255);
			$black = imagecolorallocate($image, 0, 0, 0);
			if($albumInfo && $playcount)
			{
				imagettfstroketext($image, 10, 0, 5, 20, $white, $black, $font, $rawdata['artist'], 1);
				imagettfstroketext($image, 10, 0, 5, 32, $white, $black, $font, $rawdata['album'], 1);
				imagettfstroketext($image, 10, 0, 5, 44, $white, $black, $font, "Plays: ".$rawdata['playcount'], 1);
			}
			elseif($albumInfo)
			{
				imagettfstroketext($image, 10, 0, 5, 20, $white, $black, $font, $rawdata['artist'], 1);
				imagettfstroketext($image, 10, 0, 5, 32, $white, $black, $font, $rawdata['album'], 1);
			}
			elseif($playcount)
			{
				imagettfstroketext($image, 10, 0, 5, 20, $white, $black, $font, "Plays: ".$rawdata['playcount'], 1);
			}
		}

		imagecopy($canvas, $image, $coords['x'], $coords['y'], 0, 0, $pixels, $pixels);

		//Increase X coords each time
		$coords['x'] += $pixels;

		//If we've hit the side of the image, move down and reset x position.
		if($i % $cols == 0)
		{
			$coords['y'] += 300;
			$coords['x'] = 0;
		}

		$i++;

	}
	return $canvas;
}

function imagettfstroketext(&$image, $size, $angle, $x, $y, &$textcolor, &$strokecolor, $fontfile, $text, $px)
{
	/*
		Function to add shadow to text.
	*/
  for($c1 = ($x-abs($px)); $c1 <= ($x+abs($px)); $c1++)
    for($c2 = ($y-abs($px)); $c2 <= ($y+abs($px)); $c2++)
      $bg = imagettftext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);

  return imagettftext($image, $size, $angle, $x, $y, $textcolor, $fontfile, $text);
}



function getArt($albums, $quality)
{
	global $request;
	/*
	   0 = Low (34)
	   1 = Medium (64s)
	   2 = Large (126)
	   3 = xlarge (300)
	 */
	$i = 0;
	$artUrl = null;
	//Create SQS Client to send Messages to be consumed and stored in DB.
	$sqs = SqsClient::factory(array(
		'credentials.cache' => $cache,
		'region' => 'eu-west-1'
	));

	foreach($albums as $album)
	{
		$url = $album->{'image'}[$quality]->{'#text'};

		if(strpos($url, 'noimage') != false)
		{
			error_log('No album art for - '.$album->{'artist'}->{'name'}.' - '.$album->{'name'});
			continue;
		}

		$artUrl[$i]['artist'] = $album->{'artist'}->{'name'};
		$artUrl[$i]['album'] = $album->{'name'};
		$artUrl[$i]['mbid'] = $album->{'mbid'};
		$artUrl[$i]['playcount'] = $album->{'playcount'};
		$artUrl[$i]['url'] = $url;
		$artUrl[$i]['user'] = $request['user'];
		try
		{
			$result = $sqs->sendMessage(array(
				'QueueUrl' => 'https://sqs.eu-west-1.amazonaws.com/346795263809/lastfm-albums',
				'MessageBody' => json_encode($artUrl[$i])
			));
		}
		catch(Exception $e)
		{
			error_log("SQS Error = ".$artUrl[$i]);
		}
		$i++;
	}

	return $artUrl;
}

function getAlbums($json)
{
  return $json->{'topalbums'}->{'album'};
}

function errorImage($message)
{
  $x = 500;
  $y = 50;
  $font = "resources/OpenSans-Regular.ttf";

  $image = imagecreatetruecolor($x, $y);
  $background = imagecolorallocate($image, 0xF0, 0xF0, 0xF0);
  $foreground = imagecolorallocate($image, 0x00, 0x00, 0x00);
  imagefill($image, 0, 0, $background);
  imagettftext($image, 20, 0, 45, 20, $foreground, $font ,$message);

  return $image;
}

if(!isset($config))
{
  //if not defined, use Environment variables
  $config['bucket'] = getenv("bucket");
  $config['api_key'] = getenv("api_key");
}

//Cache AWS's temporary role credentials
$cache = new DoctrineCacheAdapter(new FilesystemCache('/tmp/cache'));
$s3 = S3Client::factory(array(
      'credentials.cache' => $cache,
      'region' => 'eu-west-1'));

//Get localhost
$url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$url = substr($url, strpos($url, '?')+1);

//Parses the $vars and assigns the values as in the URL. $name and $period expected here.
parse_str($url);
$request['user'] = $user;
$request['period'] = $period;
$request['cols'] = $cols;
$request['rows'] = $rows;
$plays = isset($playcount) && $playcount == 1;
$albumInfo = isset($info) && $info == 1;

//Hack to prevent albums with no images
$limit = $request['cols'] * $request['rows'] + 15;
$bucket = $config['bucket'];

//If Configuration isn't defined, throw and error and exit
if(empty($config['bucket']) && empty($config['api_key']))
{
  error_log("Configuration not defined, check environment variables or config.inc.php");
  die();
}
//S3 Key.
$key = 'images/'.$request['user'].'-'.$request['period'].'.jpg';

//Define Lastfm API calls
$lastfmApi = "http://ws.audioscrobbler.com/2.0/?method=user.gettopalbums&user=".$request['user']."&period=".$request['period']."&api_key=".$config['api_key']."&limit=$limit&format=json";
$validUser = "http://ws.audioscrobbler.com/2.0/?method=user.getinfo&user=".$request['user']."&api_key=".$config['api_key']."&format=json";

//Check if a valid user
$infoJson = json_decode(getJson($validUser));

//If an error is thrown, generate an error image and exit
if(isset($infoJson->{"error"}))
{
  header("Content-Type: image/png");
  error_log($infoJson->{"message"}." - ".$request['user']);
  imagepng(errorImage($infoJson->{"message"}));
  $sns = SnsClient::factory(array(
        'credentials.cache' => $cache,
        'region' => 'eu-west-1'));
  $sns->publish(array(
        'TopicArn' => 'arn:aws:sns:eu-west-1:346795263809:LastFM-Errors',
        'Message' => $infoJson->{"message"}." - ".$request['user'],
        'Subject' => "Lastfm Error: ".$infoJson->{"error"}
        ));
  return;
}

//Get User's albums and generate a MD5 hash based on this
$json = getJson($lastfmApi);
$jsonhash = md5($json);

//Cache based on user set variables and JSON hash
$filename = "images/$user.$period.$rows.$cols.$albumInfo.$plays.$jsonhash";

//if a previous file exists - request is cached, serve from cache and exit
if(file_exists($filename))
{
  header("Content-Type: image/jpeg");
  error_log("Serving from cache - ".$filename);
  echo file_get_contents($filename);
  exit;
}

//otherwise carry on and getAlbums from LastFM.
$albums = getAlbums(json_decode($json));
//Pass the Albums to getArt to download the art into a $covers array
$covers = getArt($albums, 3);

//From the covers array, create a collage while passing user variables required
$image = createCollage($covers, 3, 0, $cols, $rows, $albumInfo, $plays);
//Output HTTP ContentType header.
header("Content-Type: image/jpeg");
//Output image on stdout.
imagejpeg($image, NULL, 100);
//Save image to local filesystem as cache
imagejpeg($image, $filename, 100);

//After output, save image to S3 for static content
$result = $s3->putObject(array(
      'Bucket'			=> $bucket,
      'Key'				=> strtolower($key),
      'SourceFile'		=> $filename,
      'ACL'				=> 'public-read',
      'ContentType'		=> 'image/jpeg',
	  'CacheControl'	=>	'max-age=16400'
      ));

//Free resources
imagedestroy($image);
?>
