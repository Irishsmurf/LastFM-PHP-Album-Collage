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
$width = 30;
$length = 3;
$request['user'] = 'irishsmurf';
$request['period'] = 'overall';
$request['width'] = $width;
$request['length'] = $length;
$limit = $request['width'] * $request['length'];

$lastfmApi = "http://ws.audioscrobbler.com/2.0/?method=user.gettopalbums&user=".$request['user']."&period=".$request['period']."&api_key=".$config['api_key']."&limit=$limit&format=json";
$noimage = "http://cdn.last.fm/flatness/catalogue/noimage/2/default_album_medium.png";

echo "\n$lastfmApi\n\n";
$albums = getAlbums($lastfmApi);

foreach($albums as $album)
{
    echo $album->{'name'}." - ".$album->{'artist'}->{'name'}."\n";
}
?>
