var CHAR_SIZE = 8;
// convert array to binary little-endian format.
function array2binl(str)
{
    var bin = Array();
    var mask = (1 << CHAR_SIZE) - 1;
    for(var i = 0; i < str.length * CHAR_SIZE; i += CHAR_SIZE) {
        bin[i>>5] |= (str[i / CHAR_SIZE] & mask) << (i%32);
    }
    return bin;
}
// get URL parameter
function get_param(name)
{
    if (location.href.indexOf("?") >= 0) {
        var query=location.href.split("?")[1];
        var params=query.split("&");
        for (var i = 0; i < params.length; i ++) {
            value_pair=params[i].split("=");
            if (value_pair[0] == name)
                return unescape(value_pair[1]);
        }
    }
    return "";
}
// calculate the CHAP response
function hotspot_response(password, challenge)
{
    var ch = Array();
    for (var i = 0; i < challenge.length; i += 2) {
        var num = challenge.substr(i, 2);
        ch[i/2] = parseInt(num, 16);
    }
    var arr = [0];
    for (var i = 0; i < password.length; i ++) {
        arr[i+1] = password.charCodeAt(i);
    }
    arr=arr.concat(ch);
    var bin = array2binl(arr);
    return binl2hex(core_md5(bin, arr.length * CHAR_SIZE));
}
// hotspot login form handler - Calculate CHAP response and clear temp password
// Called before submit
function hotspot_login(challenge)
{
    var password=document.hotspot_login_form.temp_password.value;
    document.hotspot_login_form.response.value = hotspot_response(password, challenge);
    document.hotspot_login_form.temp_password.value=password.replace("/./g","*");
}
// print location description
function location_description()
{
    document.write(get_param('locationdesc'));
}
// output hotspot login form
// hidden fields: userurl - the URL requested by user
// response - the CHAP response
// on submit: call hotspot_login
function hotspot_login_form()
{
    document.write('<form name="hotspot_login_form" method="get"');
    document.write('action="http://' + get_param('uamip') + ':' + get_param('uamport') + '/login"');
    document.write('onsubmit="return hotspot_login(\'' + get_param('challenge') + '\');">\n');
    document.write('<input type="hidden" name="userurl" value="' + get_param('userurl') + '">\n');
//    document.write('<input type="hidden" name="response">\n');
}
function popup_status_window()
{
    var status="http://" + get_param("uamip") + ":" +
        get_param("uamport") + "/status";
    var w = window.open(status, "smallwin", "width=400,height=300,status=yes,resizable=yes");
}
function message()
{
    var res = get_param("res");
    if (res == "failed") {
        document.write("Login Failed");
    }
}
function redirection()
{
    var res=get_param('res');
    if (res == "Authentication successful!") {
        var redirurl = get_param('redirurl');
        var userurl = get_param('userurl');
        if (redirurl != "") {
            document.location = redirurl;
        } else if (userurl != "") {
            document.location = userurl;
        }
        popup_status_window();
    }
}
function session_start_time()
{
    var st = get_param("starttime");
    if (st != "") {
        var d = new Date(parseInt(st) * 1000);
        document.write(d.toLocaleTimeString());
    }
}
function logout_link()
{
    document.write('<a href="http://' + get_param('uamip') + ':' + get_param('uamport') + '/logout">Logout</a>');
}
function update_display()
{
    var res = get_param("res");
    var display = {"login":"none", "logoff":"none","status":"none"};
    if (res == "already" ) {
        display["status"] = "block";
    } else if (res == "logoff") {
        display["logoff"] = "block";
    } else if (res == "notyet" || res == "failed") {
        display["login"] = "block";
    }
    for (d in display) {
        document.getElementById(d).style.display = display[d];
    }
}