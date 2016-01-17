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
    $result = $this->utils->getJson("some.api.com", $curl, true);
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
}

?>
