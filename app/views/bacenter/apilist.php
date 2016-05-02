<html>
    <head>
        <title> api列表</title>
    </head>
    <body>
    api列表 <br/>
    <?php
    if(!empty($apis)) {
        foreach($apis as $api) {
            echo "url: ",$api->url,"  args: ",$api->args,"<br/>";
        }
    }
    ?>
    <br/>
   <a href="/bacenter">ba中心</a><br>
    <a href="/bacenter/addapi">添加api</a>

   </body>
</html>

