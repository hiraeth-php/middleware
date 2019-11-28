<?php

namespace Hiraeth\Middleware;

use InvalidArgumentException;

use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Psr\Container\ContainerInterface as Container;

/**
 * The middleware manager is responsible for aggregating middleware and their options and providing
 * lazy loading wrappers.
 */
class Manager
{
	/**
	 * The PSr-11 container used to instantiate middleware
	 *
	 * @var Container
	 */
	protected $container = NULL;


	/**
	 * Options for middleware keyed by class
	 *
	 * @var array
	 */
	protected $options = array();


	/**
	 * Priorities for middleware keyed by class
	 *
	 * @var array
	 */
	protected $priorities = array();


	/**
	 * Create a new instance of the manager
	 */
	public function __construct(Container $container = NULL)
	{
		$this->container = $container;
	}


	/**
	 * Get a single lazy loading middleware
	 *
	 * @var
	 */
	public function get(string $class): Middleware
	{
		return new class($this->container, $class) implements Middleware {
			protected $container = NULL;
			protected $class = NULL;

			public function __construct($container, $class)
			{
				$this->container = $container;
				$this->class     = $class;
			}

			public function process(Request $request, Handler $handler): Response
			{
				$middleware = $this->container
					? $this->container->get($this->class)
					: new $this->class;

				return $middleware->process($request, $handler);
			}
		};
	}


	/**
	 * Get all lazy loading middleware for registered middlewares
	 */
	public function getAll(): array
	{
		$classes = array_keys($this->priorities);

		uksort($classes, function($a, $b) {
			return $this->priorities[$a] - $this->priorities[$b];
		});

		return array_map($classes, [$this, 'get']);
	}


	/**
	 * Get the options for a middleware
	 */
	public function getOptions(string $class, array $defaults = array()): array
	{
		if (isset($this->options[$class])) {
			return $this->options[$class] + $defaults;
		}

		return $defaults;
	}


	/**
	 * Register a middleware with priority and options
	 */
	public function register(string $class, int $priority = 50, array $options = array()): Manager
	{
		if (!in_array(Middleware::class, class_implements($class))) {
			throw new InvalidArgumentException(sprintf(
				'Cannot register middleware "%s", does not implement middleware interface',
				$class
			));
		}

		$this->priorities[$class] = $priority;
		$this->options[$class]    = $options;

		return $this;
	}
}
