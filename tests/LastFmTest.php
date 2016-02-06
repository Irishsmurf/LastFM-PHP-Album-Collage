<?php

require_once 'lib/Utils.php';
require_once 'lib/curl.php';

class StubCurl extends Curl {
  public function _construct() {
    $this->body = null;
    $this->data = null;
    $this->status = null;
    $this->type = 'GET';
    $this->url = 'null';
  }

  public function setBody($body) {
    $this->status = $body;
  }

  public function setType($type) {
    $this->type = $type;
  }

  public function setUrl($url) {
    $this->url = $url;
  }

  public function getBody() {
    return $this->body;
  }

  public function send() {
    if(!$this->url){
      return null;
    }
    if('GET' == $this->type){
      $this->body = '"{"somename":"somevalue"}"';
    }
  }

}

class CurlTest extends PHPUnit_Framework_TestCase{
  private $curl = null;
  public function setUp(){
    $this->$curl = new Curl();
  }

  public function testGetBody(){
    $body = $this->$curl->getBody();
    $this->assertNull($body);
  }

  public function testSetData_string(){
    $instance = $this->$curl->setData("Test Data");
    $this->assertEquals($instance->getData(), "Test Data");
  }

  public function testSetData_array(){
    $array = array("some", "array");
    $instance = $this->$curl->setData($array);
    $this->assertEquals($instance->getData(), http_build_query($array));
  }

  public function testGetStatus(){
    $status = $this->$curl->getStatusCode();
    $this->assertNull($status);
  }
}
class LastFmTest extends PHPUnit_Framework_TestCase {
  private $utils = null;
  public function setUp() {
    $this->utils = new Utils();
  }

  public function testJsonValid() {
    $curl = $this->getMockBuilder('Curl')
                 ->setMethods(array('getBody', 'getStatusCode'))
                 ->getMock();
    $curl->expects($this->once())
         ->method('getBody')
         ->willReturn('{"some":"api", "value":"mock"}');

    $curl->method('getStatusCode')
         ->willReturn('200');

    $result = $this->utils->getJson("some.api.com", $curl);
    $this->assertEquals('{"some":"api", "value":"mock"}', $result);
  }

  public function testJsonInvalid() {
    $curl = $this->getMockBuilder('Curl')
                 ->setMethods(array('getBody', 'getStatusCode'))
                 ->getMock();
    $curl->expects($this->once())
         ->method('getBody')
         ->willReturn('{"invalid":"api"');

    $curl->method('getStatusCode')
         ->willReturn('503');

    $this->setExpectedException(
         'CurlException', 'Error: 503'
    );
    try {
      $result = $this->utils->getJson("some.api.com", $curl, true);
      return $result;
    } catch(Exception $e) {
      echo $e;
      throw $e;
    }
  }

  public function testGetAlbumsValid() {
    $jsonExample = json_decode('{"topalbums": {"album": "hello"}}');
    $album = Utils::getAlbums($jsonExample);
    $this->assertEquals($album, "hello");
  }

  public function testGetAlbumsInvalid() {
    $jsonExample = json_decode('{"topalbums": 0}');
    $album = Utils::getAlbums($jsonExample);
    $this->assertEquals($album, null);
  }

  public function testErrorImageValid() {
    $image = Utils::errorImage("some message");
    $this->assertEquals(500, imagesx($image));
    $this->assertEquals(50, imagesy($image));
  }

  public function testGetImagesInvalid() {
    $images = $this->utils->getImages(array());
    $this->assertEmpty($images);

  }

  public function testGetArtNoAlbumCover() {
    $json = '[{"name":"AlbumName",
        "playcount":"1000","mbid":"c9294302-9589-4859-a0ed-d82c65b017db",
        "url":"http://www.website.com/some+url/",
        "artist":{"name":"ArtistName","mbid":"edcea99a-630e-4567-a5b8-21b4c4a01ae2",
        "url":"http://www.last.fm/music/Brand+New"},"image":[
        {"#text":"ttp://www.website.com/noimage/small","size":"small"},
        {"#text":"ttp://www.website.com/noimage/medium","size":"medium"},
        {"#text":"ttp://www.website.com/noimage/large","size":"large"},
        {"#text":"http://www.website.com/noimage/xlarge","size":"extralarge"}],
        "@attr":{"rank":"1"}}]';
    $returnedArray = $this->utils->getArt(json_decode($json), 3);
    $this->assertNull($returnedArray);
  }

  public function testGetArtValid() {
    $json = '[{"name":"AlbumName",
        "playcount":"1000","mbid":"c9294302-9589-4859-a0ed-d82c65b017db",
        "url":"http://www.website.com/some+url/",
        "artist":{"name":"ArtistName","mbid":"edcea99a-630e-4567-a5b8-21b4c4a01ae2",
        "url":"http://www.last.fm/music/Brand+New"},"image":[
        {"#text":"ttp://www.website.com/some+url/small","size":"small"},
        {"#text":"ttp://www.website.com/some+url/medium","size":"medium"},
        {"#text":"ttp://www.website.com/some+url/large","size":"large"},
        {"#text":"http://www.website.com/some+url/xlarge","size":"extralarge"}],
        "@attr":{"rank":"1"}}]';
    $returnedArray = $this->utils->getArt(json_decode($json), 3);
    $this->assertEquals($returnedArray[0]['artist'], 'ArtistName');
    $this->assertEquals($returnedArray[0]['album'], 'AlbumName');
    $this->assertEquals($returnedArray[0]['mbid'], 'c9294302-9589-4859-a0ed-d82c65b017db');
    $this->assertEquals($returnedArray[0]['playcount'], '1000');
    $this->assertEquals($returnedArray[0]['url'], 'http://www.website.com/some+url/xlarge');
  }

  public function testGetArtInvalid() {
    $json = '[{"name":"AlbumName",
        "playcount":"1000","mbid":"c9294302-9589-4859-a0ed-d82c65b017db",
        "url":"http://www.website.com/some+url/",
        "artist":{"name":"ArtistName","mbid":"edcea99a-630e-4567-a5b8-21b4c4a01ae2",
        "url":"http://www.last.fm/music/Brand+New"},"image":[
        {"#text":"noimage","size":"small"}],
        "@attr":{"rank":"1"}}]';
    $returnedArray = $this->utils->getArt(json_decode($json), 0, true);
    $this->assertNotEmpty($returnedArray);
  }

  public function testStrokeText() {
    $image = imagecreatetruecolor(1, 1);
    $color = imagecolorallocate($image, 200, 150, 100);
    $font = "resources/NotoSansCJK-Regular.ttc";
    $someNum = 42;
    $this->utils->imagettfstroketext(
                $image, $someNum, $someNum, $someNum, $someNum,
                $color, $color, $font, "String", 1);
    $this->assertInternalType('resource', $image);

  }

  public function testCreateCollage() {
    $covers = array();
    $canvas = $this->utils->createCollage(
                      $covers, 0, 6, 3, 3, false, false);
    $this->assertInternalType('resource', $canvas);
    $canvas = $this->utils->createCollage(
                      $covers, 1, 6, 3, 3, false, false);
    $this->assertInternalType('resource', $canvas);
    $canvas = $this->utils->createCollage(
                      $covers, 2, 6, 3, 3, false, false);
    $this->assertInternalType('resource', $canvas);
    $canvas = $this->utils->createCollage(
                      $covers, 3, 6, 3, 3, false, false);
    $this->assertInternalType('resource', $canvas);
  }

}

?>
