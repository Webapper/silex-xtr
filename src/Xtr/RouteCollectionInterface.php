<?php
/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2015.12.24.
 * Time: 7:17
 */

namespace Webapper\Xtr;


interface RouteCollectionInterface {
	public function __construct(array $subRoutes=[]);
	public static function FromArray(array $array, $pattern=null);
	public function toArray(array &$parent=null);
	public function addSubroute(RouteDefinition $route);
	public function getSubroutes();
	public function hasSubroutes();
	public function detachSubroutes();
	public function flat(RouteCollectionInterface $parent=null);
}