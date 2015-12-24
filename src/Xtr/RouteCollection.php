<?php
/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2015.12.23.
 * Time: 10:11
 */

namespace Webapper\Xtr;

use Symfony\Component\Routing\RouteCollection as SilexCollection;
use Silex\Route;

class RouteCollection implements RouteCollectionInterface {
	/**
	 * @var RouteDefinition[]
	 */
	protected $subRoutes = [];

	/**
	 * RouteCollection constructor.
	 * @param array $subRoutes
	 */
	public function __construct(array $subRoutes=[]) {
		$this->subRoutes = $subRoutes;
	}

	public static function FromArray(array $array, $pattern=null) {
		$result = new static();
		foreach ($array as $pattern=>$definition) {
			$result->addSubroute(RouteDefinition::FromArray($definition, $pattern));
		}
		return $result;
	}

	public function toArray(array &$parent=null) {
		$result = [];
		foreach ($this->subRoutes as $route) {
			/** @var $route RouteDefinition */
			$route->toArray($result);
		}
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
		$result = [];

		foreach ($this->subRoutes as $pattern=>$route) {
			$result = array_merge($result, $route->flat($this)->getSubroutes());
		}

		// order by pattern on all:
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

		$collection = new RouteCollection($result);

		return $collection;
	}

	public function toSilexCollection() {
		$xtrColl = $this->flat();
		$result = new SilexCollection();

		foreach ($xtrColl->getSubroutes() as $route) {
			foreach ($route->sliceByMethods() as $methodRoute) {
				$silexRoute = new Route(
					$methodRoute->pattern,
					$methodRoute->defaults,
					$methodRoute->assert,
					[],	// options
					'',	// host
					[],	// schemes
					explode('|', $methodRoute->method),
					''	// condition
				);
				foreach ($methodRoute->convert as $item=>$value) {
					$silexRoute->convert($item, $value);
				}

				$result->add($methodRoute->getBind(), $silexRoute);
			}
		}

		return $result;
	}
}