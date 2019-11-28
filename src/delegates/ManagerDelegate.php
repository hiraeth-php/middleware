<?php

namespace Hiraeth\Middleware;

use Hiraeth;

/**
 * {@inheritDoc}
 */
class ManagerDelegate implements Hiraeth\Delegate
{
	/**
	 * Default configuration for a middleware
	 *
	 * @var array
	 */
	static $defaultConfig = [
		'class'    => NULL,
		'disabled' => FALSE,
		'priority' => 50,
		'options'  => array()
	];


	/**
	 * {@inheritDoc}
	 */
	static public function getClass(): string
	{
		return Manager::class;
	}


	/**
	 * {@inheritDoc}
	 */
	public function __invoke(Hiraeth\Application $app): object
	{
		$configs  = $app->getConfig('*', 'middleware', static::$defaultConfig);
		$instance = new Manager($app);

		foreach ($configs as $config) {
			if (!$config['class'] || $config['disabled']) {
				continue;
			}

			$instance->register($config['class'], $config['priority'], $config['options']);
		}

		return $instance;
	}
}
