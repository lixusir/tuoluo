<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Uploader_WxShopPage extends MobilePage
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		load()->func('file');

		$token = trim($_GPC['token']);
		$uid = m('game')->getuid($token);

		$field = $_GPC['file'];
		if (!(empty($_FILES[$field]['name']))) 
		{
			if (is_array($_FILES[$field]['name'])) 
			{
				$files = array();
				foreach ($_FILES[$field]['name'] as $key => $name ) 
				{
					if (strrchr($name, '.') === false) 
					{
						$name = $name . '.jpg';
					}
					$file = array('name' => $name, 'type' => $_FILES[$field]['type'][$key], 'tmp_name' => $_FILES[$field]['tmp_name'][$key], 'error' => $_FILES[$field]['error'][$key], 'size' => $_FILES[$field]['size'][$key]);
					$files[] = $this->upload($file);
				}
				$ret = array('status' => 1, 'msg' => $files);
				

				pdo_update('wx_shop_member',array('avatar'=>$files['url']),array('id'=>$uid));


				show_json_w(1,$files['url'],'成功');
				// exit(json_encode($ret));
			}
			else 
			{
				if (strrchr($_FILES[$field]['name'], '.') === false) 
				{
					$_FILES[$field]['name'] = $_FILES[$field]['name'] . '.jpg';
				}
				$result = $this->upload($_FILES[$field]);


				if($result['status'] == 1) {
					
					pdo_update('wx_shop_member',array('avatar'=>$result['url']),array('id'=>$uid));

				}

				show_json_w($result['status'],$result['url'],$result['msg']);



				// exit(json_encode($result));
			}
		}
		else 
		{
			$result['msg'] = '请选择要上传的图片！';
			show_json_w(-1,null,$result['msg']);

			// exit(json_encode($result));
		}
	}
	protected function upload($uploadfile) 
	{
		global $_W;
		global $_GPC;
		$result['status'] = -1;
		
		$result['url'] = null;
		if ($uploadfile['error'] != 0) 
		{
			$result['msg'] = '上传失败，请重试！';
			return $result;
		}
		load()->func('file');
		$path = '/images/wx_shop/' . $_W['uniacid'];
		if (!(is_dir(ATTACHMENT_ROOT . $path))) 
		{
			mkdirs(ATTACHMENT_ROOT . $path);
		}
		$_W['uploadsetting'] = array();
		$_W['uploadsetting']['image']['folder'] = $path;
		$_W['uploadsetting']['image']['extentions'] = $_W['config']['upload']['image']['extentions'];
		$_W['uploadsetting']['image']['limit'] = $_W['config']['upload']['image']['limit'];
		// echo '<pre>';
		//     print_r($_W['uploadsetting']['image']);
		// echo '</pre>';
		$file = file_upload($uploadfile, 'image');
		// echo '<pre>';
		//     print_r($file);
		// echo '</pre>';
		// echo '<pre>';
		//     print_r($uploadfile);
		// echo '</pre>';
		if (is_error($file)) 
		{
			$result['msg'] = $file['message'];
			return $result;
		}
		if (function_exists('file_remote_upload')) 
		{
			$remote = file_remote_upload($file['path']);
			if (is_error($remote)) 
			{
				$result['msg'] = $remote['message'];
				return $result;
			}
		}
		$result['status'] = 1;
		$result['url'] = $file['url'];
		$result['error'] = 0;
		$result['filename'] = $file['path'];
		$result['url'] = trim($_W['attachurl'] . $result['filename']);
		pdo_insert('core_attachment', array('uniacid' => $_W['uniacid'], 'uid' => $_W['member']['uid'], 'filename' => $uploadfile['name'], 'attachment' => $result['filename'], 'type' => 1, 'createtime' => TIMESTAMP));
		return $result;
	}
	public function remove() 
	{
		global $_W;
		global $_GPC;
		load()->func('file');
		$file = $_GPC['file'];
		file_delete($file);
		exit(json_encode(array('status' => 1)));
	}
}
?>