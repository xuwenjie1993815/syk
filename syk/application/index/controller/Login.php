<?php
namespace app\index\controller;
use think\View;
use think\Db;
use think\Controller;
use think\Config;
use think\Cookie;
class Login extends controller
{

    public function index()
    {
    	return view('Index/login');
    }

    public function login()
    {
    	$username = input('username');
    	$password = input('password');
    	$user = Db::table('user')->where('username',$username)->where('state','1')->find();
    	if (!$user) {
    		return array('code' => 2,'msg' => '用户名或密码错误' );die;
    	}
    	$pass = md5(md5($password).$user['pwd_hash'].Config::get('QS_pwdhash'));
    	if ($pass == $user['password']) {
            
            // if ($user['type'] == 3) {
            //     $school_info = Db::table('school')->where('admin_id',$user['id'])->find();
            //     Cookie::set('school_info',$school_info,604800);
            // }
    		Cookie::set('adminInfo',$user,604800);
    		return array('code' => 1,'msg' => '登陆成功,请稍等...' );die;
    	}else{
    		return array('code' => 2,'msg' => '用户名或密码错误' );die;
    	}
    }
}