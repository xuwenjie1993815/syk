<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);

use think\Db;
use think\Config;
// 应用公共文件
//消息通知
function sendmsg($data,$type,$adminInfo,$id)
{
	//新增通知给管理员
	$announcement_id = Db::table('announcement')->insertGetId($data);
	$admin_list = '';
	//新增通知到用户表
	switch ($type) {
		//指定用户
		case '0':
			$admin_list = Db::table('user')->where('id',$id)->field('id')->select();
			break;
		//管理员
		case '1':
			$admin_list = Db::table('user')->where('type',$type)->field('id')->select();
			break;
		//街道
		case '2':
			$admin_list = Db::table('user')->where('type',$type)->field('id')->select();
			break;
		default:
			# code...
			break;
	}
	// $admin_list = Db::table('admin')->where('type',$type)->field('id')->select();
	$str = '';
	foreach ($admin_list as $key => $value) {
		if (count($admin_list) == $key+1) {
			$str .= "'".$value['id']."'";
		}else{
			$str .= "'".$value['id']."'".',';
		}
	}
	$announcement_user['user_arr_id'] = $str;
	$announcement_user['announcement_id'] = $announcement_id;
	Db::table('announcement_user')->insert($announcement_user);
}

//分镇街获取待处理的报错数量
function getReportCountByState()
{

	$map = array('street_id' => array('in', Config::get('street_id')));
	$report = Db::table('report')->field('street_id, count(*)')->where($map)->where('state','1')->group('street_id')->select();
	$res = array();
    foreach ($report as $key => $value) {
        $res[$value['street_id']] = $value['count(*)'];
    }
    $res_end = array();
    foreach (Config::get('street_id') as $key => $value) {
    	if (!$res[$value]) {
    		$res_end[$value] = 0;
    	}else{
    		$res_end[$value] = $res[$value];
    	}
    }
	return $res_end;
}
