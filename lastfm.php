<?php
/*
    t
	Last.fm Album Collage
	David Kernan
	
	Version 0.5 = 26/06/2010
	Version 0.6 = 17/9/2010
	Version 0.7 = 10/6/2011
	Version 0.9 = 02/8/2011
    Version 1.0 = 26/10/2014

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

	0.95
		Elastic Beanstalk Support
		Amazon S3 Support
		Total code refiguration to make a bit more sense


*/
//Grabs the query included in the URL.

include('config.inc.php');
include('aws-autoloader.php');

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;


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

function getImages($coverUrls)
{
	$chs = array();
	$responses = array();
	$running = null;
	$mh = curl_multi_init();
	$i = 0;
	foreach($coverUrls as $url)
	{
		$chs[$i] = curl_init($url);
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
		$images[$i] = curl_multi_getcontent($ch);
		curl_multi_remove_handle($mh, $ch);
        $i++;
	}

	curl_multi_close($mh);
	return $images;
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
	$images = getImages($covers);
    foreach($images as $rawdata)
    {
		$image = imagecreatefromstring($rawdata);
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
    	$url = $album->{'image'}[$quality]->{'#text'};
    	if (strpos($url, 'noimage') != false) {
    		//LastFM doesn't have the image, use MBID to get it for coverartarchive.
    		$mb_api = 'http://coverartarchive.org/release/'.$album->{'mbid'};
   			$json = getJson($mb_api)
   			$url = $json->{'images'}->{'thumbnails'}->{'large'}; 		
       	}
        $artUrl[$i] = $url;
        $i++;
    }

    return $artUrl;
}

function getAlbums($url)
{
    $json = getJson($url);
    return $json->{'topalbums'}->{'album'};
}

if(!isset($config))
{
	//if not defined, use Environment variables
	$config['bucket'] = getenv("bucket");
	$config['api_key'] = getenv("api_key");
	$config['accessKey'] = getenv("accessKey");
	$config['secretKey'] = getenv("secretKey");
}


$s3 = S3Client::factory(array(
    'key' => $config['accessKey'],
    'secret' => $config['secretKey'],
    'region' => 'eu-west-1'));


$url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
$url = substr($url, strpos($url, '?')+1);

//Parses the $vars and assigns the values as in the URL. $name and $period expected here.
parse_str($url);
$request['user'] = $user;
$request['period'] = $period;
$request['width'] = $width;
$request['length'] = $length;
$limit = $request['width'] * $request['length'];
$bucket = $config['bucket'];
$key = "images/".$request['period']."/".$request['width']*$request['length']."/".$request['user'].".jpg";

$lastfmApi = "http://ws.audioscrobbler.com/2.0/?method=user.gettopalbums&user=".$request['user']."&period=".$request['period']."&api_key=".$config['api_key']."&limit=$limit&format=json";

$albums = getAlbums($lastfmApi);
$covers = getArt($albums, 3);
$image = createCollage($covers, 3, 0, $width, $length);
$filepath = tempnam(sys_get_temp_dir(), null);

header("Content-Type: image/jpeg");
imagejpeg($image);
imagejpeg($image, $filepath, 100);


$result = $s3->putObject(array(
    'Bucket' => $bucket,
    'Key'   => $key,
    'SourceFile' => $filepath,
    'ACL'   => 'public-read',
    'ContentType' => 'image/jpeg'
    ));

unlink($filepath);
imagedestroy($image);
?>
