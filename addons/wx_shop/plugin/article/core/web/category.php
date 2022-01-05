<?php

if (!defined('IN_IA')) {

	exit('Access Denied');

}



class Category_WxShopPage extends PluginWebPage

{

	public function main()

	{

		global $_W;

		global $_GPC;

		$list = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_article_category') . ' WHERE uniacid = \'' . $_W['uniacid'] . '\' ORDER BY displayorder desc ,id desc');

		// echo '<pre>';
		//     print_r(array( 'text' => '游戏', 
		//     	'list' => array('text' => '游戏商品', 'main' => '查看列表', 'add' => '添加-log', 'edit' => '修改-log', 'delete' => '删除-log'),
		//     	 'level' => array( 'text' => '会员等级', 'main' => '查看列表', 'add' => '添加-log', 'edit' => '修改-log', 'delete' => '删除-log', 'xxx' => array('enable' => 'edit') ),
		//     	  'list' => array( 'text' => '会员管理', 'view' => '浏览', 'edit' => '修改-log', 'detail' => '查看修改资料-log', 'delete' => '删除-log', 'xxx' => array('setblack' => 'edit') ),
		//     	   'rank' => array('text' => '排行榜', 'main' => '查看', 'edit' => '修改-log'), 
		//     	   'tmessage' => array( 'text' => '会员群发', 'send' => '群发消息-log', 'xxx' => array('sendmessage' => 'send', 'fetch' => 'send') ), 
		//     	   'card' => array('text' => '微信会员卡管理', 'add' => '添加', 'edit' => '修改', 'delete' => '删除', 'stock' => '修改库存', 'active' => '激活设置') )
		// 	);
		// echo '</pre>';
		// exit;
		// echo '<pre>';
		//     print_r($list);
		// echo '</pre>';
		// exit;
		include $this->template();

	}



	public function save()

	{

		global $_W;

		global $_GPC;



		if (!empty($_GPC['cate'])) {




			foreach ($_GPC['cate'] as $id => $cate) {

				$data = array('category_name' => trim($cate['name']), 'displayorder' => intval($cate['displayorder']), 'isshow' => intval($cate['isshow']));

				$data['img'] = $_GPC['img'.$id];



				if (!empty($id) && !empty($data['category_name'])) {

					pdo_update('wx_shop_article_category', $data, array('id' => $id));

					plog('article.category.save', '修改文章分类 ID: ' . $id . ' 名称: ' . $data['category_name']);

				}

			}

		}



		if (!empty($_GPC['cate_new'])) {

			foreach ($_GPC['cate_new'] as $cate_new) {

				$cate_new = trim($cate_new);



				if (empty($cate_new)) {

					continue;

				}



				pdo_insert('wx_shop_article_category', array('category_name' => $cate_new, 'uniacid' => $_W['uniacid']));

				$insert_id = pdo_insertid();

				plog('article.category.save', '添加分类 ID: ' . $insert_id . ' 名称: ' . $cate_new);

			}

		}



		plog('article.category.save', '批量修改分类');

		show_json(1);

	}



	public function delete()

	{

		global $_W;

		global $_GPC;

		$id = intval($_GPC['id']);

		$item = pdo_fetch('SELECT id,category_name FROM ' . tablename('wx_shop_article_category') . ' WHERE id = \'' . $id . '\' AND uniacid=' . $_W['uniacid'] . '');



		if (!empty($item)) {

			pdo_delete('wx_shop_article_category', array('id' => $id));

			plog('article.category.delete', '删除分类 ID: ' . $id . ' 标题: ' . $item['name'] . ' ');

		}



		show_json(1);

	}

}



?>

