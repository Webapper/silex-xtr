<?php
/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2015.12.22.
 * Time: 10:59
 */

namespace Webapper\Xtr\Services\I18nSwitcher;


use Silex\Application;

class LocaleTrait {
	public function initLocale() {
		$locale = $this['session']->get('current_locale');
		if ($locale === null) {
			$locale = static::LOCALE_DEFAULT;
			$this['session']->set('current_locale', $locale);
		}

		// setting current locale by session (#1) or application constant (#2)
		$this['translator']->setLocale($locale);
	}

	public function changeLocale($locale) {
		$this['session']->set('current_locale', $locale);
		return $this->redirectSir($locale);
	}

	public function getLocale() {
		if ($this['session']->get('current_locale') === null) $this->initLocale();
		return $this['session']->get('current_locale');
	}
}

function get_locale(Application $app) {
	if (!method_exists($app, 'getLocale')) throw new \RuntimeException('Application have no getLocale() method. You must be sure if you using the Webapper\Xtr\Services\I18nSwitcher\LocaleTrait trait or implement its methods!');
	return $app->getLocale();
}