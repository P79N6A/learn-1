<!--<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no"/>
    <title>东哥聊天室</title>
    <style type="text/css">
        body, p {
            margin: 0px;
            padding: 0px;
            font-size: 14px;
            color: #333;
            font-family: Arial, Helvetica, sans-serif;
        }

        #ltian, .rin {
            width: 98%;
            margin: 5px auto;
        }

        #ltian {
            border: 1px #ccc solid;
            overflow-y: auto;
            overflow-x: hidden;
            position: relative;
        }

        #ct {
            margin-right: 111px;
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
        }

        #us {
            width: 110px;
            overflow-y: auto;
            overflow-x: hidden;
            float: right;
            border-left: 1px #ccc solid;
            height: 100%;
            background-color: #F1F1F1;
        }

        #us p {
            padding: 3px 5px;
            color: #08C;
            line-height: 20px;
            height: 20px;
            cursor: pointer;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        #us p:hover, #us p:active, #us p.ck {
            background-color: #069;
            color: #FFF;
        }

        #us p.my:hover, #us p.my:active, #us p.my {
            color: #333;
            background-color: transparent;
        }

        button {
            float: right;
            width: 80px;
            height: 35px;
            font-size: 18px;
        }

        input {
            width: 100%;
            height: 30px;
            padding: 2px;
            line-height: 20px;
            outline: none;
            border: solid 1px #CCC;
        }

        .rin p {
            margin-right: 160px;
        }

        .rin span {
            float: right;
            padding: 6px 5px 0px 5px;
            position: relative;
        }

        .rin span img {
            margin: 0px 3px;
            cursor: pointer;
        }

        .rin span form {
            position: absolute;
            width: 25px;
            height: 25px;
            overflow: hidden;
            opacity: 0;
            top: 5px;
            right: 5px;
        }

        .rin span input {
            width: 180px;
            height: 25px;
            margin-left: -160px;
            cursor: pointer
        }

        #ct p {
            padding: 5px;
            line-height: 20px;
        }

        #ct a {
            color: #069;
            cursor: pointer;
        }

        #ct span {
            color: #999;
            margin-right: 10px;
        }

        .c2 {
            color: #999;
        }

        .c3 {
            background-color: #DBE9EC;
            padding: 5px;
        }

        .qp {
            position: absolute;
            font-size: 12px;
            color: #666;
            top: 5px;
            right: 130px;
            text-decoration: none;
            color: #069;
        }

        #ems {
            position: absolute;
            z-index: 5;
            display: none;
            top: 0px;
            left: 0px;
            max-width: 230px;
            background-color: #F1F1F1;
            border: solid 1px #CCC;
            padding: 5px;
        }

        #ems img {
            width: 44px;
            height: 44px;
            border: solid 1px #FFF;
            cursor: pointer;
        }

        #ems img:hover, #ems img:active {
            border-color: #A4B7E3;
        }

        #ems a {
            color: #069;
            border-radius: 2px;
            display: inline-block;
            margin: 2px 5px;
            padding: 1px 8px;
            text-decoration: none;
            background-color: #D5DFFD;
        }

        #ems a:hover, #ems a:active, #ems a.ck {
            color: #FFF;
            background-color: #069;
        }

        .tc {
            text-align: center;
            margin-top: 5px;
        }
    </style>
</head>

<body>
<div id="ltian">
    <div id="us" class="jb"></div>
    <div id="ct"></div>
    <a href="javascript:;" class="qp" onClick="this.parentNode.children[1].innerHTML=''">清屏</a>
</div>
<div class="rin">
    <button id="sd">发送</button>
    <p><input id="nrong"></p>
</div>
<script src="./root/resource/js/jquery.min.js"></script>
<script>
    if (typeof(WebSocket) == 'undefined') {
        alert('你的浏览器不支持 WebSocket ，推荐使用Google Chrome 或者 Mozilla Firefox');
    }
    $(function () {
        var url = 'ws://127.0.0.1', port = '8888';
        var ws = new WebSocket(url + ':' + port);
        $('#ltian').height((document.documentElement.clientHeight - 70)+'px');
        ws.onopen = function () {
            if (ws.readyState == 1) {
                alert('握手成功了!')
            }
        }
    })
</script>
</body>
</html>-->