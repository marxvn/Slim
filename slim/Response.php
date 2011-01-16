<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author		Josh Lockhart
 * @link		http://www.slimframework.com
 * @copyright	2011 Josh Lockhart
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * Response
 *
 * Object-oriented representation of an HTTP response that is
 * returned to the client. This class is responsible for:
 *
 * - HTTP response status
 * - HTTP response body
 * - HTTP response headers
 * - HTTP response cookies
 *
 * @package	Slim
 * @author	Josh Lockhart <info@joshlockhart.com>
 * @author	Kris Jordan <http://github.com/KrisJordan>
 * @since	Version 1.0
 */
class Response {

    /**
     * @var Request
     */
    private $request;
    
	/**
	 * @var int HTTP status code
	 */
	private $status = 200;

	/**
	 * @var array Key-value array of HTTP response headers
	 */
	private $headers = array();

	/**
	 * @var string HTTP response body
	 */
	private $body = '';

	/**
	 * @var int Length of HTTP response body
	 */
	private $length = 0;

	/**
	 * @var array HTTP response codes and messages
	 */
	private static $messages = array(
		//Informational 1xx
		100 => '100 Continue',
		101 => '101 Switching Protocols',
		//Successful 2xx
		200 => '200 OK',
		201 => '201 Created',
		202 => '202 Accepted',
		203 => '203 Non-Authoritative Information',
		204 => '204 No Content',
		205 => '205 Reset Content',
		206 => '206 Partial Content',
		//Redirection 3xx
		300 => '300 Multiple Choices',
		301 => '301 Moved Permanently',
		302 => '302 Found',
		303 => '303 See Other',
		304 => '304 Not Modified',
		305 => '305 Use Proxy',
		306 => '306 (Unused)',
		307 => '307 Temporary Redirect',
		//Client Error 4xx
		400 => '400 Bad Request',
		401 => '401 Unauthorized',
		402 => '402 Payment Required',
		403 => '403 Forbidden',
		404 => '404 Not Found',
		405 => '405 Method Not Allowed',
		406 => '406 Not Acceptable',
		407 => '407 Proxy Authentication Required',
		408 => '408 Request Timeout',
		409 => '409 Conflict',
		410 => '410 Gone',
		411 => '411 Length Required',
		412 => '412 Precondition Failed',
		413 => '413 Request Entity Too Large',
		414 => '414 Request-URI Too Long',
		415 => '415 Unsupported Media Type',
		416 => '416 Requested Range Not Satisfiable',
		417 => '417 Expectation Failed',
		//Server Error 5xx
		500 => '500 Internal Server Error',
		501 => '501 Not Implemented',
		502 => '502 Bad Gateway',
		503 => '503 Service Unavailable',
		504 => '504 Gateway Timeout',
		505 => '505 HTTP Version Not Supported'
	);

	/**
	 * @var CookieJar Manages Cookies to be sent with this Response
	 */
	protected $cookieJar;

	/**
	 * Constructor
	 */
	public function __construct( Request $req ) {
	    $this->request = $req;
		$this->header('Content-Type', 'text/html');
	}

	/***** ACCESSORS *****/

	/**
	 * Set and/or get the HTTP response status code
	 *
	 * @param	int $status
	 * @return 	int
	 * @throws	InvalidArgumentException If argument is not a valid HTTP status code
	 */
	public function status( $status = null ) {
		if ( !is_null($status) ) {
			if ( !in_array(intval($status), array_keys(self::$messages)) ) {
				throw new InvalidArgumentException('Cannot set Response status. Provided status code "' . $status . '" is not a valid HTTP response code.');
			}
			$this->status = intval($status);
		}
		return $this->status;
	}

	/**
	 * Get HTTP response headers
	 *
	 * @return array
	 */
	public function headers() {
		return $this->headers;
	}

	/**
	 * Get and/or set an HTTP response header
	 *
	 * @param	string		$key	The header name
	 * @param	string		$value	The header value
	 * @return 	string|null			The header value, or NULL if header not set
	 */
	public function header( $key, $value = null ) {
		if ( !is_null($value) ) {
			$this->headers[$key] = $value;
		}
		return isset($this->headers[$key]) ? $this->headers[$key] : null;
	}

	/**
	 * Set the HTTP response body
	 *
	 * @param	string $body 	The new HTTP response body
	 * @return 	string 			The new HTTP response body
	 */
	public function body( $body = null ) {
		if ( !is_null($body) ) {
			$this->body = '';
			$this->length = 0;
			$this->write($body);
		}
		return $this->body;
	}

	/**
	 * Append the HTTP response body
	 *
	 * @param	string $body 	Content to append to the current HTTP response body
	 * @return 	string 			The updated HTTP response body
	 */
	public function write( $body ) {
		$body = (string)$body;
		$this->length += strlen($body);
		$this->body .= $body;
		$this->header('Content-Length', $this->length);
		return $body;
	}

	/***** COOKIES *****/

	/**
	 * Set cookie jar
	 *
	 * @param	CookieJar $cookieJar
	 * @return 	void
	 */
	public function setCookieJar( CookieJar $cookieJar ) {
		$this->cookieJar = $cookieJar;
	}

	/**
	 * Get cookie jar
	 *
	 * @return CookieJar
	 */
	public function getCookieJar() {
		return $this->cookieJar;
	}

	/***** FINALIZE BEFORE SENDING *****/

	/**
	 * Finalize response headers before response is sent
	 *
	 * @return void
	 */
	public function finalize() {
		if ( in_array($this->status, array(204, 304)) ) {
			$this->body('');
			unset($this->headers['Content-Type']);
		}
	}

	/***** HELPER METHODS *****/

	/**
	 * Get message for HTTP status code
	 *
	 * @return string|null
	 */
	public static function getMessageForCode( $status ) {
		return isset(self::$messages[$status]) ? self::$messages[$status] : null;
	}

	/**
	 * Can this HTTP response have a body?
	 *
	 * @return bool
	 */
	public function canHaveBody() {
		return ( $this->status < 100 || $this->status >= 200 ) && $this->status != 204 && $this->status != 304;
	}

	/***** SEND RESPONSE *****/

	/**
	 * Send headers for HTTP response
	 *
	 * @return void
	 */
	protected function sendHeaders() {

		//Finalize response
		$this->finalize();
        
        //Remove body BUT NOT HEADERS if HEAD request
        if ( $this->request->method === Request::METHOD_HEAD ) {
            $this->body = '';
        }
        
		if ( substr(PHP_SAPI, 0, 3) === 'cgi') {
			//Send Status header if running with fastcgi
		    header('Status: ' . Response::getMessageForCode($this->status()));
		} else {
			//Else send HTTP message
		    header('HTTP/1.1 ' . Response::getMessageForCode($this->status()));
		}

		//Send headers
		foreach ( $this->headers() as $name => $value ) {
			header("$name: $value");
		}

		//Send cookies
		foreach ( $this->getCookieJar()->getResponseCookies() as $name => $cookie ) {
			setcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpires(), $cookie->getPath(), $cookie->getDomain(), $cookie->getSecure(), $cookie->getHttpOnly());
		}

		//Flush all output to client
		flush();

	}

	/**
	 * Send HTTP response
	 *
	 * This method will set Response headers, set Response cookies,
	 * and `echo` the Response body to the current output buffer.
	 *
	 * @return void
	 */
	public function send() {
		if ( !headers_sent() ) {
			$this->sendHeaders();
		}
		if ( $this->canHaveBody() ) {
			echo $this->body;
		}
	}

}

?>