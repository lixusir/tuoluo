<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require WX_SHOP_PLUGIN . 'sns/core/page_mobile.php';
require __DIR__ . '/base.php';
class User_WxShopPage extends Base_WxShopPage//extends SnsMobilePage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$set = $this->getSet();
		$_W['openid'] = empty($_W['openid']) ? $_GPC['openid'] : $_W['openid'];
		// $id = empty($_W['openid']) ? $_GPC['openid'] : $_W['openid'];
		$id = intval($_GPC['id']);
		if (empty($id)) {
			if (!$this->islogin) {
				// $url = str_replace('./index.php?', '', mobileUrl('sns/user'));
				// $loginurl = mobileUrl('account/login', array('backurl' => urlencode(base64_encode($url))));
				// header('location: ' . $loginurl);
				// exit();
				show_json(0, array('error' => '用户未登录'));
			}
			$member = $this->model->getMember($_W['openid']);
		}
		else {
			$member = $this->model->getMember($id);
		}
		$member['avatar'] = $this->model->getAvatar($member['avatar']);
		if (empty($member)) {
			// show_message('未找到用户!', '', 'error');
			show_json(0, '未找到用户');
		}
		$openid = $member['openid'];
		$level = array('levelname' => empty($set['levelname']) ? '社区粉丝' : $set['levelname'], 'color' => empty($set['levelcolor']) ? '#333' : $set['levelcolor'], 'bg' => empty($set['levelbg']) ? '#eee' : $set['levelbg']);
		if (!empty($member['sns_level'])) {
			$level = pdo_fetch('select * from ' . tablename('wx_shop_sns_level') . ' where id=:id  limit 1', array(':id' => $member['sns_level']));
		}

		$boardcount = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_sns_board_follow') . ' where uniacid=:uniacid and openid=:openid', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
		$postcount = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_sns_post') . ' where uniacid=:uniacid and openid=:openid and pid=0 and deleted = 0 and checked=1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
		$boards = pdo_fetchall('select b.id,b.logo,b.title from ' . tablename('wx_shop_sns_board_follow') . ' f ' . ' left join ' . tablename('wx_shop_sns_board') . ' b on f.bid = b.id ' . '   where f.uniacid=:uniacid and f.openid=:openid limit 5', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
		$boards = set_medias($boards, 'logo');
		$followcount = count($boards);
		$posts = pdo_fetchall('select p.id,p.images,p.title ,p.views, b.title as boardtitle,b.logo as boardlogo from ' . tablename('wx_shop_sns_post') . ' p ' . ' left join ' . tablename('wx_shop_sns_board') . ' b on p.bid = b.id ' . '   where p.uniacid=:uniacid and p.openid=:openid and pid=0 and deleted=0 and checked=1 order by createtime desc limit 3', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
		foreach ($posts as &$r) {
			$images = iunserializer($r['images']);
			$thumb = '';
			if (is_array($images) && !empty($images)) {
				$thumb = $images[0];
			}

			if (empty($thumb)) {
				$thumb = $r['boardlogo'];
			}

			$r['thumb'] = tomedia($thumb);
		}
		$isMe = 0;
		unset($r);
		if ($openid == $_W['openid']) {
			$replycount = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_sns_post') . ' where uniacid=:uniacid and openid=:openid and pid>0 and deleted = 0 and checked=1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			$replys = pdo_fetchall("select p.id, p.content, p.views,\r\n                  parent.id as parentid, \r\n                  parent.nickname as parentnickname,parent.title as parenttitle ,\r\n                  rparent.nickname as rparentnickname\r\n                  from " . tablename('wx_shop_sns_post') . ' p ' . ' left join ' . tablename('wx_shop_sns_post') . ' parent on p.pid = parent.id ' . ' left join ' . tablename('wx_shop_sns_post') . ' rparent on p.rpid = rparent.id ' . '   where p.uniacid=:uniacid and p.openid=:openid and p.pid>0 and p.deleted=0 and p.checked=1 order by p.createtime desc limit 3', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));

			foreach ($replys as &$r) {
				$parentnickname = $r['rparentnickname'];

				if (empty($parentnickname)) {
					$parentnickname = $r['parentnickname'];
				}

				$r['parentnickname'] = $parentnickname;
			}
			$isMe = 1;
			unset($r);
		}

		$_W['shopshare'] = array('title' => $this->set['share_title'], 'imgUrl' => tomedia($this->set['share_icon']), 'link' => mobileUrl('sns', array(), true), 'desc' => $this->set['share_desc']);
		// include $this->template();
		show_json(1, 
			array(
				'boardcount' => $boardcount,
				'postcount'  => $postcount,
				'boards'	 => $boards,
				'followcount'=> $followcount,
				'posts'		 => $posts,
				'replys'	 => $replys,
				'replycount' => $replycount,
				'member'     => $member,
				'isMe'		 => $isMe,


		));
	}

	public function boards()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$_W['openid'] = empty($_W['openid']) ? $_GPC['openid'] : $_W['opendi'];

		if (empty($id)) {
			if (!$this->islogin) {
				$url = str_replace('./index.php?', '', mobileUrl('sns/user/boards'));
				$loginurl = mobileUrl('account/login', array('backurl' => urlencode(base64_encode($url))));
				$this->message('您未登录!', $url, 'error');
			}

			$member = $this->model->getMember($_W['openid']);
		}
		else {
			$member = $this->model->getMember($id);
		}

		if (empty($member)) {
			show_message('未找到用户!', '', 'error');
		}

		$openid = $member['openid'];
		// include $this->template();
	}

	public function get_boards()
	{
		global $_W;
		global $_GPC;

		if (empty($id)) {
			if (!$this->islogin) {
				show_json(0, '未登录!');
			}

			$member = $this->model->getMember($_W['openid']);
		}
		else {
			$member = $this->model->getMember($id);
		}

		if (empty($member)) {
			show_message('未找到用户!', '', 'error');
		}

		$openid = $member['openid'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$condition = ' and f.uniacid = :uniacid and f.openid=:openid';
		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $openid);
		$sql = 'select b.id,b.logo,b.title from ' . tablename('wx_shop_sns_board_follow') . ' f ' . ' left join ' . tablename('wx_shop_sns_board') . ' b on f.bid = b.id ' . '   where 1 ' . $condition . ' ORDER BY f.createtime asc LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
		$list = pdo_fetchall($sql, $params);
		$total = pdo_fetchcolumn('select b.id,b.logo,b.title from ' . tablename('wx_shop_sns_board_follow') . ' f ' . ' left join ' . tablename('wx_shop_sns_board') . ' b on f.bid = b.id ' . ' where 1 ' . $condition, $params);

		foreach ($list as &$row) {
			$row['postcount'] = $this->model->getPostCount($row['id']);
			$row['followcount'] = $this->model->getFollowCount($row['id']);
			$row['logo'] = tomedia($row['logo']);
		}

		unset($row);
		show_json(1, array('list' => $list, 'pagesize' => $psize, 'total' => $total));
	}

	public function posts()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$_W['openid'] = empty($_W['openid']) ? $_GPC['openid'] : $_W['openid'];
		
		if (empty($id)) {
			if (!$this->islogin) {
				$url = str_replace('./index.php?', '', mobileUrl('sns/user/posts'));
				$loginurl = mobileUrl('account/login', array('backurl' => urlencode(base64_encode($url))));
				show_json(0, '您未登录');
				// $this->message('您未登录!', $url, 'error');
			}

			$member = $this->model->getMember($_W['openid']);
		}
		else {
			$member = $this->model->getMember($id);
		}

		if (empty($member)) {
			// show_message('未找到用户!', '', 'error');
			show_json(0, '未找到用户');
		}

		$openid = $member['openid'];
		show_json(1, 'success');
		// include $this->template();
	}

	/**
	 * 我的话题
	 * @author lucky
	 * @DateTime 2018-08-06T10:15:57+0800
	 * @return   [type]                   [description]
	 */
	public function get_posts()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		
		if (empty($id)) {
			if (!$this->islogin) {
				show_json(0, '未登录!');
			}
			$member = $this->model->getMember($_W['openid']);
		}
		else {
			$member = $this->model->getMember($id);
		}

		$openid = $member['openid'];
		$shop = m('common')->getSysset('shop');
		$uniacid = $_W['uniacid'];
		$bid = intval($_GPC['bid']);
		$isbest = trim($_GPC['isbest']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$condition = ' and `uniacid` = :uniacid and `pid`=0 and `deleted`=0 and openid=:openid';
		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $openid);

		if (!empty($bid)) {
			$condition .= ' and `bid`=' . $bid;
		}

		if ($isbest == '1') {
			$condition .= ' and `isboardbest`=1';
		}
		$isManager = $this->model->isManager($bid);
		if (!$isManager) {
			$condition .= ' and `checked`=1';
		}

		$sql = 'select id,title,createtime,content,images , nickname,avatar,isbest,isboardbest,checked from ' . tablename('wx_shop_sns_post') . '  where 1 ' . $condition . ' ORDER BY createtime desc,id DESC LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
		$list = pdo_fetchall($sql, $params);
		$total = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_sns_post') . ' where 1 ' . $condition, $params);
		$pages = ceil($total/$psize);

		foreach ($list as &$row) {
			$row['avatar'] = $this->model->getAvatar($row['avatar']);

			$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
			$row['goodcount'] = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_sns_like') . ' where pid=:pid limit 1', array(':pid' => $row['id']));
			$row['postcount'] = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_sns_post') . ' where pid=:pid limit 1', array(':pid' => $row['id']));
			$row['content'] = htmlspecialchars_decode($row['content']);
			$images = array();
			$rowimages = iunserializer($row['images']);
			if (is_array($rowimages) && !empty($rowimages)) {
				foreach ($rowimages as $img) {
					if (count($images) <= 2) {
						$images[] = tomedia($img);
					}
				}
			}

			$row['images'] = $images;
			$row['imagewidth'] = '100%';
			$row['imagecount'] = count($rowimages);

			if (count($row['images']) == 2) {
				$row['imagewidth'] = '50%';
			}
			else {
				if (count($row['images']) == 3) {
					$row['imagewidth'] = '33%';
				}
			}

			$row['content'] = $this->model->replaceContent($row['content']);
			//处理新的评论
			$row['content_new'] = str_replace('../addons/', 'https://xcxvip.iiio.top/addons/', $row['content']);
			if (is_array($row['images']) && !empty($row['images'])) {
				foreach ($row['images'] as $key => $image) {
					$row['images'][$key] = tomedia($image);
					// var_dump($row['images']);die;
					$row['content_new'] .= '<p><img src="'. $image .'"></p>';
				}
			}
		}

		unset($row);
		show_json(1, array('list' => $list, 'pagesize' => $psize, 'total' => $total, 'pages' => $pages));
	}

	public function replys()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$_W['openid'] = empty($_W['openid']) ? $_GPC['openid'] : $_W['openid'];
		if (empty($id)) {
			if (!$this->islogin) {
				$url = str_replace('./index.php?', '', mobileUrl('sns/user/replys'));
				$loginurl = mobileUrl('account/login', array('backurl' => urlencode(base64_encode($url))));
				// $this->message('您未登录!', $url, 'error');
				show_json(0, '您未登录');
			}

			$member = $this->model->getMember($_W['openid']);

		}
		else {
			$member = $this->model->getMember($id);

		}

		if (empty($member)) {
			// show_message('未找到用户!', '', 'error');
			show_json(0, '未找到用户');
		}

		$openid = $member['openid'];
		// echo $openid;die;
		show_json(1, 'success');
		// include $this->template();
	}

	public function get_replys()
	{
		global $_W;
		global $_GPC;
		if (!$this->islogin) {
			show_json(0, '未登录!');
		}
		$openid = empty($_W['openid']) ? $_GPC['openid'] : $_W['openid'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$condition = ' p.uniacid=:uniacid and p.openid=:openid and p.pid>0 and p.deleted=0 and p.checked=1';
		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $openid);
		$sql = "select p.id, p.content, p.views,\r\n                  parent.id as parentid, parent.nickname as parentnickname,parent.title as parenttitle ,parent.images as parentimages,\r\n                  rparent.nickname as rparentnickname,\r\n                  b.title as boardtitle, b.logo as boardlogo,b.id as boardid\r\n                  from " . tablename('wx_shop_sns_post') . ' p ' . ' left join ' . tablename('wx_shop_sns_post') . ' parent on p.pid = parent.id ' . ' left join ' . tablename('wx_shop_sns_post') . ' rparent on p.rpid = rparent.id ' . ' left join ' . tablename('wx_shop_sns_board') . ' b on b.id=p.bid ' . '   where 1 and ' . $condition . ' order by p.createtime desc limit ' . (($pindex - 1) * $psize) . ',' . $psize;
		$list = pdo_fetchall($sql, $params);
		$total = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_sns_post') . ' p ' . ' left join ' . tablename('wx_shop_sns_post') . ' parent on p.pid = parent.id ' . ' left join ' . tablename('wx_shop_sns_post') . ' rparent on p.rpid = rparent.id ' . ' where 1 and ' . $condition, $params);
		$pages = ceil($total/$psize);

		foreach ($list as &$r) {
			$parentnickname = $r['rparentnickname'];

			if (empty($parentnickname)) {
				$parentnickname = $r['parentnickname'];
			}

			$r['parentnickname'] = $parentnickname;
			$r['boardlogo'] = tomedia($r['boardlogo']);
			$images = iunserializer($r['parentimages']);
			$thumb = '';
			if (is_array($images) && !empty($images)) {
				$thumb = $images[0];
			}

			if (empty($thumb)) {
				$thumb = $r['boardlogo'];
			}

			$r['thumb'] = tomedia($thumb);
		}
		unset($r);
		show_json(1, array('list' => $list, 'pagesize' => $psize, 'total' => $total, 'pages' => $pages));
	}

	public function delete_reply()
	{
		global $_W;
		global $_GPC;

		if (!$this->islogin) {
			show_json(0, '未登录!');
		}

		$id = intval($_GPC['id']);

		if (empty($id)) {
			show_json(0, '参数错误!');
		}

		$post = $this->getPost($id);
		
		if (empty($post)) {
			show_json(0, '数据未找到!');
		}

		if ($post['openid'] !== $_W['openid']) {
			show_json(0, '无权删除TA人数据!');
		}
		pdo_update('wx_shop_sns_post', array('deleted' => 1, 'deletedtime' => time()), array('id' => $id));
		show_json(1, '删除成功');
	}
	/**
	 * 修改个人签名
	 * @author lucky
	 * @DateTime 2018-08-04T10:30:33+0800
	 * @return   void
	 */
	public function submit_sign()
	{
		global $_W;
		global $_GPC;

		if (!$this->islogin) {
			show_json(0, '未登录!');
		}
		$openid = empty($_W['openid'])? $_GPC['openid'] : $_W['openid'];
		$sign = trim($_GPC['sign']);
		$member = pdo_fetch('select * from ' . tablename('wx_shop_sns_member') . ' where uniacid=:uniacid and openid=:openid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
		if (empty($member)) {
			$member = array('uniacid' => $_W['uniacid'], 'openid' => $openid, 'sign' => $sign , 'createtime' => time());
			pdo_insert('wx_shop_sns_member', $member);
			show_json(1, '修改成功');
		}
		pdo_update('wx_shop_sns_member', array('sign' => $sign), array('openid' => $openid, 'uniacid' => $_W['uniacid']));
		// $res2 = pdo_fetch('select * from '. tablename('wx_shop_sns_member') . 'where openid = :openid', array(':openid' => $openid));
		show_json(1, '修改成功');
	}

}

?>
