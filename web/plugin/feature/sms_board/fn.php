<?php

/*
 * Implementations of hook checkavailablekeyword()
 *
 * @param $keyword
 *   checkavailablekeyword() will insert keyword for checking to the hook here
 * @return 
 *   TRUE if keyword is NOT available
 */
function sms_board_hook_checkavailablekeyword($keyword)
{
    $ok = false;
    $db_query = "SELECT board_id FROM "._DB_PREF_."_featureBoard WHERE board_keyword='$keyword'";
    if ($db_result = dba_num_rows($db_query))
    {
        $ok = true;
    }
    return $ok;
}

/*
 * Implementations of hook setsmsincomingaction()
 *
 * @param $sms_datetime
 *   date and time when incoming sms inserted to playsms
 * @param $sms_sender
 *   sender on incoming sms
 * @param $board_keyword
 *   check if keyword is for sms_board
 * @param $board_param
 *   get parameters from incoming sms
 * @return $ret
 *   array of keyword owner uid and status, TRUE if incoming sms handled
 */
function sms_board_hook_setsmsincomingaction($sms_datetime,$sms_sender,$board_keyword,$board_param='')
{
    $ok = false;
    $db_query = "SELECT uid,board_id FROM "._DB_PREF_."_featureBoard WHERE board_keyword='$board_keyword'";
    $db_result = dba_query($db_query);
    if ($db_row = dba_fetch_array($db_result))
    {
	$c_uid = $db_row['uid'];
	if (sms_board_handle($sms_datetime,$sms_sender,$board_keyword,$board_param))
	{
	    $ok = true;
	}
    }
    $ret['uid'] = $c_uid;
    $ret['status'] = $ok;
    return $ret;
}

function sms_board_handle($sms_datetime,$sms_sender,$board_keyword,$board_param='')
{
    global $web_title,$email_service,$email_footer,$gateway_module;
    $ok = false;
    if ($sms_sender && $board_keyword && $board_param)
    {
	// masked sender sets here
	$masked_sender = substr_replace($sms_sender,'xxxx',-4);
	$db_query = "
	    INSERT INTO "._DB_PREF_."_featureBoard_log 
	    (in_gateway,in_sender,in_masked,in_keyword,in_msg,in_datetime) 
	    VALUES ('$gateway_module','$sms_sender','$masked_sender','$board_keyword','$board_param','$sms_datetime')
	";
	if ($cek_ok = @dba_insert_id($db_query))
	{
	    $db_query1 = "SELECT board_forward_email FROM "._DB_PREF_."_featureBoard WHERE board_keyword='$board_keyword'";
	    $db_result1 = dba_query($db_query1);
	    $db_row1 = dba_fetch_array($db_result1);
	    $email = $db_row1[board_forward_email];
	    if ($email)
	    {
		$subject = "[SMSGW-$board_keyword] from $sms_sender";
		$body = "Forward WebSMS ($web_title)\n\n";
		$body .= "Date Time: $sms_datetime\n";
		$body .= "Sender: $sms_sender\n";
		$body .= "Keyword: $board_keyword\n\n";
		$body .= "Message:\n$board_param\n\n";
		$body .= $email_footer."\n\n";
		sendmail($email_service,$email,$subject,$body);
	    }
	    $ok = true;
	}
    }
    return $ok;
}

function outputtorss($keyword,$line="10")
{
    global $apps_path,$web_title;
    include_once "$apps_path[libs]/external/feedcreator/feedcreator.class.php";
    $keyword = strtoupper($keyword);
    if (!$line) { $line = "10"; };
    $format_output = "RSS0.91";
    $rss = new UniversalFeedCreator();
    $db_query1 = "SELECT * FROM "._DB_PREF_."_featureBoard_log WHERE in_keyword='$keyword' ORDER BY in_datetime DESC LIMIT 0,$line";
    $db_result1 = dba_query($db_query1);
    while ($db_row1 = dba_fetch_array($db_result1))
    {
        $title = $db_row1[in_masked];
        $description = $db_row1[in_msg];
        $datetime = $db_row1[in_datetime];
        $items = new FeedItem();
        $items->title = $title;
	$items->description = $description;
	$items->comments = $datetime;
	$items->date = strtotime($datetime);
	$rss->addItem($items);	    
    }
    $feeds = $rss->createFeed($format_output);
    return $feeds;
}

// part of SMS board
function outputtohtml($keyword,$line="10",$pref_bodybgcolor="#E0D0C0",$pref_oddbgcolor="#EEDDCC",$pref_evenbgcolor="#FFEEDD")
{
    global $apps_path,$web_title;
    $keyword = strtoupper($keyword);
    if (!$line) { $line = "10"; };
    if (!$pref_bodybgcolor) { $pref_bodybgcolor = "#E0D0C0"; }
    if (!$pref_oddbgcolor) { $pref_oddbgcolor = "#EEDDCC"; }
    if (!$pref_evenbgcolor) { $pref_evenbgcolor = "#FFEEDD"; }
    $db_query = "SELECT board_pref_template FROM "._DB_PREF_."_featureBoard WHERE board_keyword='$keyword'";
    $db_result = dba_query($db_query);
    if ($db_row = dba_fetch_array($db_result))
    {
    	$template = $db_row[board_pref_template];
	$db_query1 = "SELECT * FROM "._DB_PREF_."_featureBoard_log WHERE in_keyword='$keyword' ORDER BY in_datetime DESC LIMIT 0,$line";
	$db_result1 = dba_query($db_query1);
	$content = "<html>\n<head>\n<title>$web_title - Keyword: $keyword</title>\n<meta name=\"author\" content=\"http://playsms.sourceforge.net\">\n</head>\n<body bgcolor=\"$pref_bodybgcolor\" topmargin=\"0\" leftmargin=\"0\">\n<table width=100% cellpadding=2 cellspacing=2>\n";
	$i = 0;
	while ($db_row1 = dba_fetch_array($db_result1))
	{
	    $i++;
	    $sender = $db_row1[in_masked];
	    $datetime = $db_row1[in_datetime];
	    $message = $db_row1[in_msg];
	    $tmp_template = $template;
	    $tmp_template = str_replace("{SENDER}",$sender,$tmp_template);
	    $tmp_template = str_replace("{DATETIME}",$datetime,$tmp_template);
	    $tmp_template = str_replace("{MESSAGE}",$message,$tmp_template);
	    if (($i % 2) == 0)
	    {
		$pref_zigzagcolor = "$pref_evenbgcolor";
	    }
	    else
	    {
		$pref_zigzagcolor = "$pref_oddbgcolor";
	    }
	    $content .= "\n<tr><td width=100% bgcolor=\"$pref_zigzagcolor\">\n$tmp_template</td></tr>\n\n";
	}
	$content .= "</table>\n</body>\n</html>\n";
	return $content;
    }
}

?>