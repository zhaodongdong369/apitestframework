<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="测试首页">
    <meta name="author" content="zhaodongdong">

    <title>测试接口</title>

    <!-- Bootstrap core CSS -->
    <link href="/assert/css/bootstrap.min.css" rel="stylesheet">
    <!-- Json view -->
    <link href="/assert/css/jquery.jsonview.css" />
    <script type="text/javascript" src="http://code.jquery.com/jquery.min.js"></script>
    <script type="text/javascript" src="/assert/js/jquery.jsonview.js"></script>
    <script type="text/javascript">
    $(function() {
 <?php
if(!empty($result)) {
    echo '$("#json").JSONView('.$result.')';
}
?>
    });

    </script>
 
    <!-- Custom styles for this template -->
    <link href="/assert/css/navbar.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <div class="container">

      <!-- Static navbar -->
      <nav class="navbar navbar-default">
        <div class="container-fluid">
          <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
              <span class="sr-only">Toggle navigation</span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">测试中心</a>
          </div>
          <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
              <li><a href="/bacenter/addba">添加ba认证资料</a></li>
              <li><a href="/bacenter/balist">ba列表</a></li>
              <li><a href="/bacenter/addapi">添加api接口</a></li>
              <li><a href="/bacenter/apilist">api列表</a></li>
              <li class="active"> <a href="/bacenter/testapi">测试接口</a></li>
              <li><a href="/user/logout">注销</a></li>
            </ul>

          </div><!--/.nav-collapse -->
        </div><!--/.container-fluid -->
      </nav>
<script language="javascript">
function changeF() {
    document.getElementById('api').value = document.getElementById('sel').options[document.getElementById('sel').selectedIndex].text;
    document.getElementById('args').value = document.getElementById('sel').options[document.getElementById('sel').selectedIndex].value;
} 
</script> 
</head>
<body>

<form action="/bacenter/testapi" method="POST" role="form">
    <div class="form-group">
        <label for="api">接口<label>
        <select  id="sel" class="form-control" onchange="changeF();">
            <option value=''>请选择</option>
        <?php 
            if(!empty($apis)) {
                foreach($apis as $api) {
        ?>
       <option value="<?php echo htmlentities($api->args);?>"><?php echo $api->url;?></option>
       <?php
                }
            }
        ?>
        </select>
    </div>
    <div class="form-group">
        <input type="url" name="api" id="api" value="<?php echo set_value('api');?>" class="form-control input-group-lg" />
    </div>
    <div class="form-group">
        <label for="args">参数</label>
        <textarea type="text" name="args" class="form-control" rows=14   id="args"><?php echo set_value('args');?></textarea>
    </div>
    <div class="form-group">
        <label for="ba">ba</label>
        <select name="ba" class="form-control">
        <?php 
            if(!empty($bas)) {
                foreach($bas as $ba) {
        ?>
            <option value="<?php echo $ba->id;?>"><?php echo $ba->appkey;?></option>
       <?php
                }
            }
        ?>
        </select>
    </div>
    <div class="form-group">
        <label for="httpmethod">HTTP方式</label>
        <select name="http" class="form-control">
            <option value="get">GET</option>
            <option value="post">POST</option>
        </select> 
    <div>
    <button type="submit" class="btn btn-default"/>测试</button>
</form>
<h5>结果</h5>
<div id="json">
</div>
</body>
</html>

