<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require WX_SHOP_PLUGIN . 'globonus/core/page_login_mobile.php';
class Bonus_WxShopPage //extends GlobonusMobileLoginPage
{   
	public $set;
	public $model;
	

	public function __construct()
	{
		
		// $this->model = m('plugin')->loadModel($GLOBALS['_W']['plugin']);
		$this->model=$this->getModel();
		$this->set = $this->model->getSet('globonus');
	}

	public function main()
	{
		global $_W;
		global $_GPC;
		$status = intval($_GPC['status']);
		$openid = !empty($_W['openid']) ? $_W['openid'] : $_GPC['openid'];
		// var_dump($_W['uniacid']);
		 // var_dump($openid);
		// $bonus = $this->model->getBonus($_W['openid'], array('ok', 'lock', 'total'));
		
		$bonus = $this->model->getBonus($openid, array('ok', 'lock', 'total'));
		show_json(1,array('status'=>$status,'bonus'=>$bonus,'set'=>$this->set));
		// include $this->template();
	}

	public function get_list()
	{
		global $_W;
		global $_GPC;
		$member = m('member')->getMember($_W['openid']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and `openid`=:openid and uniacid=:uniacid';
		$params = array(':openid' => $_W['openid'], ':uniacid' => $_W['uniacid']);
		$status = trim($_GPC['status']);

		if ($status == 1) {
			$condition .= ' and status=1';
		}
		else {
			if ($status == 2) {
				$condition .= ' and (status=-1 or status=0)';
			}
		}

		$billdData = pdo_fetchall('select id from ' . tablename('wx_shop_globonus_bill') . ' where 1 and uniacid = ' . intval($_W['uniacid']));
		$id = '';

		if (!empty($billdData)) {
			$ids = array();

			foreach ($billdData as $v) {
				$ids[] = $v['id'];
			}

			$id = implode(',', $ids);
			$list = pdo_fetchall('select *  from ' . tablename('wx_shop_globonus_billp') . ' where 1 ' . $condition . ' and billid in(' . $id . ') order by id desc LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize, $params);
			$total = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_globonus_billp') . ' where 1 ' . $condition . ' and billid in(' . $id . ')', $params);
			show_json(1, array('total' => $total, 'list' => $list, 'pagesize' => $psize));
		}
		else {
			$list = array();
			$total = 0;
			show_json(1, array('total' => $total, 'list' => $list, 'pagesize' => $psize));
		}
	}

	public function getSet($pluginname) 
		{
			if (empty($GLOBALS['_S'][$pluginname])) {
			    $set = m('common')->getPluginset($pluginname);
		    }else{
			    $set = $GLOBALS['_S'][$pluginname];
		    }

			// $set = parent::getSet($uniacid);
			$set['texts'] = array('aagent' => (empty($set['texts']['aagent']) ? '区域代理' : $set['texts']['aagent']), 'center' => (empty($set['texts']['center']) ? '区域代理中心' : $set['texts']['center']), 'become' => (empty($set['texts']['become']) ? '成为区域代理' : $set['texts']['become']), 'bonus' => (empty($set['texts']['bonus']) ? '分红' : $set['texts']['bonus']), 'bonus_total' => (empty($set['texts']['bonus_total']) ? '累计分红' : $set['texts']['bonus_total']), 'bonus_lock' => (empty($set['texts']['bonus_lock']) ? '待结算分红' : $set['texts']['bonus_lock']), 'bonus_pay' => (empty($set['texts']['bonus_lock']) ? '已结算分红' : $set['texts']['bonus_pay']), 'bonus_wait' => (empty($set['texts']['bonus_wait']) ? '预计分红' : $set['texts']['bonus_wait']), 'bonus_detail' => (empty($set['texts']['bonus_detail']) ? '分红明细' : $set['texts']['bonus_detail']), 'bonus_charge' => (empty($set['texts']['bonus_charge']) ? '扣除个人所得税' : $set['texts']['bonus_charge']));
			return $set;
		}


	public function getModel(){
         $model = m('plugin')->loadModel('globonus');
		return $model;
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

	public function footerMenus($diymenuid = NULL, $ismerch = false, $texts = array()) 
	{
		global $_W;
		global $_GPC;
		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']);
		$cartcount = pdo_fetchcolumn('select ifnull(sum(total),0) from ' . tablename('wx_shop_member_cart') . ' where uniacid=:uniacid and openid=:openid and deleted=0 and isnewstore=0  and selected =1', $params);
		$commission = array();
		if (p('commission') && intval(0 < $_W['shopset']['commission']['level'])) 
		{
			$member = m('member')->getMember($_W['openid']);
			if (!($member['agentblack'])) 
			{
				if (($member['isagent'] == 1) && ($member['status'] == 1)) 
				{
					$commission = array('url' => mobileUrl('commission'), 'text' => (empty($_W['shopset']['commission']['texts']['center']) ? '分销中心' : $_W['shopset']['commission']['texts']['center']));
				}
				else 
				{
					$commission = array('url' => mobileUrl('commission/register'), 'text' => (empty($_W['shopset']['commission']['texts']['become']) ? '成为分销商' : $_W['shopset']['commission']['texts']['become']));
				}
			}
		}
		$showdiymenu = false;
		$routes = explode('.', $_W['routes']);
		$controller = $routes[0];
		if (($controller == 'member') || ($controller == 'cart') || ($controller == 'order') || ($controller == 'goods')) 
		{
			$controller = 'shop';
		}
		if (empty($diymenuid)) 
		{
			$pageid = ((!(empty($controller)) ? $controller : 'shop'));
			(($pageid == 'index' ? 'shop' : $pageid));
			if (!(empty($_GPC['merchid'])) && (($_W['routes'] == 'shop.category') || ($_W['routes'] == 'goods'))) 
			{
				$pageid = 'merch';
			}
			if (($pageid == 'merch') && !(empty($_GPC['merchid'])) && p('merch')) 
			{
				$merchdata = p('merch')->getSet('diypage', $_GPC['merchid']);
				if (!(empty($merchdata['menu']))) 
				{
					$diymenuid = $merchdata['menu']['shop'];
					if (!(is_weixin()) || is_h5app()) 
					{
						$diymenuid = $merchdata['menu']['shop_wap'];
					}
				}
			}
			else 
			{
				$diypagedata = m('common')->getPluginset('diypage');
				if (!(empty($diypagedata['menu']))) 
				{
					$diymenuid = $diypagedata['menu'][$pageid];
					if (!(is_weixin()) || is_h5app()) 
					{
						$diymenuid = $diypagedata['menu'][$pageid . '_wap'];
					}
				}
			}
		}
		if (!(empty($diymenuid))) 
		{
			$menu = pdo_fetch('SELECT * FROM ' . tablename('wx_shop_diypage_menu') . ' WHERE id=:id and uniacid=:uniacid limit 1 ', array(':id' => $diymenuid, ':uniacid' => $_W['uniacid']));
			if (!(empty($menu))) 
			{
				$menu = $menu['data'];
				$menu = base64_decode($menu);
				$diymenu = json_decode($menu, true);
				$showdiymenu = true;
			}
		}
		if ($showdiymenu) 
		{
			include $this->template('diypage/menu');
		}
		else if (($controller == 'commission') && ($routes[1] != 'myshop')) 
		{
			include $this->template('commission/_menu');
		}
		else if ($controller == 'creditshop') 
		{
			include $this->template('creditshop/_menu');
		}
		else if ($controller == 'groups') 
		{
			include $this->template('groups/_groups_footer');
		}
		else if ($controller == 'merch') 
		{
			include $this->template('merch/_menu');
		}
		else if ($controller == 'mr') 
		{
			include $this->template('mr/_menu');
		}
		else if ($controller == 'newmr') 
		{
			include $this->template('newmr/_menu');
		}
		else if ($controller == 'sign') 
		{
			include $this->template('sign/_menu');
		}
		else if ($controller == 'sns') 
		{
			include $this->template('sns/_menu');
		}
		else if ($controller == 'seckill') 
		{
			include $this->template('seckill/_menu');
		}
		else if ($controller == 'mmanage') 
		{
			include $this->template('mmanage/_menu');
		}
		else if ($ismerch) 
		{
			include $this->template('merch/_menu');
		}
		else 
		{
			include $this->template('_menu');
		}
	}
	public function getMember($openid = '') 
	{
		global $_W;
		$uid = (int) $openid;
		if ($uid == 0) 
		{
			$info = pdo_fetch('select * from ' . tablename('wx_shop_member') . ' where  openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			if (empty($info)) 
			{
				if (strexists($openid, 'sns_qq_')) 
				{
					$openid = str_replace('sns_qq_', '', $openid);
					$condition = ' openid_qq=:openid ';
					$bindsns = 'qq';
				}
				else if (strexists($openid, 'sns_wx_')) 
				{
					$openid = str_replace('sns_wx_', '', $openid);
					$condition = ' openid_wx=:openid ';
					$bindsns = 'wx';
				}
				else if (strexists($openid, 'sns_wa_')) 
				{
					$openid = str_replace('sns_wa_', '', $openid);
					$condition = ' openid_wa=:openid ';
					$bindsns = 'wa';
				}
				if (!(empty($condition))) 
				{
					$info = pdo_fetch('select * from ' . tablename('wx_shop_member') . ' where ' . $condition . '  and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
					if (!(empty($info))) 
					{
						$info['bindsns'] = $bindsns;
					}
				}
			}
		}
		else 
		{
			$info = pdo_fetch('select * from ' . tablename('wx_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $openid));
		}
		if (!(empty($info))) 
		{
			if (!(strexists($info['avatar'], 'http://')) && !(strexists($info['avatar'], 'https://'))) 
			{
				$info['avatar'] = tomedia($info['avatar']);
			}
			if ($_W['ishttps']) 
			{
				$info['avatar'] = str_replace('http://', 'https://', $info['avatar']);
			}
				if(strpos($info['avatar'],'132132')){
				$upgrade2=array();
				$upgrade2['avatar'] = str_replace('132132', '132', $info['avatar']);
				pdo_update('wx_shop_member', $upgrade2, array('id' => $info['id']));
			}
			
			$info = $this->updateCredits($info);
		}
		return $info;
	}

	// 获取分红
	public function getBonus($openid = '', $params = array())//'ok''lock''total'
		{
			global $_W;
			$ret = array();

			if (in_array('ok', $params)) {
				$ret['ok'] = pdo_fetchcolumn('select ifnull(sum(paymoney),0) from ' . tablename('wx_shop_globonus_billp') . ' where openid=:openid and status=1 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			}

			if (in_array('lock', $params)) {
				$billdData = pdo_fetchall('select id from ' . tablename('wx_shop_globonus_bill') . ' where 1 and uniacid = ' . intval($_W['uniacid']));
				$id = '';

				if (!empty($billdData)) {
					$ids = array();

					foreach ($billdData as $v) {
						$ids[] = $v['id'];
					}

					$id = implode(',', $ids);
					$ret['lock'] = pdo_fetchcolumn('select ifnull(sum(paymoney),0) from ' . tablename('wx_shop_globonus_billp') . ' where openid=:openid and status<>1 and uniacid=:uniacid  and billid in(' . $id . ') limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
				}
			}

			$ret['total'] = $ret['ok'] + $ret['lock'];
			return $ret;
		}
}

?>
