<html>
    <head>
        <title> api测试</title>
    </head>
    <body>
    api测试 <br/>
<?php echo validation_errors(); ?>

<?php echo form_open('/bacenter/testapi'); ?>

<h5>Api</h5>
<select name="api">
    <?php 
        if(!empty($apis)) {
            foreach($apis as $api) {
                ?>
                <option value="<?php echo $api->id;?>"><?php echo $api->url;?></option>
                <?php
            }
        }
        ?>
</select>
<h5>args</h5>
<input type="text" name="args" value="<?php echo set_value('args');?>" size="50" />
<h5>appkey</h5>
<select name="ba">
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
<h5>HTTP方式</h5>
<select name="http">
<option value="get">GET</option>
<option value="post">POST</option>
</select> 
<div><input type="submit" value="Submit" /></div>
</form>
<br/>
<a href="/bacenter">ba中心</a><br>
   </body>
</html>

