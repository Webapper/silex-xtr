<?php
/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2015.12.22.
 * Time: 12:40
 */

namespace Webapper\Xtr;

class MethodDefinition {
	/**
	 * @var string
	 */
	public $method = 'GET';

	/**
	 * @var string
	 */
	public $action;

	/**
	 * @var string
	 */
	public $bind;

	/**
	 * @param string $pattern
	 * @param array $array
	 * @return static
	 */
	public static function FromArray(array $array, $pattern=null) {
		if (!isset($array['action'])) throw new \RuntimeException('No "action" defined for method "'.strtoupper(isset($array['method'])? $array['method'] : 'get').'" on route: '.$pattern);

		$def = new static();
		$def->method = strtoupper(isset($pattern)? $pattern : (isset($array['method'])? $array['method'] : 'get'));
		$def->action = $array['action'];
		$def->bind = isset($array['bind'])? $array['bind']: null;

		return $def;
	}

	/**
	 * @param array $parent
	 */
	public function toArray(array &$parent=null) {
		if (!isset($parent)) throw new \InvalidArgumentException('Missing argument.');
		$parent[$this->method] = [
			'action'	=> $this->action,
			'bind'		=> $this->bind
		];
	}

	public function __set($attr, $value) {
		throw new \RuntimeException('Adding new properties is disallowed by this object.');
	}
}