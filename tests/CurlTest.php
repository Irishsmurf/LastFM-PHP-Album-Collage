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

  public function testSetData_string(){
    $instance = $this->curl->setData("Test Data");
    $this->assertEquals($instance->getData(), "Test Data");
  }

  public function testSetData_array(){
    $array = array("some", "array");
    $instance = $this->curl->setData($array);
    $this->assertEquals($instance->getData(), http_build_query($array));
  }

  public function testGetStatus(){
    $status = $this->curl->getStatusCode();
    $this->assertNull($status);
  }
}

?>
