<?

/**
 * v3.0
 * 
 * 必須使用 Jsnao ArrayObject
 * 若使用快取，需引用 phpfastcache.php
 * 
 */

/**
 * 抽象類別, 公用區域
 * 
 */
abstract class Abstract_Jsnpdo
{
	protected static $cache;

	protected static $cache_status = 0;

	//目前快取是 set 還是 get
	protected static $cache_set_get;

	//定義快取設定
	protected static function cache_init()
	{
		if (! class_exists("phpFastCache"))
		{
			throw new Exception("若要使用快取功能，請先引用 phpFastCache");
		}

		phpFastCache::setup("storage","auto");

		self::$cache 			= 	phpFastCache();
	}

	/**
	 * 取得快取
	 * @param   $key 快取的辨識鍵
	 * @return       反回快取值   
	 */
	protected static function cache_get($key)
	{
		$result 						=	self::$cache->get($key);

		self::$cache_set_get 			=	"get";

		return $result;
	}

	/**
	 * 設定快取
	 * @param   $key 			要設定的鍵
	 * @param   $data           設定的值
	 * @param   $sec           	存活秒數
	 * @return                 	bool
	 */
	protected static function cache_set($key, $data, $sec)
	{
		$result 						=	self::$cache->set($key, $data, $sec);

		if (!$result)				throw new Exception("快取製作發生錯誤");

		self::$cache_set_get 			=	"set";

		return $result;
	}



}


class Jsnpdo extends Abstract_Jsnpdo
{
	public static $PDO;

	// 使用PDO的fetch()或fetchAll()的參數，
	// PDO::FETCH_ASSOC 為陣列，PDO預設設 PDO::FETCH_BOTH提取兩種型態，設 PDO::FETCH_OBJ 為物件
	// 提取物件的效能最高，但我們取出陣列，再透過 Jsnao 轉換成 ArrayObject
	public static $fetch_type = PDO::FETCH_ASSOC;

	// 預設使用try catche系統設置
	public static $is_trycatch = "1";
	
	//查詢總數量
	public static $select_num;

	//除錯模式： 
	//0: 不使用 
	//1:停止只顯示文字 			全部方法 
	//str: 顯示查詢表並停止 	sel() selone() iary() uary() delete()
	//chk: 顯示查詢表並繼續		sel() selone() 
	public static $debug;
	
	// 偵錯要顯示的文字。當該屬性被設定時，偵錯顯示將優先採用。
	public static $debug_msg;
		
	public static $debug_style = "block";

	//設定 1 會直接返回SQL字串，而不會執行
	public static $get_string = 0;

	//執行SQL前的字串
	public static $sql;

	//快取的存活間
	public static $cache_life 		= 	3;


	function __construct()
	{
	}

	// 連線
	public static function connect($sql_database, $hostname, $dbname, $user, $password)
	{
		try 
		{
			$pdo       = new PDO("{$sql_database}:host={$hostname};dbname={$dbname}", $user, $password);
			
			$pdo->query("SET NAMES 'UTF8'");
			
			self::$PDO = $pdo;
			
			return self::$PDO;
		}
		catch (PDOException $e) 
		{
			self::warning('stop', '資料庫連接錯誤: ' . $e->getMessage());
		}
	}

	/**
	 * PDO執行
	 * @param   $sql          SQL 指令
	 * @param   $status_debug 除錯模式
	 * @return                PDO狀態的資源物件 或 SQL 字串
	 */
	public static function query($sql, $status_debug)
	{

		//可外部讀取檢視
		self::$sql = $sql;

		if (self::$get_string == 1)
		{
			return $sql;
		}

		// debug 純文字
		if (self::debug($status_debug) == 1)
		{
			self::warning('stop', $sql);
		}

		//正確執行
		else
		{
			$result = self::$PDO->query($sql);

			if (!empty($result))
			{
				return $result;
			}

			$error_ary = self::$PDO->errorinfo();

			throw new Exception("PDO 執行 query 發生錯誤：{$error_ary[2]}");
		}
	}

	/**
	 * 新增
	 * @param   $table_name   資料表名稱
	 * @param   $ary          添加的資料
	 * @param   $post_get     POST | GET
	 * @param   $status_debug 除錯模式
	 * @return                回傳增加數量 
	 */
	public static function iary($table_name, array $ary, $post_get, $status_debug = NULL)
	{
		if ($post_get != "POST" and $post_get != "GET") 
		{
			throw new Exception("請指定為POST或GET");
		}

		foreach ($ary as $key => $val)
		{
			//欄位名稱陣列
			$col_name[] 	=	self::get_col_name($key);

			// 欄位值陣列
			$col_val[]  	= 	self::get_col_val($key, $val, $post_get);
		}

		$col_name_str 		= 	implode(", ", $col_name);
		$col_val_str 		= 	implode(", ", $col_val);

		$sql 				= 	" insert into `{$table_name}` ({$col_name_str}) values ({$col_val_str}); ";
		
		//debug str
		if (self::debug($status_debug) == "str")
		{
			self::$debug_msg = $sql;

			self::sel("*", $table_name, "", "str");
		}

		$result 			=	self::query($sql, $status_debug);

		if (self::$get_string == 1)
		{
			return $result;
		}

		return $result->rowCount();
	}

