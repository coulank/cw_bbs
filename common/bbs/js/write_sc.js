window.old_mode = false;
window.mf_g = false;
window.mf_g_mode = false;
window.natural_mode = false;
window.text_activate = false;
window.form = null;
window.textarea = null;
window.update_target = null;
window.search = null;
window.search_option = null;
window.activeFocus = null;
window.textarea_default = '';
window.jump_url = '';

if (typeof(window.other_sc) !== 'object') window.other_sc = new Object();
if (typeof(window.other_sc.doc) !== 'object') window.other_sc.doc = new Array();
if (typeof(window.other_sc.form) !== 'object') window.other_sc.form = new Array();

window.get_next_alarm = function(alarm_str){
    alarm_str = cws.check.nullvar(alarm_str, cws.check.nullvar(cws.cookie.get('alarm'), '3'));
    var now = new Date();
    if (alarm_str !== null) {
        var set_hour = parseInt(alarm_str);
        var next_hour = (Math.floor(now.getHours() / set_hour) + 1) * set_hour;
        var next_time = new Date(new Date().setHours(next_hour, 0, 0, 0));
        return next_time;
    } else {
        return now;
    }
};

var alarm_next_time_g = get_next_alarm();
var now_time_g = new Date;
window.alarm_elem = null;

window.get_alarm_body = function(alarm_str){
    now_time_g = new Date();
    var now = now_time_g;
    var next_time = alarm_next_time_g;
    var diff_time = next_time - now;
    if (diff_time < 0) {
        alarm_next_time_g = get_next_alarm(alarm_str);
        next_time = alarm_next_time_g;
        diff_time = next_time - now;
    }
    var next_hour = next_time.getHours();
    if (diff_time >= 0) {
        var diff_hour = Math.floor(diff_time / (1000 * 60 * 60));
        var diff_min = Math.floor(diff_time / (1000 * 60)) - diff_hour * 60;
        var diff_sec = Math.floor(diff_time / (1000)) - (diff_hour * 60 + diff_min) * 60;
        return next_hour + '時まで 後 '
            + ((diff_hour > 0) ? diff_hour + '時間' : '')
            + ((diff_min > 0) ? diff_min + '分' : '')
            + ((diff_hour < 1 && diff_min < 10) ? diff_sec + '秒' : '');
    } else {
        return '';
    }
};
window.post_tick_func = function(){
    alarm_elem = document.getElementById('post_alarm');
    if (alarm_elem !== null) {
        var alarm_body = document.getElementById('body_alarm');
        var alarm_date = document.getElementById('date_alarm');
        if (alarm_body !== null) {
            var update_alarm = function(){
                alarm_body.innerHTML = get_alarm_body();
                if (alarm_date !== null) alarm_date.innerHTML = cws.get.date('Y-m-d H:i:s', now_time_g);
            };
            setTimeout(
                function() {
                    update_alarm();
                    setInterval(update_alarm, 1000);
                }
            , 1000 - now_time_g.getMilliseconds());
        }
    }
    size_elem = document.getElementById('post_size');
    if (size_elem !== null) {
        var size_body = document.getElementById('body_size');
        var set_size_value = function(){
            size_body.innerHTML =
                "画面サイズ一覧<br/><ul>" +
                "<li>ブラウザ内の画面<ul><li>幅: " + window.innerWidth + "</li>" +
                "<li>高さ: " + window.innerHeight + "</li></ul>" +
                "<li>ウィンドウの画面<ul><li>幅: " + window.outerWidth + "</li>" +
                "<li>高さ: " + window.outerHeight + "</li></ul>" +
                "</ul>"
            ;
        }
        set_size_value();
        window.addEventListener('resize', set_size_value);
    }
}

