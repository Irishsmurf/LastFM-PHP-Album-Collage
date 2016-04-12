<?

class JsonTest extends PHPUnit_Framework_TestCase {
	public function test_json_get() {
		$jsonRaw = getJson("http://ip.paddez.com/?json");
		$json = json_decode($jsonRaw);
		$this->assertTrue(isset($json->{"ip"}));
	}
}
