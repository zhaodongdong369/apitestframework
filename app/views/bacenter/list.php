<html>
    <head>
        <title> ba列表</title>
    </head>
    <body>
    ba列表 <br/>
    <?php
    if(!empty($bas)) {
        foreach($bas as $ba) {
            echo "appkey: ",$ba->appkey,"    secretkey: ",$ba->secretkey,"<br>";
        }
    }
    ?>
    <br/>
   <a href="/bacenter">ba中心</a><br>
    <a href="/bacenter/addba">添加ba</a>

   </body>
</html>

