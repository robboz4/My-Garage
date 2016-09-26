<?php
/****************************************************************
    logger.php
    Copyright (C) 2012, Tom Milner
    All Rights Reserved
    July 11, 2012
*****************************************************************/


/**
 * This is a crude logging facility, way simplier than Apache's Log4j or
 * Log4Net... but its a simple application.  ;-)
 *
 * This logger "rolls" log files after they reach "$_maxsize", keeping
 * the old logs with a timestamp on their name.
 *
 * Log messages also have a priority to allow filtering of messages.
 *      -1: The message is an Error message
 *      0: An important ("must log") message
 *      1: A slightly verbose message
 *      2: An even more verbose message
 *      3: Chatty...
 *
 * Lower "$_priority" to filter out the verbose messages if less logging is 
 * desired.  Turn "$_enableLogging" to false to disable ALL logging.
 *
 * Portability:
 *    Modify the function "getVar()" when porting.
 *
 * Sample Usage:
 *    Logger::logMessage( "MyFunc", 0, "This is the log message text" );
 *    Logger::logMessage( "MyFunc", 2, "This is a verbose message" );
 *    Logger::logError  ( "MyFunc", "An error occurred" );
 *    Logger::panic     ( "MyFunc", "Catastrophic error occurred" );
 *
 * Revisions:
 *  07/11/2012: Initial
 *  07/07/2015: Add suffix to target file on rollover.
 *
 */
class Logger
{
    const Version = "07/07/2015" ;

    public static $_priority = 0 ;               // logging threshold
    public static $_enableLogging = true ;
    public static $_filebase = 'logs/activity' ; // Base of logfile name
    public static $_maxsize = 1000000 ;          // Max size of log file


   /**
    * Returns current logging priority.
    */
    public static function getPriority()
    {
        return self::$_priority;
    }

   /**
    * Sets the new logging priority, and returns current.  Like:
    *
    *     $oldLevel = Logger::setPriority( 0 );
    *        ...
    *     Logger::setPriority( $oldLevel );
    */
    public static function setPriority( $newPriority )
    {
        $old = self::$_priority;
        self::$_priority = $newPriority ;
        /***
        self::logMessage( 'Logger', 0, 
            "Priority changed, old=$old new=$newPriority" );
            ***/
        return $old ;
    }


   /**
    * Clears (Deletes) the log file.
    */
    public static function clearLog()
    {
        $filename = self::$_filebase . ".log" ;
        if ( file_exists( $filename ) ) {
            @unlink( $filename );
        }
    }


   /**
    * Archives the current log file and starts a new one.
    * This is normally done if the current file exceeds the max size.
    */
    public static function rollLogFile()
    {
        $filebase = self::$_filebase ;
        $filename = "$filebase" . ".log" ;
        $q = array( '', 'A','B','C','D','E','F','G','H','I','J','K','L',
                    'M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z' );

        if ( file_exists ( $filename ) ) {
            date_default_timezone_set( 'America/Los_Angeles' );
            $timestamp = date( 'Y-m-d' );
            foreach( $q as $qualifier ) {
                $newname = sprintf( '%s.%s%s.log',
                                    $filebase,
                                    $timestamp,
                                    $qualifier );
                if ( !file_exists( $newname ) ) {
                    rename( $filename, $newname );
                    self::logMessage( "Logger", 0, "Log file rolled" );
                    break ;
                }
            }
        }
    }


   /**
    * Called to register a shutdown handler to catch PHP errors.
    * Sample Usage: Logger::registerShutdown();
    */
    public static function registerShutdown()
    {
        register_shutdown_function( 'myCrashAndBurn' );
    }


   /**
    * This is invoked when the PHP engine shuts down, including when
    * there is an error.  When there is an error we log it.
    */
    public static function crashAndBurn()
    {
        $me = 'crashAndBurn';

        $marker = error_get_last();
        if ( $marker == NULL ) {
            return ;    // exit occurred normally
        }
        $msg = sprintf( '%s, in %s on line %s',
                    $marker[ 'message' ],
                    $marker[ 'file' ],
                    $marker[ 'line' ] );

        if ( $marker['type'] == E_WARNING ) {
            self::panic( $me, 'Warning: ' . $msg );
            $trace = self::getStackTrace();
            self::logError( $me, 'E_WARNING: ' . $trace );
            return ;
        }
        if ( $marker['type'] == E_ERROR ) {
            self::panic( $me, $msg );
            $trace = self::getStackTrace();
            self::logError( $me, 'E_ERROR: ' . $trace );
        }
    }

   /**
    * Logs a panic (catastrophic) message into the log.
    *
    * @param string $fcn    Function name detecting the issue
    * @param string $msg    Log message.
    */
    public static function panic( $fcn, $msg )
    {
        self::logMessage( "***PANIC***", -1,"*******************************" );
        self::logMessage( $fcn, -1, $msg );
        self::logMessage( "***PANIC***", -1,"*******************************" );

    }

