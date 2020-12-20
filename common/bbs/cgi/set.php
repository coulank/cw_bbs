<?php
namespace cws;
?><!DOCTYPE html>
<html>
<head>
<?php
$add_autotag = (isset($add_autotag) && is_array($add_autotag)) ? $add_autotag : array();
if ($owner_login) {
    $add_autotag[] = "/common/bbs/js/owner_sc.js";
}

if (!isset($this_define)) $this_define = array();
$this_define = array_merge($this_define, array(
    'app-title' => '掲示板',
    'twitter:creator' => '@tos', 'twitter:site' => '@tos', 
    'twitter:card' => 'summary',
    // 'og:title' => '', 'og:description' => '',
)) + array(
    'title' => '匿名掲示板',
    'description' => '',
    'url' => null,
    'manifest' => '/common/bbs/manifests/thread.json',
    'image' => '/common/images/icon/bbs_icon.png',
    'theme-color' => 'black',
);
$theme_prefer = get_val($_COOKIE, 'theme_prefer', '');
$title_length = 32;
$title_add_text = '';
if (isset($cws_request['id']) && !empty($cws_request['id'])) {
    $arr_top_cnt = count($arr) - $search_count;
    if ($arr_top_cnt > 0) {
        $arr_top = null;
        foreach($arr as &$v) {
            if (isset($v['direct_index']) && $v['direct_index']) {
                $arr_top = $v; break;
            }
        }
        if (is_null($arr_top)) $arr_top = $arr[1];
        $arr_top_image = null;
        $arr_top_html = implode(set_autolink($arr_top['text'], array('cbn_hatena' => true)), '');
        if (preg_match('/img\s.*alt=\s*[\'\"]([^\'\"]*)[\'\"]\s.*src\s*=\s*[\'\"]([^\'\"]*)[\'\"]/', $arr_top_html, $m)) {
            $arr_top_alt = $m[1];
            $this_define['image'] = $m[2];
        } else {
            $arr_top_alt = $arr_top['ID'].': '.$arr_top['text'];
        }
        $title_add_text = $arr_top_alt;
    }
} else if(isset($cws_request['q']) && $cws_request['q'] !== '') {
    $title_add_text = $cws_request['q'];
}
if ($title_add_text !== '') {
    if (mb_strlen($title_add_text) > $title_length) {
        $title_add_text = mb_substr($title_add_text, 0, $title_length) . '…';
    }
    $this_define['title'] = $title_add_text . ' - ' . $this_define['title'];
}

$set_autotag = array(
    -1 => array('add_date' => false),
    'define' => $this_define,
    'charset', 'viewport-w',
    'title', 'description',
    'og', 'twitter', 'app',
    'theme-color', 'manifest', 'sw',
    'icon',
    "/common/css/reset.css",
    '/common/css/general.css',
    "/common/cw_init/cws.js",
    "/common/bbs/js/write_sc.js",
    "/common/bbs/js/read_sc.js",
    "/common/bbs/css/main.css",
    array(
        ($theme_prefer === 'dark') ? "/common/bbs/css/dark.css" :
        (($theme_prefer === 'light') ? "" : "/common/bbs/css/prefer.css")
    , "id" => "prefer-css"),
    $add_autotag
);
if ($q_or_id_enable) {
    foreach (array('manifest', 'sw') as $value) {
        unset($set_autotag[array_search($value, $set_autotag)]);
    }
    $set_autotag = array_merge($set_autotag);
}
set_autotag($set_autotag);
$id_isreq = isset($cws_request['id']);
$q_value = get_val($cws_request, 'q', '');
?>
</head>
<body name='first'>
    <div class='main'>
    <div>
    <div class='set fixed right top unselectable search'>
        <button class='jump top-fade' onclick="return smooth_scroll_top(false);">▲</button>
        <form id='form_search_option' onsubmit='return search_action();'>
            <input type='button' class='button top' onclick="switch_search_option(this);" value='▽'>
            <div id='search_option' class='hidden text_left'>
                <div>
                    <label><span>regex:</span><input type='checkbox' name='regex'></label>
