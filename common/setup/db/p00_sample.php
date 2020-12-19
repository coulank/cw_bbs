<?php
namespace cws;
// debug(true); $cws_err_dump = true;

// ログ用には以下のサンプルを使います
// $cws_db_host = 'log.db';
// $cws_table_log = 'log001';
// $cws_flag_log = true;

// MySQL対応
$db_mysql = array('service' => 'mysql');
$cws_db_name = isset($cws_db_name) ? $cws_db_name : 'sample';
// サンプルなのでSQLite一択になるようにしています
if ($cws_sqlite_flag = true) {
	$dbi->db_list = array($db_sqlite);
} else {
	$db_mysql['name'] = $cws_db_name;
	$db_mysql['user'] = 'root';
	$db_mysql['pass'] = 'hogehoge';
	$db_mysql['host'] = 'localhost';
	$dbi->db_list = array($db_mysql);
}
?>