<?php
/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2015.12.21.
 * Time: 17:50
 */

namespace Webapper\Xtr\Services;

use Webapper\Xtr\XtrService;

class I18nSwitcher implements XtrServiceInterface {
	/**
	 * @var array
	 */
	protected $defaultConfig = [
		'priority'		=> XtrServiceInterface::PRIORITY_NORMAL,
		'get_locale'	=> 'Webapper\XtrService\Services\I18nSwitcher\get_locale',
		'locale'		=> 'none'
	];

	/**
	 * @var array
	 */
	protected $config = [];

	/**
	 * @var XtrService
	 */
	protected $xtr;

	public function __construct($config, XtrService $xtr) {
		$this->config = array_merge($this->defaultConfig, (array)$config);
		$this->xtr = $xtr;
	}

	public function getPriority() {
		$priority = $this->config['priority'];

		switch (strtoupper($priority)) {
			case 'HIGH': $priority = XtrServiceInterface::PRIORITY_HIGH; break;
			case 'LOW': $priority = XtrServiceInterface::PRIORITY_LOW; break;
			case 0:
			case 'NULL':
			case 'AUTO':
			case 'NORMAL': $priority = XtrServiceInterface::PRIORITY_NORMAL; break;
		}

		return $priority;
	}

	public function run() {
		if (strtolower($this->config['locale']) == 'none') return;

		if (strtolower($this->config['locale']) == 'auto') {
			$get_locale = $this->config['get_locale'];
			if (!function_exists($get_locale)) throw new \RuntimeException('Unable to find an implementation for get_locale(Application $app) function by config value: "' . $get_locale . '"');
			$locale = $get_locale($this->xtr->getApplication());
		} else {
			$locale = strtolower($this->config['locale']);
		}

		$routes = $this->xtr['routes'];
		if (!is_array($routes)) throw new \RuntimeException('Unable to find routes definitions.');
		if (!isset($routes[$locale])) throw new \RuntimeException('Unable to find routes for locale in settings at "routes.'.$locale.'".');

		$this->xtr['routes'] = $routes[$locale];
	}
}