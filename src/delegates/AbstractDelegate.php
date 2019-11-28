<?php

namespace Hiraeth\Middleware;

use Hiraeth;

/**
 * {@inheritDoc}
 */
abstract class AbstractDelegate implements Hiraeth\Delegate
{
	/**
	 * Default options for the middleware
	 *
	 * @var array
	 */
	protected static $defaultOptions = array();


	/**
	 * The middleware manager
	 *
	 * @var Manager
	 */
	protected $manager = NULL;


	/**
	 * Create a new instance of the delegate
	 */
	public function __construct(Manager $manager)
	{
		$this->manager = $manager;
	}


	/**
	 * Get the options for the mdidleware
	 */
	public function getOptions()
	{
		return $this->manager->getOptions(static::getClass(), static::$defaultOptions);
	}
}
