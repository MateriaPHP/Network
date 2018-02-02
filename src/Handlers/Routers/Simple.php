<?php

namespace Materia\Network\Handlers\Routers;

/**
 * Basic routing class
 *
 * @package	Materia.Network
 * @author	Filippo Bovo
 * @link	https://lab.alchemica.io/projects/materia/
 **/

use \Materia\Network\Handlers\Router as Router;
use \Materia\Network\Request as Request;
use \Materia\Network\Response as Response;

class Simple implements Router {

	protected $_routes = [];

	/**
	 * @see Router::set()
	 **/
	public function set( string $pattern, callable $callback ) : Router {

		// Parse allowed request methods
		if ( strpos( $pattern, ' ' ) !== FALSE ) {

			list( $methods, $url ) = explode( ' ', trim( $pattern ), 2 );

			$methods = array_map( 'strtoupper', explode( '|', $methods ) );
			$pattern = trim( $url );

		}
		else {

			$methods = [ 'GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD' ];
			$pattern = trim( $pattern );

		}

		// Register routes
		foreach ( $methods as $method ) {

			$this->_routes[$method][$pattern] = $callback;

		}

		return $this;

	}

	/**
	 * @see	Router::execute()
	 **/
	public function execute( Request $request ) : Response {

		$params = [];
		$method = $request->getMethod();
		$path   = $request->getPath();

		// Method not supported
		if( !isset( $this->_routes[$method] ) ) {

			return ( new Response( 404 ) );

		}

		// Sort routes by length first, then alphabetically
		uksort( $this->_routes[$method], function( $a, $b ) {

			$la = strlen( $a );
			$lb = strlen( $b );

			if ( $la == $lb ) {

				return strcasecmp( $a, $b );

			}

			return $la - $lb;

		});

		// Iterate
		foreach ( $this->_routes[$method] as $pattern => $callback ) {

			$pattern = str_replace( '/%', '', $pattern, $count );

			// Matches
			if ( stripos( $path, $pattern ) === 0 ) {

				// Get params
				$params = explode( '/', substr( $path, strlen( $pattern ) ) );
				$params = array_filter( $params );

				// Add 1 to count because of the trailing slash
				if ( count( $params ) > $count ) {

					continue;

				}

				// Prepend the request
				array_unshift( $params, $request );

				return call_user_func_array( $callback, $params );

			}

		}

		return ( new Response( 404 ) );

	}

}
