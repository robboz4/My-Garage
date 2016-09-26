<?php
/****************************************************************
    logm.php
    Copyright (C) 2016, Tom Milner
    All Rights Reserved
    August 3, 2016

    Provides remote logging capabilities by merely loading this page.
    Suitable for Ajax usage also.

    Usage: http://logm.php?fcn=xxx&pri=yyy&msg=zzz
    Where:
        fcn [optional]: function name.  Default is "monitor".
        pri [optional]: Priority (-1..3, where -1 is an error, 1 is default).
        msg: Log message to report 

    HTML Example:
        http://logm.php&msg=Here's a message!

    Python Example:
        import urllib
        urllib.urlopen("http://localhost:86/PiGMi/logm.php?msg=Starting" )

        "I forgot that I had used port 86. The only way I could get the 
        port number to take was add localhost. Now to convert my logging 
        routine to use this  method."

*****************************************************************/
    require_once( 'lib/config.php' );
    require_once( 'lib/logger.php' );

    try {
        $fcn = $_GET[ 'fcn' ];
        if ( !isset( $fcn ) || $fcn == "" ) {
            $fcn = "monitor" ;
        }
        $pri = $_GET[ 'pri' ];
        if ( !isset( $pri ) || $pri == "" ) {
            $pri = 1 ;
        }
        $msg = $_GET[ 'msg' ];
        if ( !isset( $msg ) || $msg == "" ) {
            throw new Exception( "Missing argument \"msg\"" );
        }

        $cfg = new Config();
        Logger::setPriority( $cfg->getProperty( 'loglevel' ) );
        Logger::logMessage( $fcn, $pri, $msg );
          // Result suitable for Ajax usage
        echo sprintf( 'OK: fcn="%s", pri="%d", msg="%s"', $fcn, $pri, $msg );
        exit;

    } catch( Exception $e ) {
        echo 'No: ' . $e->getMessage();
        exit;
    }
?>
