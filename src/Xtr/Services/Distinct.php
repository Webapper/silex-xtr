<?php
/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2015.12.24.
 * Time: 21:12
 */

namespace Webapper\Xtr\Services;

use Silex\ControllerCollection;
use Webapper\Xtr\RouteCollection;
use Webapper\Xtr\XtrService;

class Distinct implements XtrServiceInterface {
	/**
	 * @var array
	 */
	protected $defaultConfig = [
		'priority'		=> XtrServiceInterface::PRIORITY_NORMAL,
		'settings'		=> [
			'allow'	=> ['*'],
			'deny'	=> ['-']
		]
	];

	/**
	 * @var array
	 */
	protected $config = [];

	public $settings = [];

	/**
	 * @var XtrService
	 */
	protected $xtr;

	public function __construct($config, XtrService $xtr) {
		$this->config = array_merge($this->defaultConfig, (array)$config);
		$this->settings = $this->config['settings'];
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

	protected function migrateSettings() {
		if (!is_array($this->settings)) throw new \RuntimeException('Distinct settings must be array, '.gettype($this->settings).' given.');

		// formal normalization:
		if (!isset($this->settings['allow']) or empty($this->settings['allow'])) $this->settings['allow'] = ['*'];
		if (!isset($this->settings['deny']) or empty($this->settings['deny'])) $this->settings['deny'] = ['-'];
		if (!is_array($this->settings['allow'])) $this->settings['allow'] = [$this->settings['allow']];
		if (!is_array($this->settings['deny'])) $this->settings['deny'] = [$this->settings['deny']];

		// structural checking:
		if ($this->settings['allow'][0] == '*') {
			foreach ($this->xtr['routes'] as $item=>$value) {
				if ($item{0} != '/') throw new \RuntimeException('Block "'.$item.'" detected at routes while distinct set to allowing all.');
			}
		} else {
			foreach ($this->xtr['routes'] as $item=>$value) {
				if ($item{0} == '/') throw new \RuntimeException('Path pattern "'.$item.'" detected at routes while distinct set to allowing specified blocks.');
			}
		}

		// migration:
		$this->config['settings'] = $this->settings;
	}

	public function run() {
		$this->migrateSettings();
		$app = $this->xtr->getApplication();
		$routes = $this->xtr['routes'];
		/** @var $appRoutes \Symfony\Component\Routing\RouteCollection */
		$appRoutes = $app['routes'];

		// adding allowed routes before routing, silex could match one:
		foreach ($this->config['settings']['allow'] as $allowed) {
			if ($allowed == '*') {
				$silexRoutes = RouteCollection::FromArray($routes)->flat()->toSilexCollection();
			} else {
				if (!isset($routes[$allowed]) or !is_array($routes[$allowed])) throw new \RuntimeException('Allowed distinct block "'.$allowed.'" not found or illegal.');
				$silexRoutes = RouteCollection::FromArray($routes[$allowed])->flat()->toSilexCollection();
			}
			$appRoutes->addCollection($silexRoutes);
		}

		// adding denied routes before the controller executes, because these might be needed for generating URLs:
		$app->before(function (Request $request, Application $app) use ($routes, $appRoutes) {
			foreach ($this->config['settings']['deny'] as $denied) {
				if ($denied == '-') break;

				if (!isset($routes[$denied]) or !is_array($routes[$denied])) throw new \RuntimeException('Denied distinct block "'.$denied.'" not found or illegal.');
				$silexRoutes = RouteCollection::FromArray($routes[$denied])->flat()->toSilexCollection();
				$appRoutes->addCollection($silexRoutes);
			}
		});

		// flagging XTR service that we completed the handling of routes, no more sub-services need to be executed:
		$this->xtr->flagProcessed();
	}
}