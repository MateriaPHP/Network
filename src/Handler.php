<?php

namespace Materia\Network;

/**
 * Request Handler interface
 *
 * @package	Materia.Network
 * @author	Filippo Bovo
 * @link	https://lab.alchemica.io/projects/materia/
 **/

interface Handler {

	/**
	 * Performs the request
	 *
	 * @param	Request		$request
	 * @return	Response
	 **/
	public function execute( Request $request ) : Response;

}
