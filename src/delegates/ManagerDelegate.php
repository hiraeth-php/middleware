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
	 * @var array<string, mixed>
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
		$middleware = $app->getConfig('*', 'middleware', static::$defaultConfig);
		$directors  = $app->getConfig('*', 'director', static::$defaultConfig);
		$instance   = new Manager($app);

		foreach ($middleware as $config) {
			if (!$config['class'] || $config['disabled']) {
				continue;
			}

			$instance->addMiddleware($config['class'], $config['priority'], $config['options']);
		}


		foreach ($directors as $config) {
			if (!$config['class'] || $config['disabled']) {
				continue;
			}

			$instance->addDirector($config['class'], $config['priority'], $config['options']);
		}

		return $app->share($instance);
	}
}
