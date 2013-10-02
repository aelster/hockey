<?php
function WriteHeader() {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: WriteHeader<br>"; }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>Hockey Manager</title>
<!-- overLIB 4.21 (c) Erik Bosrup -->
<?php
	$scripts = array();
	$scripts[] = "overlib/overlib.js";
	$scripts[] = "overlib/overlib_hideform.js";
	$scripts[] = "board.js";
	foreach( $scripts as $file ) {
		echo sprintf( "<script type=\"text/javascript\" src=\"%s/%s\"></script>\n", $gSitePrefix, $file );
	}
?>
  <link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
<div align=center>
<form name=fMain method="post" action="<?php echo $gSourceCode ?>">
<input type=hidden name=action id=action>
<?php
}

function OneTimeInit() {
	include( "globals.php" );
	
	$gCAL = CAL_GREGORIAN;
	$cal = cal_info( $gCAL );
	$gMonths = $cal[ "months" ];
	
	$gLF = "\n";
	$tmp = $gServer["PHP_SELF"];
	$gSourceCode = str_replace( "index.php", "", $tmp );
	$gSourceCode .= ( isset( $_REQUEST['bozo'] ) ) ? "?bozo" : "";
}

function WriteFooter() {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: WriteFooter<br>"; }
	?>
</body>
</html>
<?php
}

function DisplayLogin() {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: DisplayLogin<br>"; }
?>
<?php AddOverlib() ?>
<input type=hidden name=from value="DisplayLogin">
<p>Enter Password</p>
<p><input type="password" name="MainPassword" id=MainPassword size="20"></p>
<p>
  <input type=submit name="action" value="Login">
  <input type=reset  name="action" value="Reset">
</p>
<script type="text/javascript">setFocus( 'MainPassword' )</script>
<?php
}

function VerifyUser() {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: VerifyUser<br>"; }

  $gPwd = $gPost[ "MainPassword" ];
  if( strlen( $gPwd ) < 20 ) { $gPwd = md5( $gPwd ); }
  DoQuery( "select * from `members` where Password = '$gPwd'" );
  if( $gNumRows > 0 ) {
    $row = mysql_fetch_assoc( $result );
		$gMemberId = $row[ "MemberId" ];
		$gDBA = $row[ "DBA" ];

		$from = $gPost[ "from" ];
		if( $from == "DisplayLogin" ) {
			if( $row[ "PwdChanged" ] == "0000-00-00 00:00:00" ) {
				ChangePassword();
				WriteFooter();
				exit;
			} else {
				$dstr = date("Y-m-d H:i:s");
				DoQuery( "UPDATE `members` SET `LastLogin` = '$dstr' where `MemberId` = '$gMemberId'" );
			}
		}
		
		$query = "select poolid from pool_member";
		if( ! $gDBA ) $query .= " where memberid = '$gMemberId'";
		DoQuery( $query );
		list( $gPost["pool_id"] ) = mysql_fetch_array( $result );

  } else {
		echo "Sorry, I couldn't verify your password.  Please try again or contact Andy to obtain a valid password<br>";
		DisplayLogin();
		WriteFooter();
		exit;
	}
}

function ChangePassword() {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: ChangePassword<br>"; }
?>
<body onLoad="document.forms.area.oldpassword.focus()">
<div align=center>
<div style="width:4in">
You are being asked to change your password because this is your first login.  The
password you choose is encrytped and never stored in clear text.  If your password is forgotten it
can be changed but I'm unable to tell you what it was.
<br><br>
</div>
<input type=hidden name=MainPassword value="<?php echo $gPwd ?>">
<input type=hidden name=from value=ChangePassword>
<input type=hidden name=MemberId value="<?php echo $gMemberId ?>">
<table class=norm>
<tr>
  <th class=norm>Old Password
	<td class=norm><input type=password name=oldpassword size=20>
</tr>
<tr>
  <th class=norm>New Password
	<td class=norm><input type=password name=newpassword1 size=20>
</tr>
<tr>
  <th class=norm>New Password
	<td class=norm><input type=password name=newpassword2 size=20>
</tr>
</table>
<br><br>
<input type=submit name="action" value="Update">
<input type=reset  name="action" value="Reset">
</form>
</div>
</body>
</html>
  <?php
}

function UpdatePassword() {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: UpdatePassword<br>"; }
	
	$ok_to_update = 1;
	
	$pass = md5( $gPost[ "oldpassword" ] ); 
	DoQuery( "SELECT * from `members` WHERE `MemberId` = '$gMemberId' and `Password` = '$pass'" );
	if( $gNumRows == 0 ) {
		$ok_to_update = 0;
		?>
<input type=hidden name=MainPassword value="<?php echo $gPwd ?>">
<input type=hidden name=from value=UpdatePassword>
<input type=hidden name=MemberId value="<?php echo $gMemberId ?>">
<h2>Old Password mismatch</h2>
<input type=submit name=action value="Try Again">
</form>
<?php
	}
	$new1 = md5( $gPost[ "newpassword1" ] );
	$new2 = md5( $gPost[ "newpassword2" ] );
	$blank = md5( "" );
	if( $new1 != $new2 || $new1 == $blank ) {
		$ok_to_update = 0;
		?>
<input type=hidden name=MainPassword value="<?php echo $gPwd ?>">
<input type=hidden name=from value=UpdatePassword>
<input type=hidden name=MemberId value="<?php echo $gMemberId ?>">
<h2>Blank Password or Password mismatch</h2>
<input type=submit name=action value="Try Again">
</form>
<?php
	}
	
	if( $ok_to_update > 0 ) {
		DoQuery( "UPDATE `members` SET `Password` = '$new1' where `MemberId` = '$gMemberId'" );
		$dstr = date("Y-m-d H:i:s");
		DoQuery( "UPDATE `members` SET `PwdChanged` = '$dstr', `LastLogin` = '$dstr' where `MemberId` = '$gMemberId'" );
		?>
<input type=hidden name=MainPassword value="<?php echo $new1 ?>">
<input type=hidden name=from value=UpdatePassword>
<input type=hidden name=MemberId value="<?php echo $gMemberId ?>">
<h2>Password successfully changed</h2>
<input type=submit name=action value="Continue">
</form>
<?php
	}
}

function DoQuery( $query ) {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "  Func: DoQuery<br>" . $gLF; }
	
	if( $gDebug > 0 ) { echo "  query: $query" . $gLF; }
	
	$result = mysql_query( $query, $gDb );
	if( mysql_errno( $gDb ) != 0 ) {
		if( ! $gDebug ) { echo "query: $query<br>" . $gLF; }
		echo "result: " . mysql_error( $gDb ) . "<br>" . $gLF;
		echo "Please e-mail the query and result to Andy<br>" . $gLF;
	} else {
		if( preg_match( "/select/i", $query ) ) {
			$gNumRows = mysql_num_rows( $result );
		} else {
			$gNumRows = mysql_affected_rows( $gDb );
		}
		if( $gDebug > 0 ) {
			echo sprintf( ", # rows: %d<br>", $gNumRows );
		}
	}
}

function AddOverlib(){
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: AddOverlib<br>"; }
	?>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<div style="width:770px">
<?php
}

function OpenDb() {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: OpenDb<br>"; }
	
  $gDb = mysql_connect( $mysql_host, $mysql_user, $mysql_pass );
	if( ! $gDb ) {
		die( 'Could not connect: ' . mysql_error() );
	}
  $stat = mysql_select_db( $dbname, $gDb);
	if( ! $stat ) {
		die( 'Can\'t use database $dbname: ' . mysql_error() );
	}
}

function CloseDb() {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: OpenDb<br>"; }
	
  mysql_close( $gDb );
}

function DumpPostVars() {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: DumpPostVars<br>"; }

  $dump_server = 0;
	
  ksort( $gPost );
  echo "---------------------------------------<br>";
  foreach( $gPost as $var => $val ) {
    if( preg_match( "/password/i", $var ) ) {
      printf( "dpv:  %-20s: %s<br>\n", $var, "******" );
    } else {
      printf( "dpv:  %-20s: %s<br>\n", $var, $val );
    }
  }
  if( $dump_server > 0 ) {
    echo "---------------------------------------<br>";
    foreach( $gServer as $var => $val ) {
      if( $var != "passwd" ) {
        printf( "dsv:  %-20s: %s<br>\n", $var, $val );
      } else {
        printf( "dsv:  %-20s: %s<br>\n", $var, "******" );
      }
    }
  }
  echo "---------------------------------------<br>";
}

