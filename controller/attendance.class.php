<?php
if (!defined('IN')) {
	die('bad request');
}

include_once AROOT . 'controller' . DS . 'app.class.php';

class attendanceController extends appController
{
	public function index()
	{
		$filename = c('file')."group.json";
    	$json_string = file_get_contents($filename);
    	$data=json_decode($json_string,true);
		return render($data, 'web', 'card');
	}
	public function downloadexcelfile()
	{
		if(empty($_POST['group'])){
			dump('请选择组别');
			exit;
		}
		if($_POST['group']!='all'){
			$filename = c('file')."group.json";
			$json_string = file_get_contents($filename);
	    	$data=json_decode($json_string,true);
	    	$group=$data[$_POST['group']];
    	}
		$dir=c('file') . $_POST['date'] . DS;
		if(is_dir($dir)&&$_POST['date']){
			$handle=opendir($dir.".");
			//定义用于存储文件名的数组
			$array_file = array();
			while (false !== ($file = readdir($handle)))
			{
				if ($file != "." && $file != "..") {
					$array_file[] = $file=iconv("gb2312","utf-8",$file); //输出文件名
				}
			}
			closedir($handle);
			$data=[];
			foreach ($array_file as $file_key => $file_name) {
				$res = $this->read ( $dir . $file_name );
				foreach ($res as $key => $value) {
					$week=date('w',strtotime($value[5]));
					// 签到时间
					$start=$value[9];
					// 签退时间
					$end=$value[10];
					// 迟到时间
					$time=$value[13];
					// 姓名
					$member_name=$value[3];
					if($_POST['group']!='all'&&in_array($member_name, $group)){
						if (empty($start)&&empty($end)) {
							if($week!='6'&&$week!='0'){
								$data[]=$value;
								// dump($value[5].$value[3].'未打卡');
							}
						}elseif (empty($start)||empty($end)) {
							$data[]=$value;
							// dump($value[5].$value[3].'未打卡');
						}else{
							if(($week!='6'&&$week!='0')&&!empty($time)){
								$time=explode(':',$time);
								if ($time>=15) {
									$data[]=$value;
									// dump($value[5].$value[3].'迟到');
								}
							}
						}
					}elseif ($_POST['group']=='all') {
						if (empty($start)&&empty($end)) {
							if($week!='6'&&$week!='0'){
								$data[]=$value;
								// dump($value[5].$value[3].'未打卡');
							}
						}elseif (empty($start)||empty($end)) {
							$data[]=$value;
							// dump($value[5].$value[3].'未打卡');
						}else{
							if(($week!='6'&&$week!='0')&&!empty($time)){
								$time=explode(':',$time);
								if ($time>=15) {
									$data[]=$value;
									// dump($value[5].$value[3].'迟到');
								}
							}
						}
					}
				}
			}
		}elseif (!is_dir($dir)) {
			dump('该月份未上传考勤');
		}else{
			dump('请选择考勤月份');
		}
		if($data){
		 $person = my_sort($data,1,SORT_ASC,SORT_STRING);
		 export($person);
		}
	}
	public function uploadexcelfile()
	{
		if (! empty ( $_FILES ['file_stu'] ['name'] ))
		{
			for ($i=0; $i < sizeof($_FILES['file_stu']['name']); $i++) {
				$filename = $_FILES['file_stu']['name'][$i];
				$tmp_file = $_FILES ['file_stu'] ['tmp_name'][$i];
				$file_types = explode ( ".", $_FILES ['file_stu'] ['name'][$i] );
				$file_type = $file_types [count ( $file_types ) - 1];
				/*判别是不是.xls文件，判别是不是excel文件*/
				if (strtolower ( $file_type ) != "xlsx")
				{
					dump( '不是Excel文件，重新上传' );
				 }
				/*设置上传路径*/
				$savePath = AROOT . 'file' . DS . $_POST['date'] . DS;
				if(!is_dir($savePath)){
					mkdir($savePath);
				}
				/*以时间来命名上传的文件*/
				$str = date ( 'Ymdhis' ); 
				$file_name = $str . $i . "." . $file_type;
				 /*是否上传成功*/
				if (! copy ( $tmp_file, $savePath . $file_name )) 
				{
					dump ( '上传失败' );
				}else{
					dump ('上传成功');
				}
			}
		}
	}
	public function read($filename,$encode='utf-8'){
		$objReader = PHPExcel_IOFactory::createReader('Excel2007');
		$objReader->setReadDataOnly(true);
		$objPHPExcel = $objReader->load($filename);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		$highestRow = $objWorksheet->getHighestRow();
		$highestColumn = $objWorksheet->getHighestColumn();
		$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
		$excelData = array();
		for ($row = 2; $row <= $highestRow; $row++) {
			for ($col = 0; $col < $highestColumnIndex; $col++) {
				$excelData[$row][] =(string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
			}
		}
		return $excelData;
	}
}