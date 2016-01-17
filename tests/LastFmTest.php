<?php

require_once 'lib/Utils.php';

class StubCurl extends Curl {
  public function _contruct() {
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
    } catch(Exception $e) {
      echo $e;
      throw $e;
    }
  }

  public function testGetAlbumsValid() {
    $json_example = json_decode('{"topalbums": {
                      "album": "hello"}}');
    $album = Utils::getAlbums($json_example);

    $this->assertEquals($album, "hello");
  }

  public function testGetAlbumsInvalid() {
    $json_example = json_decode('{"topalbums": 0}');
    $album = Utils::getAlbums($json_example);
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
    $returned_array = $this->utils->getArt(json_decode($json), 3, true);
    $this->assertEquals($returned_array[0]['artist'], 'ArtistName');
    $this->assertEquals($returned_array[0]['album'], 'AlbumName');
    $this->assertEquals($returned_array[0]['mbid'], 'c9294302-9589-4859-a0ed-d82c65b017db');
    $this->assertEquals($returned_array[0]['playcount'], '1000');
    $this->assertEquals($returned_array[0]['url'], 'http://www.website.com/some+url/xlarge');
  }

  public function testGetArtInvalid() {
    $json = '[{"name":"AlbumName",
        "playcount":"1000","mbid":"c9294302-9589-4859-a0ed-d82c65b017db",
        "url":"http://www.website.com/some+url/",
        "artist":{"name":"ArtistName","mbid":"edcea99a-630e-4567-a5b8-21b4c4a01ae2",
        "url":"http://www.last.fm/music/Brand+New"},"image":[
        {"#text":"noimage","size":"small"}],
        "@attr":{"rank":"1"}}]';
    $returned_array = $this->utils->getArt(json_decode($json), 0, true);
    $this->assertNotEmpty($returned_array);
  }

}

?>
