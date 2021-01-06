<?php
namespace cws;
// データ取得用の関数

function get_exec($param = null){
    global $db, $table_list, $thread_list, $thread_tables, $only_threads,
        $main_thread, $main_table, $page_limit;
    if (!is_array($param)) {
        if (is_null($param)) {
            $param = array();
        } else {
            $param = array('id' => $param);
        }
    }
    $_db = (isset($param['db']) ? $param['db'] : $db);
    $_table_list = (isset($param['table_list']) ? $param['table_list'] : $table_list);
    $_thread_list = (isset($param['thread_list']) ? $param['thread_list'] : $thread_list);
    $_thread_tables = (isset($param['thread_tables']) ? $param['thread_tables'] : $thread_tables);
    $_main_thread = (isset($param['main_thread']) ? $param['main_thread'] : $main_thread);
    $_main_table = (isset($param['main_table']) ? $param['main_table'] : $main_table);
    $_only_threads = (isset($param['only_threads']) ? $param['only_threads'] : $only_threads);
    $_page_limit = (isset($param['page_limit']) ? $param['page_limit'] : $page_limit);
    $return_array = array();
    $other_opt = array();
    $stmt_array = array();
    $add_strs = array();
    $where_in = '';
    $nest_array = array();
    $name_in_array = array();
    $flag_array = array();
    $exec = array();
    $since = null;
    $until = null;
    $where = '';
    $between = '';
    $thread_filters = array();
    $thread_nots = array();
    $order = 'DESC';
    if (isset($_COOKIE['order']) && strtoupper($_COOKIE['order']) == 'ASC') $order = 'ASC';
    global $cws_request, $cws_sitemap, $cws_filter_tag, $cws_add_search_q, $cws_secret_search_q, $private_owner_login, $index_post_value;
    if (isset($cws_request) && is_array($cws_request)) $_req = $cws_request; else $_req = $_REQUEST;
    if (isset($cws_sitemap)) $_sitemap = $cws_sitemap; else $_sitemap = '[./:トップ]';
    if (isset($cws_filter_tag) && is_array($cws_filter_tag)) $_filter_tag = $cws_filter_tag; else $_filter_tag = array();
    if (!isset($cws_add_search_q)) $cws_add_search_q = '';
    if (!isset($index_post_value)) $index_post_value = '';
    if (!isset($cws_secret_search_q)) $cws_secret_search_q = ''; else $cws_secret_search_q = "( $cws_secret_search_q )";
    $secret_search_list = explode(' ', $cws_secret_search_q);
    $req_notag = get_val($_req, 'nq', '');
    $req_q = get_val($_req, 'q', '');
    if ($req_notag !== '' && $req_q !== '') $req_notag .= ' ';
    $search_q = preg_replace('/^\s*(OR|AND)\s*/', '', "($req_notag$req_q)");
    $search_r = get_val($_req, array('r', 're'), '');
    if ($search_r !== '' && !$_db->regable()) {
        $search_q = '(' . $search_r . ($search_q === '' ? '' : ' ') . $search_q . ')';
        $search_r = '';
    }
    $collate_str_uni = $_db->collater(true, true, true);
    $collate_str_gen = $_db->collater(true, false, true);

    $e_request_a = array();
    $null = false;
    $j = 0;
    $add_strs_func = function($str, $ins, $abs_flag = false, $not = '', $escape = '\\') use (&$stmt_array, &$add_strs, &$collate_str_uni, &$collate_str_gen, &$_db) {
        $stmt_array[$ins] = $str;
        $collate = '';
        if ($_db->dbi->db_service !== 'sqlite') {
            if ($escape === '\\') $escape = '\\\\';
            if (!$abs_flag && preg_match('/^[%_]*(@?)[!-~]+$/', $str, $m)) {
                if ($m[1] === '') {
                    $collate = $collate_str_uni;
                } else {
                    $collate = $collate_str_gen;
                }
            }
        }
        if (!empty($escape)) {
            $str_escape = " ESCAPE '$escape'";
        } else {
            $str_escape = '';
        }
        $add_strs[] = "`text`$collate $not LIKE :$ins".$str_escape;
    };

    $get_exp_func = function($a_search_q) use (
            &$search_q, &$get_exp_func, &$stmt_array, &$add_strs, &$add_strs_func, &$_page_limit,
            &$where_in, &$nest_array, &$name_in_array, &$flag_array, &$thread_filters, &$thread_nots,
            &$since, &$until, &$order, &$e_request_a, &$_db, &$exec, $_req, $_sitemap, &$_filter_tag,
            &$cws_add_search_q, &$secret_search_list, &$null, &$j, &$other_opt,
            &$private_owner_login, &$index_post_value){
        $e_search_q = array_merge($secret_search_list, (function($q){
            if (preg_match_all('/\s*(^|[\s()])\s*([^\s()]*)/', $q, $m)){
                $i = 0; $c = count($m[1]);
                $retval = array();
                while($i < $c){
                    if ($m[0][$i] !== '') {
                        if ($m[1][$i] === '(' || $m[1][$i] === ')') $retval[] = $m[1][$i];
                        if (!empty($m[2][$i])) {
                            $retval[] = $m[2][$i];
                        }
                    }
                    ++$i;
                }
                return $retval;
            } else {
                return array();
            }
        })($a_search_q));
        $not_flag = false;
        $abs_flag = false;
        $reload_flag = false;
        $order_change = false;
        $enable_keyword = false;
        $andor = '';
        ++$j; $l_j = $j;
        for ($i = 0; $i < count($e_search_q); $i++) {
            $add_str = '';
            $ins = 'where_q'.strval($l_j).'_'.strval($i);
            $str = $e_search_q[$i];
            // var_dump("$i:[$where_in] [$str]");
            if ($str !== '' && !$null) {
                $f_str = substr($str, 0, 1);
                if ($f_str === '-') {
                    $not_flag = true;
                    $str = substr($str, 1);
                } elseif ($f_str === '"') {
                    $g_str = substr($str, -1);
                    if ($g_str === '"') {
                        $abs_flag = true;
                        $str = substr($str, 1, strlen($str) - 2);
                    }
                } else {
                    switch ($str) {
                        case 'NOT':
                            $not_flag = true;
                        continue 2; break;
                        case 'AND': case 'OR':
                            $andor = $str;
                        continue 2; break;
                        case ')':
                            if (array_pop($nest_array)) {
                                $where_in .= ')';
                            }
                            $where_in .= ')';
                            $andor = '';
                        continue 2; break;
                        case '(':
                            array_push($nest_array, false);
                            if ($andor !== '') $andor = " $andor "; else $andor = " AND ";
                            $where_in .= $andor.'(';
                            $andor = '';
                        continue 2; break;
                        case 'NULL':
                            $null = true;
                        continue 2; break;
                        default:
                            $count = count($nest_array) - 1;
                            if ($andor === '') {
                                if ($nest_array[$count]) $where_in .= ')';
                                $nest_array[$count] = true;
                                if ($andor !== '') $andor = " $andor "; else $andor = " AND ";
                                $where_in .= $andor.'(';
                                $andor = '';
                            }
                        break;
                    }
                }
                if ($not_flag) {
                    $not = ' NOT';
                    $not_flag = false;
                } else {
                    $not = '';
                }
                $already_stmt = false;
                $request_add = true;
                if (strpos($str, ':') !== false) {
                    $colon_exp = \explode(':', strtolower($str), 3);
                    $colon_count = count($colon_exp);
                    $exp_key = strtolower($colon_exp[0]);
                    $exp_value = strtolower($colon_exp[1]);
                    $exp_value_2 = ($colon_count>2) ? strtolower($colon_exp[2]) : '';
                    $exp_value_m = $exp_value . (($exp_value_2=='') ? '' : ":$exp_value_2");
                    switch ($exp_key) {
                        case 'from':
                            $add_str = "`name`$not LIKE :$ins";
                            $name_in_array[] = $colon_exp[1];
                            $stmt_array[$ins] = $colon_exp[1];
                            $already_stmt = true;
                            $request_add = false;
                            $enable_keyword = true;
                        break;
                        case 'since':
                            $since = $exp_value_m;
                            $enable_keyword = true;
                            continue 2; break;
                        case 'until':
                            $until = $exp_value_m;
                            $enable_keyword = true;
                        continue 2; break;
                        case 'filter':
                            $_str=''; $_highlight = ''; $re=''; $like='';
                            switch($exp_value) {
                                case 'images':
                                    $re = '\:image|\.(jpeg|jpg|png|gif)';
                                    $like = '%[%:image%]%';
                                break;
                                case 'videos':
                                    $re = '\:video|\.(mp4|mov|avi)';
                                    $like = '%[%:video%]%';
                                break;
                                case 'audios':
                                    $re = '\:audio|\.(m4a|wav|mp3|ogg)';
                                    $like = '%[%:audio%]%';
                                break;
                                case 'links':
                                    $like = '%[%]%|%http%://%';
                                break;
                                case 'response':
                                    $re = '>>\d';
                                    $like = '%>>%';
                                break;
                                default:
                                    $val = get_val($_filter_tag, $exp_value, null);
                                    if (is_array($val)) {
                                        $_str = get_val($val, 'like', null);
                                        $_highlight = get_val($val, array('highlight', 0, 'like'), '');
                                        if (is_null($_str)) {
                                            $_str = implode(' OR ', explode(' ', $_highlight));
                                            if ($_str !== '') $_str = "($_str)";
                                        }
                                        $re = get_val($val, 're', '');
                                        $_add_tag = array();
                                        foreach(explode(' ', get_val($val, array('tag', 1), '')) as $_tag) {
                                            if (strpos($search_q, $_tag) == false) {
                                                $_add_tag[] = $_tag;
                                            }
                                        }
                                        $cws_add_search_q = add_search_r($cws_add_search_q, implode(' ', $_add_tag));
                                        unset($_add_tag);
                                    } else if (is_null($val)) {
                                        if ($andor !== '') $andor = ' AND '; else $andor = '';
                                        $where_in .= $andor.'NULL';
                                        $andor = '';
                                    } else {
                                        $_str = $val;
                                    }
                                    if ($_str === '') continue 3;
                                break;
                            }
                            if ($re !== '' && $_str !== '') {
                                list($exp, $restr, $collate) = $_db->reg_classify($re, $_str);

                                if ($exp === 'REGEXP') {
                                    $stmt_array[$ins] = $restr;
                                    $ins_str = "`text` $exp :$ins";
                                    if ($andor !== '') $andor = " $andor "; else $andor = " AND ";
                                    $where_in .= $andor.$ins_str;
                                    $e_request_a[] = $_highlight;
                                    $andor = '';
                                } else {
                                    $get_exp_func($_str);
                                }
                            } else if (($re.$like) !== '') {
                                list($exp, $relike) = $_db->reg_classify($re, $like, '', true);
                                if ($exp === 'LIKE') {
                                    $relike_array = explode('|', $relike);
                                } else {
                                    $relike_array = array($relike);
                                }
                                $ins_array = array();
                                foreach($relike_array as $relike_i => &$relike_value) {
                                    $ins_relike_key = $ins.'_'.$relike_i;
                                    $stmt_array[$ins_relike_key] = $relike_value;
                                    array_push($ins_array, "`text` $exp :$ins_relike_key");
                                }
                                $ins_str = (count($ins_array) > 0) ? ('('.implode(' OR ', $ins_array).')') : '';
                                if ($andor !== '') $andor = " $andor "; else $andor = " AND ";
                                $where_in .= $andor.$ins_str;
                                $andor = '';
                            } else {
                                $get_exp_func($_str);
                            }
                                $enable_keyword = true;
                            continue 2;
                        break;
                        case 'theme':
                            $theme_value = $exp_value;
                            switch($theme_value) {
                                case 'dark': case 'light': case 'prefer': case 'auto':
                                setcookie('theme_prefer', $theme_value, time()+60*60*24*30*6, '/');
                                break;
                                case '':
                                    setcookie('theme_prefer', '', time(), '/');
                                break;
                            }
                            $reload_flag = true;
                        continue 2; break;
                        case 'order':
                            $order = strtoupper($exp_value);
                            $v2up = strtoupper($exp_value_2);
                            if (!($order=='ASC'||$order=='DESC')) { $v2up .= $order; $order = ''; }
                            $enable_keyword |= ($v2up !== 'SET');
                            $order_change = true;
                        continue 2; break;
                        case 'on':
                        case 'off':
                            ;
                            $set_cookie = null;
                            $set_path = '/';
                            switch($exp_value) {
                                case 'task':
                                    $set_cookie = 1;
                                    $set_path = $index_post_value;
                                break;
                                case 'alarm':
                                    $set_cookie = 3;
                                break;
                            }
                            if (!is_null($set_cookie)) {
                                switch($exp_key) {
                                    case 'on':
                                        setcookie($exp_value, $set_cookie, time()+60*60*24*30*6, $set_path);
                                    break;
                                    case 'off':
                                        setcookie($exp_value, '', time(), $set_path);
                                    break;
                                }
                            }
                            $reload_flag = true;
                        continue 2; break;
                        case 'view':
                            switch($exp_value) {
                                case 'server': case 'sv':
                                    if ($private_owner_login) $flag_array['server'] = true;
                                break;
                                case 'user-agent': case 'user_agent': case 'ua':
                                    $flag_array['ua'] = true;
                                break;
                                case 'cookie':
                                    $flag_array['cookie'] = true;
                                break;
                                case 'sitemap':
                                    $flag_array['sitemap'] = true;
                                break;
                                case 'size':
                                    $other_opt['view_size'] = true;
                                break;
                                case 'alarm':
                                    $other_opt['view_alarm'] = true;
                                break;
                                case 'task':
                                    $other_opt['view_task'] = true;
                                break;
                                case 'memory':
                                    $flag_array['memory'] = true;
                                break;
                                case 'temperature':
                                    $flag_array['temperature'] = true;
                                break;
                            }
                        break;
                        case 'limit':
                            $_page_limit = $exp_value;
                        continue 2; break;
                        case 'thread':
                            if ($not === '') {
                                array_push($thread_filters, $exp_value);
                            } else {
                                array_push($thread_nots, $exp_value);
                            }
                        continue 2; break;
                    }
                } else {
                    $enable_keyword = true;
                }
                if (!$already_stmt) {
                    $add_strs = array();
                    if ($str != '') {
                        if (strncmp($str, '#', 1) === 0) {
                            $add_strs_func("%$str", $ins.'_1', $abs_flag);
                            $add_strs_func("%$str %", $ins.'_2', $abs_flag);
                            $add_strs_func("%$str\n%", $ins.'_3', $abs_flag);
                        } else {
                            $add_strs_func("%$str%", $ins, $abs_flag);
                        }
                    } else {
                        continue;
                    }
                } elseif($add_str != '') {
                    $add_strs = array($add_str);
                } elseif($str != '') {
                    $add_strs = array($str);
                }
                $highlight_q = preg_replace('/\\\\([\\\\_%\'"])/', '$1', $str);
                if ($andor !== '') $andor = " $andor "; else $andor = " AND ";
                $add_str = implode(' OR ', $add_strs);
                if (count($add_strs) > 1) { $add_str = "($add_str)"; }
                $f_add_str = $add_str !== '';
                if ($f_add_str && $not !== '') $highlight_q = "-$highlight_q";
                $where_in .= $andor.$not.$add_str;
                $andor = '';
                if ($request_add) $e_request_a[] = $highlight_q;
                $abs_flag = false;
            }
        }
        if (!$enable_keyword && $order_change) {
            switch($order) {
                case '':
                    setcookie($exp_key, '', time(), '/');
                break;
                default:
                    setcookie($exp_key, $exp_value, time()+60*60*24*30*6, '/');
                break;
            }
            $reload_flag = true;
        }
        if ($reload_flag) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();
        }
    };
    $search_id = get_val($_req, 'id', '');
    $page = intval(get_val($_req, 'p', 1));
    $viewtree = false;
    if ($search_id === '') {
        $get_exp_func($search_q);
        $Rpar_func = function() use (&$where_in) {
            $where_in .= ')';
        };
        
        while (($nest_val = array_pop($nest_array)) !== null) {
            if ($nest_val) $Rpar_func();
            $Rpar_func();
        }
        do {
            $while_loop = false;
            $where_in = preg_replace_callback(
            '/\s*(OR|AND|)\s*\(\s*\)\s*|(^|\s*\()\s*(OR|AND)\s*|^\s*\)\s*/',
            function($m) use (&$while_loop) {
                $str = '';
                if (isset($m[2])) $str = $m[2];
                $while_loop = true;
                return $str;
            }, $where_in);
        } while($while_loop);
    
        $highlight_q = implode(' ', $e_request_a);
        
        if ($search_r !== '') {
            list($exp, $stmt_array['where_r'], $collate) = $_db->reg_classify($search_r);
        if (preg_match('/^\s*$/', $where_in)) $andor = ''; else $andor = ' AND ';
            $where_in .= "$andor`text`$collate $exp :where_r";
        }
        if ($null) {
            $where_in = 'NULL';
            $stmt_array = array();
        } else {
            $between_flag = true;
            if (empty($since)) {
                if (empty($until)) {
                    $between_flag = false;
                } else {
                    $since = '1000-01-01 00:00:00';
                }
            } else {
                $utspl = explode('_', $since);
                $utcnt = count($utspl);
                if ($utcnt > 1) {
                    if ($utcnt > 2) date_default_timezone_set($utspl[2]);
                    $since = date("Y-m-d H:i:s", strtotime($utspl[0].' '.$utspl[1]));
                } else if ($utcnt > 0) {
                    $since = date("Y-m-d 00:00:00", strtotime($utspl[0]));
                }
            }
            if ($between_flag) {
                if (empty($until)) {
                    $until = '9999-12-31 23:59:59';
                } else {
                    $utspl = explode('_', $until);
                    $utcnt = count($utspl);
                    if ($utcnt > 1) {
                        if ($utcnt > 2) date_default_timezone_set($utspl[2]);
                        $until = date("Y-m-d H:i:s", strtotime($utspl[0].' '.$utspl[1]));
                    } else if ($utcnt > 0) {
                        $until = date("Y-m-d 23:59:59", strtotime($utspl[0]));
                    }
                }
                $between = '`new` BETWEEN :since AND :until';
                $stmt_array['since'] = $since;
                $stmt_array['until'] = $until;
            }
        }
    } else {
        $search_table = $_main_table;
        $search_thread = $_main_thread;
        $idt = to_idt($search_id);
        if (is_array($idt)) {
            list('id' => $search_id, 'posts' => $search_posts, 'thread' => $search_thread, 'table' => $search_table) = $idt;
            $stmt_array_id['id'] = $search_id;
            $where_id = "WHERE `ID` = :id";
            $select_value = '*';
            $exec['direct_index'] = array('select' => $select_value, 'tables' => $search_table, 'where' => $where_id, 'stmt' => $stmt_array_id);
        }
        $stmt_array = array();
        $all = false;
        if (($thread_pos = strpos($search_q, 'thread:')) !== false) {
            preg_match('/\:(\w+)/', substr($search_q, $thread_pos), $pos_m);
            $all = ($pos_m[1] === 'all');
            array_push($thread_filters, $pos_m[1]);
        } else {
            if ($search_thread !== '') {
                array_push($thread_filters, $search_thread);
            }
        }
        global $cws_deleted_viewtree;
        if (!isset($cws_deleted_viewtree)) $cws_deleted_viewtree = false;
        $viewtree = ($page > 0 && $where_id !== '') || ((isset($cws_deleted_viewtree)) ? $cws_deleted_viewtree : false);
        if ($viewtree) {
            $order = 'ASC';
            $search_idposts = "_$search_id|$search_posts";
            $search_posts_ex = $search_posts.'_'.$search_thread;
            $search_posts_all = $all ? "$search_idposts|$search_posts_ex" : $search_idposts;
            list($exp, $stmt_array['id_target'], $collate) = $_db->reg_classify(
                "/(^|[\s ])(>>)($search_posts_all)($|[\:\s\r\n ])/i",
                "%>>$search_posts"
            );
            $where_in = "`text`$collate $exp :id_target";
            if ($exp === 'LIKE') {
                $stmt_array['id_target_i'] = '%>>_'.$search_id;
                $where_in .= " OR `text` $exp :id_target_i";
                if ($all) {
                    $stmt_array['id_target_x'] = '%>>'.$search_posts_ex;
                    $where_in .= " OR `text` $exp :id_target_x";
                }
                $id_t_i = 0; $id_t_list = array("\r%", "\n%", ":%", " %");
                while ($id_t_i < count($id_t_list)) {
                    $id_t_n = $id_t_i + 1;
                    $stmt_array['id_target_'.$id_t_n] = '%>>'.$search_posts.$id_t_list[$id_t_i];
                    $where_in .= " OR `text` $exp :id_target_$id_t_n";
                    $stmt_array['id_target_i'] = '%>>_'.$search_id.$id_t_list[$id_t_i];
                    $where_in .= " OR `text` $exp :id_target_i$id_t_n";
                    if ($all) {
                        $stmt_array['id_target_x'.$id_t_n] = '%>>'.$search_posts_ex.$id_t_list[$id_t_i];
                        $where_in .= " OR `text` $exp :id_target_x$id_t_n";
                    }
                    ++$id_t_i;
                }
                $where_in = "($where_in)";
            }
            $highlight_q = "";
        } else {
            $where_in = "NULL";
        }
    }
    $return_array['all'] = false;
    if (isset($_only_threads)) {
        if (is_array($_only_threads)) {
            $thread_filters = $_only_threads;
        } else {
            $thread_filters = array($_only_threads);
        }
    }
    $tf_i = 0; $tf_c = count($thread_filters);
    if ($tf_c !== 0) {
        while (++$tf_i <= $tf_c) {
            $tf_v = $thread_filters[$tf_i - 1];
            if ($tf_v === 'all') {
                $thread_filters = $thread_list;
                $return_array['all'] = true;
                break;
            } 
        }
    }
    if ($return_array['all']) {
        $thread_filter = '';
    } else {
        $thread_filter = where_filter_thread($stmt_array, array('threads' => $thread_filters, 'not_threads' => $thread_nots));
        if ($thread_filter !== '') {
            $where_in = $thread_filter . ($where_in !== '' ? " AND $where_in" : '');
        }
    }
    $use_tables = use_tables($thread_filters);
    if (!isset($between_flag)) $between_flag = false;

    $where = ($between_flag || $where_in !== '') ? " WHERE $where_in" : "";
    if ($where_in !== '' && $between_flag) $between = " AND $between";
    $union = union_all($use_tables, $where.$between);
    $sql = "SELECT COUNT(*) AS `count` FROM $union";
    $search_count = intval((function($result){
        return is_null($result) ? 0 : $result->fetch()['count'];
    })($_db->execute($sql, $stmt_array)));
    $_page_limit = intval($_page_limit);
    $max_page = intval(($search_count - 1) / $_page_limit) + 1;
    if ($page > $max_page) $page = $max_page; elseif ($page < 1) $page = 1;
    $limit_from = ($page - 1) * $_page_limit;
    $stmt_array['limit_from'] = $limit_from;
    $stmt_array['limit_max'] = $_page_limit;
    $exec['main'] = array('select' => '*', 'tables' => $use_tables, 'threads' => $thread_filters, 'not_threads' => $thread_nots, 'where' => "$where$between ORDER BY `new` $order LIMIT :limit_from, :limit_max", 'stmt' => $stmt_array);
    $return_array = array_merge($return_array, array('exec' => $exec, 'flag' => $flag_array, 'max' => $max_page, 'limit' => $_page_limit, 'count' => $search_count, 'page' => $page, 'highlight' => $highlight_q, 'order' => $order, 'option' => $other_opt));
    return $return_array;
}

