<?php
// default: mySQL(Lolipop)
namespace cws;
require_once($_SERVER['DOCUMENT_ROOT']."/common/setup/set_cws_require.php");
// debug(true);
// $cws_err_dump = true;
if (!isset($cws_local_flag)) {
    // $cws_local_flag = preg_match("/.*192\.168\..*|127\.|^\:\:1$/u", $_SERVER["REMOTE_ADDR"]);
}
if (!isset($cws_sqlite_flag)) {
    $cws_sqlite_flag = preg_match("/local|127\.|^\:\:1$/u", $_SERVER["REMOTE_ADDR"]);
}
if (!isset($cws_sqlite_local_flag)) {
    // $cws_sqlite_local_flag = true;
    $cws_sqlite_local_flag = $cws_sqlite_flag;
}
require_once(get_docpath("/common/cw_init/cws.php"));
require_once(get_docpath("/common/cw_init/cws_db.php"));
// $db = DB::create($dbi);
if (!isset($cws_load)) $cws_load = array();
if(!isset($cws_load['require']) || $cws_load['require']) require_once(preg_replace('/([\\/\\\\]common).*$/', '$1/cw_init/cws_require.php', __FILE__));
$dbi = DBI::create();
$local_addr = "127.0.0.1";
if (!isset($cws_sqlite_host)) {
    if ($cws_local_flag=false) {
        $cws_sqlite_host = get_docpath('/db/local/data.db');
    } else {
        $cws_sqlite_host = get_docpath('/db/data.db');
    }
}
$db_sqlite = array('service' => 'sqlite', 'host' => $cws_sqlite_host);
// $dbi->db_list = array($db_sqlite);
require_once("db/p00_sample.php");
?>