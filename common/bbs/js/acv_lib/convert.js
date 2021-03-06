function date_format(format_str, date = new Date()){
    var d = date;
    var rp = format_str;
    var year = d.getFullYear();
    rp = rp.replace(/Y/, year);
    rp = rp.replace(/y/, year.toString().slice(-2));
    var month = d.getMonth() + 1;
    rp = rp.replace(/n/, month);
    rp = rp.replace(/m/, ("0" + month).slice(-2));
    var day = d.getDate();
    rp = rp.replace(/j/, day);
    rp = rp.replace(/d/, ("0" + day).slice(-2));
    var week = d.getDay();
    rp = rp.replace(/w/, week);
    rp = rp.replace(/WW/, [ "日", "月", "火", "水", "木", "金", "土" ][week]);
    var hour = d.getHours();
    var hour2 = hour % 12;
    var hour2i = (hour / 12 < 1) ? 0 : 1;
    rp = rp.replace(/G/, hour);
    rp = rp.replace(/g/, hour2);
    rp = rp.replace(/H/, ("0" + hour).slice(-2));
    rp = rp.replace(/h/, ("0" + hour2).slice(-2));
    rp = rp.replace(/AA/, ["午前", "午後"][hour2i]);
    var minute = d.getMinutes();
    rp = rp.replace(/I/, minute);
    rp = rp.replace(/i/, ("0" + minute).slice(-2));
    var second = d.getSeconds();
    rp = rp.replace(/S/, second);
    rp = rp.replace(/s/, ("0" + second).slice(-2));

    rp = rp.replace(/A/, ["AM", "PM"][hour2i]);
    rp = rp.replace(/a/, ["am", "pm"][hour2i]);
    rp = rp.replace(/W/, [ "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" ][week]);
    return rp;
}
var getLocation = function(href) {
    var l = document.createElement("a");
    l.href = href;
    return l;
};