<?php if ($visible_thread_view) { ?>
                    <label><span>thread:</span><select name='thread'>
                        <option value=''></option>
<?php foreach($thread_list as &$a_thread) { ?>
                        <option value='<?php echo $a_thread ?>'><?php echo $a_thread ?></option>
<?php } ?>
                        <option value='all'>all</option>
                        <option value='default'>default</option>
                    </select></label>
<?php } ?>
                </div>
                <div>
                    <label><span>filter:</span><select name='filter'>
                        <option value=''></option>
                        <option value='images'>Images</option>
                        <option value='videos'>Videos</option>
                        <option value='audios'>Audios</option>
                        <option value='links'>Links</option>
                        <option value='response'>Response</option>
                        <option value='none'>None</option>
                    </select></label>
                    <label><span>page:</span><input type='number' name='page' min='<?php echo ($max_page > 0) ? 1 : $max_page; ?>' max='<?php echo $max_page; ?>' value='<?php echo $page; ?>'></label>
                </div>

                <div>
                    <label><span>order:</span><select name='order'>
                            <option value=''></option>
                            <option value='asc'>ASC</option>
                            <option value='desc'>DESC</option>
                            <option value='default'>DEFAULT</option>
                        </select></label>
                    <label><span>limit:</span><input type='number' name='limit' min='-1' value='<?php if ($request_enable) echo $page_limit; ?>'></label>
                </div>
                <div>
                    <label><span>view:</span><select name='view'>
                        <option value=''></option>
                        <option value='ua'>UserAgent</option>
<?php if ($private_owner_login){ ?>
                        <option value='sv'>Server</option>
<?php } ?>
                        <option value='cookie'>Cookie</option>
                        <option value='size'>Size</option>
                        <option value='alarm'>Alarm</option><?php if ($os_mode === 'linux') { ?>
                        <option value='memory'>Memory</option><?php } if ($os_mode !== '') { ?>
                        <option value='temperature'>Temperature</option><?php } ?>

                        <option value='none'>None</option>
                    </select></label>
                </div>
                <div>
                    <label><span>since:</span><input type='datetime-local' name='since'></label>
                </div>
                <div>
                    <label><span>until:</span><input type='datetime-local' name='until'></label>
                </div>
                <div class='center search_buttons'>
                    <input type='button' class='submit button' value='Clear' onclick='clear_search_option();'>
                    <input type='submit' class='submit button' value='Search'>
                </div>
            </div>
        </form>
        <form id='form_search_main' method='get' autocomplete="off" onsubmit='return search_action();'>
            <input type='hidden' id='search_page' value='<?php echo $page; ?>'>
            <input type='hidden' id='search_hidden'>
            <input type='text' name='q' id='search_keyword' placeholder='キーワード検索' value="<?php echo str_replace('"', '&quot;', str_replace('&', '&amp;', $q_value)); ?>">
        </form>
        <span class='buttons paging'>
            <input type='button' onclick="page_back();" class='left button' value='＜'>
            <input type='button' class='right button' onclick="page_forward();" value='＞'>
        </span>
    </div><?php if ($ip_check && $postform_enable) { ?>
    <div class='post'>
        <form id='post_form' class='' method='post' action='' onsubmit='return post_action(this);'>
            <div class="upload">
                <div class="file_input">
                    <input type='file' id='file_selector' accept="<?php echo $accept; ?>" multiple>
                </div>
                <div class="up_list" id="up_list"></div>
            </div>
            <input type='hidden' name='update_target' value="">
            <input type='hidden' name='q' value="<?php echo get_val($cws_request, 'q', ''); ?>"><?php
            if (isset($cws_request['p'])) { ?>
            <input type='hidden' name='p' value="<?php echo($cws_request['p']); ?>"><?php }
            if (isset($cws_request['id'])) { ?>
            <input type='hidden' name='id' value="<?php echo($cws_request['id']); ?>"><?php } ?>
            <div class="write_space" id="write_space"><!--
                --><div class='right buttons'><input type='button' class="button unselectable" value='F' id="file_selector_button"></div><!--
                --><textarea contenteditable="true" name='text'><?php if ($id_isreq) echo '>>'.$cws_request['id'].' '; ?></textarea><!--
    --><div class='right buttons'><input class="button disabled" type="submit" value="▷" id='form_submit'></div><!--
            --></div>
        </form>
    </div><?php } ?>

    <div class='thread'>
