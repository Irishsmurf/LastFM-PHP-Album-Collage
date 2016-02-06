<?php
/**
 * Class abstracting the cURL library for easier use.
 *
 * Usage:
 *     $curl = new Curl();
 *     $curl->setUrl('http://www.google.com/#')
 *         ->setData('&q=testing+curl')
 *         ->setType('GET');
 *     $curl->send();
 *     echo $curl->getStatusCode(), PHP_EOL;
 *     echo $curl->getResponse(), PHP_EOL;
 */
class Curl {
    /**
     * @var string Body returned by the last request.
     */
    protected $body;

    /**
     * @var resource Actual CURL connection handle.
     */
    public $ch;

    /**
     * @var mixed Data to send to server.
     */
    protected  $data;

    /**
     * @var integer Response code from the last request.
     */
    protected $status;

    /**
     * @var string Request type.
     */
    protected $type;

    /**
     * @var string Url for the connection.
     */
    protected $url;


    /**
     * Constructor.
     */
    public function __construct() {
        $this->body = null;
        $this->ch = curl_init();
        $this->data = null;
        $this->status = null;
        $this->type = 'GET';
        $this->url = null;
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,
            true);
        curl_setopt($this->ch, CURLOPT_USERAGENT,
            'www.paddez.com/lastfm/');
        curl_setopt($this->ch, CURLOPT_FAILONERROR,
            true);
    }

    /**
     * Return the body returned by the last request.
     * @return string
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Return the current payload.
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Set the payload for the request.
     *
     * This can either by a string, formatted like a query
     * string:
     *      foo=bar&mitz=fah
     * or a single-dimensional array:
     *      array('foo' => 'bar', 'mitz' => 'fah')
     * @param mixed $data
     * @return Curl
     */
    public function setData($data) {
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        $this->data = $data;
        return $this;
    }

    /**
     * Return the status code for the last request.
     * @return integer
     */
    public function getStatusCode() {
        return $this->status;
    }

    /**
    * Set curl_opt for the local curl object
    *
    */

    public function setOption($option, $value) {
      curl_setopt($this->ch, $option, $value);
    }

    /**
     * Return the current type of request.
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    public function getResource() {
      return $this->ch;
    }

    /**
     * Set the type of request to make (GET, POST, PUT,
     * DELETE, etc)
     * @param string $type Request type to send.
     * @return Curl
     */
    public function setType($type) {
        $this->type = $type;
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST,
            $type);
        return $this;
    }

    /**
     * Return the connection's URL.
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Set the URL to make an HTTP connection to.
     * @param string $url URL to connect to.
     * @return Curl
     */
    public function setUrl($url) {
        $this->url = $url;
        curl_setopt($this->ch, CURLOPT_URL, $url);
        return $this;
    }

    public function close() {
      curl_close($this->ch);
    }

    /**
     * Send the request.
     * @return Curl|null
     */
    public function send() {
        if (!$this->url) {
            return null;
        }
        if ('GET' == $this->type) {
            $this->url .= '?' . $this->data;
        } else {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS,
                $this->data);
            if ('PUT' == $this->type) {
                $header = 'Content-Length: '
                    . strlen($this->data);
                curl_setopt($this->ch, CURLOPT_HTTPHEADER,
                    array($header));
            }
        }
        $this->body = curl_exec($this->ch);
        $this->status = curl_getinfo($this->ch,
            CURLINFO_HTTP_CODE);
        return $this;
    }
}
?>
