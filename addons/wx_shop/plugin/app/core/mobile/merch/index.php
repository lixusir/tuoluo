<?php
if (!(defined('IN_IA'))) {

	exit('Access Denied');
}



class Index_WxShopPage  //extends PluginMobilePage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		// $this->diypage('home');
		
		
		$uniacid = $_W['uniacid'];
		$mid = intval($_GPC['mid']);
		$merchid = intval($_GPC['merchid']);
		if (!($merchid)) {
			// $this->message('没有找到此商户', '', 'error');
			show_json(0, '参数错误');
		}
		$index_cache = $this->getpage($merchid);

		//var_dump($index_cache);die;
		if (!(empty($mid))) {

			$index_cache = preg_replace_callback('/href=[\\\'"]?([^\\\'" ]+).*?[\\\'"]/', function($matches) use($mid) {
				$preg = $matches[1];
				if (strexists($preg, 'mid=')) {

					return 'href=\'' . $preg . '\'';
				}

				if (!(strexists($preg, 'javascript'))) {
					$preg = preg_replace('/(&|\\?)mid=[\\d+]/', '', $preg);
					if (strexists($preg, '?')) {
						$newpreg = $preg . '&mid=' . $mid;
					}
					else {
						$newpreg = $preg . '?mid=' . $mid;
					}
					return 'href=\'' . $newpreg . '\'';
				}
			}, $index_cache);
		}

		$set = p('merch')->getListUserOne($merchid);
		//$set["sets"] = iunserializer($set["sets"]);
		//var_dump($set["sets"]);die;
		if (!(empty($set))) {

			$_W['shopshare'] = array('title' => $set['merchname'], 'imgUrl' => tomedia($set['logo']), 'desc' => $set['desc'], 'link' => mobileUrl('merch', array('merchid' => $merchid), true));
			if (p('commission')) {

				$set = p('commission')->getSet();
				if (!(empty($set['level']))) {
					$member = m('member')->getMember($_GPC['openid']);
					if (!(empty($member)) && ($member['status'] == 1) && ($member['isagent'] == 1)) {
						$_W['shopshare']['link'] = mobileUrl('merch', array('merchid' => $merchid, 'mid' => $member['id']), true);
					}
					else if (!(empty($mid))) {
						$_W['shopshare']['link'] = mobileUrl('merch', array('merchid' => $merchid, 'mid' => $mid), true);
					}
				}
			}
		}

		// $data = p('merch')->getSet('diypage');
		$merch_set = pdo_fetch('select sets from ' . tablename('wx_shop_merch_user') . ' where uniacid=:uniacid and id=:id limit 1 ', array(':uniacid' => $_W['uniacid'], ':id' => $merchid));
		$data = iunserializer($merch_set['sets']);
		 //var_dump($data);die;
		// var_dump($merch_set);die;
		// $diypage_plugin = p('diypage');
		// $home_list = $diypage_plugin->getPageList('allpage', ' and type=2 ');
		$home_list['list'] = pdo_fetchall('select id, `name`, `type`, createtime, lastedittime, keyword from ' . tablename('wx_shop_diypage') . ' where merch=:merch and type=2 and type>0 and type<99 and uniacid=:uniacid order by `type` desc, id desc ' . $limit, array(':merch' => intval($merchid), ':uniacid' => $_W['uniacid']));
		$home_list['total'] = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('wx_shop_diypage') . ' where merch=:merch and uniacid=:uniacid ' . $c, array(':merch' => intval($merchid), ':uniacid' => $_W['uniacid']));
		$home_list['pager'] = pagination2($total, $pindex, 20);

		// var_dump();die;

		$diypage = pdo_fetch('select * from ' . tablename('wx_shop_diypage') . ' where id=:id and uniacid=:uniacid and merch=:merchid' . ' limit 1 ', array(':id' => $data['diypage']['page']['home'], ':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
		// var_dump($diypage);die;
		if($diypage){
			$diypage['data'] = json_decode(base64_decode($diypage['data']), true);
		}

		//推荐的商品
		$args = array('page' => intval($_GPC['page']), 'pagesize' => 6, 'isrecommand' => 1, 'order' => 'displayorder desc,createtime desc', 'by' => '', 'merchid' => intval($_GPC['merchid']));
		$recommand = m('goods')->getList($args);

		$index_cache['recommandlist'] = array('list' => $recommand['list'], 'pagesize' => $args['pagesize'], 'total' => $recommand['total'], 'page' => intval($_GPC['page']));
		//var_dump($index_cache['recommandlist']);die;
		

		show_json(1,array('set'=>$set, 'index_cache'=>$index_cache, 'home_list'=>$home_list,  'data'=>$data, 'diypage'=>$diypage ));
		// include $this->template('index');
	}

	public function get_recommand()
	{
		global $_W;
		global $_GPC;
		$args = array('page' => intval($_GPC['page']), 'pagesize' => 6, 'isrecommand' => 1, 'order' => 'displayorder desc,createtime desc', 'by' => '', 'merchid' => intval($_GPC['merchid']));
		$recommand = m('goods')->getList($args);

		show_json(1, array('list' => $recommand['list'], 'pagesize' => $args['pagesize'], 'total' => $recommand['total'], 'page' => intval($_GPC['page'])));
	}

	private function getcache()
	{
		global $_W;
		global $_GPC;
		return m('common')->createStaticFile(mobileUrl('getpage', NULL, true));
	}

	public function getpage($merchid)
	{
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$merchid = intval($merchid);
		$defaults = array(
			'adv'    => array('text' => '幻灯片', 'visible' => 1),
			'search' => array('text' => '搜索栏', 'visible' => 1),
			'nav'    => array('text' => '导航栏', 'visible' => 1),
			'notice' => array('text' => '公告栏', 'visible' => 1),
			'cube'   => array('text' => '魔方栏', 'visible' => 1),
			'banner' => array('text' => '广告栏', 'visible' => 1),
			'goods'  => array('text' => '推荐栏', 'visible' => 1)
			);
		$shop = p('merch')->getSet('shop', $merchid);
		$sorts = ((isset($shop['indexsort']) ? $shop['indexsort'] : $defaults));
		$sorts['recommand'] = array('text' => '系统推荐', 'visible' => 1);
		$advs = pdo_fetchall('select id,advname,link,thumb from ' . tablename('wx_shop_merch_adv') . ' where uniacid=:uniacid and merchid=:merchid and enabled=1 order by displayorder desc', array(':uniacid' => $uniacid, ':merchid' => $merchid));
		$navs = pdo_fetchall('select id,navname,url,icon from ' . tablename('wx_shop_merch_nav') . ' where uniacid=:uniacid and merchid=:merchid and status=1 order by displayorder desc', array(':uniacid' => $uniacid, ':merchid' => $merchid));
		$cubes = ((is_array($shop['cubes']) ? $shop['cubes'] : array()));
		$banners = pdo_fetchall('select id,bannername,link,thumb from ' . tablename('wx_shop_merch_banner') . ' where uniacid=:uniacid and merchid=:merchid and enabled=1 order by displayorder desc', array(':uniacid' => $uniacid, ':merchid' => $merchid));
		$bannerswipe = $shop['bannerswipe'];


		if (!(empty($shop['indexrecommands']))) {

			$goodids = implode(',', $shop['indexrecommands']);


			if (!(empty($goodids))) {

				$indexrecommands = pdo_fetchall('select id, title, thumb, marketprice, productprice, minprice, total from ' . tablename('wx_shop_goods') . ' where id in( ' . $goodids . ' ) and uniacid=:uniacid and merchid=:merchid and status=1 order by instr(\'' . $goodids . '\',id),merchdisplayorder desc', array(':uniacid' => $uniacid, ':merchid' => $merchid));
			}



		}

		$goodsstyle = $shop['goodsstyle'];
		$notices = pdo_fetchall('select id, title, link, thumb from ' . tablename('wx_shop_merch_notice') . ' where uniacid=:uniacid and merchid=:merchid and status=1 order by displayorder desc limit 5', array(':uniacid' => $uniacid, ':merchid' => $merchid));
		// ob_start();
		// ob_implicit_flush(false);
		// require $this->template('index_tpl');
		// return ob_get_clean();
		// show_json(1, array(
		// 	'shop'=>$shop,
		// 	'sorts'=>$sorts,
		// 	'advs'=>$advs,
		// 	'navs'=>$navs,
		// 	'cubes'=>$cubes,
		// 	'banners'=>$banners,
		// 	'bannerswipe'=>$bannerswipe,
		// 	'indexrecommands'=>$indexrecommands
		// ));
		$_sorts = '';
		$_index = 0;
		if(count($sorts)>0){
			foreach ($sorts as $k => $v) {
				$_index++;
				$_sorts[$_index]['name'] = $k;
			}
		}
		
		if(count($shop['cubes'])>0){
			foreach ($shop['cubes'] as $k => $v) {
				$shop['cubes'][$k]['img'] = tomedia($v['img']);
			}
			}
		if(count($advs)>0){
			foreach ($advs as $k => $v) {
				$advs[$k]['thumb'] = tomedia($v['thumb']);
			}
		}
		if(count($navs)>0){
			foreach ($navs as $k => $v) {
				$navs[$k]['icon'] = tomedia($v['icon']);
			}
		}
		if(count($cubes)>0){
			foreach ($cubes as $k => $v) {
				$cubes[$k]['img'] = tomedia($v['img']);
			}
		}
		if(count($banners)>0){
			foreach ($banners as $k => $v) {
				$banners[$k]['thumb'] = tomedia($v['thumb']);
			}
		}
		if(count($indexrecommands)>0){
			foreach ($indexrecommands as $k => $v) {
				$indexrecommands[$k]['thumb'] = tomedia($v['thumb']);
			}
		}
		if(count($notices)>0){
			foreach ($notices as $k => $v) {
				$notices[$k]['thumb'] = tomedia($v['thumb']);
			}
		}

		

		return array(
			'shop'=>$shop,
			'sorts'=>$sorts,
			'_sorts'=>$_sorts,
			'advs'=>$advs,
			'navs'=>$navs,
			'cubes'=>$cubes,
			'banners'=>$banners,
			'bannerswipe'=>$bannerswipe,
			'indexrecommands'=>$indexrecommands,
			'goodsstyle'=>$goodsstyle,
			'notices'=>$notices
		);

		
	}
}


?>