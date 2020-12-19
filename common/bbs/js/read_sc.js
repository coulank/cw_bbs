if (typeof(window.other_sc) !== 'object') window.other_sc = new Object();
if (typeof(window.other_sc.doc) !== 'object') window.other_sc.doc = new Array();
window.post_list = null;
window.post_cursor = -1;
window.post_pre = -1;
window.session_key = '';
window.set_post_cursor = function(){
    if (post_cursor !== post_pre) {
        cws.storage.session.out(session_key, post_cursor);
    }
}
window.other_sc.doc.push(function(e){
    switch (e.keyCode) {
        case 72: // h
            if (mf_g_mode) window.jump_url = './'; break;
        case 84: // t
            if (mf_g_mode) window.jump_url = '?q=%23タグ'; break;
        case 86: // v
            if (mf_g_mode) { window.jump_url = '?q=filter%3avideos'; }
        break;
        case 65: // a
            if (mf_g_mode) { window.jump_url = '?q=filter%3aaudios'; }
        break;
        case 190: // .
            if (natural_mode) { location.href = ''; }
        break;
        case 74: // j
            if (natural_mode) {
                window.posts_back();
            }
        break;
        case 75: // k
            if (natural_mode) {
                window.posts_forward();
            }
        break;
        case 13: // Enter
            set_post_cursor();
            if (natural_mode && document.activeElement.tagName.toUpperCase() === 'BODY') {
                post_list[post_cursor].querySelector('a').click();
            }
        break;
        case 85: // back -> u
            if (natural_mode) {
                window.page_back();
            }
        break;
        case 73: // next -> i
            if (natural_mode) {
                set_post_cursor();
                window.page_forward();
            } else if (mf_g_mode) {
                window.jump_url = '?q=filter%3aimages';
            }
        break;
    }
});
window.posts_back = function(){ window.postsing(-1); };
window.posts_forward = function(){ window.postsing(1); };
window.postsing = function(plus) {
    if (post_cursor < 0) return;
    plus = cws.check.nullvar(plus, 0);
    if (post_list[post_cursor].classList.contains('select_target')) {
        post_list[post_cursor].classList.remove('select_target'); 
        post_cursor += plus;
        if (post_cursor < 0) post_cursor = 0;
        if (post_cursor >= post_list.length) {
            post_cursor = post_list.length - 1;
            setTimeout(function(){ window.scrollTo({top: post_list[post_cursor].offsetTop}); }, 0);
        }
    }
    cws.dom.setupFocus(post_list[post_cursor]);
    if (post_cursor >= 0) post_list[post_cursor].classList.add('select_target'); 
}
window.page_back = function(){ window.paging(-1, true); };
window.page_forward = function(){ window.paging(1); };
window.paging = function(plus, auto_back){
    plus = cws.check.nullvar(plus, 0);
    auto_back = cws.check.nullvar(auto_back, false);
    var r = request;
    r['p'] = cws.check.key(r, 'p', 1);
    var next_page = Number(r['p']) + plus;
    var max_page = Number(cws.check.nullvar(window.max_page, 1));
    if (max_page <= 1 || next_page <= 1)
        next_page = null;
    else if (next_page > max_page)
        next_page = max_page;
    cws.jump.location(cws.to.setQuery({p: next_page}), auto_back);
}
window.cookieScroll = function(value){
    if (cws.cookie.get(value)) {
        var elms = document.getElementsByName(value);
        if (elms.length > 0) {
            var options = {top: elms[0].offsetTop};
            setTimeout(function(){ window.scrollTo(options); }, 0);
        }
        cws.cookie.remove(value);
    }
}
window.smooth_scroll_top = function(retval){
    var option = new Object();
    option.behavior = 'smooth';
    option.top = 0;
    window.scrollTo(option);
    return retval;
}
window.switch_search_option = function(e, open, close){
    var e_flag = typeof(e) !== 'undefined';
    if (old_mode) {
        var hidden = false;
        search_option.className = search_option.className.replace(/(^|\s)(hidden)($|\s)/,
        function(m, m1, m2, m3){
            hidden = true;
            return m1;
        });
        if (!hidden) {
            search_option.className = search_option.className + ' hidden';
        }
    } else if (search_option.classList.contains('hidden')) {
        search_option.classList.remove('hidden');
        close = (typeof(close) === 'undefined') ? '△' : close;
        if (e_flag) e.value = close;
    } else {
        search_option.classList.add('hidden');
        open = (typeof(open) === 'undefined') ? '▽' : open;
        if (e_flag) e.value = open;
    }
    if (window.innerHeight < (search_option.offsetTop + search_option.clientHeight)) {
        search_option.style.height = (window.innerHeight - search_option.offsetTop - 8) + "px";
    } else {
        search_option.style.height = '';
    }
}
window.clear_search_option = function(){
    if (confirm('検索の入力を空にしますか？')) {
        var option_form = search_option.parentElement;
        var value_clear = function(target, value, child, offset) {
            if (typeof(target) === 'string') { target = option_form[target]; }
            if (typeof(target) === 'object') {
                if (typeof(value) === 'undefined') { value = ''; }
                if (typeof(child) !== 'undefined') {
                    if ((target.value !== target[child]) || !(typeof(offset) === 'undefined' ? true : offset)) {
                        value = target[child];
                    }
                }
                target.value = value;
            }
        }
        value_clear(search, '');
        value_clear('page', '1');
        value_clear('thread', '');
        value_clear('limit', '', 'defaultValue');
        value_clear('view', '');
        value_clear('filter', '');
        value_clear('order', '');
        value_clear('since', '');
        value_clear('until', '');
        option_form.regex.checked = false;
    }
}
window.search_action = function(){
    try{
    var option_form = search_option.parentElement;
    var search_value_flag = false;
    if (option_form.regex.checked) {
        search.name = 'r';
        var hidden_elem = document.getElementById('search_hidden');
        hidden_elem.name = 'q';
        search_value_flag |= (search.value !== '');
        search = hidden_elem;
    }
    if (!search_option.className.match(/(^|\s)(hidden)($|\s)/)) {
        var option_insert = function(target, option, delete_value) {
            if (typeof(option_form[target]) !== 'undefined'
                && option_form[target].value !== option_form[target].defaultValue
                && option_form[target].value !== '') {
                if (typeof(option) === 'undefined') option = '';
                if (typeof(delete_value) === 'undefined') delete_value = '';
                search.value = search.value.replace(new RegExp('([\\s()]|)(\-?' + target + '\\:\\S*|$)'), function(m, p1, p2)
                {
                    var set_value = option_form[target].value;
                    if (delete_value === set_value) {
                        return '';
                    } else {
                        var bp = p1; if (bp === '') bp = ' ';
                        if (option === 'time') { set_value = set_value.replace('T', '_'); }
                        return bp + target + ':' + set_value;
                    }
                });
            }
        };
        option_insert('thread', '', 'default');
        option_insert('limit', '', '-1');
        option_insert('view', '', 'none');
        option_insert('filter', '', 'none');
        option_insert('order', '', 'default');

        option_insert('since', 'time'); option_insert('until', 'time');
        search.value = search.value.replace(/^\s+/, '');
    }
    if ((option_form.page.value > 1) && (search.value === search.defaultValue || option_form.page.value !== option_form.page.defaultValue)) {
        var page_elem = document.getElementById('search_page');
        page_elem.name = 'p';
        page_elem.value = option_form.page.value;
        search_value_flag |= true;
    }

    if (search.value === '') { search.name = ''; } else { search_value_flag |= true; }
    if (search_value_flag) {
        search.form.submit();
    } else {
        location.href = search.form.action.replace(/([^\?]*)\?[\s\S]*$/, '$1');
    }
    return false;
    } catch(e) { console.log(alert(e)); return false; }
}
window.search_load = function(){
    var option_form = search_option.parentElement;
    var option_load = function(target, option) {
        if (typeof(option_form[target]) !== 'undefined') {
            if (typeof(option) === 'undefined') option = '';
            if (m = search.value.match(new RegExp('(^|[\\s()]+)' + target + '\\:(\\S*)'))) {
                if (option === 'time') { m[2] = m[2].replace('_', 'T'); }
                option_form[target].value = m[2];
            }
        }
    };
    option_load('thread');
    option_load('limit');
    option_load('view');
    option_load('filter');
    option_load('order');
    option_load('since', 'time'); option_load('until', 'time');
    var re = cws.check.key(request, ['r', 're'], '');
    if (re !== '') {
        document.getElementById('search_hidden').value = search.value;
        search_option.parentElement.regex.checked = 'checked';
        search.value = re;
    }
    var relm = option_form.regex;
    var telm = option_form.thread;
    if (relm !== null && telm !== null) {
        relm.onchange = function(){
            if (relm.checked && telm.value === '') telm.value = 'all';
        }
    }
}
cws.ready(function(){
    search_load();
    if (typeof(document.querySelector)!=='undefined') {
        var m = null;
        if (m = cws.v.href.match(/[^:/](\/.*)$/)){
            window.session_key = m[1];
        }
        post_list = document.querySelectorAll(".post_data");
        for (var i = 0; i < post_list.length; i++){
            v = post_list[i];
            if(v.classList.contains("post_main")){
                window.post_pre = i;
                break;
            }
        }
        var session_value =  cws.check.nullvar(cws.storage.session.get(window.session_key, true), '');

        if (session_value === '') {
            window.post_cursor = window.post_pre;
        } else {
            window.post_cursor = parseInt(session_value);
        }
        if (window.post_cursor < 0 && post_list.length > 0) {
            window.post_cursor = window.post_list.length - 1;
            window.post_pre = window.post_cursor;
        }
        if (window.post_cursor >= 0) {
            cws.dom.setupFocus(post_list[window.post_cursor]);
        }
    }
    cookieScroll('first');
    cookieScroll('last');
}, window);
