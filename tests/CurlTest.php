<?php

class CurlTest extends PHPUnit_Framework_TestCase{
  private $curl = null;
  public function setUp(){
    $this->curl = new Curl();
  }

  public function testGetBody(){
    $body = $this->curl->getBody();
    $this->assertNull($body);
  }

  public function testSetDataString(){
    $instance = $this->curl->setData("Test Data");
    $this->assertEquals($instance->getData(), "Test Data");
  }

  public function testSetDataArray(){
    $array = array("some", "array");
    $instance = $this->curl->setData($array);
    $this->assertEquals($instance->getData(), http_build_query($array));
  }

  public function testGetStatus(){
    $status = $this->curl->getStatusCode();
    $this->assertNull($status);
  }

  public function testGetResource(){
    $resource = $this->curl->getResource();
    $this->assertInternalType('resource', $resource);
  }

  public function testGetType(){
    $this->curl->setType('GET');
    $type = $this->curl->getType();
    $this->assertEquals('GET', $type);
  }

  public function testGetTypeFail(){
    $this->curl->setType('PUT');
    $type = $this->curl->getType();
    $this->assertNotEquals('GET', $type);
  }

  public function testGetOption(){
    $this->curl->setOption(CURLOPT_USERAGENT, "Something");
    $resource = $this->curl->getResource();
    $this->assertInternalType('resource', $resource);
  }
  public function testSendNull(){
    $result = $this->curl->send();
    $this->assertNull($result);
  }

  public function testGetUrl(){
    $this->curl->setUrl("some.domain.com");
    $this->assertEquals($this->curl->getUrl(), "some.domain.com");
  }

  public function testSendPut(){
    $this->curl->setType('PUT');
    $result = $this->curl->send();
    $resource->close();
    $this->assertInternalType('resource', $resource);
  }
}

?>