<?php
        if ($asc_flag) {
            $cur_count = 1 + ($page - 1) * $page_limit;
        } else {
            $cur_count = $search_count - ($page - 1) * $page_limit;
        }
        $autolink = new AutoLink($arr,
            array(
                'id' => $cws_request['id'],
                'htmlsp' => $cws_thread_htmlsp,
                'q' => $cws_request['q'],
                'highlight_q' => $highlight_q
            ));
        $view_alarm = isset($option['view_alarm']);
        $view_task = isset($option['view_task']);
        $cookie_task = isset($_COOKIE['task']);
        $cookie_alarm = isset($_COOKIE['alarm']);
        $is_delete_tmp = $delete_tmp_path !== '';
        $var = array();
        $deftbl = false;    // 今のメインテーブルと異なる書き込みかどうか
        $autolink->g_opt['response_link'] = function($m) use (&$var, &$threads, &$deftbl) {
            $_deftbl = $deftbl;
            $add_query = '';
            // 他のテーブルへのリプライをクリックしたときは全テーブルを検索する
            // if (strpos($m[3], 'thread') !== false) {
            if (!is_numeric($m[3])) {
                $add_query = $add_query.'&q=thread:all';
                $_deftbl = false;
            }

            if ($_deftbl) {
                return $m[0].'_'.$var['thread'].$add_query;
            } else {
                return $m[0].$add_query;
            }
        };
        while($autolink->next()) {
            $var = $autolink->get_value();
            if (isset($var['posts'])) {
                $posts_value = $var['posts'];
                if (is_numeric($posts_value)) {
                    $deftbl = isset($var['thread']) && ($var['thread'] !== $main_thread);
                    if ($deftbl) {
                        $id_str = $posts_value.'_'.$var['thread'];
                    } else {
                        $id_str = $posts_value;
                    }
                } else {
                    $deftbl = false;
                    $id_str = $posts_value;
                }
            } else {
                $deftbl = false;
                $id_value = $var['ID'];
                if (is_numeric($id_value)) {
                    $id_str = '_'.$id_value;
                } else {
                    $id_str = $id_value;
                }
            }
            $_edit_mode = $edit_mode;
            $new = get_val($var, 'new', '');
            $new_str = (($new === '') ? $id_str.'_date' : $new);
            $text = $autolink->get_text();
            $text_origin = \htmlspecialchars($var['text']);
            $id_is_num =  preg_match('/\d+/', $id_str);
            $id_is_index = $id_str === 'index';

            if ($id_is_index && $is_delete_tmp) {
                $_edit_mode = false;
            }
            $id_is_alarm = $id_str === 'alarm';
            $id_is_task = $id_str === 'task';
            $strong_mode = ($id_is_alarm && $view_alarm);
            $center_mode = ($id_is_alarm);
            $id_is_edit_num = $_edit_mode && ($id_is_num||$id_is_index);
    
            if (!$id_isreq && $id_is_num) {
                if ($asc_flag) {
                    $tag_index = $cur_count++;
                } else {
                    $tag_index = $cur_count--;
                }
            } else {
                $tag_index = $id_str;
            }
            $direct_index = isset($var['direct_index']);
            if ($treemode && $direct_index) { $tag_index = 'tree_'.$tag_index; }
            $body_id_ins = " id='body_$id_str'";
        ?>
    <div class='post_data<?php if ($id_is_num) { ?> post_main<?php }
        ?>' id='post_<?php echo ($id_str) ?>' data-post-id='<?php echo ($id_str);
        ?>' data-text-origin="<?php echo $text_origin ?>">
        <div class='text'>
            <span class='body<?php if ($strong_mode) { echo(' h4'); } if ($center_mode) { echo(' center'); } ?>'<?php echo($body_id_ins); ?>><?php switch ($text) {
            case 'deleted-'.$owner_user: echo ('（削除されました）'); break;
            default: 
                echo ($text);
            break;
        } ?></span>
        </div>
        <div class='info<?php if ($id_str === 'info-hide') echo(' hidden'); ?>'>
            <span class='num'><?php echo $tag_index; ?>: </span><?php
        if ($setinfo_owner && $owner_user === $var['name']) { ?><span><?php echo('(管理人)'); ?></span><?php }
        else if ($cws_thread_name_visible) { ?><span><?php echo($var['name']); ?></span><?php }
        $date_id_ins =  " id='date_$id_str'";
        if ($id_is_num) {
            ?> <a href='?id=<?php echo($id_str); if ($direct_index){echo('&p=0');} ?>'><span class='date'><?php echo ($new_str); ?></span></a><?php
        } elseif ($id_is_index) {
            if ($id_is_index) {
                ?> <a href='?id=<?php echo($id_str); if ($direct_index){echo('&p=0');} ?>'><span class='date'><?php echo ($new_str) ?></span></a><?php
            }
            $theme = isset($_COOKIE['theme_prefer']) ? $_COOKIE['theme_prefer']: 'prefer';
            switch ($theme) {
                case 'dark':
                    ?> <a href='?q=theme%3Alight'><span class='date'>Dark</span></a><?php
                break;
                case 'light':
                    ?> <a href='?q=theme%3A'><span class='date'>Light</span></a><?php
                break;
                default:
                    ?> <a href='?q=theme%3Adark'><span class='date'>Prefer</span></a><?php
                break;
            }
            if ($task_enable && !$cookie_task) {
                if ($view_task) {
                    ?> <a href='?q=on%3Atask' onclick='return confirm("タスクを設置しますか？");'><span class='date'>＋</span></a><?php
                } else {
                    ?> <a href='?q=view%3Atask'><span class='date'>Ｔ</span></a><?php
                }
            }
            if ($alarm_enable && !$cookie_alarm) {
                if ($view_alarm) {
                    ?> <a href='?q=on%3Aalarm' onclick='return confirm("3時間毎のアラームを設置しますか？");'><span class='date'>＋</span></a><?php
                } else {
                    ?> <a href='?q=view%3Aalarm'><span class='date'>Ⅲ</span></a><?php
                }
            }
        } else if ($id_is_task) {
            ?> <a href='?q=view%3Atask'><span class='date'<?php echo($date_id_ins); ?>><?php echo ($new_str); ?></span></a><?php
        } else if ($id_is_alarm) {
            ?> <a href='?q=view%3Aalarm'><span class='date'<?php echo($date_id_ins); ?>><?php echo ($new_str); ?></span></a><?php
        } else {
            ?><span class='date'<?php echo($date_id_ins); ?>><?php echo ($new_str); ?></span><?php
        }
        if ($addr_edit_mode && isset($var['addr'])) {
            ?> <a href='' onclick='return addr_action("<?php echo $var["addr"]; ?>",<?php
                if (check_blacklist($var["addr"], true))
                { ?> 0);'>IP解除</a><?php } else { ?> 1);'>IP制</a><?php }
        }
        if ($id_is_edit_num) {
            ?> <a href='' onclick='return delete_action("<?php echo $id_str; ?>");'>×</a><?php
        } elseif ($id_is_task && $cookie_task) {
            ?> <a href='?q=off%3Atask' onclick='return confirm("タスクを非表示にしますか？");'>－</a><?php
            ?> <a href='' onclick='return delete_action("<?php echo $id_str; ?>");'>×</a><?php
        } elseif ($id_is_alarm && $cookie_alarm) {
            ?> <a href='?q=off%3Aalarm' onclick='return confirm("アラームを非表示にしますか？");'>－</a><?php
        } elseif ($id_is_index && $is_delete_tmp) {
            ?> <a href='?id=tmp&delete_action' onclick='return confirm("一時ファイルを削除しますか？");'>×</a><?php
        }
        if ($id_is_edit_num||$id_is_alarm||$id_is_task) {
            ?> <a href='' class='update_calling_elem' onclick='return update_postdata_textarea("<?php echo $id_str; ?>", true);'>▽</a><?php
        }?>
        </div>
    </div>
<?php } ?>
    </div>
    </div>
    </div>
    <div id='last_element' name='last' class='under'></div>
    <script>
    var min_page = 0;
    var max_page = <?php echo $max_page ?>;
    var add_search_q = '<?php echo $cws_add_search_q ?>';
    </script>
</body>
</html>