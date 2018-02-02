<?php

namespace Materia\Network\Handlers;

/**
 * CURL Request handler class
 *
 * @package	Materia.Network
 * @author	Filippo Bovo
 * @link	https://lab.alchemica.io/projects/materia/
 **/

use \Materia\Network\Handler as Handler;
use \Materia\Network\Request as Request;
use \Materia\Network\Response as Response;

class CURL implements Handler {

	/**
	 * @see	Handler::execute()
	 **/
	public function execute( Request $request ) : Response {

		$url  = $request->buildURL();
		$curl = curl_init();

		// Auth
		if ( $user = parse_url( $url,  PHP_URL_USER ) ) {

			if ( $pass = parse_url( $url,  PHP_URL_PASS ) ) {

				curl_setopt( $curl, CURLOPT_USERPWD, $user );

			}
			else {

				curl_setopt( $curl, CURLOPT_USERPWD, $user . ':' . $pass );

			}

		}

		switch ( $request->getMethod() ) {

			// DELETE
			case 'DELETE':
				curl_setopt( $curl, CURLOPT_URL, $url );
				curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'DELETE' );
				break;

			// PUT & PATCH
			case 'PUT':
			case 'PATCH':
				curl_setopt( $curl, CURLOPT_URL, $url );
				curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $request->getMethod() );
				curl_setopt( $curl, CURLOPT_POSTFIELDS, $request->getData() );
				break;

			// POST
			case 'POST':
				curl_setopt( $curl, CURLOPT_URL, $url );
				curl_setopt( $curl, CURLOPT_POST, TRUE );
				curl_setopt( $curl, CURLOPT_POSTFIELDS, $request->getData() );
				break;

			// GET
			case 'GET':
				curl_setopt( $curl, CURLOPT_URL, $url );
				break;

		}

		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $request->getHeaders() );

		$body   = curl_exec( $curl );
		$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

		curl_close( $curl );

		$response = new Response();

		$response->setStatus( $status )
		         ->setBody( trim( $body ), FALSE );

		return $response;

	}

}
