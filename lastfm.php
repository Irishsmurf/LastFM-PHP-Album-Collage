<?php
/*
    t
	Last.fm Album Collage
	David Kernan
	
	Version 0.5 = 26/06/2010
	Version 0.6 = 17/9/2010
	Version 0.7 = 10/6/2011
	Version 0.9 = 02/8/2011
	
    Version 0.95 26/10/2014 - Upgraded to S3 & Rejigged code.
	
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

include('config.inc.php');
include('aws-autoloader.php');

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;


function createImageFromFile($filename, $use_include_path = false, $context = null, &$info = null)
{
  $info = array("image"=>getimagesize($filename));
  $info["image"] = getimagesize($filename);
  if($info["image"] === false) throw new InvalidArgumentException("\"".$filename."\" is not readable or no php supported format");
  else
  {
    $imageRes = imagecreatefromstring(file_get_contents($filename, $use_include_path, $context));
    
    if(isset($http_response_header)) $info["http"] = $http_response_header;
    return $imageRes;
  }
}

function getJson($url)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'www.paddez.com/lastfm/');
    $response = curl_exec($curl);

    if($response == false)
    {
        $info = curl_getinfo($curl);
        curl_close($curl);
        die('Error: '.var_export($info));
    }

    curl_close($curl);
    $decoded = json_decode($response);
    return $decoded;
}

function downloadImage($url)
{
    return createImageFromFile($url);
}

function createCollage($covers, $quality ,$totalSize, $width, $length)
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

    $canvas = imagecreatetruecolor($pixels * $width, $pixels * $length);
    $backgroundColor = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $backgroundColor);
    
    $coords['x'] = 0;
    $coords['y'] = 0;

    $i = 1;
    foreach($covers as $cover)
    {
        if(strpos($cover, 'noimage'))
            continue;
        $image = downloadImage($cover);
        imagecopy($canvas, $image, $coords['x'], $coords['y'], 0, 0, $pixels, $pixels);
        $coords['x'] += $pixels;
        
        if($i % $width == 0)
        {
            $coords['y'] += 300;
            $coords['x'] = 0;
        }

        $i++;

    }

    return $canvas;
}


function getArt($albums, $quality)
{
    /*
        0 = Low (34)
        1 = Medium (64s)
        2 = Large (126)
        3 = xlarge (300)
    */
    $i = 0;
    $artUrl = null;
    foreach($albums as $album)
    {
        $artUrl[$i] = $album->{'image'}[$quality]->{'#text'};
        $i++;
    }

    return $artUrl;
}

function getAlbums($url)
{
    $json = getJson($url);
    return $json->{'topalbums'}->{'album'};
}

$s3 = S3Client::factory(array(
    'key' => $config['accessKey'],
    'secret' => $config['secretKey']));


#$url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
#$url = substr($url, strpos($url, '?')+1);

//Parses the $vars and assigns the values as in the URL. $name and $period expected here.
#parse_str($url);
$width = 10;
$length = 10;
$request['user'] = 'irishsmurf';
$request['period'] = 'overall';
$request['width'] = $width;
$request['length'] = $length;
$limit = $request['width'] * $request['length'];

$lastfmApi = "http://ws.audioscrobbler.com/2.0/?method=user.gettopalbums&user=".$request['user']."&period=".$request['period']."&api_key=".$config['api_key']."&limit=$limit&format=json";

//echo "\n$lastfmApi\n\n";
$albums = getAlbums($lastfmApi);

//getArt($albums, 3);

$covers = getArt($albums, 3);
header("Content-Type: image/jpeg");

$image = createCollage($covers, 3, 0, $width, $length);

imagejpeg($image);
imagedestroy($image);
?>