	/**
	 * 修改
	 * @param   $table_name   資料表名稱
	 * @param   $ary          修改陣列
	 * @param   $else         其他條件
	 * @param   $post_get     POST | GET
	 * @param   $status_debug 
	 * @return                返回影響數量
	 */
	public static function uary ($table_name, array $ary, $else, $post_get, $status_debug = NULL)
	{
		if ($post_get != "POST" and $post_get != "GET") 
		{
			throw new Exception("請指定為POST或GET");
		}

		$table_name 				=	trim($table_name);

		$else 						=	trim($else);

		//組合 set 欄位 = 值
		foreach ($ary as $key => $val)
		{
			if (!empty($val))
			{
				$str[] 				= 	" `{$key}` = " . self::quo($val);
				continue;
			}

			$request_ary 			=	($post_get == "POST") ? $_POST : $_GET;

			$str[] 					= 	"`{$key}` = " . self::quo($request_ary[$key]);
		}

		$cond						=	implode(", ", $str);

		$sql 						=	"update `{$table_name}` set {$cond} {$else}";

		//debug str
		if (self::debug($status_debug) == "str")
		{
			self::$debug_msg = $sql;

			self::sel("*", $table_name, "", "str");
		}

		$result 					=	self::query($sql, $status_debug);
		
		if (self::$get_string == 1)
		{
			return $result;
		}

		return $result->rowCount();
	}


	/**
	 * 多筆查詢
	 * @param   $column       查詢欄位
	 * @param   $table_name   資料表
	 * @param   $else         其他條件
	 * @param   $status_debug 除錯指令
	 * @return                返回 ArrayObject 或 SQL 字串 或 0
	 */
	public static function sel($column, $table_name, $else = NULL, $status_debug = NULL)
	{
		
		$mix 						=	self::select_run("sel", $column, $table_name, $else, $status_debug);

		if ($mix == "get_string")
		{
			return self::$sql;
		}

		//若沒有資料回傳0
		return ($mix->count == 0) ? "0" : $mix->data;
	}

	/**
	 * 多筆查詢
	 * @param   $column       查詢欄位
	 * @param   $table_name   資料表
	 * @param   $else         其他條件
	 * @param   $status_debug 除錯指令
	 * @return                返回 ArrayObject 或 SQL 字串 或 0
	 */
	public static function selone($column, $table_name, $else, $status_debug = NULL)
	{

		$mix 						=	self::select_run("selone", $column, $table_name, $else, $status_debug);
		
		if ($mix == "get_string")
		{
			return self::$sql;
		}

		if ($mix->count > 1)
		{
			throw new Exception("查詢指令錯誤，數量多於一筆");
		}

		return ($mix->count == 0) ? "0" : $mix->data;
	}


	public static function delete($table_name, $where, $status_debug = NULL)
	{
		$table_name				=	trim($table_name);
		
		$where 					=	trim($where);

		if (empty($where))
		{
			throw new Exception("delete 方法務必指定 where 條件");
		}

		$sql					=	"delete from {$table_name} where {$where}";

		//debug str
		if (self::debug($status_debug) == "str")
		{
			self::$debug_msg 	= 	$sql;

			self::sel("*", $table_name, "", "str");
		}

		$result 				=	self::query($sql, $status_debug);

		if (self::$get_string == 1)
		{
			return $result;
		}

		return $result->rowCount();
	}

	public static function truncate($table_name, $status_debug = NULL)
	{
		$table_name				=	trim($table_name);

		$sql					=	"truncate table {$table_name}";

		//debug str
		if (self::debug($status_debug) == "str")
		{
			self::$debug_msg 	= 	$sql;

			self::sel("*", $table_name, "", "str");
		}

		$result 				=	self::query($sql, $status_debug);

		if (self::$get_string == 1)
		{
			return $result;
		}

		return $result;
	}

	/**
	 * 多筆SQL一次執行
	 * @param   $ary          多筆要執行的SQL語句
	 * @param   $status_debug 除錯語句	
	 * @return 				  返回PDO狀態資源
	 */
	public static function with(array $ary, $status_debug = NULL)
	{
		$sql 					=	implode(NULL, $ary);
		$result 				=	self::query($sql, $status_debug);

		if (self::$get_string == 1)
		{
			return $result;
		}

		//必須釋放多筆緩存
		$result->closeCursor();
		
		return $result;
	}

