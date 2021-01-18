<?php
namespace app\index\controller;
use think\View;
use think\Db;
use think\Controller;
use think\Config;
use think\Cookie;
class Permissions extends controller
{
	//角色管理
	public function role()
	{
		$list = Db::table('role')->where('state','1')->select();
		$this->assign('list',$list);
    	return $this->fetch();
	}

	//查看拥有权限用户
	public function role_user()
	{
		$role_id = input('role_id');
		$user = Db::table('user_role')->alias('r')->field('u.username,u.id as user_id,u.state')->join('user u','u.id = r.user_id')->where('r.role_id',$role_id)->where('u.state','neq','3')->select();
		$this->assign('user',$user);
    	return $this->fetch();
	}

	//管理员用户状态管理
	public function user_state()
	{
		$id = $_POST['id'];
		$state = $_POST['state'];
		$res = Db::table('user')->where('id',$id)->update($_POST);
		if ($res > 0) {
			return array('code' => '1');
		}else{
			return array('code' => '2','msg' => '操作失败,请稍后再试');
		}
	}

	//查看角色拥有的权限
	public function role_authority()
	{
		$role_id = input('role_id');
		$describe = Db::table('role_authority')->alias('r')->field('a.*')->join('authority a','a.id = r.authority_id')->where('r.role_id',$role_id)->where('a.state','neq','3')->select();
		$this->assign('describe',$describe);
    	return $this->fetch();
	}

	//权限状态管理
	public function authority_state()
	{
		$id = $_POST['id'];
		$state = $_POST['state'];
		$res = Db::table('authority')->where('id',$id)->update($_POST);
		if ($res > 0) {
			return array('code' => '1');
		}else{
			return array('code' => '2','msg' => '操作失败,请稍后再试');
		}
	}

	//管理员管理
	public function user_list()
	{
		$user_list = Db::table('user')->alias('u')->join('user_role r','r.user_id = u.id')->join('role le','le.id = r.role_id')->where('u.state','neq','3')->where('le.state','1')->field('u.username,u.id,u.state,r.role_id,le.role')->select();
		
		$this->assign('user_list',$user_list);
    	return $this->fetch();
	}

