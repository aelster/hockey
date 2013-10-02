<?php
include( "globals.php" );
include( "library.php" );

include( "local_hockey.php");

$test = isset( $_REQUEST['bozo'] ) ? 1 : 0;

$gDebug = $test;
$gTrace = $test;

//-----------------------------------------------------------------------------
// Main Program
//-----------------------------------------------------------------------------
//
OneTimeInit();

WriteHeader();	# Write the HTML headers/style page
if( ! empty( $gDebug ) ) { DumpPostVars(); }	# Debug display

$gAction = ( isset( $gPost[ "action" ] ) ) ? $gPost[ "action" ] : "";
$gFrom = ( isset( $gPost[ 'from' ] ) ) ? $gPost[ "from" ] : "";	# This is where I came from
$passwd_set = isset( $gPost['MainPassword'] );
$gCalendar = 0;
if( $gAction == "Logout" ) {
	$gAction = "";
	$passwd_set = 0;
}

if( $gAction == "" && ! $passwd_set ) {
	if( ! empty( $gDebug ) ) { echo "(blank) gAction = $gAction<br>"; }
	DisplayLogin();
	WriteFooter();
	exit;
}
OpenDb();		# Open the MySQL database
VerifyUser();	# Verify the supplied password

if( $gAction == "Update" && $gFrom == "ChangePassword" ) {
	UpdatePassword();
	exit;
}

if( $gDBA ) {
	UpdateAdmin();
	switch( $gAction ) {
		case( "" ):
	      if( $gFrom == "AdminUsers" ) {
	        $gAction = "Setup";
	      }
	      break;
    
	    case( "Update" ):
	      $gAction = "RSVPs";
	      break;
    
	    case( "Back" ):
	      $gAction = "AdminPage";
	      break;
    
	    case( "Calendar" );
	      $gCalendar = 1;
	      break;
	  }

	switch( $gAction ) {
		case( "Setup" ):
	      AdminUsers();
	      break;
    
		case( "Calendar" ):
		case( "Refresh" ):
		case( "RSVPs" ):
		case( "Show Games" ):
			DisplayMainDBA();
			break;
    
		default:
		   AdminPage();
		   break;
	}
	exit;
}

$gFrom = ( isset( $gPost[ 'from' ] ) ) ? $gPost[ "from" ] : "";	# This is where I came from

if( $gAction == "Login" ) {
	if( $gTeamId == 0 ) {
		$gAction = "Main"; # Default page
	} else {
		$gAction = "Manage";
	}
}

if( $gAction == "Back" ) {
	switch( $gFrom ) {
		case "ManagePlayers":
		case "ManageEvents":
		case "DisplayCalendar":
		case "DisplayFields":
			$gAction = "Manage";
			break;

		case "EventRsvp":
			$gAction = $gPost[ "event_type" ];
			break;
    
		default:
			$gAction = "Main";
			break;
	}
}

if( $gAction !== "Main" ) {
	DisplayTeamBanner();
}

if( $gAction == "Add" ) {
	switch( $gFrom ) {
		case "DisplayMain":
			AddTeam();
			$gAction = "Main";
			break;
    
		case "ManagePlayers":
			AddPlayer();
			$gAction = "Players";
			break;
		
		case "ManageEvents":
			$gAction = $gPost[ "event_type" ];
			AddEvent( $gAction );
			break;
	}
}

if( $gAction == "Update" ) {
	$et = isset( $gPost[ "event_type" ] ) ? $gPost[ "event_type" ] : "";
	switch( $gFrom ) {
		case "ManageEvents":
			EventRsvp( $et );
			$gAction = "Exit";
			break;
    
		case "EventRsvp":
			UpdateRsvps( $et );
			$gAction = "Calendar";
			break;
    
		case "ChangePassword":
			UpdatePassword();
			$gAction = "Main";
			break;
    
		case "game_popup":
		case "trade_popup":
			UpdateGameRSVP();
			$gAction = "Main";
			break;
    
		case "DisplayMain":
			UpdatePhone();
			$gAction = "Main";
			break;
	
		default:
			echo "unknown et";
			exit;
	}
}

switch ($gAction ) {
  case "Exit":
    break;
	
	case "Calendar":
    $gCalendar = 1;
    DisplayMain();
		break;
  
	case "Fields":
		DisplayFields();
		break;

	case "Main":
  case "Continue":
	case "Refresh":
  case "Show Games":
		DisplayMain();
		break;

  case "Manage":
    ManageTeam();
    break;
  
  case "Players":
    ManagePlayers();
    break;
  
	case "Practices":
		ManageEvents( "Practices" );
		break;
	
	case "Games":
		ManageEvents( "Games" );
		break;
	
	default:
		$gDebug = 1;
		DumpPostVars();
		echo "Uh oh!, Contact Andy<br>";
		echo "Don't know what to do";
		break;
}
	
WriteFooter();
CloseDb();
?>