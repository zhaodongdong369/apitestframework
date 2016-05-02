<html>
<head>
<title>添加api接口</title>
</head>
<body>

<?php echo validation_errors(); ?>

<?php echo form_open('/bacenter/addapi'); ?>

<h5>Url</h5>
<input type="text" name="url" value="<?php echo set_value('url'); ?>" size="50" />

<h5>参数demo，a=1&b=2,或json格式</h5>
<input type="text" name="args" value="<?php echo set_value('args');?>" size="50" />
<div><input type="submit" value="Submit" /></div>
</form>
<br/>
<a href="/bacenter">ba中心</a><br>
<a href='/bacenter/balist'>ba列表</a>
</body>
</html>
