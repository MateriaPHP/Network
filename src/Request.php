<?php

namespace Materia\Network;

/**
 * Request class
 *
 * @package	Materia.Network
 * @author	Filippo Bovo
 * @link	https://lab.alchemica.io/projects/materia/
 **/

class Request {

	protected $_host;
	protected $_fragment;
	protected $_agent;
	protected $_response;

	protected $_method  = 'GET';
	protected $_scheme  = 'http';
	protected $_port    = 0;
	protected $_user    = NULL;
	protected $_pass    = NULL;
	protected $_path    = '';
	protected $_body    = '';
	protected $_data    = [];
	protected $_query   = [];
	protected $_headers = [];
	protected $_locale  = 'en';
	protected $_ajax    = FALSE;

	/**
	 * Constructor
	 *
	 * @param	boolean	$globals	build the request from globals
	 **/
	public function __construct( bool $globals = FALSE ) {

		if ( $globals ) {

			// Set defaults
			$this->_ajax = isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ? ( $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) : FALSE;

			// Set scheme (protocol)
			if ( isset( $_SERVER['SERVER_PROTOCOL'] ) && ( strpos( $_SERVER['SERVER_PROTOCOL'], 'HTTPS' ) !== FALSE ) ) {

				$this->setScheme( 'https' );

			}

			// Set host
			if ( isset( $_SERVER['HTTP_HOST'] ) ) {

				if ( !isset( $_SERVER['SERVER_PORT'] ) ) {

					$this->setHost( $_SERVER['HTTP_HOST'] );

				}
				else {

					$this->setHost( rtrim( $_SERVER['HTTP_HOST'], ':' . $_SERVER['SERVER_PORT'] ) );

				}

			}

			// Set request method and data
			if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {

				$this->setMethod( $_SERVER['REQUEST_METHOD'] );

				if ( $_SERVER['REQUEST_METHOD'] != 'GET' ) {

					parse_str( file_get_contents( 'php://input' ), $data );

					$this->setData( $data );

				}

			}

			// Set port
			if ( isset( $_SERVER['SERVER_PORT'] ) ) {

				$this->setPort( $_SERVER['SERVER_PORT'] );

			}

			// Set path
			if ( isset( $_SERVER['REQUEST_URI'] ) ) {

				$path   = rtrim( $_SERVER['REQUEST_URI'], '/' );
				$script = str_replace( '\\', '/', $_SERVER['SCRIPT_NAME'] );
				$pos    = $script ? strpos( $path, $script ) : FALSE;

				// Remove script name if necessary
				if ( $pos === 0 ) {

					$path = substr( $path, strlen( $script ) );

				}

				// Rebuild the URL for validation
				$uri = $this->buildURL() . $path;

				if ( filter_var( $uri, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED ) ) {

					$this->setPath( parse_url( $uri, PHP_URL_PATH ) );

				}

			}

			// Set query string
			if ( isset( $_SERVER['QUERY_STRING'] ) ) {

				parse_str( $_SERVER['QUERY_STRING'], $query );

				$this->setQuery( $query );

			}

			// Set User Agent
			if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {

				$this->setUserAgent( $_SERVER['HTTP_USER_AGENT'] );

			}

			// Set language
			if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) && function_exists( 'locale_accept_from_http' ) ) {

				$this->setLocale( locale_accept_from_http( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) );

			}

