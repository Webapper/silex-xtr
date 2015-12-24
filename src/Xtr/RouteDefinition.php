<?php
/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2015.12.22.
 * Time: 12:37
 */

namespace Webapper\Xtr;

class RouteDefinition extends MethodDefinition implements RouteCollectionInterface {
	/**
	 * @var string
	 */
	public $pattern;

	/**
	 * @var string
	 */
	public $ns;

	/**
	 * @var MethodDefinition[]
	 */
	public $methods = [];

	/**
	 * @var array
	 */
	public $assert = [];

	/**
	 * @var array
	 */
	public $defaults = [];

	/**
	 * @var array
	 */
	public $convert = [];

	/**
	 * @var RouteDefinition[]
	 */
	protected $subRoutes = [];

	/**
	 * RouteDefinition constructor.
	 * @param array $subRoutes
	 */
	public function __construct(array $subRoutes=[]) {
		$this->subRoutes = $subRoutes;
	}

	/**
	 * @param string $pattern
	 * @param array $array
	 * @return static
	 */
	public static function FromArray(array $array, $pattern=null) {
		$def = new static();
		$def->pattern = $pattern;
		$def->ns = isset($array['ns'])? $array['ns'] : '';
		$def->assert = isset($array['assert'])? $array['assert'] : [];
		$def->convert = isset($array['convert'])? $array['convert'] : [];
		$def->defaults = isset($array['defaults'])? $array['defaults'] : [];

		if (isset($array['methods'])) {
			if (!is_array($array['methods']) and !($array['methods'] instanceof \Traversable)) throw new \RuntimeException('Definition of "methods" should be array or traversable on route: '.$pattern);
			foreach ($array as $method=>$definition) {
				$methodDefinition = array_merge(['method'=>strtoupper($method)], $definition);
				$def->methods[$methodDefinition['method']] = MethodDefinition::FromArray($methodDefinition, $pattern);
			}
		} else {
			if (!isset($array['action'])) throw new \RuntimeException('No "action" defined for method "'.strtoupper(isset($array['method'])? $array['method'] : 'get').'" on route: '.$pattern);
			$def->method = strtoupper(isset($array['method'])? $array['method'] : 'get');
			$def->action = $array['action'];
			$def->bind = isset($array['bind'])? $array['bind']: null;
		}

		// identifying sub-routes:
		foreach ($array as $index=>$value) {
			if ($index{0} === '/') {
				$def->addSubroute(static::FromArray($value, $index));
			}
		}

		return $def;
	}

	/**
	 * @param array $parent
	 */
	public function toArray(array &$parent=null) {
		if (!isset($parent)) throw new \InvalidArgumentException('Missing argument.');
		$parent[$this->pattern] = [
			'ns'		=> $this->ns,
			'assert'	=> $this->assert,
			'convert'	=> $this->convert,
			'defaults'	=> $this->defaults
		];
		if (isset($this->action)) {
			$parent[$this->pattern]['method'] = $this->method;
			$parent[$this->pattern]['action'] = $this->action;
			if (isset($this->bind)) $parent[$this->pattern]['bind'] = $this->bind;
		} else {
			$parent[$this->pattern]['methods'] = [];
			foreach ($this->methods as $method) {
				/** @var $method MethodDefinition */
				$method->toArray($parent[$this->pattern]['methods']);
			}
		}
		foreach ($this->subRoutes as $route) {
			/** @var $route RouteDefinition */
			$route->toArray($parent[$this->pattern]);
		}
	}

	/**
	 * @return RouteDefinition[]
	 */
	public function sliceByMethods() {
		if (count($this->methods) == 0) return [clone $this];

		$result = [];
		foreach ($this->methods as $method=>$definition) {
			$route = clone $this;
			$route->methods = [];
			$route->method = $definition->method;
			$route->action = $definition->action;
			$route->bind = $definition->bind;
			$result[] = $route;
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function getBind() {
		if (isset($this->bind)) return $this->bind;

		$reSneak = '#[^a-z0-9]#i';
		$reSlug = '#\_+#';

		$result = $this->pattern;
		$result = preg_replace($reSneak, '_', $result);
		$result = preg_replace($reSlug, '_', $result);
		$result = strtolower($this->method.'_'.$result);

		return $result;
	}

	/**
	 * @param RouteDefinition $route
	 * @return $this
	 */
	public function addSubroute(RouteDefinition $route) {
		$this->subRoutes[$route->pattern] = $route;
		return $this;
	}

	/**
	 * @return RouteDefinition[]
	 */
	public function getSubroutes() {
		return $this->subRoutes;
	}

	/**
	 * @return bool
	 */
	public function hasSubroutes() {
		return (bool)count($this->subRoutes);
	}

	/**
	 * @return $this
	 */
	public function detachSubroutes() {
		$this->subRoutes = [];
		return $this;
	}

	/**
	 * @param RouteCollectionInterface|null $parent
	 * @return RouteCollection
	 */
	public function flat(RouteCollectionInterface $parent=null) {
		$patternPrefix = isset($parent) && $parent instanceof RouteDefinition? $parent->pattern : '';
		$self = clone $this;
		$result = [
			$patternPrefix.$this->pattern	=> $self->detachSubroutes()
		];
		if ($parent !== null and $parent instanceof RouteDefinition) {
			$self->pattern = $patternPrefix.$this->pattern;
			$self->ns = $parent->ns.$this->ns;
		}
		foreach ($this->subRoutes as $pattern=>$route) {
			$result = array_merge($result, $route->flat($this)->getSubroutes());
		}

		// order by pattern on all:
		if ($parent === null) {
			$getSortIndex = function($pattern) {
				$re = '#\{[^\}]*?\}#';
				$count = '000';
				if ($c = preg_match_all($re, $pattern)) {
					$count = str_pad($c, 3, '0', STR_PAD_LEFT);
				}
				$index = preg_replace($re, '%', $pattern).$count;

				return $index;
			};

			// normalized sorting:
			uksort($result, function($a, $b) use ($getSortIndex) {
				$a = $getSortIndex($a);
				$b = $getSortIndex($b);
				return strcmp($a, $b);
			});
			$result = array_reverse($result);
		}

		$collection = new RouteCollection($result);

		return $collection;
	}
}