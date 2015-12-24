<?php
/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2015.12.22.
 * Time: 11:17
 */

namespace Webapper\Xtr\Services;

use Silex\RedirectableUrlMatcher;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Webapper\Xtr\RouteCollection;
use Webapper\Xtr\RouteDefinition;
use Webapper\Xtr\XtrService;

class Fallbacks implements XtrServiceInterface {
	/**
	 * @var array
	 */
	protected $defaultConfig = [
		'priority'		=> XtrServiceInterface::PRIORITY_NORMAL
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
		$app = $this->xtr->getApplication();
		/** @var $matcher RedirectableUrlMatcher */
		$context = $app['request_context'];
		$matched = false;
		$path = $this->xtr->getRequest()->getPathInfo();
		$lastException = null;

		// matching fallback route groups:
		foreach ($this->xtr['routes'] as $fallback=>$routes) {
			$routes = RouteCollection::FromArray($routes)->flat();
			$matcher = new RedirectableUrlMatcher($routes->toSilexCollection(), $context);
			try {
				$matcher->match($path);
				$matched = $fallback;
				break;
			} catch (ResourceNotFoundException $e) {
				// not found: silent continue
				$lastException = $e;
			}
		}

		if ($matched === false) throw $lastException;

		// configuring distinct service:
		$allow = [$matched];
		$fallbacks = array_flip(array_keys($this->xtr['routes']));
		unset($fallbacks[$matched]);
		$deny = array_keys($fallbacks);
		$this->xtr['@distinct.settings'] = [
			'allow'	=> $allow,
			'deny'	=> $deny
		];
	}
}