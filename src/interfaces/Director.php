<?php

namespace Hiraeth\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 *
 */
interface Director
{
	/**
	 *
	 */
	public function check(Request $request, Manager $manager, string $class): bool;
}
