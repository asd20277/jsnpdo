jsnpdo
======

##讓你快速使用PDO對MySQL做溝通

<pre>

//查詢並自動解決 sql injection
    $w->id 					=	Jsnpdo::quo(1);
    $DataInfo 				=	Jsnpdo::selone("*", "jsntable", "where id = $w->id");

//新增
    $_POST['title'] 	    =	"經由POST的標題";
    $ary['id']				=	NULL;
    $ary['title']			=	NULL;
    $result 			  	=	Jsnpdo::iary("jsntable", $ary, "POST");

//修改
    $_POST['content'] 		=	"經由POST的內容" . time();
    $w->id 					=	Jsnpdo::quo(1);
    $ary['title'] 			=	"更動標題";
    $ary['content']			=	NULL;
    $result 				=	Jsnpdo::uary("jsntable", $ary, "where id = $w->id", "POST");
	
</pre>

或一般的實體物件寫法
<pre>
    $ary['title']			=		NULL;
    $result 				=		$j->iary("jsntable", $ary, "POST");
</pre>

若查詢需要快取
<pre>
	Jsnpdo::$cache_life 		=	3; //快取存活時間
	Jsnpdo::cache(true); //開始快取
	$DataList 					=	Jsnpdo::selone("count(id) as `num_1`", "jsntable", "");
	Jsnpdo::cache(false); //停止快取

</pre>

###使用方法

- 前往 jsnpdo/jsnpdo/Demo.php 
- include_once 對應你的所有路徑設定
- 設定你的資料庫資料Jsnpdo::connect("mysql", "localhost", "ci_jsn", "root", "");
- 重新整理就會看到極簡的單元測試

<pre>
    資料表建立成功
    新增了1筆資料
    新增了1筆資料
    新增了1筆資料
    最後新增的編號是：3
    一次多筆執行成功
    查詢多筆列表、取得總數成功 
    查詢單筆資料成功 
    取得查詢快取的狀態成功：get
    查詢快取成功 
    修改1筆成功 
    刪除1筆成功 
    清空成功 
    刪除資料表成功
    傳統寫法
    
    新增成功 
    查詢成功
    查詢單筆成功
    修改成功 
    刪除成功 
    一次多筆新增成功
    若使用快取，可了解目前的快取狀態是：get
    快取查詢成功
    若使用快取，可了解目前的快取狀態是：get
    快取查詢成功
    清空成功
    刪除資料表成功
</pre>