function DisplayMain () {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: DisplayMain<br>"; }
  
  $numColumns = 3;
  
	DoQuery( "SELECT `Name`, `LastLogin`, `Phone`, viewonly from `members` where `MemberId` = '$gMemberId'" );
	list ( $name, $last, $phone, $guest ) = mysql_fetch_array( $result );
	$str = $name;
	echo "<h2>Welcome back $str</h2>";
	echo "Last login: $last";
	echo "<br>";

	DoQuery( "SELECT `SeasonId` from `season` WHERE `Active` > '0'" );
	list( $sid ) = mysql_fetch_array( $result );
	$pid = isset( $gPost[ "pool_id" ] ) ? $gPost[ "pool_id" ] : 0;
  
?>
<input type=hidden name=MainPassword value="<?php echo $gPwd ?>">
<input type=hidden name=from id=from value=DisplayMain>
<input type=hidden name=MemberId value="<?php echo $gMemberId ?>">
<input type=hidden name=season_id value="<?php echo $sid ?>">
<input type=hidden name=pool_id value="<?php echo $pid ?>">
<input type=hidden name=game_id id=game_id>
<?php
  	
	$query = "select p.memberid, m.name from pool_member as p, members as m";
	$query .= " where p.memberid = m.memberid and p.poolid = '$pid'";
	$query .= " order by m.name asc";
	
	$name_mid = $names = $mid_to_name = array();
	DoQuery( $query );
	while( list( $mid, $tname ) = mysql_fetch_array( $result ) ) {
		$name_mid[] = $mid;
		$names[] = $tname;
		$mid_to_name[ $mid ] = $tname;
	}

  $query = "SELECT `PoolId` from `pool_member`";
  if( $gDBA == 0 ) { $query .= "WHERE `MemberId` = '$gMemberId'"; }
	DoQuery( $query );
	unset( $pools );
	while( list( $p ) = mysql_fetch_array( $result ) ) {
		$gPoolId = $p;
		$pools[ $p ] = 1;
	}
  
  echo "<br>";
  echo "<table border=0><tr valign=top>";
?>
<?php
	if( ! $guest )
	{
?>
<td align=center>
<table class=norm>
<tr>
  <th class=norm>Contact #</th>
  <td class=norm><input type=text name=phone value="<?php echo $phone ?>"></td>
</tr>
</table>
</td>
<?php
	}
	
	$space = "&nbsp;&nbsp;&nbsp;";
	echo "<td>";
	if( ! $guest ) echo $space . "<input type=submit name=action value=Update>";
	echo $space . "<input type=submit name=action value=\"Show Games\">";
	echo $space . "<input type=submit name=action value=\"Calendar\">";
	echo $space . "<input type=submit name=action value=\"Refresh\">";
	if( $gDBA > 0 ) {
		echo $space . "<input type=submit name=action value=Back>";
	}
	echo "</td></tr></table>";
	
	if( $gDraftMode )
	{
	  DoQuery( "select * from rsvps where seasonid = '$sid'" );
		$j = $gNumRows + 1;
		DoQuery( "select name from draft_order where id >= '$j' order by id asc" );
		echo "<br>";
		echo "<div style=\"border: 3px solid #000000; background-color:#ccccff; padding: 5px; width:400px;\">";
		echo "Next up (in order):$space";
		$i = 0;
		$text = array();
		while( $i < 5 && list($up_name) = mysql_fetch_array( $result ) )
		{
			if( $i == 0 ) $now_up = $up_name;
			$i++;
			$text[] = sprintf( "%s", $up_name );
		}
		echo join( ',$space', $text );
		echo "</div>";
	} else {
		$now_up = $name;
	}
  
	$num = preg_match( "/$name/i", $now_up );
	$disabled = ( $num ) ? "" : "disabled";
  
  if( $gCalendar > 0 ) {
    DisplayCalendar( $sid, $pid );
    exit;
  }
  
  echo "<ul style=\"text-align: left; width:500px;\">";
  echo "<li>Members:  You can give back games or offer them for a trade by clicking a game on your list";
  echo "<li>Guests:  You can see available games but can't click anything.  Contact the member directly for interest in any tradable game";
  echo "</ul>";
  
  if( $sid > 0 ) {
    echo "<br>";
    echo "<table border=0><tr valign=top>";
  
    DoQuery( "SELECT * from `games` where `SeasonId` = '$sid' ORDER BY `DateTime`" );
    $max = $gNumRows / $numColumns;
    $i = 0;
    $outer = $result;
    while( $row = mysql_fetch_assoc( $outer ) ) {
      if( $i % $max == 0 ) {
        if( $i > 0 ) { echo "</td></table>"; }
?>
<td>
<table class=norm>
<tr><th class=normc colspan=4>Game Schedule</th></tr>
<tr>
	<th class=normc>#</th>
	<th class=normc>Date/Time</th>
	<th class=normc>Opponent</th>
	<th class=normc>Tickets</th>
</tr>
<?php
      }
      
      $class1 = ( $row[ "Regular" ] ) ? "class=normc" : "class=normcw";
		$class2 = $class1;
      $date = date( "D n/d g:i A", strtotime( $row[ "DateTime" ] ));
      $opp = $row[ "Opponent" ];
      $gid = $row[ "GameId" ];
      $mid = 0;
      $query = "SELECT * from `rsvps` WHERE `SeasonId` = '$sid' and `GameId` = '$gid'";
      DoQuery( $query );
		$rsvp = mysql_fetch_assoc( $result );
		if( $rsvp[ 'Tradeable' ] ) {
			$class2 = "class=normt";
		}
		$avail = ( $gNumRows == 0 ) ? "normg" : "normc";
      echo "<tr>";
		$k = ( $i < 4 ) ? $i - 4 : $i - 3;
		echo sprintf( "  <td $class1>%d</td>", $k );
      echo "  <td $class2>$date</td>";
//		echo "<td class=game>";
		echo "<td $class2>";
      if( $gNumRows == 0 || $gDBA > 0 ) {
			$opt = array();
			$opt[] = "setValue('game_id', '$gid')";
			$opt[] = "setValue('from','game_popup')";
			$opt[] = "addAction('Update')";
			$jscript = sprintf( "onClick=\"%s\"", join( ';', $opt ) );
			if( $disabled || $guest ) {
				$class = "class=navail";
				$disabled = "disabled";
			} else {
				$class = "class=avail";
			}
			echo "<input type=button $disabled $class $jscript value=\"$opp\">";
			
      } else {
        echo "$opp";
      }
		echo "</td>";
		
		if( $gNumRows > 0 ) {
			$mid = $rsvp[ "MemberId" ];
			$name = $mid_to_name[ $mid ];
			echo "  <td $class2>" . $name . "</td>";
		} else {
			echo "  <td $class2>&nbsp;</td>";
		}
      echo "</tr>";
      $i++;
    }
    echo "</td></tr></table>";
    echo "</table>";
    echo "<br>";
    
    echo "<table class=norm>";
    echo "<tr>";
    echo "<td class=normc>Key:</td>";
    echo "<td class=normcw>Pre-Season</td>";
    echo "<td class=normc>Reg-Season</td>";
    echo "<td class=normg>Available</td>";
    echo "<td class=normt>Tradeable</td>";
    echo "</tr>";
    echo "</table>";
  } 
  ShowRSVPs( $sid, $pid );
  
	echo "</form>";
}

