var _submit = function(url, data, method) {
    var form_elem = {'action': url, 'method': method};
    const tgttype = typeof(submit_target);
    switch (tgttype){
        case "object", "undefined":
            break;
        default:
            form_elem['target'] = submit_target;
    }
    var $form = $('<form/>', form_elem);
    for(var key in data) {
            $form.append($('<input/>', {'type': 'hidden', 'name': key, 'value': data[key]}));
    }
    $form.appendTo(document.body);
    $form.submit();
};
var get_submit = function(url, data) {
    _submit(url, data, 'get');
};
var post_submit = function(url, data) {
    _submit(url, data, 'post');
};
function absolutePath(path) {
    const baseUrl = location.href;
    const url = new URL(path, baseUrl);
    return url.href;
}
function linkgo(atag) {
    const hrefattr = atag.attr('href');
    // #の場合はイベントチェック、イベントがあればイベントを実行
    if (hrefattr == '#')
    {
        if (atag[0].onclick !== null)
        {
            atag[0].onclick();
        }
    }
    const href = absolutePath(hrefattr);
    const target = atag.attr('target');
    if (typeof(target) === 'undefined') {
        window.location = href;
    } else {
        window.open(href, target);
    }
}