function union_all($a, $d = '', $o = '') {
    if (!is_array($a)) $a = array($a);
    $n = count($a);
    if ($n < 2) { return ('`'.implode('`', $a).'` '.$d); }
    $i = 0; $c = array();
    while($i < $n) { $t = $a[$i]; $c[] = "SELECT * FROM `$t` AS _T_$i"; ++$i; }
    return '('.implode(' UNION ALL ', $c).") AS _T_union $d";
}
function exec_to_select($exec) {
    $sql = 'SELECT '.$exec['select'].' FROM '.union_all($exec['tables'], $exec['where']);
    // var_dump($sql);
    // var_dump($exec['stmt']);
    return $sql;
}
function exec_to_arr($_db, $exec) {
    return $_db->execute_all(exec_to_select($exec), $exec['stmt']);
}
function get_thread_search($param = null){
    global $db;
    if (!is_array($param)) {
        if (is_null($param)) {
            $param = array();
        } else {
            $param = array();
        }
    }
    $_db = (isset($param['db']) ? $param['db'] : $db);

    $tmp_err = $_db->dbi->err_dump;
    $arr = array();
    $_db->dbi->err_dump = false;
    $exec_arr = get_exec($param);
    $exec = $exec_arr['exec'];
    
    if (isset($exec['direct_index'])) {
        $di_arr = exec_to_arr($_db, $exec['direct_index']);
        $c = count($di_arr);
        for($i = 0; $i < $c; ++$i) {
            $di_arr[$i]['direct_index'] = true;
        }
        $arr = array_merge($arr, $di_arr);
    }
    if (isset($exec['main'])) {
        $arr = array_merge($arr, exec_to_arr($_db, $exec['main']));
    }
    $exec_arr['data'] = $arr;
    $_db->dbi->err_dump = $tmp_err;
    return $exec_arr;
}
function get_thread($param = null){
    $thread_search_arr = get_thread_search($param);
    $arr = array();
    $flag_array = $thread_search_arr['flag'];
    if (isset($flag_array['server'])) {
        array_push($arr, array(
            'text' => var_export($_SERVER, true),
            'posts'=>'server', 'name' => 'サーバーリスト'
        ));
    }
    if (isset($flag_array['ua'])) {
        array_push($arr, array(
            'text' => var_export($_SERVER['HTTP_USER_AGENT'], true),
            'posts'=>'UA', 'name' => 'ユーザーエージェント'
        ));
    }
    if (isset($flag_array['cookie'])) {
        array_push($arr, array(
            'text' => var_export($_COOKIE, true),
            'posts'=>'Cookie', 'name' => 'クッキー'
        ));
    }
    if (isset($flag_array['sitemap'])) {
        array_push($arr, array(
            'text' => $_sitemap,
            'posts'=>'sitemap', 'name' => 'サイトマップ', 'time' =>''
        ));
    }
    if (isset($flag_array['memory'])) {
        global $os_mode;
        $out = '';
        switch ($os_mode) {
            case 'linux':
                exec('free', $out);
            break;
        }
        if (is_array($out)) $out = implode("\n", $out);
        if ($out !== '') {
            array_push($arr, array(
                'text' => $out, 'ID'=>'memory', 'name' => 'メモリ', 'time' =>''
            ));
        }
    }
    if (isset($flag_array['temperature'])) {
        global $os_mode;
        switch ($os_mode) {
            case 'linux':
                $round = 1000;
                exec('cat /sys/class/thermal/thermal_zone0/temp', $temp_raw);
            break;
            case 'windows':
                $round = 100;
                exec('wmic /namespace:\\\\root\\wmi PATH MSAcpi_ThermalZoneTemperature get CurrentTemperature', $temp_raw);
            break;
        }
        $temps = array();
        foreach ($temp_raw as $temp) {
            if (is_numeric($temp)) {
                $temps[] = '%Core '.count($temps).' : '.round(($temp/$round),2).' ℃';
            }
        }

        if (count($temps) > 0) {
            array_push($arr, array(
                'text' => implode("\n", $temps), 'ID'=>'temperature', 'name' => '温度', 'time' =>''
            ));
        }
    }
    $thread_search_arr['data'] = array_merge($arr, $thread_search_arr['data']);
    unset($thread_search_arr['exec']);
    return $thread_search_arr;
}
?>