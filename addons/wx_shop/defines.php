<?php
if (!(defined('IN_IA'))) {
	exit('Access Denied');
}


define('WX_SHOP_DEBUG', false);
!(defined('WX_SHOP_PATH')) && define('WX_SHOP_PATH', IA_ROOT . '/addons/wx_shop/');
!(defined('WX_SHOP_CORE')) && define('WX_SHOP_CORE', WX_SHOP_PATH . 'core/');
!(defined('WX_SHOP_DATA')) && define('WX_SHOP_DATA', WX_SHOP_PATH . 'data/');
!(defined('WX_SHOP_VENDOR')) && define('WX_SHOP_VENDOR', WX_SHOP_PATH . 'vendor/');
!(defined('WX_SHOP_CORE_WEB')) && define('WX_SHOP_CORE_WEB', WX_SHOP_CORE . 'web/');
!(defined('WX_SHOP_CORE_MOBILE')) && define('WX_SHOP_CORE_MOBILE', WX_SHOP_CORE . 'mobile/');
!(defined('WX_SHOP_CORE_SYSTEM')) && define('WX_SHOP_CORE_SYSTEM', WX_SHOP_CORE . 'system/');
!(defined('WX_SHOP_PLUGIN')) && define('WX_SHOP_PLUGIN', WX_SHOP_PATH . 'plugin/');
!(defined('WX_SHOP_PROCESSOR')) && define('WX_SHOP_PROCESSOR', WX_SHOP_CORE . 'processor/');
!(defined('WX_SHOP_INC')) && define('WX_SHOP_INC', WX_SHOP_CORE . 'inc/');
!(defined('WX_SHOP_URL')) && define('WX_SHOP_URL', $_W['siteroot'] . 'addons/wx_shop/');
!(defined('WX_SHOP_TASK_URL')) && define('WX_SHOP_TASK_URL', $_W['siteroot'] . 'addons/wx_shop/core/task/');
!(defined('WX_SHOP_LOCAL')) && define('WX_SHOP_LOCAL', '../addons/wx_shop/');
!(defined('WX_SHOP_STATIC')) && define('WX_SHOP_STATIC', WX_SHOP_URL . 'static/');
!(defined('WX_SHOP_PREFIX')) && define('WX_SHOP_PREFIX', 'wx_shop_');
define('WX_SHOP_PLACEHOLDER', '../addons/wx_shop/static/images/placeholder.png');

?>