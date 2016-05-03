<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>用户登陆</title>

    <!-- Bootstrap -->
    <link href="/assert/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assert/css/signin.css" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <div class="container">
<?php echo validation_errors(); ?>
    <form class="form-signin" action="/user/register" method="POST">
        <label for="inputName" class="sr-only">用户名</label>
        <input type="text" id="inputName" name="username" class="form-control" value="<?php echo set_value('username'); ?>" placeholder="用户名" required autofocus>
        <label for="inputPassword" class="sr-only">密码</label>
        <input type="password" id="inputPassword" name="password" value="<?php echo set_value('password');?>" class="form-control" placeholder="Password" required>
        <label for="inputPassword" class="sr-only">确认密码</label>
        <input type="password" id="inputPassword" name="passconf" value="<?php echo set_value('passconf');?>" class="form-control" placeholder="Passconf" required>
        <label for="inputPassword" class="sr-only">邮箱</label>
        <input type="email" id="inputPassword" name="email" value="<?php echo set_value('email');?>" class="form-control" placeholder="Email" required>
        <button class="btn btn-lg btn-primary btn-block" type="submit">注册</button>
      </form>
</body>
</html>
