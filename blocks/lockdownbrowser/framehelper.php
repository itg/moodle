<!DOCTYPE html>
<html>
<!--
// Respondus LockDown Browser Extension for Moodle
// Copyright (c) 2011-2020 Respondus, Inc.  All Rights Reserved.
// Date: April 10, 2020.
-->
<head>
<META http-equiv="Content-Type" content="text/html;charset=UTF-8">
</head>
<body onload="parentIframeResize()">
<script type="text/javascript">
function parentIframeResize() {
    var height = getParam('height');
    parent.parent.ldb_ResizeLDBFrame(height);
}

function getParam(name) {
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regexS = "[\\?&]" + name + "=([^&#]*)";
    var regex = new RegExp(regexS);
    var results = regex.exec(window.location.href);
    if (results == null) {
        return "";
    }
    else {
        return results[1];
    }
}
</script>
</body>
</html>