			// Set headers
			if ( function_exists( 'getallheaders' ) ) {

				$this->setHeaders( getallheaders() );

			}

		}

	}

	/**
	 * Get remote IP address
	 *
	 * @param	bool	$real		try to guess the real IP address
	 * @return	string
	 **/
	public function getIP( bool $real = FALSE ) : string {

		$ip = '0.0.0.0';

		if ( $real && isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {

			$ip = $_SERVER['HTTP_CLIENT_IP'];

		}
		else if ( $real &&  isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ){

			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];

		}
		else if( $real && isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {

			$ip = $_SERVER['HTTP_X_FORWARDED'];

		}
		else if( $real && isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {

			$ip = $_SERVER['HTTP_FORWARDED_FOR'];

		}
		else if( $real && isset( $_SERVER['HTTP_FORWARDED'] ) ) {

			$ip = $_SERVER['HTTP_FORWARDED'];

		}
		else if( isset( $_SERVER['REMOTE_ADDR'] ) ) {

			$ip = $_SERVER['REMOTE_ADDR'];

		}

		return $ip;

	}

	/**
	 * Set request host
	 *
	 * @param	string	$host		host name
	 * @return	self
	 **/
	public function setHost( string $host ) : self {

		$this->_host = $host;

		return $this;

	}

	/**
	 * Get request host
	 *
	 * @return	string
	 **/
	public function getHost() : string {

		return $this->_host;

	}

	/**
	 * Set port numbner
	 *
	 * @param	integer	$port		port number
	 * @return	self
	 **/
	public function setPort( int $port ) : self {

		$this->_port = $port;

		return $this;

	}

	/**
	 * Get port number
	 *
	 * @return	integer
	 **/
	public function getPort() : int {

		return $this->_port;

	}

	/**
	 * Set scheme
	 *
	 * @param	string	$scheme		scheme
	 * @return	self
	 **/
	public function setScheme( string $scheme ) : self {

		$this->_scheme = $scheme;

		return $this;

	}

	/**
	 * Get scheme
	 *
	 * @return	string
	 **/
	public function getScheme() : string {

		return $this->_scheme;

	}

	/**
	 * Set username and password
	 *
	 * @param	string	$user	user name
	 * @param	string	$pass	password
	 * @return	self
	 **/
	public function setAuth( string $user, string $pass = NULL ) : self {

		$this->_user = $user;
		$this->_pass = $pass;

		return $this;

	}

	/**
	 * Set request method
	 *
	 * @param	string	$method		request method
	 * @return	self
	 **/
	public function setMethod( string $method ) : self {

		$method = strtoupper( $method );

		if ( in_array( $method, [ 'GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS' ] ) ) {

			$this->_method = $method;

		}

		return $this;

	}

	/**
	 * Get request method
	 *
	 * @return	string
	 **/
	public function getMethod() : string {

		return $this->_method;

	}

	/**
	 * Set request path
	 *
	 * @param	string	$path		request path
	 * @return	self
	 **/
	public function setPath( string $path ) : self {

		$this->_path = trim( $path, '/' );

		return $this;

	}

	/**
	 * Get request path
	 *
	 * @return	string
	 **/
	public function getPath() : string {

		return $this->_path;

	}

	/**
	 * Set request data
	 *
	 * @param	array	$data		request data
	 * @return	self
	 **/
	public function setData( array $data ) : self {

		$this->_data = $data;

		return $this;

	}

	/**
	 * Get data
	 *
	 * @return	array
	 **/
	public function getData() : array {

		return $this->_data;

	}

	/**
	 * Set query data
	 *
	 * @param	array	$data		query data
	 * @return	self
	 **/
	public function setQuery( array $data ) : self {

		$this->_query = $data;

		return $this;

	}

	/**
	 * Get query data
	 *
	 * @return	array
	 **/
	public function getQuery() : array {

		return $this->_query;

	}

	/**
	 * Set query fragment
	 *
	 * @param	string	$fragment	fragment
	 * @return	self
	 **/
	public function setFragment( string $fragment ) : self {

		$this->_fragment = ltrim( $fragment, '#' );

		return $this;

	}

	/**
	 * Get query fragment
	 *
	 * @return	string
	 **/
	public function getFragment() : string {

		return $this->_fragment;

	}

	/**
	 * Set User Agent
	 *
	 * @param	string	$agent	User Agent string
	 * @return	self
	 **/
	public function setUserAgent( string $agent ) : self {

		$this->_agent = filter_var( $agent, FILTER_SANITIZE_STRING, ( FILTER_FLAG_NO_ENCODE_QUOTES | FILTER_FLAG_STRIP_LOW ) );

		return $this;

	}

	/**
	 * Get User Agent
	 *
	 * @return	string
	 **/
	public function getUserAgent() : string {

		return $this->_agent;

	}

	/**
	 * Set language
	 *
	 * @param	string	$locale		language code
	 * @return	self
	 **/
	public function setLocale( string $locale ) : self {

		$this->_locale = $locale;

		return $this;

	}

	/**
	 * Get language
	 *
	 * @return	string
	 **/
	public function getLocale() : string {

		return $this->_locale;

	}

	/**
	 * Set Response instance
	 *
	 * @param	Response	$response	Response instance
	 * @return	self
	 **/
	public function setResponse( Response &$response ) : self {

		$this->_response = $response;

		return $this;

	}

	/**
	 * Get Response instance
	 *
	 * @return	Response
	 **/
	public function &getResponse() : Response {

		return $this->_response;

	}

	/**
	 * Set header
	 *
	 * @param	string	$name
	 * @param	mixed	$value
	 * @return	self
	 **/
	public function setHeader( string $name, $value ) : self {

		$this->_headers[$name] = $value;

		return $this;

	}

	/**
	 * Set multiple headers
	 *
	 * @param	array	$headers
	 * @return	self
	 **/
	public function setHeaders( array $headers ) : self {

		foreach ( $headers as $key => $value ) {

			$this->setHeader( $key, $value );

		}

		return $this;

	}

	/**
	 * Get headers
	 *
	 * @return	array
	 **/
	public function getHeaders() : array {

		return $this->_headers;

	}

	/**
	 * Build request URL
	 *
	 * @param	string	$path
	 * @param	array	$query
	 * @param	string	$fragment
	 * @return	string
	 **/
	public function buildURL( string $path = NULL, array $query = [], string $fragment = NULL ) : string {

		$url = NULL;

		// Append the protocol
		if ( isset( $this->_scheme ) ) {

			$url .= $this->_scheme . '://';

		}

		// Append auth credentials
		if ( isset( $this->_user ) ) {

			$url .= $this->_user;

			if ( isset( $this->_pass ) && $this->_pass ) {

				$url .= ':' . $this->_pass;

			}

			$url .= '@';

		}

		// Append hostname
		if ( isset( $this->_host ) ) {

			$url .= rtrim( $this->_host, '/' );

		}

		// Append the port number
		if ( isset( $this->_port ) && $this->_port ) {

			$url .= ':' . $this->_port;

		}

		// Append the path
		if ( $path ) {

			$url .= '/' . trim( $path, '/' );

		}
		else if ( $this->_path ) {

			$url .= '/' . trim( $this->_path, '/' );

		}

		// Append the query string
		if ( $query ) {

			$url .= '?' . http_build_query( $query );

		}
		else if ( $this->_query ) {

			$url .= '?' . http_build_query( $this->_query );

		}

		// Append fragment
		if ( $fragment ) {

			$url .= '#' . $fragment;

		}

		return $url;

	}

}
