<?php
/****************************************************************
    ajax.php
    Copyright (C) 2015, Tom Milner
    All Rights Reserved
    May 12, 2015

    General purpose Ajax target (see ajax.js).
    Usage: ajax.php?cmd="XYZ...", where "XYZ" is one of the 
           command routines below (i.e., "cmdXYZ").
*****************************************************************/
    require_once( 'lib/config.php' );
    require_once( 'lib/logger.php' );
    require_once( 'lib/mgserver.php' );

    $result = "";
    $me = "ajax" ;
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
    
    /*
     * cmdClear - Deletes the log file
     */
    function cmdClear()
    {
        try {
            Logger::clearLog();
            return( 'OK' );

        } catch( Exception $e ) {
            return 'Exception: ' . $e->getMessage();
        }
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

            $newState = MgServer::doAction( $doorId, 'Open' );
            return( 'OK' . $newState );

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

            $newState = MgServer::doAction( $doorId, 'Close' );
            return( 'OK' . $newState );

        } catch( Exception $e ) {
            Logger::logError( $me, 'Exception: ' . $e->getMessage() );
            return 'Exception: ' . $e->getMessage();
        }
    }
    
    
    /*
     * cmdMonitor - Toggle state
     */
    function cmdMonitor()
    {
        try {
            if ( !isset( $_GET["s"] ) ) {
                throw new Exception( 'missing arg "s"' );
            }
            $newState = $_GET[ "s" ];  // Get Parameter

            $cfg = new Config();
            $cfg->setProperty( Config::MonitorState, $newState );
            $cfg->saveConfig( Config::MonitorState, $newState );
            return( 'OK' );

        } catch( Exception $e ) {
            Logger::logError( $me, 'Exception: ' . $e->getMessage() );
            return 'Exception: ' . $e->getMessage();
        }
    }
?>
