<?php

namespace Materia\Network\Handlers;

/**
 * Routing interface
 *
 * @package	Materia.Network
 * @author	Filippo Bovo
 * @link	https://lab.alchemica.io/projects/materia/
 **/

use \Materia\Network\Handler as Handler;
use \Materia\Network\Request as Request;
use \Materia\Network\Response as Response;

interface Router extends Handler {

    /**
     * Set a route
     *
     * @param   string      $pattern    pattern
     * @param   callable    $callback   callable
     * @return  self
     **/
    public function set( string $pattern, callable $callback ) : self;

}
