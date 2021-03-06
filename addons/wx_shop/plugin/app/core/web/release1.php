<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Release1_WxShopPage extends PluginWebPage
{
	private $key = 'asdf734JH3464tr56GJ'; 

	public function main()
	{
		global $_W;
		$error = NULL;
		$auth = $this->model->getAuth1();

		if (is_error($auth)) {
			$error = $auth['message'];
		}
		else {
			$is_auth = (is_array($auth) ? $auth['is_auth'] : false);
			$authUrl = WX_SHOP_AUTH_WXAPP . 'index/index/xcxAuth.html?site_id=' . SITE_ID . '&uniacid=' . $_W['uniacid'];

			if ($is_auth) {
				$release = $this->model->getRelease1($auth['id']);
			}
		}

		include $this->template();
	}

	public function audit()
	{
		global $_W;
		global $_GPC;

		if (!$_W['ispost']) {
			show_json(0, '错误的请求');
		}

		$auth = $this->model->getAuth1();

		if (is_error($auth)) {
			show_json(0, $auth['message']);
		}

		$action = trim($_GPC['action']);
		if (($action != 'upload') && ($action != 'audit')) {
			show_json(0, '请求参数错误');
		}

		load()->func('communication');

		if ($action == 'upload') {
			$request = ihttp_post(WX_SHOP_AUTH_WXAPP . 'code-manage/submit-only?site_id=' . SITE_ID . '&uniacid=' . $_W['uniacid'].'&ismanage=1');
		}
		else {
			$request = ihttp_post(WX_SHOP_AUTH_WXAPP . 'code-manage/audit-only?site_id=' . SITE_ID . '&uniacid=' . $_W['uniacid'].'&ismanage=1', array());
		}

		if ($request['code'] != 200) {
			show_json(0, '信息查询失败！稍后重试(' . $request['code'] . ')');
		}

		if (empty($request['content'])) {
			show_json(0, '信息查询失败！稍后重试(nodata)');
		}

		$content = json_decode($request['content'], true);

		if (!is_array($content)) {
			show_json(0, '信息查询失败！稍后重试(dataerror)');
		}

		if ($content['status'] != 1) {
			show_json(0, $content['errmsg']);
		}

		show_json(1);
	}

	public function auth()
	{
		global $_W;
		$auth = $this->model->getAuth1();

		if (is_error($auth)) {
			$this->message($auth['message']);
		}

		$authid = $this->encrypt($auth['id'] . $this->key, $this->key);
		header('Location:' . WX_SHOP_AUTH_WXAPP . 'index/index/xcxAuth.html?site_id=' . SITE_ID . '&uniacid=' . $_W['uniacid'].'&ismanage=1');
	}

	protected function encrypt($data, $key)
	{
		$key = md5($key);
		$char = '';
		$str = '';
		$x = 0;
		$len = strlen($data);
		$l = strlen($key);
		$i = 0;

		while ($i < $len) {
			if ($x == $l) {
				$x = 0;
			}

			$char .= $key[$x];
			++$x;
			++$i;
		}

		$i = 0;

		while ($i < $len) {
			$str .= chr(ord($data[$i]) + (ord($char[$i]) % 256));
			++$i;
		}

		return base64_encode($str);
	}
}

?>
