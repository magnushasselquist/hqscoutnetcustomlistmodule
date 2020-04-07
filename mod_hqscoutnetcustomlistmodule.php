<?php

/**
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 * @author      Magnus Hasselquist <magnus.hasselquist@gmail.com> - http://mintekniskasida.blogspot.se/
 */
 
// No direct access
defined('_JEXEC') or die;

// Include the syndicate functions only once
require_once( dirname(__FILE__) . '/helper.php' );
 
$hello = modHelloWorldHelper::getHello($params);

require( JModuleHelper::getLayoutPath('mod_hqscoutnetcustomlistmodule'));

?>
