<?php
namespace app\index\controller;
use think\View;
use think\Db;
use think\Controller;
use think\Config;
use think\Cookie;
use app\index\controller\Base;
class File extends Base
{
	public function syj_file()
	{
		return $this->fetch();
	}

	public function syj_file_data()
	{
		if( request()->isAjax() ){
            //接收所有传过来的post数据
            $datatables = request()->post();
            //得到排序的方式
            $order = $datatables['order'][0]['dir'];
            //得到排序字段的下标
            $order_column = $datatables['order'][0]['column'];
            //根据排序字段的下标得到排序字段
            $order_field = $datatables['columns'][$order_column]['data'];
            //得到limit参数
            $limit_start = $datatables['start'];
            $limit_length = $datatables['length'];
            //得到搜索的关键词
            $search = $datatables['search']['value'];
            $where_like = 'title';
            //从数据库取出结果
            //如果有搜索行为，则按照关键词查询数据
            
            if ($limit_length == '-1') {
                $limit_length = '9999999999999999999999';
            }
            if ($search) {
                $data = Db::table('syj_file')
                        ->order("$order_field $order")
                        ->limit($limit_start,$limit_length)
                        ->where('state','1')
                        ->where($where_like,'LIKE',"%$search%")
                        ->select();
                $data_keyword = Db::table('syj_file')
                                ->where('state','1')
                        ->where($where_like,'LIKE',"%$search%")
                                ->select();
                $cnt = count($data_keyword);   //获取满足关键词的总记录数

            }else{
                //没有关键词，则查询全部
                $data = Db::table('syj_file')
                ->where('state','1')
                ->order("create_time $order")
                ->limit($limit_start,$limit_length)
                ->select();
                $cnt = Db::table('syj_file')
                        ->where('state','1')->count(); // 数据总数
            }

            if($data) {
                $data = collection($data)->toArray();
            }  
            foreach ($data as $key => $value) {
	            $file = $data[0]['url'];
				$data[$key]['url_down'] = explode('/', $file)[1];
            }
			// var_dump($file);
			// die;
            $info['draw'] = request()->post('draw');
            $info['recordsTotal'] = count($data);
            $info['recordsFiltered'] = $cnt;
            $info['data'] = $data;
            //转为json返回
            return json( $info );
        }
	}

	//修改文件状态
	public function saveFile()
	{
		$where['id'] = $_POST['id'];
		$data['state'] = $_POST['state'];
		Db::table('syj_file')->where($where)->update($data);
		return array('code' => '1');
	}
}