	/**
	 * 保護與過濾欄位值
	 * @param   $str 字串
	 * @return       返回 '' 保護
	 */
	public static function quo($str)
	{
		return self::$PDO->quote($str);
	}

	// quo() 別名
	public static function quote($str)
	{
		return self::quo($str);
	}

	/**
	 * 最後一筆新增的編號
	 * @return  新增的編號
	 */
	public static function last_insert_id()
	{
		return self::$PDO->lastInsertId();
	}

	/**
	 * 啟用 select 快取	
	 * @param  $bool 預設不啟用 
	 */
	public static function cache($bool = 0)
	{
		if ($bool == 0) self::$cache_status = 0;

		else self::$cache_status = 1;
	}


	public static function cache_set_get()
	{
		return parent::$cache_set_get;
	}




	// HHHHHHHHH     HHHHHHHHHEEEEEEEEEEEEEEEEEEEEEELLLLLLLLLLL             PPPPPPPPPPPPPPPPP   
	// H:::::::H     H:::::::HE::::::::::::::::::::EL:::::::::L             P::::::::::::::::P  
	// H:::::::H     H:::::::HE::::::::::::::::::::EL:::::::::L             P::::::PPPPPP:::::P 
	// HH::::::H     H::::::HHEE::::::EEEEEEEEE::::ELL:::::::LL             PP:::::P     P:::::P
	//   H:::::H     H:::::H    E:::::E       EEEEEE  L:::::L                 P::::P     P:::::P
	//   H:::::H     H:::::H    E:::::E               L:::::L                 P::::P     P:::::P
	//   H::::::HHHHH::::::H    E::::::EEEEEEEEEE     L:::::L                 P::::PPPPPP:::::P 
	//   H:::::::::::::::::H    E:::::::::::::::E     L:::::L                 P:::::::::::::PP  
	//   H:::::::::::::::::H    E:::::::::::::::E     L:::::L                 P::::PPPPPPPPP    
	//   H::::::HHHHH::::::H    E::::::EEEEEEEEEE     L:::::L                 P::::P            
	//   H:::::H     H:::::H    E:::::E               L:::::L                 P::::P            
	//   H:::::H     H:::::H    E:::::E       EEEEEE  L:::::L         LLLLLL  P::::P            
	// HH::::::H     H::::::HHEE::::::EEEEEEEE:::::ELL:::::::LLLLLLLLL:::::LPP::::::PP          
	// H:::::::H     H:::::::HE::::::::::::::::::::EL::::::::::::::::::::::LP::::::::P          
	// H:::::::H     H:::::::HE::::::::::::::::::::EL::::::::::::::::::::::LP::::::::P          
	// HHHHHHHHH     HHHHHHHHHEEEEEEEEEEEEEEEEEEEEEELLLLLLLLLLLLLLLLLLLLLLLLPPPPPPPPPP      	

	//產生查詢字串
	protected static function select_string($column, $table_name, $else)
	{
		$column 				=	trim($column);

		$table_name 			=	"`" . trim($table_name) . "`";

		$else 					=	trim($else);

		return "select {$column} from {$table_name} {$else}";
	}



	/**
	 * 運行 select 並提取資料
	 * @param   $select_type  查詢的類型。sel | selone
	 * @param   $column       查詢的欄位
	 * @param   $table_name   資料表
	 * @param   $else         其他條件
	 * @param   $status_debug 
	 * @return                返回 Jsnao 的陣列物件 (ArrayObject) 或 字串 "get_string";
	 */
	protected static function select_run($select_type, $column, $table_name, $else, $status_debug)
	{
		if (!class_exists("Jsnao"))
		{
			throw new Exception("請先引用 jsnao");
		}	

		

		//除錯時避免資料量過大，將自動添加 limit
		if (self::debug($status_debug) == "str" or self::debug($status_debug) == "chk") 
		{
			if (!empty($else)) 
			{
				if (substr_count($else, "limit") == 0) 
				{
					$else .= " limit 10 ";
				}
			}
			else $else .= " limit 10 ";
		}

		$sql 							=	self::select_string($column, $table_name, $else);

		// 啟用快取
		if (self::$cache_status == 1)
		{
			parent::cache_init();

			//cache 辨識 key
			$cache_sql_key 	 			=	hash("sha1", $sql) . md5($sql);
			$cache_obj	 				=	parent::cache_get($cache_sql_key);

			// 若有製作快取
			if (!empty($cache_obj)) 			
			{	
			//設定數量
				self::$select_num 			=	$cache_obj->count;

				return $cache_obj;
			}
		}

		// 該query資源會提供給 debug str 或 正常運行多筆資料、單筆資料
		$result 				=	self::query($sql, $status_debug);

		if (self::$get_string == 1) 
		{
			return "get_string";
		}

		//數量
		$obj->count				=	
		self::$select_num  		=	$result->rowCount();

		// debug str
		if (self::debug($status_debug) == "str")
		{
			$data 				=	new jsnao( $result->fetchAll(PDO::FETCH_ASSOC) );
			self::warning('stop', $sql, $data);
		}

		//若沒資料
		if (self::$select_num == 0)
		{
			return $obj;
		}

		//多筆列表
		if ($select_type == "sel")
		{
			// 再一次query, 避免空陣列。且使用陣列。因為陣列的 key 會有欄位名稱
			$result 			=	self::query($sql, $status_debug);
			$data 				=	$result->fetchAll(self::$fetch_type);
			$obj->data 			=	new jsnao($data);
		}

		//單筆列表
		else
		{
			$data 				=	$result->fetch(self::$fetch_type);
			$obj->data 			=	new jsnao($data);
		}

		// debug chk
		if (self::debug($status_debug) == "chk")
		{
			if (self::$select_num > 0)
			{
				// 再一次query
				$result 			=	self::query($sql, $status_debug);
				$data 				=	new jsnao( $result->fetchAll(PDO::FETCH_ASSOC) );
			}
			self::warning("continue", $sql, $data);
		}

		//啟用快取
		if (self::$cache_status == 1 and empty($cache_obj))
		{
			$cache_r 				=	parent::cache_set($cache_sql_key, $obj, self::$cache_life);

			if (!$cache_r)				throw new Exception("快取製作發生錯誤");
		}

		return $obj;
	}


