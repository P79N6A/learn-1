var ws = new WebSocket("ws://192.168.195.131:9501");

ws.onopen = function() {
    alert("Opened");
    ws.send("0000000");
};

ws.onmessage = function (evt) {
    alert(evt.data);
};

ws.onclose = function() {
    alert("Closed");
};

ws.onerror = function(err) {
    console.log("Error: " + err);
};