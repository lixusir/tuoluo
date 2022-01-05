<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Plugin_WxShopModel
{
	public function exists($pluginName = '') 
	{
		$dbplugin = pdo_fetchall('select * from ' . tablename('wx_shop_plugin') . ' where identity=:identyty limit  1', array(':identity' => $pluginName));
		if (empty($dbplugin)) 
		{
			return false;
		}
		return true;
	}
	public function getAll($iscom = false, $status = '') 
	{
		global $_W;
		$plugins = '';
		if ($status !== '') 
		{
			$status = 'and status = ' . intval($status);
		}
		if ($iscom) 
		{
			//读取缓存
			//$plugins = m('cache')->getArray('coms2', 'global');
			if (empty($plugins)) 
			{
				$plugins = pdo_fetchall('select * from ' . tablename('wx_shop_plugin') . ' where iscom=1 and status=1 and deprecated=0 ' . $status . ' order by displayorder asc');
				m('cache')->set('coms2', $plugins, 'global');
			}
		}
		else 
		{
			//读取缓存
			//$plugins = m('cache')->getArray('plugins2', 'global');
			if (empty($plugins)) 
			{
				$plugins = pdo_fetchall('select * from ' . tablename('wx_shop_plugin') . ' where iscom=0 and status=1 and deprecated=0 ' . $status . ' order by displayorder asc');
				m('cache')->set('plugins2', $plugins, 'global');
			}
		}
		return $plugins;
	}
	public function refreshCache($status = '', $iscom = false) 
	{
		if ($status !== '') 
		{
			$status = 'and status = ' . intval($status);
		}
		$com = pdo_fetchall('select * from ' . tablename('wx_shop_plugin') . ' where iscom=1 and status=1 and deprecated=0  ' . $status . ' order by displayorder asc');
		m('cache')->set('coms2', $com, 'global');
		$plugins = pdo_fetchall('select * from ' . tablename('wx_shop_plugin') . ' where iscom=0 and status=1 and deprecated=0 ' . $status . ' order by displayorder asc');
		m('cache')->set('plugins2', $plugins, 'global');
		if ($iscom) 
		{
			return $com;
		}
		return $plugins;
	}
	public function getList($status = '') 
	{
		global $_W;
		$list = $this->getCategory();
		$plugins = $this->getAll(false, $status);
		$filename = '../addons/wx_shop/core/model/grant.php';
		$status = false;
		if ($_W['role'] == 'founder' or empty($_W['role'])) {
			$status = true;
		}
		if (file_exists($filename)) 
		{
			$item = pdo_fetch('select  plugins from ' . tablename('wx_shop_perm_plugin') . ' where acid=:acid limit 1', array(':acid' => $_W['uniacid']));
			$setting = pdo_fetch('select * from ' . tablename('wx_shop_system_grant_setting') . ' where id = 1 limit 1 ');
			foreach ($plugins as $key => $value ) 
			{
				if (!(strstr($item['plugins'], $value['identity'])) && !(strstr($setting['plugin'], $value['identity'])) && !(strstr($setting['com'], $value['identity']))) 
				{
					$plugin = pdo_fetch('SELECT max(permendtime) as permendtime FROM ' . tablename('wx_shop_system_grant_log') . ' ' . "\r\n" . '                    WHERE `identity` = \'' . $value['identity'] . '\' and uniacid = ' . $_W['uniacid'] . ' and isperm = 1 ');
					$plugins[$key]['isgrant'] = 1;
					$plugins[$key]['permendtime'] = $plugin['permendtime'];
				}
			}
		}
		else if (p('grant')) 
		{
			$acid = pdo_fetch('SELECT acid,uniacid FROM ' . tablename('account_wechats') . ' WHERE uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid']));
			$item = pdo_fetch('select plugins from ' . tablename('wx_shop_perm_plugin') . ' where acid=:acid limit 1', array(':acid' => $acid['acid']));
			$setting = pdo_fetch('select * from ' . tablename('wx_shop_system_plugingrant_setting') . ' where 1 = 1 limit 1 ');
			/*if (!$status) {
				foreach ($plugins as $key => $value) {
					
					$res = strstr($item['plugins'],$value['identity']);
					if ($res) {
						$data[] = $value;
					}
				}
				$plugins = $data;
			}*/
			/*foreach ($plugins as $key => $value ) 
			{
				if (!(strstr($item['plugins'], $value['identity'])) && !(strstr($setting['plugin'], $value['identity'])) && !(strstr($setting['com'], $value['identity']))) 
				{
					$plugin = pdo_fetchcolumn('SELECT count(1) FROM ' . tablename('wx_shop_system_plugingrant_log') . "\r\n" . '                    WHERE `identity` = \'' . $value['identity'] . '\' and uniacid = ' . $acid['uniacid'] . ' and isperm = 1 and `month` = 0 ');
					if (0 < $plugin) 
					{
						$plugin = pdo_fetch('SELECT max(permendtime) as permendtime,`month`,isperm FROM ' . tablename('wx_shop_system_plugingrant_log') . "\r\n" . '                        WHERE `identity` = \'' . $value['identity'] . '\' and uniacid = ' . $acid['uniacid'] . ' and isperm = 1 and `month` = 0 ');
					}
					else 
					{
						$plugin = pdo_fetch('SELECT max(permendtime) as permendtime,`month`,isperm FROM ' . tablename('wx_shop_system_plugingrant_log') . ' ' . "\r\n" . '                        WHERE `identity` = \'' . $value['identity'] . '\' and uniacid = ' . $acid['uniacid'] . ' and isperm = 1 and permendtime > ' . time() . ' ');
					}
					$plugins[$key]['isplugingrant'] = 1;
					$plugins[$key]['month'] = $plugin['month'];
					$plugins[$key]['isperm'] = $plugin['isperm'];
					$plugins[$key]['permendtime'] = $plugin['permendtime'];
					if ($value['identity'] == 'taobao') 
					{
					}
				}
			}*/
		}
		foreach ($list as $ck => &$cv ) 
		{
			$ps = array();
			foreach ($plugins as $p ) 
			{
				if ($p['category'] == $ck) 
				{
					$ps[] = $p;
				}
			}
			$cv['plugins'] = $ps;
		}
		unset($cv);
		return $list;
	}
	public function getName($identity = '') 
	{
		$plugins = $this->getAll();
		foreach ($plugins as $p ) 
		{
			while ($p['identity'] == $identity) 
			{
				return $p['name'];
			}
		}
		return '';
	}
	public function loadModel($pluginname = '') 
	{
		static $_model;
		if (!($_model)) 
		{
			$modelfile = IA_ROOT . '/addons/wx_shop/plugin/' . $pluginname . '/core/model.php';
			if (is_file($modelfile)) 
			{
				$classname = ucfirst($pluginname) . 'Model';
				require_once WX_SHOP_CORE . 'inc/plugin_model.php';
				require_once $modelfile;
				$_model = new $classname($pluginname);
			}
		}
		return $_model;
	}
	public function getCategory() 
	{
		return array( 'biz' => array('name' => '奖金类'), 'sale' => array('name' => '营销类'), 'tool' => array('name' => '工具类') );
	}
	public function getCount() 
	{
		$plugins = m('plugin')->getAll();
		$count = 0;
		if (!(empty($plugins))) 
		{
			foreach ($plugins as $plugin ) 
			{
				if (com_run('perm::check_plugin', $plugin['identity'])) 
				{
					++$count;
				}
			}
		}
		return $count;
	}
	public function getConfig($pluginname = '') 
	{
		if (empty($pluginname)) 
		{
			return false;
		}
		$config_file = $moduleroot = IA_ROOT . '/addons/wx_shop/plugin/' . $pluginname . '/config.php';
		if (!(is_file($config_file))) 
		{
			return false;
		}
		return include $config_file;
	}
}
?>