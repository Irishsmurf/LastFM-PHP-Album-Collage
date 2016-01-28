<?php

include('curl.php');

class CurlException extends Exception{
  public function __construct($message, $code = 0, Exception $previous = null) {
    parent::__construct($message, $code, $previous);
  }

  public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
  }
}

class Utils {
  public static function getJson($url, $curl, $test=false)
  {
    /*
      Method for downloading JSON from LastFM using cURL.
      Must set User-Agent, as per LastFM's API policy.
    */
    $curl->setUrl($url)
         ->setType('GET');
    $curl->send();
    $response = $curl->getBody();

    if($response == false || $curl->getStatusCode() != 200)
    {
      if(!$test)
        imagepng(Utils::errorImage($curl->getStatusCode()));
      throw new CurlException('Error: '.$curl->getStatusCode());
    }
    return ($response);
  }
  //Tested
  function getImages($coverUrls)
  {
    /*
      This method uses parallel cURL's to speed up downloads.
    */
    //Create array to hold cURL's
    $chs = array();
    //Boolean to note if the downloads are still progressing.
    $running = null;
    $mhandler = curl_multi_init();
    $i = 0;
    foreach($coverUrls as $url)
    {
      $chs[$i] = curl_init($url['url']);
      curl_setopt($chs[$i], CURLOPT_RETURNTRANSFER, true);
      curl_setopt($chs[$i], CURLOPT_USERAGENT, 'www.paddez.com/lastfm/');
      curl_setopt($chs[$i], CURLOPT_CONNECTTIMEOUT, 20);
      curl_setopt($chs[$i], CURLOPT_TIMEOUT, 120);
      curl_multi_add_handle($mhandler, $chs[$i]);
      $i++;
    }
    do
    {
      curl_multi_exec($mhandler, $running);
      curl_multi_select($mhandler);
    } while($running > 0);

    $i = 0;
    $images = array();
    foreach($chs as $ch)
    {
      $images[$i]['data'] = curl_multi_getcontent($ch);
      $images[$i] = $coverUrls[$i];
      curl_multi_remove_handle($mhandler, $ch);
      $i++;
    }

    curl_multi_close($mhandler);
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
        $font = "../resources/NotoSansCJK-Regular.ttc";
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
        imagettftext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);

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

    foreach($albums as $album)
    {
      $url = $album->{'image'}[$quality]->{'#text'};

      if(strpos($url, 'noimage') != false || strlen($url) < 5)
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
      $i++;
    }

    return $artUrl;
  }

  static function getAlbums($json)
  {
    if(is_object($json->{'topalbums'}))
      return $json->{'topalbums'}->{'album'};
    return null;
  }

  static function errorImage($message)
  {
    $xSize = 500;
    $ySize = 50;
    $font = "resources/OpenSans-Regular.ttf";

    $image = imagecreatetruecolor($xSize, $ySize);
    $background = imagecolorallocate($image, 0xF0, 0xF0, 0xF0);
    $foreground = imagecolorallocate($image, 0x00, 0x00, 0x00);
    imagefill($image, 0, 0, $background);
    imagettftext($image, 20, 0, 45, 20, $foreground, $font ,$message);

    return $image;
  }
}
?>
