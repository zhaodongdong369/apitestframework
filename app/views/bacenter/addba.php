<html>
<head>
<title>添加ba认证资料</title>
</head>
<body>

<?php echo validation_errors(); ?>

<?php echo form_open('/bacenter/addba'); ?>

<h5>appkey</h5>
<input type="text" name="appkey" value="<?php echo set_value('appkey'); ?>" size="50" />

<h5>secretkey</h5>
<input type="text" name="secretkey" value="<?php echo set_value('secretkey');?>" size="50" />
<div><input type="submit" value="Submit" /></div>
</form>
<br/>
<a href="/bacenter">ba中心</a><br>
<a href='/bacenter/balist'>ba列表</a>
</body>
</html>
