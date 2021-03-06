<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}

require WX_SHOP_PLUGIN . 'app/core/page_auth_mobile.php';
class Goods_WxShopPage extends AppMobileAuthPage
{
	public function main()
	{
		app_json(array(
	'perm' => array('goods_add' => cv('goods.add'), 'goods_view' => cv('goods.view'), 'goods_edit' => cv('goods.edit'), 'goods_status' => cv('goods.status'), 'goods_delete' => cv('goods.delete'), 'goods_restore' => cv('goods.restore'), 'goods_stock' => cv('goods.stock'))
	));
	}

	/**
     * 获取商品列表
     */
	public function get_list()
	{
		global $_W;
		global $_GPC;

		if (!cv('goods')) {
			app_error(AppError::$PermError, '您无操作权限');
		}

		$offset = intval($_GPC['offset']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$list = array();
		$condition = ' WHERE g.`uniacid` = :uniacid and type!=10 ';
		$params = array(':uniacid' => $_W['uniacid']);
		$goodsfrom = strtolower(trim($_GPC['status']));
		empty($goodsfrom) && $_GPC['status'] = $goodsfrom = 'sale';

		if ($goodsfrom == 'sale') {
			$condition .= ' AND g.`status` > 0 and g.`checked`=0 and g.`total`>0 and g.`deleted`=0';
		}
		else if ($goodsfrom == 'out') {
			$condition .= ' AND g.`status` > 0 and g.`total` <= 0 and g.`deleted`=0';
		}
		else if ($goodsfrom == 'stock') {
			$condition .= ' AND (g.`status` = 0 or g.`checked`=1) and g.`deleted`=0';
		}
		else {
			if ($goodsfrom == 'cycle') {
				$condition .= ' AND g.`deleted`=1';
			}
		}

		$keywords = trim($_GPC['keywords']);

		if ($keywords) {
			$condition .= ' AND (`title` LIKE :keywords OR `keywords` LIKE :keywords)';
			$params[':keywords'] = '%' . $keywords . '%';
		}

		$sql = 'SELECT count(g.id) FROM ' . tablename('wx_shop_goods') . 'g' . $condition;
		$total = pdo_fetchcolumn($sql, $params);

		if (0 < $total) {
			$presize = (($pindex - 1) * $psize) - $offset;
			$sql = 'SELECT g.id, g.title, g.thumb, g.merchid, g.minprice, g.maxprice, g.total, g.sales FROM ' . tablename('wx_shop_goods') . 'g' . $condition . " ORDER BY g.`status` DESC, g.`displayorder` DESC,\r\n                g.`id` DESC LIMIT " . $presize . ',' . $psize;
			$list = pdo_fetchall($sql, $params);
		}

		$list = set_medias($list, 'thumb');
		app_json(array('total' => $total, 'list' => $list, 'pagesize' => $psize));
	}

	/**
     * 获取商品详情
     */
	public function get_detail()
	{
		global $_W;
		global $_GPC;
		if (!cv('goods.view') && cv('goods.edit')) {
			app_error(AppError::$PermError, '您无操作权限');
		}

		$id = intval($_GPC['id']);

		if (!empty($id)) {
			$fields = 'id, title, subtitle, unit, `type`, hasoption, productprice, marketprice, costprice, total, totalcnf, showtotal, weight, goodssn, productsn, maxbuy, minbuy, usermaxbuy, isnodiscount, nocommission, diyformtype, diyformid, cash, invoice, status, displayorder, thumb, thumb_url, dispatchtype, dispatchprice, dispatchid, isrecommand, isnew, ishot, issendfree, merchid, cates';
			$item = pdo_fetch('SELECT ' . $fields . ' FROM ' . tablename('wx_shop_goods') . ' WHERE id = :id and uniacid = :uniacid', array(':id' => $id, ':uniacid' => $_W['uniacid']));

			if (!empty($item)) {
				unset($item['content']);
				$item['marketprice'] = price_format($item['marketprice']);
				$item['productprice'] = price_format($item['productprice']);
				$item['costprice'] = price_format($item['costprice']);
				$item['dispatchprice'] = price_format($item['dispatchprice']);

				if (!empty($item['thumb'])) {
					$item['thumb'] = array_merge(array($item['thumb']), iunserializer($item['thumb_url']));
					$item['thumb_show'] = set_medias($item['thumb']);
				}

				$item['cates'] = explode(',', $item['cates']);
			}

			$merchid = 0;
			$merch_plugin = p('merch');
			if ((0 < $item['merchid']) && !empty($item)) {
				$merchid = intval($item['merchid']);

				if ($merch_plugin) {
					$merch_user = $merch_plugin->getListUserOne($merchid);
				}
			}
		}

		$dispatch_data = pdo_fetchall('select id, dispatchname from ' . tablename('wx_shop_dispatch') . ' where uniacid=:uniacid and merchid=:merchid and enabled=1 order by displayorder desc', array(':uniacid' => $_W['uniacid'], ':merchid' => intval($merchid)));
		$levels = m('member')->getLevels();
		$levels = array_merge(array(
	array('id' => 0, 'levelname' => empty($_W['shopset']['shop']['levelname']) ? '默认会员' : $_W['shopset']['shop']['levelname'])
	), $levels);
		$groups = m('member')->getGroups();
		$groups = array_merge(array(
	array('id' => 0, 'groupname' => '未分组')
	), $groups);

		if (p('diyform')) {
			$diyform_list = p('diyform')->getDiyformList();
		}

		$category = m('shop')->getFullCategory(true, true);
		$allcategory = m('shop')->getCategory();
		app_json(array(
	'goods'         => $item,
	'level_list'    => $levels,
	'group_list'    => $groups,
	'dispatch_list' => $dispatch_data,
	'diyform_list'  => $diyform_list,
	'category_list' => $category,
	'allcategory'   => $allcategory,
	'hasdiyform'    => p('diyform'),
	'perm'          => array('goods_edit' => cv('goods.edit'))
	));
	}

	/**
     * 保存商品
     */
	public function submit()
	{
		global $_W;
		global $_GPC;

		if (!$_W['ispost']) {
			app_error(AppError::$RequestError);
		}

		if (!cv('goods.add') && cv('goods.edit')) {
			app_error(AppError::$PermError, '您无操作权限');
		}

		$id = intval($_GPC['id']);

		if (!empty($id)) {
			$fields = 'id, title, subtitle, unit, `type`, hasoption, productprice, marketprice, costprice, total, totalcnf, showtotal, weight, goodssn, productsn, maxbuy, minbuy, usermaxbuy, isnodiscount, nocommission, diyformtype, diyformid, cash, invoice, status, displayorder, thumb, thumb_url, dispatchtype, dispatchprice, dispatchid, isrecommand, isnew, ishot, issendfree, merchid, cates';
			$item = pdo_fetch('SELECT ' . $fields . ' FROM ' . tablename('wx_shop_goods') . ' WHERE id = :id and uniacid = :uniacid', array(':id' => $id, ':uniacid' => $_W['uniacid']));
		}

		$data = array('title' => trim($_GPC['title']), 'subtitle' => trim($_GPC['subtitle']), 'unit' => trim($_GPC['unit']), 'status' => intval($_GPC['status']), 'showtotal' => intval($_GPC['showtotal']), 'cash' => intval($_GPC['cash']), 'invoice' => intval($_GPC['invoice']), 'isnodiscount' => intval($_GPC['isnodiscount']), 'nocommission' => intval($_GPC['nocommission']), 'isrecommand' => intval($_GPC['isrecommand']), 'isnew' => intval($_GPC['isnew']), 'ishot' => intval($_GPC['ishot']), 'issendfree' => intval($_GPC['issendfree']), 'totalcnf' => intval($_GPC['totalcnf']), 'dispatchtype' => intval($_GPC['dispatchtype']), 'maxbuy' => intval($_GPC['maxbuy']), 'minbuy' => intval($_GPC['minbuy']), 'usermaxbuy' => intval($_GPC['usermaxbuy']), 'displayorder' => intval($_GPC['displayorder']));

		if (empty($item)) {
			$data['type'] = intval($_GPC['type']);
		}

		$thumbs = $_GPC['thumb'];

		if (is_array($thumbs)) {
			$thumb_url = array();

			foreach ($thumbs as $th) {
				$thumb_url[] = trim($th);
			}

			$data['thumb'] = save_media($thumb_url[0]);
			unset($thumb_url[0]);
			$data['thumb_url'] = serialize(m('common')->array_images($thumb_url));
		}

		if (empty($item['hasoption'])) {
			$data['hasoption'] = 0;
			$data['marketprice'] = trim($_GPC['marketprice']);
			$data['productprice'] = trim($_GPC['productprice']);
			$data['costprice'] = trim($_GPC['costprice']);
			$data['total'] = intval($_GPC['total']);
			$data['weight'] = trim($_GPC['weight']);
			$data['goodssn'] = trim($_GPC['goodssn']);
			$data['productsn'] = trim($_GPC['productsn']);
		}

		if ($item['diyformtype'] != 2) {
			$data['diyformid'] = intval($_GPC['diyformid']);

			if (!empty($data['diyformid'])) {
				$data['diyformtype'] = 1;
			}
			else {
				$data['diyformtype'] = 0;
			}
		}

		if (empty($data['dispatchtype'])) {
			$data['dispatchid'] = intval($_GPC['dispatchid']);
		}
		else {
			$data['dispatchprice'] = trim($_GPC['dispatchprice']);
		}

		$cateset = m('common')->getSysset('shop');
		$pcates = array();
		$ccates = array();
		$tcates = array();
		$fcates = array();
		$cates = array();
		$pcateid = 0;
		$ccateid = 0;
		$tcateid = 0;
		$cates = $_GPC['cates'];
		if (!is_array($cates) && !empty($cates)) {
			$cates = explode(',', $cates);
		}

		if (is_array($cates)) {
			foreach ($cates as $key => $cid) {
				$c = pdo_fetch('select level from ' . tablename('wx_shop_category') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $cid, ':uniacid' => $_W['uniacid']));

				if ($c['level'] == 1) {
					$pcates[] = $cid;
				}
				else if ($c['level'] == 2) {
					$ccates[] = $cid;
				}
				else {
					if ($c['level'] == 3) {
						$tcates[] = $cid;
					}
				}

				if ($key == 0) {
					if ($c['level'] == 1) {
						$pcateid = $cid;
					}
					else if ($c['level'] == 2) {
						$crow = pdo_fetch('select parentid from ' . tablename('wx_shop_category') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $cid, ':uniacid' => $_W['uniacid']));
						$pcateid = $crow['parentid'];
						$ccateid = $cid;
					}
					else {
						if ($c['level'] == 3) {
							$tcateid = $cid;
							$tcate = pdo_fetch('select id,parentid from ' . tablename('wx_shop_category') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $cid, ':uniacid' => $_W['uniacid']));
							$ccateid = $tcate['parentid'];
							$ccate = pdo_fetch('select id,parentid from ' . tablename('wx_shop_category') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $ccateid, ':uniacid' => $_W['uniacid']));
							$pcateid = $ccate['parentid'];
						}
					}
				}
			}
		}

		$data['pcate'] = $pcateid;
		$data['ccate'] = $ccateid;
		$data['tcate'] = $tcateid;
		$data['cates'] = implode(',', $cates);
		$data['pcates'] = implode(',', $pcates);
		$data['ccates'] = implode(',', $ccates);
		$data['tcates'] = implode(',', $tcates);

		if (!empty($item)) {
			pdo_update('wx_shop_goods', $data, array('id' => $item['id'], 'uniacid' => $_W['uniacid']));
			plog('goods.edit', '编辑商品 ID: ' . $id . '<br>' . (!empty($data['nocommission']) ? '是否参与分销 -- 否' : '是否参与分销 -- 是'));
		}
		else {
			$data['createtime'] = time();
			$data['uniacid'] = $_W['uniacid'];
			pdo_insert('wx_shop_goods', $data);
			$id = pdo_insertid();
			$result['id'] = $id;
			plog('goods.add', '添加商品 ID: ' . $id . '<br>' . (!empty($data['nocommission']) ? '是否参与分销 -- 否' : '是否参与分销 -- 是'));
		}

		if (!empty($item['hasoption'])) {
			$sql = 'update ' . tablename('wx_shop_goods') . " g set\r\n            g.minprice = (select min(marketprice) from " . tablename('wx_shop_goods_option') . ' where goodsid = ' . $id . "),\r\n            g.maxprice = (select max(marketprice) from " . tablename('wx_shop_goods_option') . ' where goodsid = ' . $id . ")\r\n            where g.id = " . $id . ' and g.hasoption=1';
			pdo_query($sql);
		}
		else {
			pdo_query('delete from ' . tablename('wx_shop_goods_option') . ' where goodsid=' . $id);
			$sql = 'update ' . tablename('wx_shop_goods') . ' set minprice = marketprice,maxprice = marketprice where id = ' . $id . ' and hasoption=0;';
			pdo_query($sql);
		}

		$sqlgoods = 'SELECT id,title,thumb,marketprice,productprice,minprice,maxprice,isdiscount,isdiscount_time,isdiscount_discounts,sales,total,description,merchsale FROM ' . tablename('wx_shop_goods') . ' where id=:id and uniacid=:uniacid limit 1';
		$goodsinfo = pdo_fetch($sqlgoods, array(':id' => $id, ':uniacid' => $_W['uniacid']));
		$goodsinfo = m('goods')->getOneMinPrice($goodsinfo);
		pdo_update('wx_shop_goods', array('minprice' => $goodsinfo['minprice'], 'maxprice' => $goodsinfo['maxprice']), array('id' => $id, 'uniacid' => $_W['uniacid']));
		app_json();
	}

	/**
     * 删除商品
     */
	public function delete()
	{
		global $_W;
		global $_GPC;

		if (!$_W['ispost']) {
			app_error(AppError::$RequestError);
		}

		if (!cv('goods.delete')) {
			app_error(AppError::$PermError, '您无操作权限');
		}

		$id = intval($_GPC['id']);
		$ids = $_GPC['ids'];
		if (empty($id) && !empty($ids)) {
			if (is_array($ids)) {
				$id = implode(',', $ids);
			}
			else {
				if (strexists($ids, ',')) {
					$id = $ids;
				}
			}
		}

		if (empty($id)) {
			app_error(AppError::$ParamsError);
		}

		$items = pdo_fetchall('SELECT id, title FROM ' . tablename('wx_shop_goods') . ' WHERE id in( ' . $id . ' ) AND uniacid=:uniacid', array(':uniacid' => $_W['uniacid']));

		if (!empty($items)) {
			foreach ($items as $item) {
				pdo_update('wx_shop_goods', array('deleted' => 1), array('id' => $item['id'], 'uniacid' => $_W['uniacid']));
				plog('goods.delete', '删除商品 ID: ' . $item['id'] . ' 商品名称: ' . $item['title'] . ' ');
			}
		}

		app_json();
	}

	/**
     * 上下架
     */
	public function status()
	{
		global $_W;
		global $_GPC;

		if (!$_W['ispost']) {
			app_error(AppError::$RequestError);
		}

		if (!cv('goods.status')) {
			app_error(AppError::$PermError, '您无操作权限');
		}

		$status = intval($_GPC['status']);
		if (($status != 0) && ($status != 1)) {
			app_error(AppError::$ParamsError);
		}

		$id = intval($_GPC['id']);
		$ids = $_GPC['ids'];
		if (empty($id) && !empty($ids)) {
			if (is_array($ids)) {
				$id = implode(',', $ids);
			}
			else {
				if (strexists($ids, ',')) {
					$id = $ids;
				}
			}
		}

		if (empty($id)) {
			app_error(AppError::$ParamsError);
		}

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('wx_shop_goods') . ' WHERE id in( ' . $id . ' ) AND uniacid=:uniacid', array(':uniacid' => $_W['uniacid']));

		if (!empty($items)) {
			foreach ($items as $item) {
				pdo_update('wx_shop_goods', array('status' => $status), array('id' => $item['id'], 'uniacid' => $_W['uniacid']));
				plog('goods.edit', ('修改商品状态<br/>ID: ' . $item['id'] . '<br/>商品名称: ' . $item['title'] . '<br/>状态: ' . $_GPC['status']) == 1 ? '上架' : '下架');
			}
		}

		app_json();
	}

	/**
     * 恢复至仓库
     */
	public function restore()
	{
		global $_W;
		global $_GPC;

		if (!$_W['ispost']) {
			app_error(AppError::$RequestError);
		}

		if (!cv('goods.restore')) {
			app_error(AppError::$PermError, '您无操作权限');
		}

		$id = intval($_GPC['id']);
		$ids = $_GPC['ids'];
		if (empty($id) && !empty($ids)) {
			if (is_array($ids)) {
				$id = implode(',', $ids);
			}
			else {
				if (strexists($ids, ',')) {
					$id = $ids;
				}
			}
		}

		if (empty($id)) {
			app_error(AppError::$ParamsError);
		}

		$items = pdo_fetchall('SELECT id,title FROM ' . tablename('wx_shop_goods') . ' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid']);

		if (!empty($items)) {
			foreach ($items as $item) {
				pdo_update('wx_shop_goods', array('deleted' => 0, 'status' => 0), array('id' => $item['id'], 'uniacid' => $_W['uniacid']));
				plog('goods.restore', '从回收站恢复商品<br/>ID: ' . $item['id'] . '<br/>商品名称: ' . $item['title']);
			}
		}

		app_json();
	}
}

?>