	//取得iary欄位名稱
	protected static function get_col_name($key)
	{
		$key = trim($key);
		return "`{$key}`";
	}


	//取得iary欄位值
	protected static function get_col_val($key, $val, $post_get)
	{

		if (!isset($val))
		{
			if ($post_get == "POST") 	
			{
				$col_val = self::quo($_POST[$key]);
			}
			else 						
			{
				$col_val = self::quo($_GET[$key]);
			}
		}

		else 
		{
			$col_val = $val;
		}
		
		return $col_val;		
	}

	public static function debug($status_debug)
	{
		$result 					=	empty($status_debug) ? self::$debug : $status_debug;
		
		return $result;
	}

	// 警告輸出
	protected static function warning($continue_stop, $msg, ArrayObject $table = NULL)
	{
		//若屬性已被指定，將優先使用
		if (!empty(self::$debug_msg))
		{
			$selmsg 				=	self::$debug_msg;
			$msg 					=	"<div>{$selmsg}</div><div class='defmsg'>{$msg}</div>";
		}

		if ($table) foreach ($table as $key => $list)
		{
			unset($mix_body);

			// thead 與 tfoot，取得每個th
			if ($key == 0)
			{
				foreach ($list as $column => $val)
				{
					$mix_head 		.=	"<th>{$column}</th>";
				}

				$thead_foot 		.=	"<tr>{$mix_head}</tr>";
			}


			//tbody, 取得每個td
			foreach ($list as $column => $val)
			{
				$mix_body 		.=	"<td>{$val}</td>";
			}

			$tbody 				.=	"<tr>{$mix_body}</tr>";
		}

		
		echo 
		"
			<style>
			.php_jsnao_warning_style
			{
				background: #5CC593;
				font-family: consolas;
				line-height: 1.7em;
				margin-top: 0.1em;
				margin-bottom: 0.1em;
				padding: 1em;
				border-radius: 4px;
				font-size: 18px;
				word-break: break-all;
			}
			.php_jsnao_warning_style .defmsg
			{
				color: #FFFFFF;
			}

			.php_jsnao_warning_style .db
			{
				border: 1px solid #FFFFFF;
				width: 98% !important;
				table-layout: fixed;
			}
			.php_jsnao_warning_style .db td, 
			.php_jsnao_warning_style .db th 
			{
				border: 1px solid #FFFFFF;
				padding: 0.3em 1em;
				font-size: 12px;
				max-height:50px;
				height:50px;
				over-flow:hidden;
			}
			</style>
		";
		if (self::$debug_style == "position")
		{
			echo 
			"
				<style>
				.php_jsnao_warning_style
				{
					position: fixed;
					top: 0px;
					left: 0px;
					right: 0px;
					opacity: 0.1;
					transition: 0.5s all;
				}
				.php_jsnao_warning_style:hover 
				{
					opacity: 1;
				}
				</style>
			";
		}

		echo 
		"
			<div class='php_jsnao_warning_style'>

				<div class='sql'>{$msg}</div>
			
				<table class='db'>
					<thead>
						{$thead_foot}
					</thead>
					<tbody>
						{$tbody}
					</tbody>
					<tfoot>
						{$thead_foot}
					</tfoot>
				</table>

			</div>


		";
		if ($continue_stop == "stop") die;
	}
	
}
?>