   /**
    * Logs an error message into the log.
    *
    * @param string $fcn    Function name detecting the issue
    * @param string $msg    Log message.
    */
    public static function logError( $fcn, $msg )
    {
        self::logMessage( $fcn, -1, $msg );
    }


   /**
    * Logs a message into the log.
    *
    * @param string $fcn        Function name posting the message.
    * @param string $priority   -1=error 0=always 1=if verbose, etc.
    * @param string $msg        Log message.
    */
    public static function logMessage( $fcn, $priority, $msg )
    {
        try {
            if ( !self::$_enableLogging ) return ; // Logging disabled
            if ( $priority > self::$_priority ) return ;
    
            $hdr = "  " ;
            if ( $priority < 0 ) {
                $hdr = "**" ;
            }
    
            $filename = self::$_filebase . ".log" ;
    
              // Roll logfile if necessary
            if ( file_exists ( $filename ) ) {
                if ( filesize ( $filename ) > self::$_maxsize ) {
                    self::rollLogFile() ;
                }
            }
    
            $usr = self::getVar( "UserId" ) ;
            if ( !isset( $usr ) ) {
                $usr = "0" ;
            }
            
            $fh = fopen( $filename, 'a' );
            /***
            $log = sprintf( "%s %03d %16s %s%s\n", 
                self::getTimeStamp(), $usr, $fcn, $hdr, $msg );
            ***/
            $log = sprintf( "%s %16s %s%s\n", 
                self::getTimeStamp(), $fcn, $hdr, $msg );
            fwrite( $fh, $log );
            fclose( $fh );

        } catch( Exception $e ) {
            // echo '<br/>Ooops ' . $e->getMessage() ;
        }
    }



   /**
    * Returns a stack trace as a string.
    */
    public static function getStackTrace()
    {
        $trace = debug_backtrace() ;
        $t = "Stack Trace Follows \n"
           . "--------------------\n" ;
        $top = true ;
        foreach( $trace as $entry ) {
            if ( $top ) {
                $top = false ;  // Ignore first entry.. its us.
            } else {
                $t .= "  " . self::fmtEntry( $entry ) . "\n" ;
            }
        }
        return $t;
    }

   /**
    * Formats a stack trace entry,
    * Like: foo.php[ 12]: charlie(Hello, World)
    */
    public static function fmtEntry( $stack )
    {
        try {
            $file = basename( $stack[ "file" ] );
            $line = $stack[ "line" ];
            $fmt = sprintf( "%15s[%4d]: ", $file, $line );
    
            $fn = $stack[ "function" ];
            if ( isset( $fmt ) ) {
                $fmt .= "$fn(" ;
            }
            $args = $stack[ "args" ] ;
            if ( isset( $args ) ) {
                $sep = "" ;
                foreach( $args as $key => $value ) {
                    if ( gettype($value) == 'string' ) {
                        $fmt .= $sep . $value ;
                        $sep = ", " ;
                    }
                }
            }
            $fmt .= ")" ;
            return $fmt ;
        } catch( Exception $e ) {
            return '(' . $e->getMessage() . ')' ;
        }
    }


   /**
    * Utility to retrieve current date in standard format date string.
    * This utility returns it in Pacific Time zone, format: YYYY-MM-DD.
    */
    public static function stdDate()
    {
        $date = new DateTime( 'now', new DateTimeZone('America/Los_Angeles') );
        return $date->format( 'Y-m-d' );
    }


   /**
    * Utility to retrieve current time in standard format.
    * This utility returns it in Pacific Time zone,
    * Like "2013-10-24 16:38:08".
    */
    public static function getTimeStamp()
    {
          // Default to Pacific Time
        // date_default_timezone_set( 'America/Los_Angeles' );
        // $timestamp = date( 'Y-m-d H:i:s' );
        $date = new DateTime( "now", new DateTimeZone('America/Los_Angeles') );
        // $date->setTimezone( new DateTimeZone( 'America/Los_Angeles' ) );
        return $date->format( 'Y-m-d H:i:s' );
    }


   /**
    * Returns the value of a session variable by name.
    * (Opposite of setVar).  Typically use the form element name or ID.
    *
    * This code was copied from the Session library in order to remove
    * the library depency on Session.php (for portability purposes).  This 
    * code may need to be modified on a site by site basis, or replaced with
    * Session::getVar(...) calls.
    *
    * @param string $name   Name of the variable whose value is returned.
    */
    public static function getVar( $name )
    {
        if ( !isset( $_SESSION[ 'tsession' ] ) ) {
            return null ;
        }
        $v = $_SESSION[ 'tsession' ][ $name ];
        return $v ;
    }

}   // End of Logger



/*
 * This is a hook for the register_shutdown_function call
 */
function myCrashAndBurn()
{
    Logger::crashAndBurn();
}

?>
