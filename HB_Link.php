<?php
/****************************************************************
    HB_Link.php
    Copyright (C) 2018, Tom Milner, Dave Robinson
    All Rights Reserved
    April 21, 2018

    General purpose Ajax target (see ajax.js) for Homebridge Link.
    Usage: HBLink.php?cmd="XYZ...", where "XYZ" is one of the 
           command routines below (i.e., "cmdXYZ").
*****************************************************************/
    require_once( 'lib/config.php' );
    require_once( 'lib/logger.php' );
    require_once( 'lib/mgserver.php' );

    $result = "";
    $me = "HB_Link" ;
    try {
        // Find a matching command and parameter from URL
        if ( isset( $_GET ) && isset ( $_GET["cmd"] ) ) {
              // Preface function name with "cmd".
            $cmd = 'cmd' . $_GET["cmd"];
        } else {
            throw new Exception( '?cmd=xyz missing in URL' );
        }
        if ( !function_exists( $cmd ) ) {
            throw new Exception( 'Unknown command "' . $cmd . '"' );
        }
    
          // execute command - Return text string
        $result = $cmd();    // Call function, retrieve result

    } catch( Exception $e ) {
        Logger::logError( $me, 'Exception: ' . $e->getMessage() );
        $result = 'Exception: ' . $e->getMessage();
    }

    // We have to set the following in the header so that the browser
    // does not cache our response.  Without this, the browser will return
    // the previous result, making the client-side think that we've hit 
    // the server again, when in fact we're just seeing the previous 
    // response all over again... :-(
    header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
    header( "Cache-Control: no-store, no-cache, must-revalidate" ); 
    header( "Cache-Control: post-check=0, pre-check=0", false );
    header( "Pragma: no-cache" );
    
    // Echo back to show we're done..
    echo $result ;
    exit;
    
    
    // ------------------------------------------------
    //      Command routines follow
    // ------------------------------------------------
    
    /* Panic test routine
     * HB.php?cmd=Panic
     */
     
     function cmdPanic()
     {
     	
     	global $me;
     	Logger::panic( $me, "Test Panic from HB_Link.php" );
     
     
     
     
     }
    
    /*
     * cmdOpen - Parameter is the door Id
     */
    function cmdOpen()
    {
        try {
            if ( !isset( $_GET["id"] ) ) {
                throw new Exception( 'Missing arg "id"' );
            }
            $doorId = $_GET[ "id" ];  // Get Parameter
            Logger::LogMessage( $me, 1, "Homebridge door open request for door: " . $doorId );
            $newState = MgServer::doAction( $doorId, 'Open' );
              
// Added this code to mgserver to keep everything in sync
//            $status = fopen("status".$doorId,w) or die("Unable to open file");
//            fwrite($status, "OPEN");
//            fclose($status);
            return("OPENING");

        } catch( Exception $e ) {
            Logger::logError( $me, 'Exception: ' . $e->getMessage() );
            return 'Exception: ' . $e->getMessage();
        }
    }
    
    
    /*
     * cmdClose - Parameter is the door Id
     */
    function cmdClose()
    {
        try {
            if ( !isset( $_GET["id"] ) ) {
                throw new Exception( 'missing arg "id"' );
            }
            $doorId = $_GET[ "id" ];  // Get Parameter
            Logger::LogMessage( $me, 1, "Homebridge door close request for door: " . $doorId );
            $newState = MgServer::doAction( $doorId, 'Close' );

// Added this code to mgserver to keep everything in sync.
//            $status = fopen("status".$doorId,w) or die("Unable to open file");
//            fwrite($status, "CLOSED");
//            fclose($status);
            return("CLOSING");


            
/*            return( 'OK' . $newState );
*/
        } catch( Exception $e ) {
            Logger::logError( $me, 'Exception: ' . $e->getMessage() );
            return 'Exception: ' . $e->getMessage();
        }
    }
   /*
     * cmdStatus - Parameter is the door Id
     */
    function cmdStatus()
    {
        global $me;
        try {
            if ( !isset( $_GET["id"] ) ) {
                throw new Exception( 'Missing arg "id"' );
            }
            $doorId = $_GET[ "id" ];  // Get Parameter
/*            Logger::LogMessage( $me, -1, "Status request for door: " . $doorId );    */
/*            $State = MgServer::getState( $doorId );    */
            $status = fopen("status".$doorId, r) or die("Unable to open file");
            $state = fread($status,filesize("status".$doorId));

            fclose($status);
            echo $state ;

/*            return( 'OK' . $State );
*/
        } catch( Exception $e ) {
            Logger::logError( $me, 'Exception: ' . $e->getMessage() );
            return 'Exception: ' . $e->getMessage();
        }
    } 

    
   
?>
