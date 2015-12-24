<?php
/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2015.12.21.
 * Time: 15:27
 */

namespace Webapper\Xtr;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Webapper\D3I\Container as D3I;
use Webapper\D3I\Provider;
use Webapper\Xtr\Services\Distinct;
use Webapper\Xtr\Services\Fallbacks;
use Webapper\Xtr\Services\I18nSwitcher;
use Webapper\Xtr\Services\XtrServiceInterface;

/**
 * Class XtrService
 *
 * @property I18nSwitcher $i18n_switcher
 * @property Fallbacks $fallbacks
 * @property Distinct $distinct
 *
 * @package Webapper\Xtr
 */
class XtrService extends D3I {
	/**
	 * @var Application
	 */
	protected $app;

	/**
	 * @var array
	 */
	protected $services = [];

	protected $settings = [
		'ns_root'	=> '',
		'disabled'	=> []
	];

	/**
	 * @var bool
	 */
	protected $processed = false;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * XtrService constructor.
	 * @param array $input
	 * @param Application $app
	 */
	public function __construct(array $input, Application $app)
	{
		$this->app = $app;

		// default services:
		$services = [
			'i18n_switcher'	=> 'Webapper\Xtr\Services\I18nSwitcher',
			'fallbacks'		=> 'Webapper\Xtr\Services\Fallbacks',
			'distinct'		=> 'Webapper\Xtr\Services\Distinct',
		];

		// adding required services
		if (isset($input['services'])) {
			foreach ($input['services'] as $name => $serviceClassName) {
				if (!in_array($serviceClassName, $services)) {
					if (!is_a($serviceClassName, 'Webapper\Xtr\Services\XtrServiceInterface', true)) throw new \RuntimeException('XTR service "'.$name.'" seems invalid (not implementing XtrServiceInterface). Remove or fix this service before of running!');
					$services[$name] = $serviceClassName;
				}
			}
		}

		// get settings:
		if (isset($input['settings']) and isset($input['settings']['ns_root'])) $this->settings['ns_root'] = $input['settings']['ns_root'];
		if (isset($input['settings']) and isset($input['settings']['disabled'])) $this->settings['disabled'] = $input['settings']['disabled'];

		// adding providers which not disabled:
		foreach ($services as $name=>$serviceClassName) {
			if (in_array($name, $this->settings['disabled'])) continue;

			$config = isset($input[$name])? $input[$name] : [];
			$this->services[$name] = $serviceClassName;
			$input[$name] = Provider::Create(function(XtrService $c) use ($name, $serviceClassName) {
				$service = new $serviceClassName($c['!'.$name], $c);
				return $service;
			})->mutate($config)->share();
		}

		// creating request from globals
		$this->request = Request::createFromGlobals();

		parent::__construct($input);
	}

	/**
	 * @return Application
	 */
	public function getApplication() {
		return $this->app;
	}

	/**
	 * @param Request $request
	 * @return $this
	 */
	public function setRequest(Request $request) {
		$this->request = $request;
		return $this;
	}

	/**
	 * @return Request
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Internal use only.
	 * @return $this
	 */
	public function flagProcessed() {
		$this->processed = true;
		return $this;
	}

	public function run() {
		$orderedRun = [];
		$priorityHigh = XtrServiceInterface::PRIORITY_HIGH;
		$priorityLow = XtrServiceInterface::PRIORITY_LOW;
		foreach ($this->services as $name=>$class) {
			$priority = count($orderedRun);
			switch ($p = $this->$name->getPriority()) {
				case XtrServiceInterface::PRIORITY_HIGH: {
					$priority = $priorityHigh;
					$priorityHigh++;
					break;
				}
				case XtrServiceInterface::PRIORITY_LOW: {
					$priority = $priorityLow;
					$priorityLow--;
					break;
				}
				default: {
					if ($p !== XtrServiceInterface::PRIORITY_NORMAL) {
						$priority = $p;
					}
					while (isset($orderedRun[$priority])) {
						$priority++;
					}
					break;
				}
			}
			$orderedRun[$priority] = $this->$name;
		}
		ksort($orderedRun);

		foreach ($orderedRun as $service) {
			/** @var $service XtrServiceInterface */
			$service->run();
			if ($this->processed) return;
		}
	}
}