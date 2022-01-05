<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Bonus_WxShopPage
{   
	public $set;
	public $model;

	public function __construct()
	{
        $this->model=m('plugin')->loadModel('weightbonus');
        $this->set=$this->model->getset('weightbonus');
	}

	public function main()
	{
		global $_W;
		global $_GPC;
		$openid = !empty($_W['openid']) ? $_W['openid'] : $_GPC['openid'];
        $status = intval($_GPC['status']);

        $bonus = p('weightbonus')->getBonus($openid, array('ok', 'lock', 'total'));
		show_json(1,array('status'=>$status,'bonus'=>$bonus,'set'=>$this->set));
	}

	public function get_list()
	{
        global $_W;
        global $_GPC;
        $openid = !empty($_W['openid']) ? $_W['openid'] : $_GPC['openid'];
        $member = m('member')->getMember($openid);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and `openid`=:openid and uniacid=:uniacid';
        $params = array(':openid' => $openid, ':uniacid' => $_W['uniacid']);
        $status = trim($_GPC['status']);

        if ($status == 1) {
            $condition .= ' and status=1';
        }
        else {
            if ($status == 2) {
                $condition .= ' and (status=-1 or status=0)';
            }
        }

        $billdData = pdo_fetchall('select id from ' . tablename('wx_shop_weightbonus_bill') . ' where 1 and uniacid = ' . intval($_W['uniacid']));
        $id = '';

        if (!empty($billdData)) {
            $ids = array();

            foreach ($billdData as $v) {
                $ids[] = $v['id'];
            }

            $id = implode(',', $ids);
            $list = pdo_fetchall('select *  from ' . tablename('wx_shop_weightbonus_billp') . ' where 1 ' . $condition . ' and billid in(' . $id . ') order by id desc LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize, $params);
            $total = pdo_fetchcolumn('select count(*) from ' . tablename('wx_shop_weightbonus_billp') . ' where 1 ' . $condition . ' and billid in(' . $id . ')', $params);
            show_json(1, array('total' => $total, 'list' => $list, 'pagesize' => $psize));
        }
        else {
            $list = array();
            $total = 0;
            show_json(1, array('total' => $total, 'list' => $list, 'pagesize' => $psize));
        }
	}

}

?>
