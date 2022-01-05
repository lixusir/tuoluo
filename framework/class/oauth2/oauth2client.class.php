<?php

/**
 */
abstract class OAuth2Client {
	protected $ak;
	protected $sk;
	protected $login_type;
	protected $stateParam = array(
		'state' => '',
		'from' => '',
		'mode' => ''
	);

	public function __construct($ak, $sk) {
		$this->ak = $ak;
		$this->sk = $sk;
	}

	public function stateParam() {
		global $_W;
		$this->stateParam['state'] = $_W['token'];
		if (!empty($_W['user'])) {
			$this->stateParam['mode'] = 'bind';
		} else {
			$this->stateParam['mode'] = 'login';
		}
		return base64_encode(http_build_query($this->stateParam, '', '&'));
	}

	public function getLoginType($login_type) {
		$this->login_type = $login_type;
	}

	public static function supportLoginType(){
		return array('system', 'qq', 'wechat', 'mobile');
	}

	public static function supportThirdLoginType() {
		return array('qq', 'wechat');
	}

	public static function supportThirdMode() {
		return array('bind', 'login');
	}

	public static function supportParams($state) {
		$state = urldecode($state);
		$param = array();
		if (!empty($state)) {
			$state = base64_decode($state);
			parse_str($state, $third_param);
			$modes = self::supportThirdMode();
			$types = self::supportThirdLoginType();
			if (in_array($third_param['mode'],$modes) && in_array($third_param['from'],$types)) {
				return $third_param;
			}
		}
		return $param;
	}

	public static function create($type, $appid = '', $appsecret = '') {
		$types = self::supportLoginType();
		if (in_array($type, $types)) {
			load()->classs('oauth2/' . $type);
			$type_name = ucfirst($type);
			$obj = new $type_name($appid, $appsecret);
			$obj->getLoginType($type);			
			return $obj;
		}
		return null;
	}

	abstract function showLoginUrl($calback_url = '');

	abstract function user();
	
	abstract function login();

	abstract function bind();
	abstract function unbind();
	
	abstract function register();

