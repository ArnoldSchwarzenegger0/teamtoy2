<?php
if (!defined('IN')) {
	die('bad request');
}

include_once AROOT . 'controller' . DS . 'app.class.php';
// include_once AROOT . 'lib' . DS . 'QyWechat.class.php';
// include_once AROOT . 'lib' . DS . 'FlieCache.class.php';
// include_once AROOT . 'lib' . DS . 'cache.class.php';

class testController extends appController
{
	public function test()
	{
		$str='未打卡考勤特殊说明.xlsx';
		$str1='HYXK-报-加班申请表.xlsx';
		dump("测试中。。。");
		dump($str.'长度'.mb_strlen($str));
		$s=mb_substr($str, 0,mb_strlen($str)-5);
		dump(mb_substr($str, -5));
		dump(mb_substr($str, 0,mb_strlen($str)-5).'v'.date('y.m.d',time()).mb_substr($str, -5));
		dump(getMonthFirstDay());
	}
	public function index()
	{
		$date='2001/10/1';
		$sql="SELECT ncl.start_date,u.eid,u.`name`,ncl.centent FROM	no_clock_log AS ncl INNER JOIN `user` AS u ON ncl.member_id = u.id WHERE ncl.start_date > ".getMonthFirstDay($date)." AND ncl.start_date < ".getMonthLastDay($date)." AND ncl.`status` = 1 ORDER BY ncl.member_id ASC,ncl.start_date ASC";
		$res=get_data($sql);
		// 判断当月是否有未打卡记录
		// if(empty($res)){
		// 	// $tip="本月无未打卡记录！";
		// 	// dump("本月无未打卡记录！");
		// 	// $tip=base64_encode("本月无未打卡记录！");
		// 	// header("Location:?c=test&a=index&tip=".$tip);
		// 	// exit;
		// }
		$arr=[];
		// 格式化时间
		if(!empty($res)){
			foreach ($res as $key => $value) {
				$value['start_date']=date("Y/m/d",(int)$value['start_date']);
				$arr[]=$value;
			}
		}
		$data['title'] = $data['top_title'] = 'TODO';
		$tpl=[];
		// $tip=z(t(v('tip')));s
		$dir=iconv("utf-8","gb2312",AROOT . 'template' . DS);
		if ($headle=opendir($dir)){
			while ($file=readdir($headle)){
				$file=iconv("gb2312","utf-8",$file);
				if ($file!='.'&&$file!='..') {
					$tpl[]=$file;
				}
			}
			closedir($headle);
		}
		$data['tpl']=$tpl;
		$data['arr']=$arr;
		// if(empty($arr)) $data['tip']=$tip;
		return render($data, 'web', 'card');
	}

public function Derived_Excel()
	{
		$template_name=z(t(v('template')));
		$date=z(t(v('date')));
		$flg=z(t(v('flg')));
		$file_name='';
		if (PHP_SAPI == 'cli')
			die('This example should only be run from a Web Browser');
		if (!file_exists(iconv('UTF-8','GB2312',AROOT . 'template' . DS . $template_name))) {
			exit($template_name.'文件不存在！');
		}
		$objPHPExcel = PHPExcel_IOFactory::load(iconv('UTF-8','GB2312',AROOT . 'template' . DS . $template_name));
		// Add some data
		if (strstr($template_name,'未打卡')) {
			// 查询未打卡说明
			// $sql="SELECT start_date,member_id,`name`,centent FROM no_clock_log INNER JOIN `user` ON member_id =eid WHERE start_date>".getMonthFirstDay()." AND start_date <".getMonthLastDay();
			$sql="SELECT ncl.start_date,u.eid,u.`name`,ncl.centent FROM	no_clock_log AS ncl INNER JOIN `user` AS u ON ncl.member_id = u.id WHERE ncl.start_date > ".getMonthFirstDay($date)." AND ncl.start_date < ".getMonthLastDay($date)." AND ncl.`status` = 1 ORDER BY ncl.member_id ASC,ncl.start_date ASC";
			$res=get_data($sql);
			// 判断当月是否有未打卡记录
			// if(empty($res)){
			// 	$tip=base64_encode("本月无未打卡记录！");
			// 	header("Location:?c=test&a=index&tip=".$tip);
			// 	exit;
			// }
			$arr=[];
			// 格式化时间
			if(!empty($res)){
				foreach ($res as $key => $value) {
					$value['start_date']=date("Y/m/d",(int)$value['start_date']);
					$arr[]=$value;
				}
			}
			if($flg){
				$info=json_encode($arr);
				ajax_echo($info);
				die();
			}
			$step=sizeof($arr)+2;
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('A2'), 'A2:A'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('B2'), 'B2:B'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('C2'), 'C2:C'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('D2'), 'D2:D'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('E2'), 'E2:E'.$step );
			for ($i=0; $i < sizeof($arr); $i++) {
				$objPHPExcel->getActiveSheet()
							->setCellValue('A'.($i+2), $i+1)
							->setCellValue('B'.($i+2), $arr[$i][start_date])
							->setCellValue('C'.($i+2), $arr[$i][eid])
							->setCellValue('D'.($i+2), $arr[$i][name])
							->setCellValue('E'.($i+2), $arr[$i][centent]);
			}
			$file_name=mb_substr($template_name, 0,mb_strlen($template_name)-5).'v'.date('y.m.d',time()).mb_substr($template_name, -5);
		}
		if (strstr($template_name, '加班')) {
			// 查询加班申请
			// $sql="SELECT member_id,`name`,end_date AS date,start_date,end_date,overtime_hours,overtime_type,centent FROM overtime_request_log INNER JOIN `user` ON member_id = eid WHERE start_date>".getMonthFirstDay()." AND end_date <".getMonthLastDay();
			$sql="SELECT u.eid, u.`name`, orl.end_date AS date, orl.start_date,	orl.end_date, orl.overtime_hours, orl.overtime_type, orl.centent FROM overtime_request_log AS orl INNER JOIN `user` AS u ON orl.member_id = u.id WHERE orl.start_date > ".getMonthFirstDay($date)." AND orl.end_date < ".getMonthLastDay($date)." AND orl.`status` = 1 ORDER BY orl.member_id ASC,orl.start_date ASC";
			$res=get_data($sql);
			$arr=[];
			// 判断当月是否有加班记录
			if(!empty($res)){
				// $tip=base64_encode("本月无加班记录！");
				// header("Location:?c=test&a=index&tip=".$tip);
				// exit;
				$sql1="SELECT Count( DISTINCT member_id) AS total,start_date,end_date FROM overtime_request_log WHERE start_date >" . getMonthFirstDay() . " AND end_date <" . getMonthLastDay();
				$res1=get_line($sql1);
				$arr=[];
				foreach ($res as $key => $value) {
					$value['date']=date("Y/m/d",(int)$value['date']);
					$value['start_date']=date("H:i:s",(int)$value['start_date']);
					$value['end_date']=date("H:i:s",(int)$value['end_date']);
					switch ($value['overtime_type'])
					{
						case 1:
							$value['overtime_type']='晚加班';
							break;
						case 2:
							$value['overtime_type']='通宵加班';
							break;
						case 3:
							$value['overtime_type']='休息日加班';
							break;
						default:
							$value['overtime_type']='节假日加班';
					}
					$value['local']='公司外';
					$value['programid']='A';
					$arr[]=$value;
				}
			}
			if($flg){
				$info=json_encode($arr);
				ajax_echo($info);
				die();
			}
			$step=sizeof($arr)+5;
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('A6'), 'A6:A'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('B6'), 'B6:B'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('C6'), 'C6:C'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('D6'), 'D6:D'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('E6'), 'E6:E'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('F6'), 'F6:F'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('G6'), 'G6:G'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('H6'), 'H6:H'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('I6'), 'I6:I'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('J6'), 'J6:J'.$step );
			$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('K6'), 'K6:K'.$step );
			for ($i=0; $i < sizeof($arr); $i++) {
				// 设置行高
				$objPHPExcel->getActiveSheet()->getRowDimension(6+$i)->setRowHeight(20.25);
				$objPHPExcel->getActiveSheet()->mergeCells('j'.(6+$i).':k'.(6+$i));
				$objPHPExcel->getActiveSheet()
							->setCellValue('A'.($i+6), $arr[$i][eid])
							->setCellValue('B'.($i+6), $arr[$i][name])
							->setCellValue('C'.($i+6), $arr[$i][date])
							->setCellValue('D'.($i+6), $arr[$i][start_date])
							->setCellValue('E'.($i+6), $arr[$i][end_date])
							->setCellValue('F'.($i+6), $arr[$i][overtime_hours])
							->setCellValue('G'.($i+6), $arr[$i][overtime_type])
							->setCellValue('H'.($i+6), $arr[$i][local])
							->setCellValue('I'.($i+6), $arr[$i][programid])
							->setCellValue('J'.($i+6), $arr[$i][centent]);
			}
			$objPHPExcel->getActiveSheet()->getStyle('A'.($i+6).':K'.($i+6))->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A4', $res1['total']);
			$file_name=mb_substr($template_name, 0,mb_strlen($template_name)-5).'v'.date('y.m.d',time()).mb_substr($template_name, -5);
		}

		// Redirect output to a client’s web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.$file_name.'"');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

		// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}




	// public function Derived_Excel()
	// {
	// 	$template_name=z(t(v('template')));
	// 	$file_name='';
	// 	if (PHP_SAPI == 'cli')
	// 		die('This example should only be run from a Web Browser');
	// 	if (!file_exists(iconv('UTF-8','GB2312',AROOT . 'template' . DS . $template_name))) {
	// 		exit($template_name.'文件不存在！');
	// 	}
	// 	$objPHPExcel = PHPExcel_IOFactory::load(iconv('UTF-8','GB2312',AROOT . 'template' . DS . $template_name));
	// 	// Add some data
	// 	if (strstr($template_name,'未打卡')) {
	// 		// 查询未打卡说明
	// 		$sql="SELECT start_date,member_id,`name`,centent FROM no_clock_log INNER JOIN `user` ON member_id =eid WHERE start_date>".getMonthFirstDay()." AND start_date <".getMonthLastDay();
	// 		$res=get_data($sql);
	// 		$arr=[];
	// 		// 格式化时间
	// 		foreach ($res as $key => $value) {
	// 			$value['start_date']=date("Y/m/d",(int)$value['start_date']);
	// 			$arr[]=$value;
	// 		}
	// 		$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('B2:E2'), 'B2:E13' );
	// 		$objPHPExcel->getActiveSheet()->fromArray($arr, NULL, 'B2');
	// 		$file_name=mb_substr($template_name, 0,mb_strlen($template_name)-5).'v'.date('y.m.d',time()).mb_substr($template_name, -5);
	// 	}
	// 	if (strstr($template_name, '加班')) {
	// 		// 查询加班申请
	// 		$sql="SELECT member_id,`name`,end_date AS date,start_date,end_date,overtime_hours,overtime_type,local,programid,centent FROM overtime_request_log INNER JOIN `user` ON member_id = eid WHERE start_date>".getMonthFirstDay()." AND end_date <".getMonthLastDay();
	// 		$res=get_data($sql);
	// 		$sql1="SELECT Count( DISTINCT member_id) AS total,start_date,end_date FROM overtime_request_log WHERE start_date >" . getMonthFirstDay() . " AND end_date <" . getMonthLastDay();
	// 		$res1=get_line($sql1);
	// 		$arr=[];
	// 		foreach ($res as $key => $value) {
	// 			$value['date']=date("Y/m/d",(int)$value['date']);
	// 			$value['start_date']=date("H:i:s",(int)$value['start_date']);
	// 			$value['end_date']=date("H:i:s",(int)$value['end_date']);
	// 			$value['overtime_type']=($value['overtime_type']=='1')?'晚加班':'休息日加班';
	// 			$value['local']=empty($value['local'])?'公司外':'公司内';
	// 			$value['programid']=empty($value['programid'])?'A':$value['programid'];
	// 			$arr[]=$value;
	// 		}
	// 		$objPHPExcel->getActiveSheet()->duplicateStyle( $objPHPExcel->getActiveSheet()->getStyle('A6:J6'), 'A6:K13' );
	// 		$PHPExcel->getActiveSheet()->mergeCells('A1:AE1');
	// 		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A4', $res1['total']);
	// 		$objPHPExcel->getActiveSheet()->fromArray($arr, NULL, 'A6');
	// 		$file_name=mb_substr($template_name, 0,mb_strlen($template_name)-5).'v'.date('y.m.d',time()).mb_substr($template_name, -5);
	// 	}

	// 	// Redirect output to a client’s web browser (Excel2007)
	// 	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	// 	header('Content-Disposition: attachment;filename="'.$file_name.'"');
	// 	header('Cache-Control: max-age=0');
	// 	// If you're serving to IE 9, then the following may be needed
	// 	header('Cache-Control: max-age=1');

	// 	// If you're serving to IE over SSL, then the following may be needed
	// 	header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	// 	header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
	// 	header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
	// 	header ('Pragma: public'); // HTTP/1.0

	// 	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	// 	$objWriter->save('php://output');
	// 	exit;
	// }
}