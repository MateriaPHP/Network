<?php

namespace Materia\Network;

/**
 * HTTP Response class
 *
 * @package	Materia.Network
 * @author	Filippo Bovo
 * @link	https://lab.alchemica.io/projects/materia/
 **/

class Response {

	protected $_headers;
	protected $_status;
	protected $_body;

	protected static $codes = [
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',

		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',

		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',

		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
	];

	/**
	 * Constructor
	 *
	 * @param	integer	$code		status code
	 **/
	public function __construct( int $status = 200 ) {

		$this->clear();
		$this->setStatus( $status );

	}

	/**
	 * Sets the HTTP status of the response
	 *
	 * @param	integer	$code	HTTP status code
	 * @return	self
	 **/
	public function setStatus( int $status ) : self {

		if ( array_key_exists( $status, self::$codes ) ) {

			$this->_status = $status;

		}
		else {

			throw new \InvalidArgumentException( sprintf( 'Invalid status code (%s)', $status ) );

		}

		return $this;

	}

	/**
	 * Gets the HTTP status of the response
	 *
	 * @return	integer	$code	HTTP status code
	 **/
	public function getStatus() {

		return $this->_status;

	}

	/**
	 * Set a response header
	 *
	 * @param	string	$name	header name
	 * @param	string	$value	header value
	 * @return	self
	 **/
	public function setHeader( string $name, string $value = NULL ) : self {

		$this->_headers[$name] = $value;

		return $this;

	}

	/**
	 * Set multiple response headers
	 *
	 * @param	array	$headers	associative array of headers / Values
	 * @return	self
	 **/
	public function setHeaders( array $headers ) : self {

		foreach ( $headers as $key => $value ) {

			$this->setHeader( $key, $value );

		}

		return $this;

	}

	/**
	 * Writes content on the response body
	 *
	 * @param	string	$body		response content
	 * @param	boolean	$append		whatever append or not
	 * @return	self
	 **/
	public function setBody( string $body, bool $append = TRUE ) : self {

		if ( $this->_body && $append ) {

			$this->_body .= $body;

		}
		else {

			$this->_body  = $body;

		}

		return $this;

	}

	/**
	 * Get response body
	 *
	 * @return	string
	 **/
	public function getBody() {

		return $this->_body;

	}

	/**
	 * Reset the response
	 *
	 * @return	self
	 **/
	public function clear() : self {

		$this->_headers = [];
		$this->_status  = 200;
		$this->_body    = NULL;

		return $this;

	}

	/**
	 * Sets caching headers for the response
	 *
	 * @param	\DateTime	$date	expiration time
	 * @return	self
	 **/
	public function setCache( \DateTime $date ) : self {

		$gmt = new \DateTimeZone( 'GMT' );
		$now = new \DateTime( 'now', $gmt );

		// Set timezone to GMT
		$date->setTimezone( $gmt );

		$this->_headers['Expires'] = $date->format( 'D, d M Y H:i:s T' );

		if ( $date < $now ) {

			$this->_headers['Cache-Control'] = [
				'no-store, no-cache, must-revalidate',
				'post-check=0, pre-check=0',
				'max-age=0',
			];
			$this->_headers['Pragma']        = 'no-cache';

		}
		else {

			$this->_headers['Cache-Control'] = 'max-age=' . ( $date->getTimestamp() - $now->getTimestamp() );

		}

		return $this;

	}

	/**
	 * Sends the response to output and exit
	 */
	public function send() {

		// Clean output buffer
		if ( ob_get_length() > 0 ) {

			ob_end_clean();

		}

		// Try to send Headers
		if ( !headers_sent() ) {

			// Remove Status header
			if ( isset( $this->_headers['Status'] ) ) {

				unset( $this->_headers['Status'] );

			}

			foreach ( $this->_headers as $key => $value ) {

				$this->sendHeader( $key, $value );

			}

			// Send the status
			$code = $this->getStatus();

			if ( strpos( php_sapi_name(), 'cgi' ) !== FALSE ) {

				header( 'Status: ' . $code . ' ' . static::$codes[$code], TRUE );

			}
			else {

				header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] . ' ' : 'HTTP/1.1 ' ) . $code . ' ' . static::$codes[$code], TRUE, $code );

			}

		}

		exit( $this->_body );

	}

	/**
	 * Send headers
	 *
	 * @param	string	$key	header name
	 * @param	mixed	$value	header value
	 **/
	private function sendHeader( string $key, $value ) {

		if ( is_array( $value ) ) {

			foreach ( $value as $v ) {

				$this->sendHeader( $key, $v );

			}

		}
		else {

			header( $key . ': ' . $value );

		}

	}

	/**
	 * Stops processing and returns a given response
	 *
	 * @param integer	$code		HTTP status code
	 * @param integer	$message	response message
	 **/
	public function halt( int $code = 200, string $message = NULL ) {

		$this->clear()
		     ->setStatus( $code )
		     ->setBody( $message )
		     ->setCache( new \DateTime( '@0' ) )
		     ->send();

	}

	/**
	 * Redirects the current request to specific URL
	 *
	 * @param	string	$url	URL
	 * @param	integer	$code	HTTP status code
	 **/
	public function redirect( string $url, int $code = 303 ) {
		$this->clear()
		     ->setStatus( $code )
		     ->setHeader( 'Location', $url )
		     ->send();

	}

}