	public function user_register($register) {
		global $_W;
		load()->model('user');
		if (is_error($register)) {
			return $register;
		}
		$member = $register['member'];
		$profile = $register['profile'];

		$member['status'] = !empty($_W['setting']['register']['verify']) ? 1 : 2;
		$member['remark'] = '';
		$member['groupid'] = intval($_W['setting']['register']['groupid']);
		if (empty($member['groupid'])) {
			$member['groupid'] = pdo_fetchcolumn('SELECT id FROM '.tablename('users_group').' ORDER BY id ASC LIMIT 1');
			$member['groupid'] = intval($member['groupid']);
		}
		$group = user_group_detail_info($member['groupid']);
		$timelimit = intval($group['timelimit']);
		if($timelimit > 0) {
			$member['endtime'] = strtotime($timelimit . ' days');
		}
		$member['starttime'] = TIMESTAMP;
		if (!empty($owner_uid)) {
			$member['owner_uid'] = pdo_getcolumn('users', array('uid' => $owner_uid, 'founder_groupid' => ACCOUNT_MANAGE_GROUP_VICE_FOUNDER), 'uid');
		}

		$user_id = user_register($member);
		if (in_array($member['register_type'], array(USER_REGISTER_TYPE_QQ, USER_REGISTER_TYPE_WECHAT, USER_REGISTER_TYPE_MOBILE))) {
			pdo_update('users', array('username' => $member['username'] . $user_id . rand(100,999)), array('uid' => $user_id));
		}
		if($user_id > 0) {
			unset($member['password']);
			$member['uid'] = $user_id;
			if (!empty($profile)) {
				$profile['uid'] = $user_id;
				$profile['createtime'] = TIMESTAMP;
				pdo_insert('users_profile', $profile);
			}
			if (in_array($member['register_type'], array(USER_REGISTER_TYPE_QQ, USER_REGISTER_TYPE_WECHAT, USER_REGISTER_TYPE_MOBILE))) {
				pdo_insert('users_bind', array('uid' => $user_id, 'bind_sign' => $member['openid'], 'third_type' => $member['register_type'], 'third_nickname' => $member['username']));
			}

			if(!empty($member['username'])){
				self::account_name($member['username'], $user_id);
			}
			
			if (in_array($member['register_type'], array(USER_REGISTER_TYPE_QQ, USER_REGISTER_TYPE_WECHAT))) {
				return $user_id;
			}
			return error(0, '注册成功'.(!empty($_W['setting']['register']['verify']) ? '，请等待管理员审核！' : '，请重新登录！'));
		}

		return error(-1, '增加用户失败，请稍候重试或联系网站管理员解决！');
	}

/**
 * 自动注册公众号
 * @param  [type] $account_name [公众号名称]
 * @param  [type] $user_id      [用户id]
 * @return [type]               [description]
 */
	public function account_name($account_name, $user_id){
		global $_W;
		load()->func('file');
		load()->model('module');
		load()->model('user');
		load()->model('account');
		load()->classs('weixin.platform');
		$update = array();
		$update['name'] = "web_".$account_name;
		if (empty($uniacid)) {
			// 添加微信公众号
			$name = trim($account_name);
			$description = "";
			$data = array(
				'name' => "web_".$name,
				'description' => $description,
				'title_initial' => get_first_pinyin($name),
				'groupid' => 0,
			);
			$account_table = table('account');
			$account_table->searchWithTitle($name);
			$account_table->searchWithType(ACCOUNT_TYPE_OFFCIAL_NORMAL);
			$check_uniacname = $account_table->searchAccountList();
			if (!empty($check_uniacname)) {
				itoast('该公众号名称已经存在', '', '');
			}
			if (!pdo_insert('uni_account', $data)) {
				itoast('添加公众号失败', '', '');
			}
			$uniacid = pdo_insertid();

			// 添加公众号样式
			$template = pdo_fetch('SELECT id,title FROM ' . tablename('site_templates') . " WHERE name = 'default'");
			$styles['uniacid'] = $uniacid;
			$styles['templateid'] = $template['id'];
			$styles['name'] = $template['title'] . '_' . random(4);
			pdo_insert('site_styles', $styles);
			$styleid = pdo_insertid();

			// 添加公众号站点信息
			$multi['uniacid'] = $uniacid;
			$multi['title'] = $data['name'];
			$multi['styleid'] = $styleid;
			pdo_insert('site_multi', $multi);
			$multi_id = pdo_insertid();

			// 添加公众号信息
			$unisettings['creditnames'] = array('credit1' => array('title' => '积分', 'enabled' => 1), 'credit2' => array('title' => '余额', 'enabled' => 1));
			$unisettings['creditnames'] = iserializer($unisettings['creditnames']);
			$unisettings['creditbehaviors'] = array('activity' => 'credit1', 'currency' => 'credit2');
			$unisettings['creditbehaviors'] = iserializer($unisettings['creditbehaviors']);
			$unisettings['uniacid'] = $uniacid;
			$unisettings['default_site'] = $multi_id;
			$unisettings['sync'] = iserializer(array('switch' => 0, 'acid' => ''));
			pdo_insert('uni_settings', $unisettings);

			// 添加会员组
			pdo_insert('mc_groups', array('uniacid' => $uniacid, 'title' => '默认会员组', 'isdefault' => 1));
			$fields = pdo_getall('profile_fields');
			foreach($fields as $field) {
				$data = array(
					'uniacid' => $uniacid,
					'fieldid' => $field['id'],
					'title' => $field['title'],
					'available' => $field['available'],
					'displayorder' => $field['displayorder'],
				);
				pdo_insert('mc_member_fields', $data);
			}
		}

		$update['account'] = trim($_GPC['account']);
		$update['original'] = trim($_GPC['original']);
		$update['level'] = intval("1");
		$update['key'] = trim($_GPC['key']);
		$update['secret'] = trim($_GPC['secret']);
		$update['type'] = ACCOUNT_TYPE_OFFCIAL_NORMAL;
		$update['encodingaeskey'] = trim($_GPC['encodingaeskey']);

		if (user_is_vice_founder()) {
			uni_user_account_role($uniacid, $user_id, ACCOUNT_MANAGE_NAME_VICE_FOUNDER);
		}		
		if (empty($acid)) {
			$acid = account_create($uniacid, $update);
			if(is_error($acid)) {
				itoast('添加公众号信息失败', url('account/post-step/', array('uniacid' => $uniacid, 'step' => 2)), 'error');
			}
			pdo_update('uni_account', array('default_acid' => $acid), array('uniacid' => $uniacid));

			if (empty($_W['isfounder'])) {
				uni_user_account_role($uniacid, $user_id, ACCOUNT_MANAGE_NAME_OWNER);
			}
		
			if (!empty($_W['user']['owner_uid'])) {
				uni_user_account_role($uniacid, $_W['user']['owner_uid'], ACCOUNT_MANAGE_NAME_VICE_FOUNDER);
			}
		} else {
			pdo_update('account', array('type' => ACCOUNT_TYPE_OFFCIAL_NORMAL, 'hash' => ''), array('acid' => $acid, 'uniacid' => $uniacid));
			unset($update['type']);
			pdo_update('account_wechats', $update, array('acid' => $acid, 'uniacid' => $uniacid));
		}
		if(parse_path($_GPC['qrcode'])) {
			copy($_GPC['qrcode'], IA_ROOT . '/attachment/qrcode_'.$acid.'.jpg');
		}
		if(parse_path($_GPC['headimg'])) {
			copy($_GPC['headimg'], IA_ROOT . '/attachment/headimg_'.$acid.'.jpg');
		}
		$_W['uniacid'] = $uniacid;
		$oauth = uni_setting($uniacid, array('oauth'));
	}
}