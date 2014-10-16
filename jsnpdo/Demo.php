<?

define("_BASEPATH", "C:/xampp/htdocs/www/CI/jsnpdo/");
include_once("jsnpdo.php");
include_once(_BASEPATH . "jsnao/jsnao.php");
include_once(_BASEPATH . "phpfastcache_v2.1_release/phpfastcache.php");
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
try 
{

	//連接資料庫
	Jsnpdo::connect("mysql", "localhost", "ci_jsn", "root", "");
	// Jsnpdo::$debug_style = "position";
	// Jsnpdo::$get_string 	= 	1; //回傳SQL指令不執行

	//建立資料表
	$sql = "CREATE TABLE IF NOT EXISTS `jsntable` (
			  `id` int(10) NOT NULL auto_increment,
			  `title` varchar(500) NOT NULL,
			  `content` varchar(500) NOT NULL,
			  PRIMARY KEY  (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
	$result = Jsnpdo::query($sql, NULL);
	if (is_object($result) and $result->queryString == $sql)
		echo "資料表建立成功<br>";
	else
		throw new Exception("資料表建立發生錯誤");
		

	//新增
	$i=0; while($i++ <= 2) 
	{ 
		$_POST['title'] 		=	"經由POST的標題";
		$ary['id']				=	NULL;
		$ary['title']			=	NULL;
		$result 				=	Jsnpdo::iary("jsntable", $ary, "POST");
		if ($result > 0) 			echo "新增了{$result}筆資料<br>";
		else 						throw new Exception("新增資料錯誤<br>");
	}

	//取得最後新增編號
	$result 					=	Jsnpdo::last_insert_id();
	$DataInfo 					=	Jsnpdo::selone("id", "jsntable", "order by id desc limit 1");	
	if ($result == $DataInfo->id)	echo "最後新增的編號是：{$result}<br>";
	else 							throw new Exception("取得最後編號錯誤<br>");

	// 一次多筆新增
	unset($_POST, $ary);
	Jsnpdo::$get_string 		=	true;
	$i=0; while($i++ < 3)
	{
		$ary['content'] 		=	Jsnpdo::quo("使用多筆新增 {$i}");
		$with[] 				=	Jsnpdo::iary("jsntable", $ary, "POST");
	}
	Jsnpdo::$get_string 		=	false;
	$result 					=	Jsnpdo::with($with);
	if (Jsnpdo::$sql == $result->queryString) 
		echo "一次多筆執行成功<br>";
	else
		throw new Exception("一次多筆執行錯誤 <br>");
		
	// 查詢
	$DataList 				=	Jsnpdo::sel("*", "jsntable", "limit 0, 3");
	if (count($DataList) == Jsnpdo::$select_num)
		echo "查詢多筆列表、取得總數成功 <br>";
	else
		throw new Exception("查詢取得總數錯誤");

	// 查詢單筆
	$w->id 					=	Jsnpdo::quo(1);
	$DataInfo 				=	Jsnpdo::selone("*", "jsntable", "where id = $w->id");
	if (Jsnpdo::$select_num == 1) 
		echo "查詢單筆資料成功 <br>";
	else
		throw new Exception("查詢單筆資料失敗");

	// 查詢快取
	Jsnpdo::$cache_life     = 	3; //快取存活時間
	Jsnpdo::cache(true); //開始快取
	$DataList 				=	Jsnpdo::selone("count(id) as `num_1`", "jsntable", "");
	Jsnpdo::cache(false); //停止快取
	if ( substr_count(Jsnpdo::cache_set_get(), "set") > 0 or substr_count(Jsnpdo::cache_set_get(), "get") > 0)
		echo "取得查詢快取的狀態成功：" . Jsnpdo::cache_set_get() . "<br>";
	else
		throw new Exception("取得查詢快取的狀態失敗");
	
	if (Jsnpdo::$select_num == count($DataList))
		echo "查詢快取成功 <br>";		
	else
		throw new Exception("查詢快取發生錯誤");

	//修改
	unset($_POST, $ary);
	$_POST['content'] 		=	"經由POST的內容" . time();
	$w->id 					=	Jsnpdo::quo(1);
	$ary['title'] 			=	"更動標題";
	$ary['content']			=		NULL;
	$result 				=	Jsnpdo::uary("jsntable", $ary, "where id = $w->id", "POST");
	if ($result > 0) 			echo "修改{$result}筆成功 <br>";
	else 						throw new Exception("修改發生錯誤");
	
	// 刪除
	unset($_POST);
	$w->id 					=	Jsnpdo::quo(1);
	$result 				=	Jsnpdo::delete("jsntable", "1 = 1 and id = 1");
	if ($result > 0) 			echo "刪除{$result}筆成功 <br>";
	else 						throw new Exception("刪除發生錯誤");

	//清空
	$result 				=	Jsnpdo::truncate("jsntable");
	if (Jsnpdo::$sql == $result->queryString) 
		echo "清空成功 <br>";
	else
		throw new Exception("清空發生錯誤 <br>");

	// 刪除資料表
	$sql = "DROP TABLE `jsntable`";
	$result = Jsnpdo::query($sql, NULL);
	if ($result->queryString == $sql)
		echo "刪除資料表成功";
	else
		throw new Exception("刪除資料表錯誤 <br>");

	echo "<h1>傳統寫法</h1>";
	$j = new Jsnpdo;
	$j->connect("mysql", "localhost", "ci_jsn", "root", "");
	$sql = "CREATE TABLE IF NOT EXISTS `jsntable` (
				  `id` int(10) NOT NULL auto_increment,
				  `title` varchar(500) NOT NULL,
				  `content` varchar(500) NOT NULL,
				  PRIMARY KEY  (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

	$resul = $j->query($sql, NULL);

	//iary
	unset($ary);
	$ary['title']			=		$j->quo("傳統寫法 iary 1");
	$j->iary("jsntable", $ary, "POST");
	$ary['title']			=		$j->quo("傳統寫法 iary 2");
	$result 				=		$j->iary("jsntable", $ary, "POST");
	if ($result > 0) echo "新增成功 <br>";

	//sel
	$DataList = $j->sel("*", "jsntable", "where id < 2 limit 10");
	if ($DataList != 0) echo "查詢成功<br>";

	//selone
	$DataInfo = $j->selone("*", "jsntable", "where id = 2");
	if ($DataInfo != 0) echo "查詢單筆成功<br>";

	//uary
	unset($_POST, $ary);
	$_POST['title'] 			=	"POST 修改" . time();
	$w->id 						=	$j->quo(1);
	$ary['title']				=	NULL;
	$result 					=	$j->uary("jsntable", $ary, "where id = $w->id", "POST");
	if ($result > 0) 				echo "修改成功 <br>";

	//delete
	unset($_POST, $ary);
	$w->id 						=	$j->quo(1);
	$result 					=	$j->delete("jsntable", "id = $w->id");
	if ($result > 0) 				echo "刪除成功 <br>";

	//多筆增加
	$j::$get_string 			=	true;
	$i=0; while($i++ < 5)
	{
		$ary['content'] 		=	$j->quo("使用多筆新增 {$i}");
		$with[] 				=	$j->iary("jsntable", $ary, "POST");
	}
	$j::$get_string 			=	false;
	$result 					=	Jsnpdo::with($with);
	if ($result > 0) 				echo "一次多筆新增成功<br>";

	//快取
	$j::$cache_life     		= 	3; 
	$j::cache(true);
	$DataList 					=	$j->selone("count(id) as `num_3`", "jsntable", "");
	$j::cache(false);
	echo "若使用快取，可了解目前的快取狀態是：" . $j->cache_set_get() . "<br>";
	if ($j::$select_num > 0) 		echo "快取查詢成功<br>";

	$j::$cache_life     		= 	5; 
	$j::cache(true);
	$DataList 					=	$j->selone("count(id) as `num_4`", "jsntable", "");
	$j::cache(false);
	echo "若使用快取，可了解目前的快取狀態是：" . $j->cache_set_get() . "<br>";
	if ($j::$select_num > 0) 		echo "快取查詢成功<br>";
	
	//truncate
	$sql 						=	"truncate table `jsntable`";
	$result 					=	$j->query($sql, NULL);
	if (!empty($result)) 			echo "清空成功<br>";

	// 刪除資料表
	$sql 						= 	"DROP TABLE `jsntable`";
	$result 					= 	$j->query($sql, NULL);
	if ($result->queryString == $sql)
		echo "刪除資料表成功";
	else
		throw new Exception("刪除資料表錯誤 <br>");
}
catch(Exception $e)
{
	echo "<h2>獲取異常！</h2>";
	echo $e->getMessage() . "<br>";
	echo $e->getFile() . "<br>";
	echo $e->getLine() . "行<br>";
}



?>