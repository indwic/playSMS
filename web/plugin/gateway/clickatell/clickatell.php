<?
if(!isadmin()){forcenoaccess();};

include "$apps_path[plug]/gateway/clickatell/config.php";

if ($gateway_module == $clickatell_param[name])
{
    $status_active = "(<font color=green><b>Active</b></font>)";
}
else
{
    $status_active = "(<font color=red><b>Inactive</b></font>) (<a href=\"menu.php?inc=gateway_clickatell&op=manage_activate\">click here to activate</a>)";
}

switch ($op)
{
    case "manage":
	if ($err)
	{
	    $content = "<p><font color=red>$err</font><p>";
	}
	$content .= "
	    <h2>Manage ".$clickatell_param[name]."</h2>
	    <p>
	    <form action=menu.php?inc=gateway_clickatell&op=manage_save method=post>
	<table width=100% cellpadding=1 cellspacing=2 border=0>
	    <tr>
		<td width=150>Gateway Name</td><td width=5>:</td><td><b>".$clickatell_param[name]."</b> $status_active</td>
	    </tr>
	    <tr>
		<td>API ID</td><td>:</td><td><input type=text size=20 maxlength=20 name=up_api_id value=\"".$clickatell_param[api_id]."\"></td>
	    </tr>	    
	    <tr>
		<td>Username</td><td>:</td><td><input type=text size=30 maxlength=30 name=up_username value=\"".$clickatell_param[username]."\"></td>
	    </tr>
	    <tr>
		<td>Password</td><td>:</td><td><input type=text size=30 maxlength=30 name=up_password value=\"".$clickatell_param[password]."\"></td>
	    </tr>	    
	    <tr>
		<td>Global Sender</td><td>:</td><td><input type=text size=16 maxlength=16 name=up_sender value=\"".$clickatell_param[sender]."\"> (Max. 16 numeric or 11 alphanumeric char. empty to disable)</td>
	    </tr>	    
	    <tr>
		<td>Clickatell API URL</td><td>:</td><td><input type=text size=40 maxlength=250 name=up_send_url value=\"".$clickatell_param[send_url]."\"> (No trailing slash \"/\")</td>
	    </tr>
	    <tr>
		<td>Clickatell Incoming Path</td><td>:</td><td><input type=text size=40 maxlength=250 name=up_incoming_path value=\"".$clickatell_param[incoming_path]."\"> (No trailing slash \"/\")</td>
	    </tr>	    
	</table>	    
	    <p>Note:<br>
	    - Your callback URL is <b>http://".$_SERVER["HTTP_HOST"].dirname($_SERVER["PHP_SELF"])."/plugin/gateway/clickatell/callback.php</b><br>
	    - If you are using callback URL to receive incoming sms you may ignore Clickatell Incoming Path<br>
	    <!-- <p><input type=checkbox name=up_trn $checked> Send SMS message without footer banner ($username) -->
	    <p><input type=submit class=button value=Save>
	    </form>
	";
	echo $content;
	break;
    case "manage_save":
	$up_api_id = $_POST[up_api_id];
	$up_username = $_POST[up_username];
	$up_password = $_POST[up_password];
	$up_sender = $_POST[up_sender];
	$up_send_url = $_POST[up_send_url];
	$up_incoming_path = $_POST[up_incoming_path];
	$error_string = "No changes made!";
	if ($up_api_id && $up_username && $up_password && $up_send_url)
	{
	    $db_query = "
		UPDATE "._DB_PREF_."_gatewayClickatell_config 
		SET c_timestamp='".mktime()."',
		    cfg_api_id='$up_api_id',
		    cfg_username='$up_username',
		    cfg_password='$up_password',
		    cfg_sender='$up_sender',
		    cfg_send_url='$up_send_url',
		    cfg_incoming_path='$up_incoming_path'
	    ";
	    if (@dba_affected_rows($db_query))
	    {
		$error_string = "Gateway module configurations has been saved";
	    }
	}
	header ("Location: menu.php?inc=gateway_clickatell&op=manage&err=".urlencode($error_string));
	break;
    case "manage_activate":
	$db_query = "UPDATE "._DB_PREF_."_tblConfig_main SET c_timestamp='".mktime()."',cfg_gateway_module='clickatell'";
	$db_result = dba_query($db_query);
	$error_string = "Gateway has been activated";
	header ("Location: menu.php?inc=gateway_clickatell&op=manage&err=".urlencode($error_string));
	break;
}

?>