	//角色编辑
	public function role_edit()
	{
		if ($_POST) {
			//通过已选的三级功能，筛选出对应的父级菜单
			$v3 = explode(',', $_POST['v3']);
			$v3_parent = array();
			$v2_parent = array();
			foreach ($v3 as $key => $value) {
				$v3_parent_id = Db::table('authority')->where('id',$value)->value('parent_id');
				if ($v3_parent_id) {
					$v3_parent[] = $v3_parent_id;
					$v2_parent_id = Db::table('authority')->where('id',$v3_parent_id)->value('parent_id');
					$v2_parent[] = $v2_parent_id;
					
				}
			}
			$v3_parent = array_unique($v3_parent);
			$v2_parent = array_unique($v2_parent);
			//通过二级菜单筛选一级菜单
			$v2 = explode(',', $_POST['v2']);
			$v2_parent_la = array();
			foreach ($v2 as $key => $value) {
				$v2_parent_id = Db::table('authority')->where('id',$value)->value('parent_id');
				$v2_parent_la[] = $v2_parent_id;
			}
			$v2_parent_la = array_unique($v2_parent_la);


			//整理出主菜单
			$v1 = explode(',', $_POST['v1']);
			if ($v2_parent[0]) {
				$v1 = array_unique(array_merge($v2_parent,$v1));
			}
			if ($v2_parent_la[0]) {
				$v1 = array_unique(array_merge($v1,$v2_parent_la));
			}
			//整理出一级菜单
			$v2 = explode(',', $_POST['v2']);
			if ($v3_parent[0] != '') {
				$v2 = array_unique(array_merge($v3_parent,explode(',', $_POST['v2'])));
			}

			//判断，如果只选择了一个主菜单未选择对应的一级菜单或二级菜单，则默认全选
			// 第一步先通过主菜单找出对应的一级，二级菜单
			// 第二步检查整理好的菜单中是否包含对应的一二级菜单，如都没包含，则视为此主菜单需要全选
			$arr_1 = array();
			$arr_2 = array();
			foreach ($v1 as $key => $value) {
				$authority_1_count = 0;
				$authority_2_count = 0;
				$authority_1 = Db::table('authority')->where('parent_id',$value)->where('state','1')->field('id')->select();
				foreach ($authority_1 as $k => $v) {
					//先判断一级菜单是否包含
					if (in_array($v['id'], $v2)) {
						$authority_1_count += 1;
					}
					//通过一级菜单找出二级菜单
					$authority_2 = Db::table('authority')->where('parent_id',$v['id'])->where('state','1')->field('id')->select();
					//判断二级菜单是否包含
					foreach ($authority_2 as $k_2 => $v_2) {
						if (in_array($v_2['id'], $v3)) {
							$authority_2_count += 1;
						}
					}				
				}
				$arr_1[$value] = $authority_1_count;
				$arr_2[$value] = $authority_2_count;
			}

			//检查是否为全选
			$authority_v1 = array();
			$authority_v2 = array();

			foreach ($arr_1 as $key => $value) {
				//筛选出哪些主菜单为全选
				if ($arr_2[$key] < 1 AND $value < 1) {
					$authority_v1[] = $key;
					//查出全选菜单的二级菜单
					$authority_v1_info = Db::table('authority')->where('parent_id',$key)->where('state','1')->field('id')->select();
					if ($authority_v1_info) {
						foreach ($authority_v1_info as $k => $v) {
							$authority_v2[] = $v['id'];
							//查出全选菜单的三级功能菜单
							$authority_v2_info = Db::table('authority')->where('parent_id',$v['id'])->where('state','1')->field('id')->select();
							if ($authority_v2_info) {
								foreach ($authority_v2_info as $k2 => $v22) {
									$authority_v3[] = $v22['id'];
								}
							}
						}
					}
				}
			}
			
			//合并去重
			if ($authority_v1) {
				$v1 = array_unique(array_merge($authority_v1,$v1));
			}
			if ($authority_v2) {
				$v2 = array_unique(array_merge($authority_v2,$v2));
			}
			if ($authority_v3) {
			$v3 = array_unique(array_merge($authority_v3,$v3));
			}
			
			//删除角色所有权限
			Db::table('role_authority')->where('role_id',input('id'))->delete();

			//为角色添加主菜单权限
			if ($v1[0] != '') {
				foreach ($v1 as $key => $value) {
					Db::table('role_authority')->insert(['role_id' => input('id'), 'authority_id' => $value]);
				}
			}
			
			//为角色添加一级菜单权限
			if ($v2[0] != '') {
				foreach ($v2 as $key => $value) {
					Db::table('role_authority')->insert(['role_id' => input('id'), 'authority_id' => $value]);
				}
			}

			//为角色添加功能权限
			if ($v3[0] != '') {
				foreach ($v3 as $key => $value) {
					Db::table('role_authority')->insert(['role_id' => input('id'), 'authority_id' => $value]);
				}
			}
			$role_info['role'] = $_POST['roleName'];
			$role_info['remarks'] = $_POST['remarks'];
			$role_info['update_at'] = date('Y-m-d H:i:s',time());
			Db::table('role')->where('id',input('id'))->update($role_info);
			return array('code' => '1');
		}
		//查询角色各个数据用于前端展示 todo
		$role_id = input('id');
		//获取角色拥有的权限
		$role_authority = Db::table('role_authority')->alias('r')->field('a.id')->join('authority a','a.id = r.authority_id')->where('r.role_id',$role_id)->where('a.state','neq','3')->select();
		$role_authority_have_list = array();
		foreach ($role_authority as $key => $value) {
			$role_authority_have_list[] = $value['id'];
		}
		
		$role = Db::table('role')->where('id',$role_id)->find();
		//获取权限节点表
		$authority_list = $this->getAuthorityList();
		$this->assign('authority_list',$authority_list);
		$this->assign('role_authority_have_list',$role_authority_have_list);
		$this->assign('role',$role);
    	return $this->fetch();
	}

	//权限管理
	public function authority()
	{
		//主菜单列表
		$list = Db::table('authority')->where('state','1')->where('type','0')->select();
		$this->assign('list',$list);
    	return $this->fetch();
	}

	//权限子节点列表
	public function authority_son_list()
	{
		$id = input('id');
		//主菜单列表
		$list = Db::table('authority')->where('parent_id',$id)->where('state','neq','3')->select();
		$this->assign('list',$list);
    	return $this->fetch();
	}

	//获取完整的权限节点列表
	public function getAuthorityList()
	{
		//主菜单权限列表
		$au1 = Db::table('authority')->where('state','1')->where('type','0')->select();
		foreach ($au1 as $key => $value) {
			$au2 = Db::table('authority')->where('state','1')->where('parent_id',$value['id'])->select();
			$au1[$key]['au2'] = $au2;
			foreach ($au2 as $k => $v) {
				$au3 = Db::table('authority')->where('state','1')->where('parent_id',$v['id'])->select();
				$au1[$key]['au2'][$k]['au3'] = $au3;
			}
		}
		return $au1;
	}

	//新增管理员
	public function create_user()
	{
		if ($_POST) {
			//检查用户是否重复
			$user_info = Db::table('user')->where('username',$_POST['username'])->where('state','1')->find();
			if ($user_info) {
				return array('code' => '2','msg' => '用户名已存在');
			}
			
			$data['pwd_hash'] = rand(1000,9999);
			$data['username'] = $_POST['username'];
			$data['password'] = md5(md5($_POST['password']).$data['pwd_hash'].Config::get('QS_pwdhash'));
			$data['type'] = $_POST['role_id'];
			$user_id = Db::table('user')->insertGetId($data);
			//为user添加角色
			$data_role['role_id'] = $_POST['role_id'];
			$data_role['user_id'] = $user_id;
			Db::table('user_role')->insert($data_role);
			return array('code' => '1');
		}
		//获取角色列表
		$role_list = Db::table('role')->where('state','1')->select();
		$this->assign('role_list',$role_list);
    	return $this->fetch();
	}

}