window.old_mode = /msie|dsi|wiiu|\(nintendo 3ds/i.test(navigator.userAgent);
var old_result = false;
window.old_func = function(_old_mode){
    if (typeof(_old_mode) === 'undefined') _old_mode = old_mode;
    if (_old_mode && !old_result) {
        if (document.getElementById('last_element') === null) {
            old_result = false;
        } else {
            var form = document.getElementById('post_form');
            var form_submit = document.getElementById('form_submit');
            if (form !== null) {
                document.body.className += ' old_mode';
                var file_selector = document.getElementById('file_selector');
                file_selector.name = 'upload_file';
                file_selector.removeAttribute('multiple');
                form.removeAttribute('onsubmit');
                form.setAttribute('enctype', 'multipart/form-data');
                form.text.removeAttribute('contenteditable');
                form_submit.disabled = false;
            }
            if (alarm_elem === null) post_tick_func();
            old_result = true;
        }
    } else {
        old_result = true;
    }
}
var limit = 30000, ivms = 100;
var loop_id = setInterval(function(){
    old_func();
    if (old_result || (limit -= ivms) < 0) { clearInterval(loop_id); }
}, ivms);
window.old_func();
cws.ready(function(){
    if (alarm_elem === null) post_tick_func();
    var scrTop = 150;
    form = document.getElementById('post_form');
    search = document.getElementById('search_keyword');
    search_option = document.getElementById('search_option');
    if (old_result) {
        var top_fade = document.querySelector('.top-fade');
        var scrollFade = function(){
            if (this.scrollY > scrTop){
                if (!top_fade.classList.contains('fade')) {
                    top_fade.classList.add('fade');
                }
            } else {
                if (top_fade.classList.contains('fade')) {
                    top_fade.classList.remove('fade');
                }
            }
        };
        scrollFade();
        window.addEventListener('scroll', scrollFade);
    }
    window.request = cws.v.request;
    if (form !== null && typeof(form.text) !== 'undefined') textarea = form.text;
    if (search !== null)
    cws.event('keydown', function(e){
        switch (e.code) {
            case 'Escape':
                if (activeFocus != null) {
                    if (isNaN(activeFocus)) {
                        cws.dom.setupFocus(activeFocus);
                    } else {
                        postsing();
                    }
                } else {
                    var post_main = document.querySelector(".post_main");
                    if (post_main !== null) {
                        cws.dom.setupFocus(post_main);
                    } else {
                        cws.dom.setupFocus(document.body);
                    }
                }
                e.returnValue = false;
            break;
        }
    }, search);
    var get_postdata_element = function(id){
        return document.getElementById('post_'+id.toString());
    };
    window.update_postdata_textarea = function(id, rewrite_text) {
        var postdata = document.querySelector('.post_data.update_target');
        var calling_elem = null;
        rewrite_text = cws.check.nullvar(rewrite_text, true);
        if (postdata !== null) {
            postdata.classList.remove('update_target');
            calling_elem = postdata.querySelector('.update_calling_elem');
            if (calling_elem !== null) calling_elem.innerHTML = '▽';
            if (update_target.value == id) {
                update_target.value = '';
                textarea.value = textarea_default;
                form_submit.disabled = true;
                return false;
            }
        }
        postdata = get_postdata_element(id);
        if (postdata !== null) {
            update_target.value = id;
            postdata.classList.add('update_target');
            calling_elem = postdata.querySelector('.update_calling_elem');
            var rewrite_value = textarea.value;
            if (calling_elem !== null) calling_elem.innerHTML = '△';
            if (id === 'alarm') {
                rewrite_value = cws.check.nullvar(cws.cookie.get('alarm'), '3');
            } else {
                rewrite_value = postdata.dataset["textOrigin"];
            }
            if (rewrite_text) {
                form_submit.disabled = true;
                textarea.value = rewrite_value;
            } else {
                form_submit.disabled = (textarea.value === rewrite_value);
            }
            setTimeout(function(){ textarea.focus(); },0);
        }
        return false;
    };
    window.delete_action = function(id) {
        var postdata = get_postdata_element(id);
        if (confirm("削除しますか？\n"+id.toString()+":"+postdata.dataset["textOrigin"])) {
            cws.ajax.run({request: {delete_action: '', id: id},
            onload:function(e){
                location.reload();
            }});
        }
        return false;
    };
    window.addr_action = function(addr, blacklist) {
        cws.ajax.run({request: {addr_action: '', addr: addr, blacklist: blacklist},
        onload:function(e){
            location.reload();
        }});
        return false;
    };

    if (form === null) return;
    if (typeof(form.update_target) !== 'undefined'){
        update_target = form.update_target;
    } else{
        update_target = document.createElement('input');
        update_target.type = 'hidden';
        update_target.name = 'update_target';
        form.appendChild(update_target);
    }
    var select_first = false;
    var select_last = false;
    var pos_mouse_down = false;
    cws.cookie.enable = true;
    cws.storage.def_json = false;
    var storage_text_key = 'thread_text';
    var storage_upt_key = 'thread_upt';
    var storage_selection_key = 'thread_sel';
    var up_list = document.getElementById('up_list');
    var form_submit = document.getElementById('form_submit');
    form_submit.disabled = true;
    form_submit.classList.remove('disabled');
    var storage_text = cws.storage.get(storage_text_key, true);
    var querylike = cws.check.key(request, 'q');
    textarea_default = textarea.value;
    if (querylike !== '' || add_search_q !== '') {
        querylike = cws.to.asctochar(querylike);
        if (add_search_q !== '') querylike = add_search_q + ' ' + querylike;
        querylike = ' ' + querylike;
        var reg = null;
        try{
            reg = new RegExp('(?<!\\sNOT)\\s+(\\#|@)\\S*', 'g');
        } catch (e) {
            reg = /\s+(\#|@)\S*/g;
        }
        querylike = cws.check.nullvar(querylike.match(reg), Array());
        var hash_list = Array();
        var at_list = Array();
        
        for(var i = 0; i < querylike.length; i++) {
            var ql_m = querylike[i].match(/\s*(.)(.*)$/);
            var mark = ql_m[1];
            if (mark === '#') {
                hash_list.push(mark+ql_m[2]);
            } else if (mark === '@') {
                at_list.push(mark+ql_m[2]);
            }
        }
        var hash_str = hash_list.join(' ');
        var at_str = at_list.join(' ');
        
        var textarea_rpl = textarea.value.replace(/\s+$/, '');
        if (hash_str !== '') {
            if (at_str !== '') {
                textarea.value = at_str + ' ' + hash_str + '\n';
                if (textarea_rpl !== '') {
                    textarea.value = textarea_rpl + ' ' + textarea.value;
                }
                select_last = true;
            } else {
                textarea.value = textarea_rpl + ' ' + hash_str;
                if (textarea_rpl === '') {
                    select_first = true;
                } else {
                    textarea.value = textarea.value + '\n';
                    select_last = true;
                }
            }
        } else {
            if (at_str !== '') {
                if (textarea_rpl === '') {
                    textarea.value = at_str + ' ';
                } else {
                    textarea.value = textarea_rpl + ' ' + at_str + '\n';
                }
                select_last = true;
            }
        }
        textarea_default = textarea.value;
    } else {
        if(cws.check.key(request, 'id', '') != '') {
            select_last = true;
        }
    }
	if (storage_text !== '') {
        textarea.value = storage_text;
        form_submit.disabled = textarea.value === textarea_default;
    }
    var storage_upt = cws.storage.get(storage_upt_key, true);
	if (storage_upt !== '') {
        update_target.value = storage_upt;
        update_postdata_textarea(storage_upt, false);
        activeFocus = post_cursor;
    }
    var storage_sel = cws.storage.get(storage_selection_key, true, true);
    if (storage_sel !== null) {
        pos_mouse_down = false;
        textSetPosition(textarea, storage_sel);
    } else {
        if (select_last) {
            textSetPosition(textarea, textarea.textLength);
        } else {
            textSetPosition(textarea, 0);
        }
    }
    textarea.addEventListener('mousedown', function(e){
        if (select_first || select_last) {
            pos_mouse_down = true;
        }
    });
    textarea.addEventListener('mouseup', function(e){
        if (pos_mouse_down) {
            if (select_first) {
                textSetPosition(textarea, 0);
                select_first = false;
            } else if (select_last) {
                textSetPosition(textarea, textarea.length);
                select_last = false;
            }
            pos_mouse_down = false;
        }
    })

    textarea.addEventListener('focus', function(e){
		textarea.classList.remove('focus');
        if (select_first || select_last) {
            setTimeout(function(){ select_first = false; select_last = false; },1);
        }
    });
    window.add_image = function(src, name){
        if (old_mode) return;
        cws.v.loop_i = cws.check.key(cws.v, 'loop_i', 0);
        if (typeof(name) === 'undefined') name = null;
        name = cws.check.nullvar(name, 
            cws.get.date_32() + '_' + (++cws.v.loop_i) + '.' + cws.to.base64toExt(src));
        var list = up_list;
        if (list === null) return;
        var div = document.createElement('div');
        var hover = document.createElement('div');
        hover.classList.add("remove");
        hover.classList.add("unselectable");
        hover.innerText = "×";
        var obj = document.createElement('object');
        obj.dataset.name = name;
        obj.data = src;
        obj.type = src.split(':')[1].split(';')[0];
        div.appendChild(hover);
        div.appendChild(obj);
        list.appendChild(div);
        hover.addEventListener("click", function(){
            var parent = this.parentElement;
            parent.parentElement.removeChild(parent);
            submit_able();
        })
        submit_able();
    }
    function submit_able() {
        if (textarea.value === '' && up_list.children.length === 0) {
            form_submit.disabled = true;
        } else {
            form_submit.disabled = false;
        }
        return !form_submit.disabled;
    }
    add_images = function(items){
        var ret = false;
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            if (!ret && item.kind === ('string')) ret = true;
            if (/image\/|video\//.test(item.type)) {
                var imageFile = item.getAsFile();
                var fr = new FileReader();
                fr.onload = function(e) {
                    var base64 = e.target.result;
                    add_image(base64);
                };
                fr.readAsDataURL(imageFile);
            }
        }
        return ret;
    }
    var file_selector = document.querySelector('#file_selector');
    if (file_selector !== null) {
        file_selector.addEventListener('change', function(e){
            if (old_mode) return;
            var files = e.target.files;
            for (var i = 0; i < files.length; i++){
                var fileReader = new FileReader();
                var file = files[i];
                fileReader.name = file.name;
                fileReader.onload = function(){
                    cws.to.base64toBlob(this.result);
                    add_image(this.result, this.name);
                }
                fileReader.readAsDataURL(file);
            }
            file_selector.value = '';
        });
    }
    textarea.addEventListener("paste", function(e){
        return add_images(e.clipboardData.items);
    });
	function showDropping() {
		textarea.classList.add('dropover');
	}
	function hideDropping() {
		textarea.classList.remove('dropover');
    }
    textarea.addEventListener('dragover', function(e){
		showDropping();
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
		textarea.classList.add('focus');
	});
	textarea.addEventListener('dragleave', function(e) {
		hideDropping();
		textarea.classList.remove('focus');
	});
    textarea.addEventListener("drop", function(e){
        e.preventDefault();
        add_images(e.dataTransfer.items);
        hideDropping();
		textarea.classList.remove('focus');
    });
    textarea.addEventListener("input", function(e){
        submit_able();
    });
    var do_submit = function(e) {
        if (form_submit.disabled) {
            document.activeElement.blur();
            location.reload();
        } else {
            form_submit.focus();
            if (e.target.form.onsubmit === null || typeof(e.target.form.onsubmit) === 'undefined') {
                e.target.form.submit();
            } else {
                e.target.form.onsubmit();
            }
        }
    }
    form.onkeydown = function(e) {
        if (e.ctrlKey) {
            if (e.code === 'KeyS') {
                do_submit(e);
                e.keyCode = 0;
                return false;
            }
        }
    }
    var set_textarea = function(select){
        textSetSelection(select, textarea);
        submit_able();
    }
    var under = document.querySelector('body>div.under');
    var large_toggle = function(){
        textarea.classList.toggle('large');
        if (under !== null) under.classList.toggle('large');
    }
    form.addEventListener('keydown', function(e){
		if (e.altKey) {
			switch (e.code) {
                case 'KeyQ':
                case 'KeyX':
                case 'KeyZ':
					document.activeElement.blur();
					e.returnValue = false;
                break;
            }
            // リスト化など
            var inc = 0;
            var str = '';
			switch (e.code) {
                case 'BracketRight':
                case 'Digit0':
                    inc = -1;
                break;
                case 'Minus':
                    inc = 1;
                    str = '-';
                break;
                case 'Equal':
                    inc = 1;
                    str = '+';
                break;
                case 'Quote':
                    inc = 1;
                    str = '*';
                break;
                case 'Backslash':
                    inc = 1;
                    str = '%';
                break;
            }
            if (inc !== 0) {
                var select = textGetSelection(textarea, true);
                select[1] = select[1].replace(/(\n|^)([+\-*%]*)/g,
                    function(m0, m1, m2) {
                        if (inc > 0) {
                            m2 += str.repeat(inc);
                        } else {
                            m2 = m2.slice(0, inc);
                        }
                        return m1 + m2;
                    });
                set_textarea(select);
            }
		}
        switch (e.code) {
            case 'Enter':
                if (e.ctrlKey) {
                    do_submit(e);
                } else if (e.altKey) {
                    large_toggle();
                }
            break;
            case 'Escape':
                if (activeFocus != null) {
                    if (isNaN(activeFocus)) {
                        cws.dom.setupFocus(activeFocus);
                    } else {
                        postsing();
                    }
                } else {
                    var post_main = document.querySelector(".post_main");
                    if (post_main !== null) {
                        cws.dom.setupFocus(post_main);
                    } else {
                        cws.dom.setupFocus(document.body);
                    }
                }
                e.returnValue = false;
            break;
            case 'KeyP':
                if (e.altKey) {
                    textarea.value = textarea.value.replace(/(#!\s*\S*\s+|@tos\s+|@tos$|^)/, "@tos ");
                }
            break;
            case 'KeyB':
                if (e.altKey) {
                	var select = textGetSelection(textarea);
                	select[1] = '[b:' + select[1] + ']';
                	set_textarea(select);
                }
            break;
            case 'KeyI':
                if (e.altKey) {
                	var select = textGetSelection(textarea);
                	select[1] = '[i:' + select[1] + ']';
                	set_textarea(select);
                }
            break;
            case 'KeyL':
                if (e.altKey) {
                	var select = textGetSelection(textarea);
                	select[1] = '[' + select[1] + ']';
                	set_textarea(select);
                }
            break;
            case 'KeyS':
                if (e.altKey) {
                	var select = textGetSelection(textarea);
                	select[1] = '[s:' + select[1] + ']';
                	set_textarea(select);
                }
            break;
            case 'KeyU':
                if (e.altKey) {
                	var select = textGetSelection(textarea);
                	select[1] = cws.to.chartoasc(select[1]);
                	set_textarea(select);
                }
            break;
            case 'Slash':
                if (e.altKey) {
                	var select = textGetSelection(textarea, true);
                	select[0] = select[0] + "/*\n";
                    select[2] = "\n*/" + select[2];
                	set_textarea(select);
                }
            break;
            case 'Period':
                if (e.altKey) {
                	var select = textGetSelection(textarea, true);
                	select[0] = select[0] + ">>\n";
                	select[2] = "\n<<" + select[2];
                	set_textarea(select);
                }
            break;
            case 'Comma':
                if (e.altKey) {
                    var esc_ci = String.fromCharCode(17), esc_co = 18;
                    var esc_qi = String.fromCharCode(19), esc_qo = 20;
                    var esc_multi = esc_ci + esc_qi;
                    var text_value = textarea.value.replace(/(\/\*|>>)/g, function(m) {
                        switch(m) {
                            case '/*': return esc_ci.repeat(m.length);
                            case '>>': return esc_qi.repeat(m.length);
                        }
                    });
                    var text_value1 = text_value.slice(0,
                        textarea.selectionEnd + text_value.slice(textarea.selectionEnd).match(/(\n|$)/).index);
                    var text_match = text_value1.match(new RegExp('(^|\n)([' + esc_multi + ']+)(\n|$)([^' + esc_multi + ']*)$'));
                    if (text_match === null) {
                        text_match = text_value.match(new RegExp('(^|\n)([' + esc_multi + ']+)(\n|$)'));
                    }
                    if (text_match !== null) {
                        var text_search = '';
                        switch(text_match[2].slice(0, 1)){
                            case esc_ci:
                                text_search = '\\*\\/';
                            break;
                            case esc_qi:
                                text_search = '<<';
                            break;
                            default:
                            break;
                        }
                        var text_value2_slice = text_match.index + text_match[1].length + text_match[2].length + text_match[3].length;
                        var text_value2 = text_value.slice(text_value2_slice);
                        var text_match2 = text_value2.match(
                            new RegExp("(^|\n)(" + text_search + ")(\n|$)")
                        );
                        if (text_match2 === null) text_match2 = text_value2.match(/()()(\n*)$/);
                        var select = (new Array(
                            textarea.value.slice(0, text_match.index) + text_match[1],
                            textarea.value.slice(text_value2_slice).slice(0, text_match2.index),
                            text_match2[3] + textarea.value.slice(
                                text_value2_slice
                                + text_match2.index + text_match2[0].length
                            )
                        ));
                        set_textarea(select);
                    }
                }
            break;
            // ～と〜いずれかに統一する秘密のショートカット
            case 'Backquote':
                if (e.altKey && e.ctrlKey) {
                    var text_position = textGetPosition(textarea);
                    if (e.shiftKey) {
                        textarea.value = textarea.value.replace(/〜/ug, '～');
                    } else {
                        textarea.value = textarea.value.replace(/～/ug, '〜');
                    }
                    textSetPosition(textarea, text_position);
                }
            break;
        }
        for (var i = 0; i < window.other_sc.form.length; i++) {
            window.other_sc.form[i](e);
        }
    });
    window.post_action = function(e) {
        var form_media = new Array();
        var objs = up_list.querySelectorAll("img, video, object");
        var obj = null, src = ''; 
        for (var i = 0; i < objs.length; i++) {
            obj = objs[i];
            if (obj.tagName === 'OBJECT') {
                src = obj.data;
            } else {
                src = obj.src;
            }
            form_media[i] = {src: src, name: obj.dataset.name};
        }
        if (e.text.value === '' && form_media.length === 0) return false;
        form_media['no-load'] = '';
        var go_firstp = function(){
            if (update_target.value == '') {
                if (cws.check.exists(request, 'id')) {
                    cws.cookie.out('last');
                } else {
                    cws.cookie.out('first');
                }
                location.href = cws.to.setQuery({p: null}) + location.hash;
            }
            location.reload();
        }
        if (select_first) { go_firstp(); return false; }
        cws.ajax.run({form:e, request: form_media, timeout: 90000,
        onload:function(e){
            textarea.value = '';
            go_firstp();
            // console.log(e);
        }});
        return false;
    }
    window.addEventListener('beforeunload', function(e){
        if (textarea === document.activeElement) {
            cws.storage.out(storage_text_key, textarea.value);
            cws.storage.out(storage_upt_key, update_target.value);
            cws.storage.out(storage_selection_key, textGetPosition(textarea), true);
            set_post_cursor();
        }
    });
    var file_selector_button = document.getElementById('file_selector_button');
    file_selector_button.onclick = function(e){
        file_selector.click();
    };
    form.oncontextmenu = function(e){
        if (e.target.id === 'write_space') {
            large_toggle();
            textarea.focus();
            return false;
        }
    }
    file_selector_button.oncontextmenu = function(e){
        var multiple = file_selector.hasAttribute("multiple");
        if (!multiple) {
            file_selector.setAttribute("multiple");
        }
        var accept = cws.check.key(file_selector, 'accept', false);
        if (accept) {
            file_selector.accept = '';
        }
        file_selector.click();
        if (accept) {
            file_selector.accept = accept;
        }
        if (!multiple) {
            file_selector.removeAttribute("multiple");
        }
        return false;
    };
    textarea.blur();
});
var textGetPosition = function(textElement){
	textElement = cws.check.nullvar(textElement, document.querySelector('textarea'));
    return [textElement.selectionStart, textElement.selectionEnd];
}
var textSetPosition = function(textElement, arg_position){
	textElement = cws.check.nullvar(textElement, document.querySelector('textarea'));
    var arg_position = cws.check.nullvar(arg_position, 'last');
    position = [0,0];
    if (typeof(arg_position) == 'object') {
        if (typeof(arg_position.length) == 'undefined') {
            position[0] = cws.check.key(arg_position, [0,'first'], 0);
            position[1] = cws.check.key(arg_position, [1,'last'], 0);
        } else {
            position = arg_position.concat(position);
        }
    } else {
        if (arg_position == 'last') {
            position[0] = textElement.textLength;
            position[1] = position[0];
        } else if (arg_position == 'all') {
            position[1] = textElement.textLength;
        } else if (!isNaN(arg_position)) {
            position[0] = Number(arg_position);
            position[1] = position[0];
        }
    }
    textElement.selectionStart = position[0];
    textElement.selectionEnd = position[1];
};
var textSetSelection = function(setValue, textElement, rangeFlag){
	textElement = cws.check.nullvar(textElement, document.querySelector('textarea'));
	setValue = cws.check.nullvar(setValue, null);
    setType = typeof(setValue);
    var select = null;
    
	if (setValue === null) { return textElement; }
	else if(setType !== 'string') {
		if (typeof(setValue.join) === 'function') {
            select = setValue;
			setValue = setValue.join('');
		} else {
			setValue = setValue.toString();
		}
	}
    if (select === null) {
        var firstPos = textElement.selectionStart;
        var endPos = firstPos;
        if (rangeFlag) endPos = textElement.selectionEnd - (textElement.value.length - setValue.length);
        textarea.value = setValue;
        textElement.selectionStart = firstPos;
        textElement.selectionEnd = endPos;
    } else {
        textarea.value = setValue;
        textarea.selectionStart = select[0].length;
        textarea.selectionEnd = textarea.selectionStart + select[1].length;
    }
	return textElement;
};
var textGetSelection = function(textElement, lineFlag){
    lineFlag = cws.check.nullvar(lineFlag, false);
	var retval = new Array(
	textElement.value.slice(0, textElement.selectionStart),
	textElement.value.slice(textElement.selectionStart, textElement.selectionEnd),
    textElement.value.slice(textElement.selectionEnd));
    if (lineFlag) {
        var mstr = '';
        if ((mstr = retval[0].match(/[^\n]*$/)[0]) !== '') {
            textElement.selectionStart -= mstr.length;
            retval[0] = textElement.value.slice(0, textElement.selectionStart);
            retval[1] = mstr + retval[1];
        }
        if ((mstr = retval[2].match(/^[^\n]*/)[0]) !== '') {
            textElement.selectionEnd += mstr.length;
            retval[2] = textElement.value.slice(textElement.selectionEnd);
            retval[1] = retval[1] + mstr;
        }
    }
    return retval;
}
cws.event('keydown', function(e){
    var do_mf_g = false;
    window.text_activate = typeof(document.activeElement.type) !== 'undefined'
        && document.activeElement.type.match(/text|password|email|tel|url|date|time|number|color/);
    window.mf_g_mode = mf_g && !e.ctrlKey && !text_activate;
    window.natural_mode = !mf_g && !e.ctrlKey && !text_activate;
    switch (e.code) {
        case 'KeyG':
            if (!text_activate) do_mf_g = true;
        break;
        case 'KeyN':
            if (natural_mode && textarea !== null) {
                if (!Object.prototype.toString.call(document.activeElement).match('HTMLBody')) {
                    activeFocus = document.activeElement;
                } else if (post_cursor !== post_pre) {
                    activeFocus = post_cursor;
                } else {
                    activeFocus = null;
                }
                textarea.focus();
                e.returnValue = false;
            }
        break;
        case 'KeyY':
            if (natural_mode && textarea !== null && post_cursor >= 0) {
                if (post_list[post_cursor].dataset.postId !== 'info-hide') {
                    window.activeFocus = post_cursor;
                    update_postdata_textarea(post_list[post_cursor].dataset.postId, true);
                }
            }
        break;
        case 'Slash': // / -> search
            if (natural_mode && search !== null) {
                if (!Object.prototype.toString.call(document.activeElement).match('HTMLBody')) {
                    activeFocus = document.activeElement;
                } else if (post_cursor !== post_pre) {
                    activeFocus = post_cursor;
                } else {
                    activeFocus = null;
                }
                search.focus();
                e.returnValue = false;
            }
        break;
        case 'F4': // F4 -> image
            // document.querySelector('#file_selector').click();
        break;
    }
    for (var i = 0; i < window.other_sc.doc.length; i++) {
        window.other_sc.doc[i](e);
    }
    if (window.jump_url !== '') {
        try {
            if (e.shiftKey) {
                var jump_request = cws.get.request(jump_url, false);
                if (typeof(jump_request.q) !== 'undefined') {
                    var req_str_q = cws.to.chartoasc(cws.check.key(request, 'q', '')).replace(' ', '+');
                    if (!(req_str_q + '+').match(jump_request.q + '+')) {
                        req_str_q = req_str_q + (req_str_q === '' ? '' : '+') + jump_request.q;
                    }
                    location.href = '?q=' + req_str_q;
                    window.jump_url = '';
                }
            }
            if (window.jump_url !== '') {
                location.href = window.jump_url;
            }
        } catch (e) {
            console.log(e);
        }
        window.jump_url = '';
    }
    mf_g = (do_mf_g) ? (!mf_g) : false;
//         console.log(e.code);
}, document);
