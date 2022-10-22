<?php
declare(strict_types=1);

namespace Scriptor\Modules;

use Scriptor\Core\Module;
use Scriptor\Core\Scriptor;
use Scriptor\Module\RouteProcessor;

/**
 * The route registrar class
 */
class Route extends Module
{
	/**
	 * RouteCollection array
	 * 
	 * @var array $_routes
	 */
	private static $_routes = [];

	/**
	 * Instance of the RouteProcessor
	 * 
	 * @var object $_processor - instance of RouteProcessor
	 */
	private static $_processor;

	/**
	 * Method that will be called if none of the specified 
	 * routes match. Can be used to display 404 Not Found 
	 * HTTP status code.
	 * 
	 * @var string|callable $_routeNotMatch
	 */
	private static $_routeNotMatch;

	/**
	 * Called when the route matches but the method does not.
	 * 
	 * @var string|callable $_methodNotAllowed
	 */
	private static $_methodNotAllowed;

	/**
	 * Get the package of registered routes
	 * 
	 * @return array
	 */
	public static function routes(): array { return self::$_routes; }

	/**
	 * Returns an instance of the RouteProcessor class if the 
	 * instance is already created, if not a new instance is 
	 * generated and buffered temporarily.
	 * 
	 * @return \Scriptor\RouteProcessor
	 */
	public static function processor():  RouteProcessor 
	{
		if(! self::$_processor) {
			Scriptor::load(__DIR__.'/RouteProcessor.php');
			self::$_processor = new RouteProcessor();
		}
		return self::$_processor;
	}
	
	/**
     * Add a route to the route collection.
     *
     * @param string $exp
     * @param array|string|callable $callable
     * @param string|array $method
     */
	public static function add(string $exp, callable|string $callable, 
		string|array $method = ['GET']): void 
	{
		self::$_routes[] = [   
			'expression' => $exp,
			'callable' => $callable,
			'methods' => is_array($method) ? array_map('strtoupper', $method) : 
				[strtoupper($method)]
		];
	}

	/**
	 * Add no match route method
	 * 
	 * @param callable|string $callable
	 */
	public static function routeNotMatch(callable|string $callable): void 
	{
		self::$_routeNotMatch = $callable;
	}

	/**
	 * Add method not allowed 
	 * 
	 * @param callable|string $callable
	 */
	public static function methodNotAllowed(callable|string $callable): void 
	{
		self::$_methodNotAllowed = $callable;
	}

	/**
     * Processing route collection.
     *
     * @param bool $request
	 */
	public static function run(bool $multimatch = false): void
	{
		$processor = self::processor();
		$matchedMethod = false;
		$matchedRoute = false;

		foreach(self::$_routes as $route) {
			if($processor->matchRoute($route['expression'])) {
				$matchedRoute = true;
				if($processor->matchMethods($route['methods'])) {
					$matchedMethod = true;
					$processor->exec($route['callable'], $processor->getParams());
					if(! $multimatch) return;
				}
			}
		}

		if(! $matchedRoute && self::$_routeNotMatch) {
			$processor->exec(self::$_routeNotMatch, 
				[$processor->segments->getUrl(['useTrailingSlash' => false])]
			);
		} elseif(! $matchedMethod && self::$_methodNotAllowed) {
			$processor->exec(self::$_methodNotAllowed, 
				[
					$processor->segments->getUrl(['useTrailingSlash' => false]),
					$processor->sanitizer->text($_SERVER['REQUEST_METHOD'])
				]
			);
		}
	}
}