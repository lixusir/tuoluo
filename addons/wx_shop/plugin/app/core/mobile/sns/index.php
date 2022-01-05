<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
// echo 11;die;
require WX_SHOP_PLUGIN . 'sns/core/page_mobile.php';
class Index_WxShopPage //extends SnsMobilePage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$openid = $_GPC['openid'];
		$uniacid = $_W['uniacid'];
		// var_dump($openid,$uniacid);die;
		// $shop = m('common')->getSysset('shop');
		$advs = pdo_fetchall('select id,advname,link,thumb from ' . tablename('wx_shop_sns_adv') . ' where uniacid=:uniacid and enabled=1 order by displayorder desc', array(':uniacid' => $uniacid));
		// var_dump($advs, $uniacid);die;
		// $credit = m('member')->getCredit($openid, 'credit1');
		$category = pdo_fetchall('select id,`name`,thumb,isrecommand from ' . tablename('wx_shop_sns_category') . ' where uniacid=:uniacid and isrecommand = 1 and enabled=1 order by displayorder desc', array(':uniacid' => $uniacid));
		$recommands = pdo_fetchall('select sb.id,sb.title,sb.logo,sb.`desc`  from ' . tablename('wx_shop_sns_board') . " as sb\r\n\t\t\t\t\t\tleft join " . tablename('wx_shop_sns_category') . " as sc on sc.id = sb.cid\r\n\t\t\t\t\t\twhere sb.uniacid=:uniacid and sb.isrecommand=1 and sb.status=1 and sc.enabled = 1 order by sb.displayorder desc", array(':uniacid' => $uniacid));
		// var_dump($recommands);exit;
		foreach ($recommands as &$row) {
			// $row['postcount'] = $this->model->getPostCount($row['id']);
			// $row['followcount'] = $this->model->getFollowCount($row['id']);
			$row['postcount'] = $this->getPostCount($row['id']);
			$row['followcount'] = $this->getFollowCount($row['id']);
		}

		$new_advs = array();
		foreach ($advs as $key => $value) {
			if(!empty($value['thumb'])) {
				if (!preg_match('/^http/', $value['thumb'])) {
					$value['thumb'] = $_W['siteroot'].'attachment/'.$value['thumb'];
				}
			}
			$new_advs[$key] = $value;
		}

		$new_category = array();
		foreach ($category as $k => $v) {
			if(!empty($v['thumb'])) {
				if (!preg_match('/^http/', $v['thumb'])) {
					$v['thumb'] = $_W['siteroot'].'attachment/'.$v['thumb'];
				}
			}
			$new_category[$k] = $v;
		}

		$new_recommands = array();
		foreach ($recommands as $k1 => $v) {
			if(!empty($v['logo'])) {
				if (!preg_match('/^http/', $v['logo'])) {
					$v['logo'] = $_W['siteroot'].'attachment/'.$v['logo'];
				}
			}
			$new_recommands[$k1] = $v;
		}
		unset($row);
		$_W['shopshare'] = array('title' => $this->set['share_title'], 'imgUrl' => tomedia($this->set['share_icon']), 'link' => mobileUrl('sns', array(), true), 'desc' => $this->set['share_desc']);
		// include $this->template();
		show_json(1,
			array(
				'advs'=> $new_advs,
				'category' => $new_category,
				'recommands' => $new_recommands
		));
		// app_json
	}

		/**
         * 社区话题数
         * @param $bid
         * @return mixed
         */
		private function getPostCount($bid)
		{
			global $_W;
			return pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_sns_post') . "\r\n            where uniacid=:uniacid and bid=:bid and pid=0 and deleted = 0 limit 1", array(':uniacid' => $_W['uniacid'], ':bid' => $bid));
		}

		/**
         * 社区关注数
         * @param $bid
         * @return mixed
         */
		private function getFollowCount($bid)
		{
			global $_W;
			return pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_sns_board_follow') . "\r\n            where uniacid=:uniacid and bid=:bid limit 1", array(':uniacid' => $_W['uniacid'], ':bid' => $bid));
		}
		
}

?>
