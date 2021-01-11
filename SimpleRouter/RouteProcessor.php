<?php

declare(strict_types=1);

namespace Scriptor;

class RouteProcessor extends Module
{
	/**
	 * Variable flag constant
	 */
	const VARIABLE = true;

	/**
	 * Array of route parts and their specifications.
	 */
	private $route = [];

	/**
	 * Array with variables located in the route
	 */
	private $params = [];

	/**
	 * Number of segments
	 */
	private $total = 0;

	/**
	 * Instance Pool
	 */
	private static $instPool = [];

	/**
     * Create a new RouteProcessor instance.
     *
     */
	public function __construct() { parent::init(); }

	/**
	 * Checks if the called HTTP method matches matches them in the passed route.
	 * 
	 * @param string $method
	 * @param string|callable|null $callable - Function that should be called.
	 * @param array|null $params - Parameters of the function to be called.
	 * 
	 * @return bool
	 */
	public function matchMethod(string $method, string|callable|null $callable = null, 
		?array $params = []): bool 
	{
		if($_SERVER['REQUEST_METHOD'] == strtoupper($method)) {
			! $callable OR $this->exec($callable, $params);
			return true;
		}
		return false;
	}

	/**
	 * The same as the function matchMethod() above, but possible with multiple 
	 * methods.
	 * 
	 * @param array|null $methods
	 * @param string|callable|null $callable - Function that should be called.
	 * @param array|null $params - Parameters of the function to be called.
	 * 
	 * @return bool
	 */
	public function matchMethods(?array $methods, string|callable|null $callable = null, 
		?array $params = []): bool 
	{
		if(in_array($_SERVER['REQUEST_METHOD'], $methods)) {
			! $callable OR $this->exec($callable, $params);
			return true;
		}
		return false;
	}

	/**
	 * Checks if the url matches the given route.
	 * 
	 * @param string $expression
	 * 
	 * @return bool
	 */
	public function matchRoute(?string $expression): bool
	{
		$this->reset();
		$this->prepare($expression);

		if($this->total != $this->segments->total) return false;

		if($this->segments->total == 0) {
			return true;
		}

		foreach($this->segments->segment as $key => $value) {
			if(! $this->route[$key]['is_variable'] && 
				$this->route[$key]['segment'] != $value) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns the variables passed in path.
	 * 
	 * @return array
	 */
	public function getParams(): array { return $this->params; }

	/**
	 * Splits uri into separate parts (params, path's ...) and 
	 * specifies them depending on their type.
	 * 
	 * @param string|null $expression
	 */
	private function prepare(?string $expression): void
	{
		$parts = $this->split($expression);
		if(empty($parts) && $this->segments->total > 0) return;

		foreach($parts as $key => $part) {
			if(preg_match('%\{(.+?)\}%', $part)) {
				$this->addElement($part, self::VARIABLE);
				$this->params[] = $this->segments->get($key);
			} else {
				$this->addElement($part);
			}
		}
	}

	/**
	 * Joins route elements to be able to compare them later.
	 * 
	 * @param string $segment - Route segment
	 * @param bool $isVar - Flag variable or path
	 */
	private function addElement(string $segment, bool $isVar = false): void 
	{
		$this->route[] = ['segment' => $segment, 'is_variable' => $isVar];
		$this->total++;
	}

	/**
	 * Splits the path into its single parts.
	 * 
	 * @param string $path
	 * @return array
	 */
	private function split(string $path): array
	{
		$parts = [];
		$parseUrl = parse_url(trim($path));
		if(isset($parseUrl['path'])) {
			foreach(array_values(array_filter(array_map('trim',
				explode('/', $parseUrl['path'])))) as $key => $value) {
					$parts[$key] = $value;
			}
		}
		return $parts;
	}

	/**
	 * Reset the route parameter.
	 */
	private function reset(): void 
	{  
		$this->route = [];
		$this->total = 0;
		$this->params = [];
	}

	/**
	 * Calls any kind of module methods
	 * 
	 * NOTE: PHP's strrev() is not safe to use on utf-8 strings 
	 * because it reverses a string one byte at a time.
	 * Don't use Unicode strings as namespaces: 
	 *  ~~ namespace 漢字; ~~
	 * 
	 * @param string|callable - Method to be called
	 * @param array - Array of the arguments to call the method with
	 * @return return of the caled method
	 */
	public function exec(string|callable $method, ?array $params = []): void
	{
		if(self::isCallable($method)) { 
			call_user_func_array($method, $params); 
			return;
		}
		$namespace = null;
		$parts = explode('::', $method, 2);
		$revStr = explode('\\', strrev($parts[0]), 2);
		if(isset($revStr[1])) {
			$namespace = strrev($revStr[1]).'\\';
			$parts[0] = strrev($revStr[0]);
		}
		! is_array($parts) OR $this->getInstance($parts[0], $namespace)
			->{$parts[1]}($params);
	}
	

	/**
	 * Returns any instance of a Scriptor module. 
	 * 
	 * @param string $name - The name of an installed Scriptor module,
	 *                       depending on the module configuration, 
	 *                       eventually initiated.
	 * 
	 * @return object Module instance
	 */
	public function getInstance(string $name, ?string $namespace): ?object
	{
		if(self::exists($name)) return self::$instPool[$name];
		return $this->load($name, $namespace);
	}

	/**
	 * This method checks and returns "true" if the parameter 
	 * passed appears to be callable, otherwise "false"
	 * 
	 * @param string|callable $f
	 * 
	 * @return bool
	 */
	private static function isCallable(string|callable $f): bool 
	{
		return (is_string($f) && function_exists($f)) || 
			(is_object($f) && ($f instanceof \Closure));
	}

	/**
	 * Checks if the module instance already exists in InstancePool
	 * 
	 * @param string $prop
	 * @return bool
	 */
	private static function exists(string $prop): bool
	{
		if(isset(self::$instPool[$prop])) return true;
		return false;
	}

	/**
	 * Loads the not yet available controller instance 
	 * into the InstancePool and returns it.
	 * 
	 * @param string $name - Instance name
	 * @param string|null $namespace
	 * 
	 * @return object Module insatnce
	 * 
	 * @throws \ErrorException
	 */
	private function load(string $name, ?string $namespace): ?object
	{
		if($namespace) $module = $this->loadModule($name, ['namespace' => $namespace]);
		else $module = $this->loadModule($name);
		
		if($module) {
			self::$instPool[$name] = $module;
			return self::$instPool[$name];
		}
		\Imanager\Util::logException(new \ErrorException(
			'Module "'.$namespace.$name.'" not found.')
		);
	}
}