function DisplayMainDBA () {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: DisplayMainDBA<br>"; }
  
	$numColumns = 3;
  
?>
<input type=hidden name=MainPassword value="<?php echo $gPwd ?>">
<input type=hidden name=from value=DisplayMain>
<input type=hidden name=MemberId value="<?php echo $gMemberId ?>">
<?php
	DoQuery( "SELECT `Name`, `LastLogin`, `Phone` from `members` where `MemberId` = '$gMemberId'" );
	list ( $name, $last, $phone ) = mysql_fetch_array( $result );
	$str = $name;
	echo "<h2>Welcome back $str (DBA)</h2>";
	echo "Last login: $last";
	
	echo "<br>";
#!
#! If a pool hasn't been selected, present a drop down list to choose from
#!
	if( empty( $gPost[ "pool_id" ] ) ) {
		echo "<br>";
		echo "<table class=norm>";
		echo "<tr>";
		echo "<th class=norm>Select Pool: </th>";
		echo "<td class=norm><select name=pool_id size=1>";
		echo "<option value=0>-- Click here --</option>";
		$query = "SELECT `PoolId`, `Title` from `pool` ORDER BY `Title`";
		DoQuery( $query );
		while( list( $pid, $title ) = mysql_fetch_array( $result ) )
		{
			echo "<option value=$pid>$title</option>";
		}
		echo "</select></td>";
		echo "<td class=norm><input type=submit value=Go></td>";
		echo "</tr>";
		echo "</table>";
		echo "<input type=hidden name=action value=RSVPs>";
		echo "</form>";
		exit;
	} else
	{
		$pid = $gPost[ "pool_id" ];
		echo "<input type=hidden name=pool_id value=$pid>";
	}
	
	DoQuery( "SELECT `SeasonId` from `season` WHERE `Active` > '0'" );
	list( $sid ) = mysql_fetch_array( $result );
	
	$query = "SELECT m.MemberId, m.Name, p.MemberId from members as m, pool_member as p";
	$query .= " WHERE m.MemberId = p.MemberId and p.PoolId = '$pid'";
	$query .= " ORDER BY m.Name";
	DoQuery( $query );
	unset( $name_mid );
	unset( $names );
	while( list( $mid, $name ) = mysql_fetch_array( $result ) ) {
		$name_mid[] = $mid;
		$names[] = $name;
		$mid_to_name[ $mid ] = $name;
	}

	echo "<br>";
	echo "<table border=0><tr valign=top>";
?>
<td align=center>
<table class=norm>
<tr>
  <th class=norm>Contact #</th>
  <td class=norm><input type=text name=phone value="<?php echo $phone ?>"></td>
</tr>
</table>
</td>
<?php

  $space = "&nbsp;&nbsp;&nbsp;";
  echo "<td>";
  echo $space . "<input type=submit name=action value=Update>";
  echo $space . "<input type=submit name=action value=\"Show Games\">";
	echo $space . "<input type=submit name=action value=\"Calendar\">";
	echo $space . "<input type=submit name=action value=\"Refresh\">";
  if( $gDBA > 0 ) {
    echo $space . "<input type=submit name=action value=Back>";
  }
  echo "</td></tr></table>";
  
  if( $gCalendar > 0 ) {
    DisplayCalendar( $sid, $pid );
    exit;
  }
  
  if( $sid > 0 ) {
    echo "<br>";
    echo "<table border=0><tr valign=top>";
  
    DoQuery( "SELECT * from `games` where `SeasonId` = '$sid' ORDER BY `DateTime`" );
    $max = $gNumRows / $numColumns;
    $i = 0;
    $outer = $result;
    while( $row = mysql_fetch_assoc( $outer ) ) {
      if( $i % $max == 0 ) {
        if( $i > 0 ) { echo "</td></table>"; }
?>
<td>
<table class=norm>
<tr><th class=normc colspan=3>Game Schedule</th></tr>
<tr>
	<th class=normc>Date/Time</th>
	<th class=normc>Opponent</th>
	<th class=normc>Tickets</th>
</tr>
<?php
      }
      
      $pre = ( $row[ "Regular" ] == 0 ) ? "Y" : "N";
      $date = date( "D n/d g:i A", strtotime( $row[ "DateTime" ] ));
      $opp = $row[ "Opponent" ];
      $class = ( $pre == "Y" ) ? "normcw" : "normc";
      $gid = $row[ "GameId" ];
      $mid = 0;
      $query = "SELECT * from `rsvps`";
	  $query .= " WHERE `SeasonId` = '$sid' and `GameId` = '$gid' and `PoolId` = '$pid'";
      DoQuery( $query );
      $avail = ( $gNumRows == 0 ) ? "normg" : "normc";
      echo "<tr>";
      echo "  <td class=$class>$date</td>";
      if( $gNumRows == 0 || $gDBA > 0 ) {
        $tmp = "<form name=area method=post action=\"$gSourceCode\">";
        $tmp .= "<input type=hidden name=MainPassword value=\"$gPwd\">";
        $tmp .= "<input type=hidden name=action value=Update>";
        $tmp .= "<input type=hidden name=from value=game_popup>";
        $tmp .= "<input type=hidden name=season_id value=$sid>";
        $tmp .= "<input type=hidden name=pool_id value=$pid>";
        $tmp .= "<input type=hidden name=member_id value=$gMemberId>";
        $tmp .= "<input type=hidden name=game_id value=$gid>";
        $tmp .= "<table class=norm>";
        for( $j = 0; $j < count( $names ); $j++ ) {
          $tag = "button_" . $name_mid[$j];
          $tmp .= "<tr><td class=normc><input type=submit name=$tag value=\"$names[$j]\"></td></tr>";
        }
        if( $gDBA > 0 ) {
          $tag = "button_delete";
          $tmp .= "<tr><td class=normc><input type=submit name=$tag value=Delete></td></tr>";
        }
        $tmp .= "</table></form>";
        $str = CVT_Str_to_Overlib( $tmp );
        $cap = "$opp $date";
        $tag = $opp;
        echo "  <td class=$avail>";
    		?><a href="javascript:void(0);"
		onclick="return overlib('<?php echo $str ?>', STICKY, VAUTO, CAPTION, '<?php echo $cap ?>')"
		onmouseout="return nd();"><?php echo $tag ?></a>
    <?php
        echo "</td>";
      } else {
        echo "  <td class=$avail>$opp</td>";
      }
      if( $gNumRows > 0 ) {
        unset( $name );
        while( $row = mysql_fetch_assoc( $result ) ) {
          $mid = $row[ "MemberId" ];
          if( isset( $name ) ) {
            $name .= ", " . $mid_to_name[ $mid ];
          } else {
            $name = $mid_to_name[ $mid ];
          }
        }
        echo "  <td class=normc>" . $name . "</td>";
      } else {
        echo "  <td class=normc>&nbsp;</td>";
      }
      echo "</tr>";
      $i++;
    }
    echo "</td></tr></table>";
    echo "</table>";
    echo "<br>";
    
    echo "<table class=norm>";
    echo "<tr>";
    echo "<td class=normc>Key:</td>";
    echo "<td class=normcw>Pre-Season</td>";
    echo "<td class=normc>Reg-Season</td>";
    echo "<td class=normg>Available</td>";
    echo "<td class=normt>Tradeable</td>";
    echo "</tr>";
    echo "</table>";
  } 
  ShowRSVPs( $sid, $pid );
  
	echo "</form>";
  
}

function AddTeam () {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: AddTeam<br>"; }
  $name = $gPost[ "name" ];
  if( $name == "" ) { return; }
  DoQuery( "SELECT * from `sports_teams` WHERE `Name` = '$name'" );
  if( mysql_num_rows( $result ) > 0 ) { return; }
  $bad_id = 1;
  while( $bad_id ) {
    $id = rand( 1, pow( 2, 23 ) );
    DoQuery( "SELECT * from `sports_teams` WHERE `TeamId` = '$id'" );
    $bad_id = mysql_num_rows( $result );
  }
  DoQuery( "INSERT INTO `sports_teams` SET `TeamId` = '$id', `Name` = '$name'" );
}

