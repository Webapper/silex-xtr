<?php
/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2015.12.21.
 * Time: 15:54
 */

namespace Webapper\Xtr\Services;

use Webapper\Xtr\XtrService;

interface XtrServiceInterface {
	const PRIORITY_HIGH = -2147483648;
	const PRIORITY_LOW = 2147483647;
	const PRIORITY_NORMAL = null;

	public function __construct($config, XtrService $xtr);
	public function getPriority();
	public function run();
}