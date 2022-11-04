<?php

namespace Hiraeth\Middleware;

use InvalidArgumentException;

use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Psr\Container\ContainerInterface as Container;
use RuntimeException;

/**
 * The middleware manager is responsible for aggregating middleware and their options and providing
 * lazy loading wrappers.
 */
class Manager
{
	/**
	 * The PSr-11 container used to instantiate middleware
	 *
	 * @var Container|null
	 */
	protected $container = NULL;


	/**
	 * Directors and related information keyed by class
	 *
	 * @var array<class-string, mixed>
	 */
	protected $directors = array();


	/**
	 * Middleware and related information keyed by class
	 *
	 * @var array<class-string, mixed>
	 */
	protected $middleware = array();


	/**
	 * Create a new instance of the manager
	 */
	public function __construct(?Container $container = NULL)
	{
		$this->container = $container;
	}


	/**
	 * Register a director with priority and options
	 *
	 * @param object|class-string $director
	 * @param array<class-string, mixed> $options
	 */
	public function addDirector($director, int $priority = 50, array $options = array()): Manager
	{
		if (is_object($director)) {
			$class  = get_class($director);
			$object = $director;

		} elseif (class_exists($director)) {
			$class  = $director;
			$object = NULL;

		} else {
			throw new InvalidArgumentException(sprintf(
				'Cannot register director "%s", must be director object or class name',
				$director
			));
		}

		$this->directors[$class] = [
			'priority' => $priority,
			'options'  => $options,
			'object'   => $object
		];

		return $this;
	}


	/**
	 * Register a middleware with priority and options
	 *
	 * @param object|class-string $middleware
	 * @param array<class-string, mixed> $options
	 */
	public function addMiddleware($middleware, int $priority = 50, array $options = array()): Manager
	{
		if (is_object($middleware)) {
			$class  = get_class($middleware);
			$object = $middleware;
		} elseif (class_exists($middleware)) {
			$class  = $middleware;
			$object = NULL;
		} else {
			throw new InvalidArgumentException(sprintf(
				'Cannot register middleware "%s", must be middleware object or class name',
				$middleware
			));
		}

		$this->middleware[$class] = [
			'active'   => TRUE,
			'priority' => $priority,
			'options'  => $options,
			'object'   => $object
		];

		return $this;
	}


	/**
	 * Get all lazy loading middleware for registered middlewares
	 *
	 * @return array<int, Middleware>
	 */
	public function getAll(): array
	{
		uasort($this->directors, function ($a, $b) {
			return $a['priority'] - $b['priority'];
		});

		uasort($this->middleware, function($a, $b) {
			return $a['priority'] - $b['priority'];
		});

		return array_map(
			function ($class) {
				return $this->proxy($class);
			},
			array_keys($this->middleware)
		);
	}


	/**
	 *
	 */
	public function getContainer(): ?Container
	{
		return $this->container;
	}


	/**
	 * Get the options for a middleware
	 *
	 * @param array<string, mixed> $defaults
	 * @return array<string, mixed>
	 */
	public function getOptions(string $class, array $defaults = array()): array
	{
		if (isset($this->middleware[$class])) {
			return $this->middleware[$class]['options'] + $defaults;
		}

		return $defaults;
	}


	/**
	 *
	 */
	public function getPriority(string $class): int
	{
		if (isset($this->middleware[$class])) {
			return $this->middleware[$class]['priority'];
		}

		return 50;
	}


	/**
	 *
	 */
	public function isActive(Request $request, string $class): bool
	{
		foreach (array_keys($this->directors) as $director) {
			if (!$this->resolveDirector($director)->check($request, $this, $class)) {
				return FALSE;
			}
		}

		return TRUE;
	}


	/**
	 * Get a single lazy loading middleware
	 *
	 * @param class-string $class
	 */
	public function proxy(string $class): Middleware
	{
		return new class ($class, $this) implements Middleware
		{
			/**
			 * @var class-string
			 */
			protected $class;

			/**
			 * @var Manager
			 */
			protected $manager;

			/**
			 * @param class-string $class
			 */
			public function __construct(string $class, Manager $manager)
			{
				$this->class     = $class;
				$this->manager   = $manager;
			}

			/**
			 *
			 */
			public function process(Request $request, Handler $handler): Response
			{
				if (!$this->manager->isActive($request, $this->class)) {
					return $handler->handle($request);
				}

				return $this->manager->resolveMiddleware($this->class)->process($request, $handler);
			}
		};
	}


	/**
	 * @param class-string $class
	 */
	public function resolveDirector(string $class): Director
	{
		if (!isset($this->directors[$class]['object'])) {
			$this->directors[$class]['object'] = $this->resolve($class);
		}

		if (!$this->directors[$class]['object'] instanceof Director) {
			throw new RuntimeException(sprintf(
				'Registered or resolved object for director %s is not actually a director',
				$class
			));
		}

		return $this->directors[$class]['object'];
	}


	/**
	 * @param class-string $class
	 */
	public function resolveMiddleware(string $class): Middleware
	{
		if (!isset($this->middleware[$class]['object'])) {
			$this->middleware[$class]['object'] = $this->resolve($class);
		}

		if (!$this->middleware[$class]['object'] instanceof Middleware) {
			throw new RuntimeException(sprintf(
				'Registered or resolved object for middleware %s is not actually middlware',
				$class
			));
		}

		return $this->middleware[$class]['object'];
	}


	/**
	 *
	 */
	protected function resolve(string $class): object
	{
		if (isset($this->container)) {
			return $this->container->get($class);
		} else {
			return new $class();
		}
	}
}