function AddPlayer () {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: AddPlayer<br>"; }
  $gTeamId = $gPost[ "TeamId" ];
  $last = $gPost[ "LastName_new" ];
  $first = $gPost[ "FirstName_new" ];
  DoQuery( "INSERT INTO `sports_players` SET `TeamId` = '$gTeamId', `LastName` = '$last',
    `FirstName` = '$first'" );
}

function AddEvent ( $type ) {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: AddEvent( $type )<br>"; }
  $gTeamId = $gPost[ "TeamId" ];
	$tm = strtotime( $gPost[ "date" ] );
	$date = date( "Y-m-d H-i-s", $tm );
	$loca = $gPost[ "loca" ];
  $desc = $gPost[ "desc" ];
  $query = "INSERT INTO `sports_events` SET `TeamId` = '$gTeamId', `Timestamp` = '$date',
    `$type` = '1', `Location` = '$loca', `Description` = '$desc'";
	DoQuery( $query );
}

function ManageTeam () {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: ManageTeam()<br>"; }
	
  DoQuery( "SELECT `ShowTimeDate`, `ShowFields` from `sports_teams` where `TeamId` = '$gTeamId'" );
	list( $showtd, $showf) = mysql_fetch_array( $result );
	
?>
<input type=hidden name=MainPassword value="<?php echo $gPwd ?>">
<input type=hidden name=from value=ManageTeam>
<input type=hidden name=MemberId value="<?php echo $gMemberId ?>">
<input type=hidden name=TeamId value="<?php echo $gTeamId ?>">
<?php
  if( $gAccess > 0 ) {
    echo "<input type=submit name=action value=Back>" . $gLF;
  }
?>
<div align=center>
<?php
	if( $gAccess > 0 ) { echo "<input type=submit name=action value=Players>"; }
	echo "<input type=submit name=action value=Calendar>";
	if( $showf > 0 ) { echo "<input type=submit name=action value=Fields>"; }

  if( $gAccess > 0 ) {
    echo "<input type=submit name=action value=Practices>";
    echo "<input type=submit name=action value=Games>";
  }
  echo "</div>";
}

function ManagePlayers () {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: ManagePlayers<br>"; }
?>
<input type=hidden name=MainPassword value="<?php echo $gPwd ?>">
<input type=hidden name=from value=ManagePlayers>
<input type=hidden name=MemberId value="<?php echo $gMemberId ?>">
<input type=submit name=action value=Back>
<br>
<?php
  $gTeamId = $gPost[ "TeamId" ];
  echo "<input type=hidden name=TeamId value=$gTeamId>" . $gLF;
  echo "<br>" . $gLF;
  
  echo "<table class=norm>" . $gLF;
  echo "<tr>" . $gLF;
  echo "  <th class=norm>Last Name</th>" . $gLF;
  echo "  <th class=norm>First Name</th>" . $gLF;
  if( $gAccess > 0 ) {
    echo "  <th class=norm>Select</th>" . $gLF;
    echo "  <th class=norm>Action</th>" . $gLF;
  }
  echo "</tr>" . $gLF;

  $disabled = ( $gAccess > 0 ) ? "" : "disabled";
  
  DoQuery( "SELECT * FROM `sports_players` WHERE `TeamId` = '$gTeamId' ORDER BY `LastName`, `FirstName`" );
  while( $row = mysql_fetch_assoc( $result ) ) {
    $pid = $row[ "PlayerId" ];
    echo "<tr>" . $gLF;
    printf( "  <td class=norm>%s</td>\n", $row[ "LastName" ] );
    printf( "  <td class=norm>%s</td>\n", $row[ "FirstName" ] );
    if( $gAccess > 0 ) {
      echo "  <td class=normc><input type=radio name=select_$pid></td>" . $gLF;
      echo "  <td class=norm>&nbsp;</td>" . $gLF;
    }
    echo "</tr>" . $gLF;
  }
  
  if( $gAccess > 0 ) {
    echo "<tr>" . $gLF;
    echo "  <td class=norm><input type=text name=LastName_new size=10></td>" . $gLF;
    echo "  <td class=norm><input type=text name=FirstName_new size=10></td>" . $gLF;
    echo "  <td class=norm>&nbsp;</td>" . $gLF;
    echo "  <td class=norm><input type=submit name=action value=Add></td>" . $gLF;
    echo "</tr>" . $gLF;
  }
  echo "</table>" . $gLF;
}

function ManageEvents( $type ) {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: ManageEvents ($type)<br>"; }
?>
<input type=hidden name=MainPassword value="<?php echo $gPwd ?>">
<input type=hidden name=from value=ManageEvents>
<input type=hidden name=event_type value=<?php echo $type ?>>
<input type=hidden name=action value=Update>
<input type=hidden name=MemberId value="<?php echo $gMemberId ?>">
<input type=submit name=action value=Back>
<input type=submit name=action value=Calendar>
<br><br>
<?php
  $gTeamId = $gPost[ "TeamId" ];
  echo "<input type=hidden name=TeamId value=$gTeamId>" . $gLF;
	
	echo "<table class=norm>" . $gLF;
	echo "<tr>" . $gLF;
	echo "  <th class=norm>#</th>" . $gLF;
	echo "  <th class=norm>Date</th>" . $gLF;
	echo "  <th class=norm>Location</th>" . $gLF;
	echo "  <th class=norm>Description</th>" . $gLF;
  if( $gAccess > 0 ) {
    echo "  <th class=norm>Action</th>" . $gLF;
  }
  echo "</tr>" . $gLF;
	
	DoQuery( "SELECT * FROM `sports_events` WHERE `TeamId` = '$gTeamId' and `$type` > '0' ORDER BY `Timestamp`" );
	$i = 0;
  $games = array();
  $total = array();
	while( $row = mysql_fetch_assoc( $result ) ) {
		$i++;
		echo "<tr>" . $gLF;
		$id = $row[ "Id" ];
		$games[] = $id;
    $total[] = 0;
		echo "  <td class=normc>$i</td>" . $gLF;
		$ts = strtotime( $row[ "Timestamp" ] );
    $date = date( "M/d g:i A", $ts );
		if( $gAccess > 0 ) {
      echo "  <td class=norm><input type=text size=15 name=date_$i value=\"$date\"></td>" . $gLF;
      echo "  <td class=norm><input type=text size=30 name=loc_$i value=\"" . $row["Location"] . "\"></td>" . $gLF;
      echo "  <td class=norm><input type=text size=30 name=desc_$i value=\"" . $row["Description"] . "\"></td>" . $gLF;
      echo "  <td class=norm>";
      echo "    <input type=submit name=update_$i value=Update>";
      echo "    <input type=submit name=delete_$i value=Delete>";
      echo "  </td>" . $gLF;
    } else {
      printf( "  <td class=norm>%s</td>\n", $date );
      printf( "  <td class=norm>%s</td>\n", $row["Location"] );
      printf( "  <td class=norm>%s</td>\n", $row[ "Description" ]);
    }
		echo "</tr>" . $gLF;
	}
	if( $gAccess > 0 ) {
		echo "<tr>" . $gLF;
    echo "  <td class=norm>&nbsp;</td>" . $gLF;
		echo "  <td class=norm><input type=text name=date size=15></td>" . $gLF;
		echo "  <td class=norm><input type=text name=loca size=30></td>" . $gLF;
		echo "  <td class=norm><input type=text name=desc size=30></td>" . $gLF;
		echo "  <td class=normc><input type=submit name=action value=Add></td>" . $gLF;
		echo "</tr>" . $gLF;
	}
	echo "</table>" . $gLF;

	echo "<br>" . $gLF;
  echo "<table class=norm>" . $gLF;
  echo "<tr>" . $gLF;
  echo "  <th class=norm>Click on Name</th>" . $gLF;
  for( $i = 0; $i < count( $games ); $i++ ) {
    printf( "  <th class=norm>%d</th>\n", $i + 1 );
  }
  echo "</tr>" . $gLF;
  
  DoQuery( "SELECT * FROM `sports_players` WHERE `TeamId` = '$gTeamId' ORDER BY `LastName`, `FirstName`" );
  $outer = $result;
  while( $player = mysql_fetch_assoc( $outer ) ) {
    echo "<tr>" . $gLF;
    $pid = $player["PlayerId"];
    $name = ( $player[ "LastName" ] != "" ) ?
      sprintf( "%s, %s", $player["LastName"], $player["FirstName"] ) :
      sprintf( "%s", $player[ "FirstName" ] );
    echo "  <td class=normc><input type=submit class=player name=edit_event_$pid value=\"$name\">" . $gLF;
    for( $i = 0; $i < count( $games ); $i++ ) {
      $query = "SELECT RSVP from `sports_rsvps` WHERE `TeamId` = '$gTeamId' and `PlayerId` = '$pid'";
      $query .= " and `EventId` = '$games[$i]'";
      DoQuery( $query );
      if( mysql_num_rows( $result ) == 0 ) {
        $class = "class=rsvp";
      } else {
        list( $rsvp ) = mysql_fetch_array( $result );
        if( $rsvp > 0 ) {
          $class = "class=rsvpy";
          $total[$i]++;
        } else {
          $class = "class=rsvpn";
        }
      }
      echo "  <td $class>&nbsp;</td>" . $gLF;
    }
    echo "</tr>" . $gLF;
  }
  printf ("<tr><th class=norm colspan=%d>&nbsp;</th></tr>\n", 1 + count( $games ) );
  echo "<tr>" . $gLF;
  echo "  <td class=normc>Total</td>" . $gLF;
  for( $i = 0; $i < count( $games ); $i++ ) {
    printf ("  <td class=normc>%d</td>\n", $total[$i] );
  }
  echo "</table>" . $gLF;
	echo "</form>" . $gLF;
}

function DisplayTeamBanner() {
	include( "globals.php" );
  if( $gTeamId == 0 ) { 
    if( isset( $gPost[ "TeamId" ] ) ) {
      $gTeamId = $gPost[ "TeamId" ];
    } else {
      $keys = preg_grep( "/^Manage_/", array_keys( $gPost ) );
      foreach( $keys as $key ) {
        list( $undef, $gTeamId ) = split( "_", $key );
      }
    }
  }
  if( $gTeamId > 0 ) {
    DoQuery( "SELECT `Name` FROM `sports_teams` WHERE `TeamId` = '$gTeamId'" );
    list( $name ) = mysql_fetch_array( $result );
    echo "<div align=center><h1>$name</h1></div>" . $gLF;
  }
}

function EventRsvp( $type ) {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: EventRsvp( $type )<br>"; }

?>
<input type=hidden name=MainPassword value="<?php echo $gPwd ?>">
<input type=hidden name=from value=EventRsvp>
<input type=hidden name=event_type value="<?php echo $type ?>">
<input type=hidden name=MemberId value="<?php echo $gMemberId ?>">
<input type=hidden name=TeamId value="<?php echo $gTeamId ?>">
<input type=submit name=action value=Back>
<?php

  $keys = preg_grep( "/^edit_event_/", array_keys( $gPost ) );
  foreach( $keys as $key ) {
    list( $undef, $undef, $pid ) = split( "_", $key );
  }
  DoQuery( "select `FirstName` from `sports_players` WHERE `PlayerId` = '$pid'" );
  list( $name ) = mysql_fetch_array( $result );
  echo "<div align=center><h3>$name's Availability for $type</h3></div>" . $gLF;
  
  echo "<table class=norm>" . $gLF;
  echo "<tr>" . $gLF;
  echo "  <th class=norm>Date/Time</th>" . $gLF;
  echo "  <th class=norm>Description</th>" . $gLF;
  echo "  <th class=norm>Yes</th>" . $gLF;
  echo "  <th class=norm>No</th>" . $gLF;
  echo "</th>" . $gLF;
  
  DoQuery( "select * from `sports_events` WHERE `TeamId` = '$gTeamId' and `$type` > '0' ORDER BY `Timestamp`" );
  $outer = $result;
  while( $event = mysql_fetch_assoc( $outer ) ) {
    echo "<tr>" . $gLF;
    $id = $event[ "Id" ];
    $ts = strtotime( $event[ "Timestamp" ] );
    printf( "  <td class=norm>%s</td>\n", date( "m/d H:i", $ts ) );
    printf( "  <td class=norm>%s</td>\n", $event[ "Description" ] );
    $query = "SELECT RSVP from `sports_rsvps` WHERE `TeamId` = '$gTeamId' and `PlayerId` = '$pid'";
    $query .= " and `EventId` = '$id'";
    DoQuery( $query );
    $name = sprintf( "rsvp_%d_%d", $id, $pid );
    if( mysql_num_rows( $result ) == 0 ) {
      echo "<td class=norm><input type=radio name=$name value=1></td>" . $gLF;
      echo "<td class=norm><input type=radio name=$name value=0></td>" . $gLF;
    } else {
      list( $rsvp ) = mysql_fetch_array( $result );
      if( $rsvp > 0 ) {
        echo "<td class=y><input type=radio name=$name value=1 checked></td>" . $gLF;
        echo "<td class=norm><input type=radio name=$name value=0></td>" . $gLF;
      } else {
        echo "<td class=norm><input type=radio name=$name value=1></td>" . $gLF;
        echo "<td class=n><input type=radio name=$name value=0 checked></td>" . $gLF;
      }
    }
    echo "</tr>" . $gLF;
  }
  echo "</table>" . $gLF;
  echo "<br>" . $gLF;
  echo "<input type=submit name=action value=Update>" . $gLF;
  echo "</form>" . $gLF;
  
}

function UpdateRsvps( $type ) {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: UpdateRsvps( type: $type )<br>"; }
  
  $tid = $gPost[ "TeamId" ];
  $keys = preg_grep( "/^rsvp_/", array_keys( $gPost ) );
  foreach( $keys as $key ) {
    list( $undef, $id, $pid ) = split( "_", $key );
    $new_rsvp = $gPost[ $key ];
    if( $new_rsvp == "Y" ) {
      $new_rsvp = 1;
    } else if( $new_rsvp == "N" ) {
      $new_rsvp = 0;
    } else if( $new_rsvp == "Undo" ) {
      $new_rsvp = -1;
    }
    $query = "SELECT `Id`, `RSVP` from `sports_rsvps` WHERE `TeamId` = '$gTeamId' and `PlayerId` = '$pid'";
    $query .= " and `EventId` = '$id'";
    DoQuery( $query );
    if( mysql_num_rows( $result ) == 0 ) {
      $query = "INSERT into `sports_rsvps` ( `TeamId`, `PlayerId`, `EventId`, `RSVP` )";
      $query .= " VALUES ( '$tid', '$pid', '$id', '$new_rsvp' )";
      DoQuery( $query );
    } else {
      list( $rid, $rsvp ) = mysql_fetch_array( $result );
      if( $new_rsvp != $rsvp ) {
        if( $new_rsvp < 0 ) {
          $query = "DELETE from `sports_rsvps` WHERE `Id` = '$rid'";
        } else {
          $query = "UPDATE `sports_rsvps` set `RSVP` = '$new_rsvp' WHERE `Id` = '$rid'";
        }
        DoQuery( $query );
      }
    }
  }
}
function DisplayFields () {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: DisplayCalendar<br>"; }
	AddOverlib();
?>
<input type=hidden name=MainPassword value="<?php echo $gPwd ?>">
<input type=hidden name=from value=DisplayFields>
<input type=hidden name=MemberId value="<?php echo $gMemberId ?>">
<input type=hidden name=TeamId value="<?php echo $gTeamId ?>">
<input type=submit name=action value=Back>
<input type=submit name=action value=Calendar>
<br><br>
<table class=norm>
<tr>
  <th class=norm>Field</th>
  <th class=norm>Directions</th>
</tr>
<tr>
  <td class=norm>Brethren</td>
  <td class=norm>
Brethren Christian School (Huntington Beach) is located adjacent to Gisler Elementary.
The schools are in the tract SW of the Brookhurst/Atlanta intersection. Take the I-405
exit Brookhurst south; take Brookhurst approx. 3 miles south to Atlanta. Turn right
(west) on Atlanta; left (south) on Strathmore. Drive slowly in the residential tract, please.
Ample parking is available.
  </td>
</tr>
<tr>
  <td class=norm>Columbus</td>
  <td class=norm>
Columbus Tustin From Irvine and South County Take I-5, Santa Ana Freeway to the 55 Freeway, Riverside exit north. Bear to your right and immediately take the 4th Street / Irvine Blvd. exit. Turn right on Irvine Blvd. and go east to Prospect. Turn left on Prospect and right into first parking lot. From Newport Beach and Costa Mesa Take the 55 Freeway north to the Irvine Blvd. exit. Turn right on Irvine Blvd. and go east to Prospect. Turn left on Prospect and right into first parking lot (TB 830-B2)
  </td>
</tr>
<tr>
  <td class=norm>Estock</td>
  <td class=norm>
Take the 55 Freeway north to the Irvine Blvd. exit. Turn right on Irvine Blvd. and go east to "B" Street. Turn left on "B" Street and left into parking lot. Additional parking in lot across the street from school. 
  </td>
</tr>
<tr>
  <td class=norm>Hicks Canyon</td>
  <td class=norm>
Hicks Canyon (North Irvine) From San Diego Take I-5 North to Culver Rd. exit, turn left on Trabuco, turn right at Culver, go four stop lights and turn left on View Park, fields on left. From the I-405, exit at Culver Rd. and turn north (away from the ocean). Follow Culver Drive north past the I-5 overpass five stop lights and turn left at View Park, fields on left.
  </td>
</tr>
<tr>
  <td class=norm>Kaiser</td>
  <td class=norm>
Kaiser 1, 2 & Track (Corner of 21st and Tustin) Costa Mesa From 405, Exit 73 southbound exit to Irvine/Campus exit. Turn right on Irvine Avenue (westbound). Drive approximately 1 ½ miles (Just past 4th signal which is Santiago/23rd St) and turn right on Holiday. Holiday becomes 21st St at Tustin Ave. Fields are on 21st, between Tustin and Santa Ana.    
  </td>
</tr>
<tr>
  <td class=norm>Lamb School</td>
  <td class=norm>
Take I-405 NORTH, exit EUCLID ST/NEWHOPE ST.  Turn Right on EUCLID ST which turns into ELLIS AVE.  Turn Left on WARD ST which becomes YORKTOWN AVE.  The school is at 10251 YORKTOWN AVE, HUNTINGTON BEACH, on the Right.
  </td>
</tr>
<tr>
  <td class=norm>Woodbury</td>
  <td class=norm>
 Woodbury Park (North Irvine)Address is 130 Sanctuary. Located at the East corner of Bryan and Jeffrey. From 405 or 5 Freeway, take Jeffrey north (away from ocean)to Bryan. park is on the right. For map:http://members.cox.net/aysoleg/announce/ayso213_parkmap_rev1.pdf
</td>
</tr>
</table>
</form>
<?php
}

function DisplayCalendar ( $sid, $pid ) {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: DisplayCalendar (sid: $sid, pid: $pid)<br>"; }
	AddOverlib();

  $start_jd = 0;
  unset( $gid_to_jd );
  unset( $jd_to_gid );
  DoQuery( "SELECT `GameId`, `DateTime` from `games` where `SeasonId` = '$sid' order by `DateTime` ASC" );
  while( list( $gid, $dt ) = mysql_fetch_array( $result ) ) {
    $jd = unixtojd( strtotime( $dt ) );
    $gid_to_jd[ $gid ] = $jd;
    $jd_to_gid[ $jd ] = $gid;
    if( $start_jd == 0 ) {
      $start_jd = $jd;
    }
  }
  $finish_jd = $jd;

  DoQuery( "SELECT `MemberId`, `Name` from `members`" );
  while( list( $mid, $name ) = mysql_fetch_array( $result ) ) {
    $mid_to_name[ $mid ] = $name;
  }
  
  DoQuery( "SELECT `PoolId` from `pool_member` WHERE `MemberId` = '$gMemberId'" );
  $constraint = "";
  while( list( $pid ) = mysql_fetch_array( $result ) ) {
    if( $constraint == "" ) {
      $constraint = "`PoolId` = '$pid'";
    } else {
      $constraint .= " or `PoolId` = '$pid'";
    }
  }
  
# First JD of month for first game
  $cal = cal_from_jd( $start_jd, $gCAL );
  $jd0 = cal_to_jd( $gCAL, $cal[ "month" ], 1, $cal[ "year" ] );
  
# Last JD of month with last game
  $cal = cal_from_jd( $finish_jd, $gCAL );
  if( $cal[ "month" ] == 12 ) {
    $jd1 = cal_to_jd( $gCAL, 1, 1, $cal[ "year" ] + 1 ) - 1;
  } else {
    $jd1 = cal_to_jd( $gCAL, $cal[ "month" ] + 1, 1, $cal[ "year" ] ) - 1;
  }

	$jd_today = unixtojd();
	
  $month = 0;
  for( $jd = $jd0; $jd <= $jd1; $jd++ ) {
    $cal = cal_from_jd( $jd, $gCAL );
    if( $cal[ "month" ] != $month ) {
      if( $month > 0 ) {
        if( $cal[ "dow" ] > 0 ) {
          for( $i = $cal[ "dow" ]; $i < 7; $i++ ) {
            echo "  <td class=datex>&nbsp;</td>$gLF";
          }
        }
        echo "</tr>$gLF";
        echo "</table>" . $gLF;
        echo "<br>" . $gLF;
      }
      echo "<table border=1 class=norm>" . $gLF;
      echo "<tr>" . $gLF;
      echo "  <th class=month colspan=7>" . $cal[ "monthname" ] . ", " . $cal[ "year" ] . "</th>" . $gLF;
      echo "</tr>" . $gLF;
      echo "<tr>" . $gLF;
      echo "  <th class=date>Sunday</th>" . $gLF;
      echo "  <th class=date>Monday</th>" . $gLF;
      echo "  <th class=date>Tuesday</th>" . $gLF;
      echo "  <th class=date>Wednesday</th>" . $gLF;
      echo "  <th class=date>Thursday</th>" . $gLF;
      echo "  <th class=date>Friday</th>" . $gLF;
      echo "  <th class=date>Saturday</th>" . $gLF;
      echo "</tr>" . $gLF;
      $month = $cal[ "month" ];
      echo "<tr>" . $gLF;
      
      for( $i = 0; $i < $cal[ "dow" ]; $i++ ) {
        echo "  <td class=datex>&nbsp;</td>" . $gLF;
      }
    }
    
    if( $cal[ "dow" ] == 0 ) { echo "<tr>$gLF"; }
		$class = ( $jd < $jd_today ) ? "class=datex" : "class=date";
    echo " <td $class>" . $cal[ "day" ];

    if( isset( $jd_to_gid[ $jd ] ) ) {  
      $gid = $jd_to_gid[ $jd ];
      DoQuery( "SELECT * from `games` WHERE `GameId` = '$gid'" );
      $game = mysql_fetch_assoc( $result );
      $str = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
      $str .= date( "g:i A", strtotime( $game[ "DateTime" ] ) );
      $str .= "<br>vs " . $game[ "Opponent" ];
      echo "$str";
      $query = "SELECT `MemberId` from `rsvps` WHERE `SeasonId` = '$sid' and `GameId` = '$gid'";
      $query .= " and ( $constraint )";
      DoQuery( $query );
      if( $gNumRows > 0 ) {
        while( list( $mid ) = mysql_fetch_array( $result ) ) {
          echo "<br>" . $mid_to_name[ $mid ];
        }
      }
    }
    echo "</td>$gLF";
    if( $cal[ "dow" ] == 6 ) { echo "</tr>$gLF"; }
  }
  
  for( $i = $cal[ "dow" ] + 1; $i < 7; $i++ ) {
    echo "  <td class=datex>&nbsp;</td>" . $gLF;
  }
  echo "</tr>$gLF";
  echo "</table>$gLF";
  
#  foreach( $ak as $k ) {
#    printf ( "key: %d, val: %s<br>", $k, $date[$k] );
#  }
}

function BuildRSVPTable( $id ) {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: BuildRSVPTable( $id )<br>"; }
	
	$vlast = array();
	$vfirst = array();
	$vpid = array();
	
	DoQuery( "SELECT * from `sports_events` WHERE `Id` = '$id'" );
	$event = mysql_fetch_assoc( $result );
	$type = ( $event[ "Games" ] > 0 ) ? "Games" : "Practices";
	
	DoQuery( "SELECT * from `sports_players` WHERE `TeamId` = '$gTeamId' ORDER BY `LastName`, `FirstName`" );
	$outer = $result;
  $total_y = 0;
  $total_n = 0;
	while( $player = mysql_fetch_assoc( $outer ) ) {
		$vlast[] = $player[ "LastName" ];
		$vfirst[] = $player[ "FirstName" ];
		$pid = $player[ "PlayerId" ];
		$vpid[] = $pid;
		DoQuery( "SELECT `RSVP` from `sports_rsvps` WHERE `TeamId` = '$gTeamId' and `PlayerId` = '$pid' and
			`EventId` = '$id'" );
		if( mysql_num_rows( $result ) == 0 ) {
			$vrsvp[] = -1;
		} else {
			list( $vrsvp[] ) = mysql_fetch_array( $result );
		}
	}
	$str = "<form name=area method=post action=\"$gSourceCode\">";
	$str .= "<input type=hidden name=TeamId value=$gTeamId>";
	$str .= "<input type=hidden name=MainPassword value=\"$gPwd\">";
	$str .= "<input type=hidden name=action value=Update>";
	$str .= "<input type=hidden name=event_type value=$type>";
	$str .= "<input type=hidden name=from value=EventRsvp>";
	$str .= "<table class=norm>";
	$str .= "<tr>";
	$str .= "<th class=norm rowspan=2>Name</th>";
	$str .= "<th class=norm colspan=3>RSVP</th>";
	$str .= "</tr>";
  $str .= "<tr><th class=norm>?</th><th class=norm>Y</th><th class=norm>N</th></tr>";
	for( $i = 0; $i < count( $vlast ); $i++ ) {
		$name = ( $vlast[$i] == "" ) ? $vfirst[$i] : $vlast[$i] . ", " . $vfirst[$i];
		$str .= "<tr><td class=norm>$name</td>";
		$str .= "<td class=norm>";
    $iname = sprintf( "rsvp_%d_%d", $id, $vpid[$i] );
		if( $vrsvp[$i] < 0 ) {
      $str .= "<input type=submit class=btn name=$iname value=Y>";
      $str .= "<input type=submit class=btn name=$iname value=N>";
      $str .= "</td>";
      $str .= "<td class=rsvp>&nbsp;</td><td class=rsvp>&nbsp;</td>";
		} elseif( $vrsvp[$i] > 0 ) {
      if( $vlast[$i] != "" ) { $total_y++; }
      $str .= "<input type=submit class=btnw name=$iname value=Undo>";
      $str .= "</td>";
      $str .= "<td class=rsvpy>Y</td><td class=rsvp>&nbsp;</td>";
		} else {
      if( $vlast[$i] != "" ) { $total_n++; }
      $str .= "<input type=submit class=btnw name=$iname value=Undo>";
      $str .= "</td>";
      $str .= "<td class=rsvp>&nbsp;</td><td class=rsvpn>N</td>";
		}
	}
  $str .= "<tr><th class=normc>Player Total</th><th class=norm>&nbsp;</th>";
  $str .= "<th class=normc>$total_y</th><th class=normc>$total_n</th></tr>";
	$str .= "</table>";
	$str .= "</form>";
	
	return CVT_Str_to_Overlib( $str );
}

function CVT_Str_to_Overlib( $str ) {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: CVT_Str_to_Overlib()<br>"; }
	
	$patterns[0] = '/</';
	$patterns[1] = '/>/';
	$patterns[2] = '/"/';
	$replacements[0] = '&lt;';
	$replacements[1] = '&gt;';
	$replacements[2] = '&quot;';
	return preg_replace( $patterns, $replacements, $str );
}

function AdminPage() {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: AdminPage<br>"; }
  
?>
<input type=hidden name=MainPassword value="<?php echo $gPwd ?>">
<input type=hidden name=from value=AdminPage>
<input type=hidden name=MemberId value="<?php echo $gMemberId ?>">
<br><br>
<input type=submit name=action value=Setup>
<input type=submit name=action value=Games>
<input type=submit name=action value=RSVPs>
</form>
<?php
}

function UpdateAdmin() {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: UpdateAdmin<br>"; }
  
  if( $gAction == "Update" && $gFrom == "game_popup" ) {
    UpdateGameRSVP();
  }
  
  $keys = preg_grep( "/^action_/", array_keys( $gPost ) );
  foreach( $keys as $key ) {
    list( $undef, $area, $id ) = preg_split( '/_/', $key );
		if( $area == "season" ) {
			$tag = $gPost[ $key ];
			$val = $gPost[ $area . "_name_" . $id ];
			$act = $gPost[ $area . "_active_" . $id ];
			if( $tag == "UPD" ) {
				$query = "UPDATE `$area` set `Title` = '$val', `Active` = '$act' where `SeasonId` = '$id'";
			} else if( $tag == "DEL" ) {
				$query = "DELETE FROM `$area` where `SeasonId` = '$id'";
			} else if( $tag == "Add" ) {
				$date = date( 'Y-m-d' );
				$query = "INSERT INTO `$area` set `Title` = '$val', `Start` = '$date'";
			}
			DoQuery( $query );

		} else if( $area == "pool" ) {
			$tag = $gPost[ $key ];
			$val = $gPost[ $area . "_name_" . $id ];
			if( $tag == "UPD" ) {
				$query = "UPDATE `$area` set `Title` = '$val' where `PoolId` = '$id'";
			} else if( $tag == "DEL" ) {
				$query = "DELETE FROM `$area` where `PoolId` = '$id'";
			} else if( $tag == "Add" ) {
				$date = date( 'Y-m-d' );
				$query = "INSERT INTO `$area` set `Title` = '$val'";
			}
			DoQuery( $query );
	
		} else if( $area == "members" ) {
			$tag = $gPost[ $key ];
			$name = $gPost[ $area . "_name_" . $id ];
			$last = $gPost[ $area . "_last_" . $id ];
			$phone = $gPost[ $area . "_phone_" . $id ];
			$dba = $gPost[ $area . "_dba_" . $id ];
			if( $tag == "UPD" ) {
				DoQuery( "DELETE FROM `pool_member` WHERE `MemberId` = '$id'" );
				$pools = $gPost[ $area . "_pool_" . $id ];
				foreach( $pools as $k => $v ) {
					DoQuery( "INSERT INTO `pool_member` set `PoolId` = '$v', `MemberId` = '$id'" );
				}
				
				$query = "UPDATE `$area` set `Name`='$name', `LastName` = '$last', `Phone`='$phone', `DBA`='$dba' where `MemberId` = '$id'";
			} else if( $tag == "DEL" ) {
				$query = "DELETE FROM `$area` where `MemberId` = '$id'";
			} else if( $tag == "Add" ) {
				$date = date( 'Y-m-d' );
				$query = "INSERT INTO `$area` set `Name`='$name', `LastName` = '$last', `Phone`='$phone', `DBA`='$dba', `Password`=md5('$name')";
			}
			DoQuery( $query );
		}
  }
}

function UpdateGameRSVP() {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: UpdateGameRSVP<br>"; }
  
  $sid = $gPost[ "season_id" ];
  $pid = $gPost[ "pool_id" ];
  $gid = $gPost[ "game_id" ];
  $keys = preg_grep( "/^button_/", array_keys( $gPost ) );
  foreach( $keys as $key ) {
    list( $undef, $mid ) = preg_split( '/_/', $key );
  }
  if( empty( $mid ) ) $mid = $gMemberId;
  
  $query = "SELECT * from `rsvps` WHERE `SeasonId` = '$sid' and `PoolId` = '$pid' and `GameId` = '$gid'";
  DoQuery( $query );
  
  if( $gNumRows == 0 ) {
    $query = "INSERT INTO `rsvps`";
    $query .= " SET `SeasonId` = '$sid', `PoolId` = '$pid', `GameId` = '$gid', `MemberId` = '$mid'";
    DoQuery( $query );
  } else {
    $row = mysql_fetch_assoc( $result );
    $did = $row[ "Id" ];
    switch( $mid ) {
      case ( "delete" ):
        DoQuery( "DELETE from `rsvps` WHERE `Id` = '$did'" );
        break;
      
      case ( "keep" ):
        DoQuery( "UPDATE `rsvps` set `Tradeable` = '0' WHERE `Id` = '$did'" );
        break;

      case ( "trade" ):
        DoQuery( "UPDATE `rsvps` set `Tradeable` = '1' WHERE `Id` = '$did'" );
        break;
        
      case ( $gDBA > 0 ):
        DoQuery( "UPDATE `rsvps` set `MemberId` = '$mid' WHERE `Id` = '$did'" );
        break;
    }
  }
}

function ShowRSVPs ( $sid, $pid ) {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: ShowRSVPs<br>"; }
  
  echo "<br>";
  echo "<table border=0>";
  echo "<tr valign=top>";
  
	DoQuery( "select gameid from games order by datetime asc" );
	$gid_to_idx = array();
	$i = 0;
	while( list( $gid ) = mysql_fetch_array( $result ) ) {
		$gid_to_idx[ $gid ] = $i;
		$i++;
	}
	
	$query = "SELECT `PoolId`, `Seats` FROM `pool`";
	DoQuery( $query );
	while( list( $tpid, $tseats ) = mysql_fetch_array( $result ) )
	{
		$seats[ $tpid ] = $tseats;
	}
  
	if( $gDBA > 0 ){
		$query = "SELECT m.MemberId, m.Name, m.dba, m.LastName, m.Phone, p.MemberId from members as m, pool_member as p";
		$query .= " WHERE m.MemberId = p.MemberId";
		$query .= " and p.PoolId = '$pid'";
	} else {
		$query = "SELECT distinct p.MemberId, m.name, m.dba, m.lastname, m.phone";
		$query .= " FROM `pool_member` as p, members as m";
		$query .= " WHERE p.poolid = '$gPoolId' and p.memberid = m.memberid and m.viewonly = '0'";
	}
	$query .= " ORDER BY m.name";

  DoQuery( $query );
  $outer = $result;
  while( list( $mid, $name, $dba, $last, $phone ) = mysql_fetch_array( $outer ) ) {
		if( $dba ) continue;
    echo "<td class=norm>";
    echo "<table class=norm>";
    echo "<tr><th class=normc colspan=3>";
    
    $str = "$name $last, $phone";
    $cap = "Contact information";
    $tag = $name;
?><a href="javascript:void(0);"
		onmouseover="return overlib('<?php echo $str ?>', VAUTO, CAPTION, '<?php echo $cap ?>')"
		onmouseout="return nd();"><?php echo $tag ?></a>
<?php

    echo "</th></tr>";
    echo "<tr><th class=norm>#</th><th class=norm>Date</th><th class=norm>Opponent</th></tr>";

    $query = "select g.*, r.Tradeable, r.PoolId from games as g, rsvps as r";
    $query .= " where g.gameid = r.gameid and";
    $query .= " r.seasonid = '$sid' and r.memberid = '$mid'";
	if( $gDBA > 0 )
	{
		$query .= " and r.poolid = '$pid'";
	}
    $query .= " order by g.datetime asc";
    DoQuery( $query );
    while( $row = mysql_fetch_assoc( $result ) ) {
  		$pre = ( $row[ "Regular" ] == 0 ) ? "Y" : "N";
      $date = date( "n/d/y", strtotime( $row[ "DateTime" ] ));
  		$opp = $row[ "Opponent" ];
      $gid = $row[ "GameId" ];
	  if( $gDBA == 0 ) { $pid = $row[ "PoolId" ]; }
      $trade = $row[ "Tradeable" ];
      $class =  ( $trade > 0 ) ? "normt" : "normc";
  		echo "<tr>";
		$i = $gid_to_idx[ $gid ];
		$k = ( $i < 4 ) ? $i - 4 : $i - 3;
		echo "  <td class=$class>$k</td>";
      echo "  <td class=$class>$date</td>";
      echo "  <td class=$class>";
      if( $mid == $gMemberId ) {
        $tmp = "<form name=area method=post action=\"$gSourceCode\">";
        $tmp .= "<input type=hidden name=MainPassword value=\"$gPwd\">";
        $tmp .= "<input type=hidden name=action value=Update>";
        $tmp .= "<input type=hidden name=from value=trade_popup>";
        $tmp .= "<input type=hidden name=season_id value=$sid>";
        $tmp .= "<input type=hidden name=pool_id value=$pid>";
        $tmp .= "<input type=hidden name=member_id value=$gMemberId>";
        $tmp .= "<input type=hidden name=game_id value=$gid>";
        $tmp .= "<table class=norm>";
        $tmp .= "<tr><td class=normc>";
        if( $trade > 0 ) {
          $tmp .= "<input type=submit name=button_keep value=Keep>";
          $tmp .= "<input type=submit name=button_delete value=\"Give Back\">";
        } else {
          $tmp .= "<input type=submit name=button_trade value=Trade>";
          $tmp .= "<input type=submit name=button_delete value=\"Give Back\">";
        }
        $tmp .= "</td></tr></table></form>";
        $str = CVT_Str_to_Overlib( $tmp );
		  $seat = array_key_exists( $pid, $seats ) ? $seats[ $pid ] : "";
        $cap = "$opp (" . $seat . ")";
        $tag = $opp;
?><a href="javascript:void(0);"
		onmouseover="return overlib('<?php echo $str ?>', VAUTO, STICKY, CAPTION, '<?php echo $cap ?>')"
		onmouseout="return nd();"><?php echo $tag ?></a>
<?php
        echo "</td>";
      } else {
        echo "$opp</td>";
      }
      echo "</tr>";
    }
    echo "</table>";
    echo "</td>";
  }
  echo "</tr>";
  echo "</table>";
}

function AdminUsers() {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: Template<br>"; }
  
?>
<input type=hidden name=MainPassword value="<?php echo $gPwd ?>">
<input type=hidden name=from value=AdminUsers>
<input type=hidden name=MemberId value="<?php echo $gMemberId ?>">
<br><br>
<input type=submit name=action value=Back>
<?php

echo "<table border=0><tr valign=top><td>";

?>
<table class=norm>
<tr>
	<th class=norm colspan=2>Pool</th>
</tr>
<?php
	DoQuery( "SELECT * from `pool` ORDER BY `Title`" );
	if( $gNumRows > 0 ) {
		while( $row = mysql_fetch_assoc( $result ) ) {
			echo "<tr>";
			$id = $row[ "PoolId" ];
			echo "<td class=normc><input type=text name=pool_name_$id value=\"" . $row[ "Title" ] . "\"></td>";
			echo "<td class=normc>";
			echo "<input type=submit name=action_pool_$id value=UPD>";
			echo "<input type=submit name=action_pool_$id value=DEL>";
			echo "</td>";
			echo "</tr>";
		}
	}
	echo "<tr>";
	echo "<td class=norm><input type=text name=pool_name_new size=20></td>";
	echo "<td class=normc><input type=submit name=action_pool_new value=Add></td>";
	echo "</tr>";
?>
</table>
<?php

echo "</td><td>";

?>
<table class=norm>
<tr>
	<th class=norm colspan=3>Season</th>
</tr>
<?php
	DoQuery( "SELECT * from `season` ORDER BY `start`" );
	if( $gNumRows > 0 ) {
		while( $row = mysql_fetch_assoc( $result ) ) {
			echo "<tr>";
			$id = $row[ "SeasonId" ];
      $active = $row[ "Active" ];
      $act_flag = ( $active > 0 ) ? "checked" : "";
			echo "<td class=norm><input type=text name=season_name_$id value=\"" . $row[ "Title" ] . "\"></td>";
      echo "<td class=normc><input type=checkbox name=season_active_$id value=1 $act_flag></td>";
			echo "<td class=normc>";
			echo "<input type=submit name=action_season_$id value=UPD>";
			echo "<input type=submit name=action_season_$id value=DEL>";
			echo "</td>";
			echo "</tr>";
		}
	}
	echo "<tr>";
	echo "<td class=norm><input type=text name=season_name_new size=20></td>";
  echo "<td class=norm>&nbsp;</td>";
	echo "<td class=normc><input type=submit name=action_season_new value=Add></td>";
	echo "</tr>";
?>
</table>
<?php

echo "</td></tr></table>";

?>
<table class=norm>
<tr><th class=normc colspan=7>Members</th></tr>
<tr>
	<th class=norm>First</th>
	<th class=norm>Last</th>
	<th class=norm>Phone</th>
	<th class=norm>PwdChanged</th>
	<th class=normc>DBA</th>
	<th class=normc>Pools</th>
	<th class=normc>&nbsp;</th>
</tr>
<?php
	DoQuery( "SELECT `PoolId`, `Title` from `pool` ORDER BY `Title`" );
	unset( $pools );
	while( list( $id, $title ) = mysql_fetch_array( $result ) ) {
		$pools[ $id ] = $title;
	}
	asort( $pools );

	DoQuery( "SELECT * from `members` ORDER BY `name`" );
	$outer = $result;
	if( $gNumRows > 0 ) {
		while( $row = mysql_fetch_assoc( $outer ) ) {
			echo "<tr>";
			$id = $row[ "MemberId" ];
			echo "<td class=norm><input type=text name=members_name_$id value=\"" . $row[ "Name" ] . "\"></td>";
			echo "<td class=norm><input type=text name=members_last_$id value=\"" . $row[ "LastName" ] . "\"></td>";
			echo "<td class=norm><input type=text name=members_phone_$id value=\"" . $row[ "Phone" ] . "\"></td>";
			echo "<td class=norm>" . $row[ "PwdChanged" ] . "</td>";
			$checked = ( $row[ "DBA" ] > 0 ) ? "checked" : "";
			echo "<td class=normc><input type=checkbox name=members_dba_$id value=1 $checked></td>";
			
			echo "<td class=normc>";
			$tag = "members_pool_" . $id . '[]';
			echo "<select multiple=yes name=$tag>";
			foreach( $pools as $k => $v ) {
				DoQuery( "SELECT * from `pool_member` where `PoolId` = '$k' and `MemberId` = '$id'" );
				$selected = ( $gNumRows > 0 ) ? "selected" : "";
				echo "  <option value=$k $selected>$v</option>";
			}
			echo "</select>";
			
			echo "<td class=normc>";
			echo "<input type=submit name=action_members_$id value=UPD>";
			echo "<input type=submit name=action_members_$id value=DEL>";
			echo "</td>";
			echo "</tr>";
		}
	}
	echo "<tr>";
	echo "<td class=norm><input type=text name=members_name_new size=20></td>";
	echo "<td class=norm><input type=text name=members_last_new size=20></td>";
	echo "<td class=norm><input type=text name=members_phone_new size=20></td>";
	echo "<td class=norm>&nbsp;</td>";
	echo "<td class=normc><input type=checkbox name=members_dba_new value=1></td>";

	$id = "new";
	echo "<td class=normc>";
	$tag = "members_pool_" . $id . '[]';
	echo "<select multiple=yes name=$tag>";
	foreach( $pools as $k => $v ) {
		DoQuery( "SELECT * from `pool_member` where `PoolId` = '$k' and `MemberId` = '$id'" );
		$selected = ( $gNumRows > 0 ) ? "selected" : "";
		echo "  <option value=$k $selected>$v</option>";
	}
	echo "</select>";

	echo "<td class=normc><input type=submit name=action_members_new value=Add></td>";
	echo "</tr>";
	echo "</table>";
	echo "<br><br>";
}

function UpdatePhone () {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: UpdatePhone<br>"; }
  $phone = $gPost[ "phone" ];
  $query = "UPDATE `members` SET `Phone` = '$phone' where `MemberId` = '$gMemberId'";
  DoQuery( $query );
}

function zz_Template () {
	include( "globals.php" );
	if( $gTrace > 0 ) { echo "Func: Template<br>"; }
}

?>