<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Weight_WxShopPage extends PluginWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$weightlevels = $this->model->getLevels(true, true);
		$level = $this->set['level'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$params = array();
		$condition = '';
		$keyword = trim($_GPC['keyword']);

		if (!empty($keyword)) {
			$condition .= ' and ( dm.realname like :keyword or dm.nickname like :keyword or dm.mobile like :keyword)';
			$params[':keyword'] = '%' . $keyword . '%';
		}

		if ($_GPC['followed'] != '') {
			if ($_GPC['followed'] == 2) {
				$condition .= ' and f.follow=0 and dm.uid<>0';
			}
			else {
				$condition .= ' and f.follow=' . intval($_GPC['followed']);
			}
		}

		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}

		if (!empty($_GPC['time']['start']) && !empty($_GPC['time']['end'])) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']);
			$condition .= ' AND dm.weighttime >= :starttime AND dm.weighttime <= :endtime ';
			$params[':starttime'] = $starttime;
			$params[':endtime'] = $endtime;
		}

		if ($_GPC['weightlevel'] != '') {
			$condition .= ' and dm.weightlevel=' . intval($_GPC['weightlevel']);
		}

		if ($_GPC['weightblack'] != '') {
			$condition .= ' and dm.weightblack=' . intval($_GPC['weightblack']);
		}

		if ($_GPC['weightstatus'] != '') {
			$condition .= ' and dm.weightstatus=' . intval($_GPC['weightstatus']);
		}

		$sql = 'select dm.*,dm.nickname,dm.avatar,l.levelname,p.nickname as parentname,p.avatar as parentavatar from ' . tablename('wx_shop_member') . ' dm ' . ' left join ' . tablename('wx_shop_member') . ' p on p.id = dm.agentid ' . ' left join ' . tablename('wx_shop_weightbonus_level') . ' l on l.id = dm.weightlevel' . ' where dm.uniacid = ' . $_W['uniacid'] . ' and (dm.isweight =1 or dm.isweight=-1)  ' . $condition . ' ORDER BY dm.weighttime desc';

		if (empty($_GPC['export'])) {
			$sql .= ' limit ' . (($pindex - 1) * $psize) . ',' . $psize;
		}

		$list = pdo_fetchall($sql, $params);
		$total = pdo_fetchcolumn('select count(dm.id) from' . tablename('wx_shop_member') . ' dm  ' . ' left join ' . tablename('wx_shop_member') . ' p on p.id = dm.agentid ' . ' left join ' . tablename('wx_shop_weightbonus_level') . ' l on l.id = dm.weightlevel' . ' where dm.uniacid =' . $_W['uniacid'] . ' and (dm.isweight =1 or dm.isweight=-1) ' . $condition, $params);

		foreach ($list as &$row) {
			$bonus = $this->model->getBonus($row['openid'], array('ok'));
			$row['bonus'] = $bonus['ok'];
			$row['followed'] = m('user')->followed($row['openid']);
		}

		unset($row);

		if ($_GPC['export'] == '1') {
			ca('weightbonus.weight.export');
			plog('weightbonus.weight.export', '导出代理数据');

			foreach ($list as &$row) {
				$row['createtime'] = date('Y-m-d H:i', $row['createtime']);
				$row['weightime'] = empty($row['weighttime']) ? '' : date('Y-m-d H:i', $row['weightime']);
				$row['groupname'] = empty($row['groupname']) ? '无分组' : $row['groupname'];
				$row['levelname'] = empty($row['levelname']) ? '普通等级' : $row['levelname'];
				$row['parentname'] = empty($row['parentname']) ? '总店' : '[' . $row['agentid'] . ']' . $row['parentname'];
				$row['statusstr'] = empty($row['status']) ? '' : '通过';
				$row['followstr'] = empty($row['followed']) ? '' : '已关注';
			}

			unset($row);
			m('excel')->export($list, array(
	'title'   => '代理数据-' . date('Y-m-d-H-i', time()),
	'columns' => array(
		array('title' => 'ID', 'field' => 'id', 'width' => 12),
		array('title' => '昵称', 'field' => 'nickname', 'width' => 12),
		array('title' => '姓名', 'field' => 'realname', 'width' => 12),
		array('title' => '手机号', 'field' => 'mobile', 'width' => 12),
		array('title' => '微信号', 'field' => 'weixin', 'width' => 12),
		array('title' => 'openid', 'field' => 'openid', 'width' => 24),
		array('title' => '推荐人', 'field' => 'parentname', 'width' => 12),
		array('title' => '代理等级', 'field' => 'levelname', 'width' => 12),
		array('title' => '累计分红', 'field' => 'bonus', 'width' => 12),
		array('title' => '注册时间', 'field' => 'createtime', 'width' => 12),
		array('title' => '成为代理时间', 'field' => 'weightagenttime', 'width' => 12),
		array('title' => '审核状态', 'field' => 'statusstr', 'width' => 12),
		array('title' => '是否关注', 'field' => 'followstr', 'width' => 12)
		)
	));
		}

		$pager = pagination2($total, $pindex, $psize);
		load()->func('tpl');
		include $this->template();
	}

	public function delete()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = (is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0);
		}

		$members = pdo_fetchall('SELECT * FROM ' . tablename('wx_shop_member') . ' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid']);

		foreach ($members as $member) {
			pdo_update('wx_shop_member', array('isweight' => 0, 'weightstatus' => 0), array('id' => $member['id']));
			plog('weightbonus.weight.delete', '取消代理资格 <br/>代理信息:  ID: ' . $member['id'] . ' /  ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
		}

		show_json(1, array('url' => referer()));
	}

	public function query()
	{
		global $_W;
		global $_GPC;
		$kwd = trim($_GPC['keyword']);
		$wechatid = intval($_GPC['wechatid']);

		if (empty($wechatid)) {
			$wechatid = $_W['uniacid'];
		}

		$params = array();
		$params[':uniacid'] = $wechatid;
		$condition = ' and uniacid=:uniacid and isweight=1';

		if (!empty($kwd)) {
			$condition .= ' AND ( `nickname` LIKE :keyword or `realname` LIKE :keyword or `mobile` LIKE :keyword )';
			$params[':keyword'] = '%' . $kwd . '%';
		}

		if (!empty($_GPC['selfid'])) {
			$condition .= ' and id<>' . intval($_GPC['selfid']);
		}

		$ds = pdo_fetchall('SELECT id,avatar,nickname,openid,realname,mobile FROM ' . tablename('wx_shop_member') . ' WHERE 1 ' . $condition . ' order by createtime desc', $params);
		include $this->template('weightbonus/query');
	}

	public function check()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = (is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0);
		}

		$status = intval($_GPC['status']);
		$members = pdo_fetchall('SELECT id,openid,nickname,realname,mobile,weightstatus FROM ' . tablename('wx_shop_member') . ' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid']);
		$time = time();

		foreach ($members as $member) {
			if ($member['parnetstatus'] === $status) {
				continue;
			}

			if ($status == 1) {
				pdo_update('wx_shop_member', array('weightstatus' => 1, 'weighttime' => $time), array('id' => $member['id'], 'uniacid' => $_W['uniacid']));
				plog('weightbonus.weight.check', '审核代理 <br/>代理信息:  ID: ' . $member['id'] . ' /  ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
				$this->model->sendMessage($member['openid'], array('nickname' => $member['nickname'], 'weighttime' => $time), TM_GLOBONUS_BECOME);
			}
			else {
				pdo_update('wx_shop_member', array('weightstatus' => 0, 'weighttime' => 0), array('id' => $member['id'], 'uniacid' => $_W['uniacid']));
				plog('weightbonus.weight.check', '取消审核 <br/>代理信息:  ID: ' . $member['id'] . ' /  ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
			}
		}

		show_json(1, array('url' => referer()));
	}

	public function weightblack()
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = (is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0);
		}

		$weightblack = intval($_GPC['weightblack']);
		$members = pdo_fetchall('SELECT id,openid,nickname,realname,mobile FROM ' . tablename('wx_shop_member') . ' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid']);

		foreach ($members as $member) {
			if ($member['weightblack'] === $weightblack) {
				continue;
			}

			if ($weightblack == 1) {
				pdo_update('wx_shop_member', array('isweight' => 1, 'weightstatus' => 0, 'weightblack' => 1), array('id' => $member['id']));
				plog('weightbonus.weight.weightblack', '设置黑名单 <br/>代理信息:  ID: ' . $member['id'] . ' /  ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
			}
			else {
				pdo_update('wx_shop_member', array('isweight' => 1, 'weightstatus' => 1, 'weightblack' => 0), array('id' => $member['id']));
				plog('weightbonus.weight.weightblack', '取消黑名单 <br/>代理信息:  ID: ' . $member['id'] . ' /  ' . $member['openid'] . '/' . $member['nickname'] . '/' . $member['realname'] . '/' . $member['mobile']);
			}
		}

		show_json(1, array('url' => referer()));
	}
}

?>
