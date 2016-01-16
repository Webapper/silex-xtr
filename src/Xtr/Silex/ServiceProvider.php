<?php
/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2016.01.15.
 * Time: 16:21
 */

namespace Webapper\Xtr\Silex;


use Silex\Application;
use Silex\ServiceProviderInterface;
use Webapper\Xtr\XtrService;

class ServiceProvider implements ServiceProviderInterface
{
	/**
	 * @var array
	 */
	protected $settings = [];

	/**
	 * ServiceProvider constructor.
	 * @param array $settings
	 */
	public function __construct(array $settings) {
		$this->settings = $settings;
	}

	public function register(Application $app) {
		$app['xtr'] = new XtrService($this->settings, $app);
	}

	public function boot(Application $app) {

	}
}