<?php
/****************************************************************
    MgServer.php
    Copyright (C) 2016, Tom Milner
    All Rights Reserved
    June 30, 2016
*****************************************************************/

require_once( 'lib/config.php' );
require_once( 'lib/logger.php' );
require_once( 'lib/macro.php' );

/**
 * Provides server functionality for MyGarage Web Application.
 *
 * Each garage door has 2 pins; an "action" pin (pin1), and a sensor pin
 * (pin2).  Pin2 is used to determine if the door moved.  If you open a
 * door but it doesn't move after a while, pin2's value will remain the same,
 * indicating a "jammed" state.  If you close a door, and pin2's state 
 * doesn't change after 15 seconds, then there must be an obstacle preventing
 * the door from closing.
 * 
 * Usage:
 *        // Returns MyGarage main page.
 *      MgServer::main();
 *        ...
 *        // Via Ajax, open a door
 *      MgServer::doAction( 0, "Open" );
 *
 *
 * Revisions:
 *  06/30/2016: Initial
 *  07/09/2016: First functional (w/ config update).
 *  07/29/2016: Changed gpio_init.
 *  07/30/2016: Various GPIO changes from Dave's testing.
 *  08/03/2016: Added monitor arm/disarm.
 *  08/07/2016: Transition to w3 css.
 *  08/30/2016: Session support, main menu added, about page added.
 *  09/25/2016: Final updates
 */ 
class MgServer
{
    const Version   = '09/25/2016' ;

      // Sensor states
    const MagnetsTogether = "0";    // Door and wall magnets aligned  
    const MagnetsApart    = "1";    // Door and wall magnets not aligned  

      // Door states
    const Closed    = self::MagnetsTogether ;
    const Opened    = self::MagnetsApart ;
    const Jammed    = "2" ;     // Door will not move, assumed Closed
    const Obstacle  = "3" ;     // Door will not close, assumed Open

      // Class variables
    public $cfg = null ;        // Loaded automatically
    public $doors ;
    public $gpio_version = "";
    public $simulation = true ;

      // Gpio commands
    public $gpio_get_version = array( 
        "gpio -v" 
    );
    public $gpio_init = array(
        "gpio -g write {{pin1}} 1",         // setting relays to off first
        "gpio -g mode {{pin1}} out",        // Setting mode to stop chatter
        "gpio -g mode {{pin2}} up",
        "gpio -g mode {{pin2}} in",
    );
    public $gpio_get_state = array(         // Gets State of Door
        "gpio -g read {{pin2}}"             // Read status
    );
    public $gpio_toggle = array(            // Open or Close Door
        "gpio -g write {{pin1}} 0",         // Press the button
        "sleep 1",
        "gpio -g write {{pin1}} 1"          // Release the button
    );


   /**
    * Constructor - Automatically loads config
    */
    function __construct()
    {
        $this->cfg = new Config();
        $this->simulation = $this->cfg->isSimulation();
        $this->doors = $this->cfg->getDoors();
        Logger::setPriority( $this->cfg->getProperty( Config::LogLevel ) );
    }
      
   /**
    * Sends the main page back to stdout.
    */
    public static function main()
    {
        $me = 'MgServer' ;
        Logger::logMessage('', 0, '' );
        Logger::logMessage($me,0,'===== Home Page ' . self::Version . ' =====');

        $mac = array();
        $mac[ 'LogPage' ] = 'logs/activity.log' ;
        $mac[ 'Doors'   ] = '<b>Fatal error encountered, refer to log file.';
        try {
            $mg = new MgServer();
            $mac[ 'Version' ] = self::Version ;
            if ( $mg->simulation ) {
                $mac[ 'HVersion' ] = 'Sim' ;
            } else {
                $mac[ 'HVersion' ] = '???' ;
                $mg->gpio_version = $mg->getVersion();
                $mac[ 'HVersion' ] = $mg->gpio_version ;
            }
    
            $mg->initialize();
    
              // Populate macros for page templates
            $n = 0 ;
            for ( $dx=0; $dx<count($mg->doors); $dx++ ) {
                if ( $mg->doors[$dx]->getName() != "" ) {
                    $n++ ;
                }
            }
            $mac[ 'Count' ] = $n ;
            switch( $n ) {
                case 1:     $mac[ 'W3Scale' ] = "w3-twothird" ; break;
                case 2:     $mac[ 'W3Scale' ] = "w3-half" ;  break;
                case 3:     $mac[ 'W3Scale' ] = "w3-third" ; break;
                default:    $mac[ 'W3Scale' ] = "w3-third" ; break;
            }
    
              // Fill in door template for each door
            $def = "";
            for ( $dx=0; $dx<count($mg->doors); $dx++ ) {

                  // Only display doors with names
                if ( $mg->doors[$dx]->getName() != "" ) {
                    $state = $mg->getState( $dx );

                    $mac[ 'Dx'       ] = $dx ;
                    $mac[ 'DoorName' ] = $mg->doors[$dx]->getName();
                    $mac[ 'State'    ] = $mg->mapState2Text( $state );
                    $mac[ 'Action'   ] = $mg->setAction( $state );

                    $def .= Macro::file2String( "templates/door.tem", $mac );
                }
            }
            $mac[ 'Doors' ] = $def ;

        } catch( Exception $e ) {
            Logger::LogError( $me, "Exception: " . $e->getMessage() );
        }

          // Fill in main template
        foreach( $mac as $name => $value ) {
            if ( $name != 'Doors' ) {
                Logger::logMessage( $me, 2, "mac[$name] = [$value]" );
            }
        }
        echo Macro::file2String( "templates/main.tem", $mac );
        // print_r( $mac );
    }


   /**
    * Sleeps and logs it.
    */
    public static function sleep( $caller, $seconds )
    {
        Logger::logMessage( $caller, 1, "Sleep[ $seconds ]" );
        sleep( $seconds );
    }


   /**
    * Returns Version string of hardware.
    */
    public function getVersion()
    {
        $me = 'getVersion' ;

        $out = $this->gpio( 'Version', $this->gpio_get_version );
        $tokens = explode( ' ', $out );
        $v = $tokens[2] ;
        $mode = ( $this->simulation ? 'Sim' : 'Real' );
        Logger::logMessage( $me, 0, 'Version: [' . $v . '] ' . $mode );
        return $v ;
    }


   /**
    * Sets pins to correct state.
    */
    public function initialize()
    {
        $me = 'initialize' ;

        foreach( $this->doors as $door ) {
            $mac = array();
            $mac[ "pin1" ] = $door->getPinR();
            $mac[ "pin2" ] = $door->getPinM();
            $out = $this->gpio( 'Init', $this->gpio_init, $mac );
        }
    }


   /**
    * Returns the open/close state of given door.  The other
    * states must be determined other ways.
    * The magnet sensor is assumend to be at the bottom of the door.  Therefore,
    * if the magnets are together, the door must be closed.
    */
    public function getState( $dx )
    {
        $me = 'getState' ;

        $door = $this->doors[ $dx ];
        Logger::logMessage( $me, 2, "dx=[$dx] Door=[" . $door->toString(). "]");
        $mac = array();
        $mac[ "pin2" ] = $door->getPinM();

          // construct commands and execute
        $magState = $this->gpio( 'Door State', $this->gpio_get_state, $mac );
        if ( $magState == self::MagnetsTogether ) {
            $state = self::Closed ;
        } else {
            $state = self::Opened ;
        }

        $name = $door->getName();
        $t = $this->mapState2Text( $state );
        Logger::logMessage( $me, 0, "dx=[$dx] State=[$state] $t $name" );
        return $state;
    }


   /**
    * Changes the state of the door.  This will be called by Ajax code.
    *
    * @param $dx        Door index, 0..N
    * @param $action    One of the action commands, like "Open" or "Close"
    * @returns          New Door state.
    * @throws exception on error.
    */
    public static function doAction( $dx, $action )
    {
        $me = 'doAction' ;
        Logger::logMessage('', 0, '' );
        Logger::logMessage( $me, 0, "dx=[$dx] Action=[$action]" );

        $mg = new MgServer();
        $door = $mg->doors[ $dx ];
        try {
            // Do the real operation
            $mac = array();
            $mac[ "pin1" ] = $door->getPinR();

              // -----------------------------------------------
              // Door open action -- Door must be already closed
              // -----------------------------------------------
            if ( $action == "Open" ) {

                $mg->gpio( 'Button Press', $mg->gpio_toggle, $mac );

                  // Wait a bit...
                $mg->sleep( $me, $mg->cfg->getProperty( Config::OpenWait ) );        
                  // Check state to see if door moved.
                $newState = $mg->getState( $dx );

                if ( $mg->simulation && $dx == 2 ) {
                    // Door 2: pretend door didn't open.
                    $newState = self::Closed ;
                }

                if ( $newState == self::Closed ) {
                    $state = self::Jammed;
                    Logger::logMessage( $me, 0, "New State[$state] = Jammed" );
                    return $state ;    // Door didn't move
                }


            } else {
                // ---------------------
                // Door Close Action
                // ---------------------
                $mg->gpio( 'Button Press', $mg->gpio_toggle, $mac );

                  // Wait a long time...
                $mg->sleep( $me, $mg->cfg->getProperty( Config::CloseWait ) );        
                  // Check state to see if door moved.
                $newState = $mg->getState( $dx );

                if ( $mg->simulation && $dx == 1 ) {
                    // Door 1: pretend door didn't move.
                    $newState = self::Opened ;
                }

                if ( $newState == self::Opened ) {
                    $state = self::Obstacle;
                    Logger::logMessage( $me, 0, "New State[$state] = Obstacle");
                    return $state ;    // Door didn't close
                }
            }
              // Normal return
            $text = $mg->mapState2Text( $newState );
            Logger::logMessage( $me, 0, "New State[$newState] = $text" );
            return $newState ;

        } catch( Exception $e ) {
            Logger::logError( $me, 'Exception: ' . $e->getMessage() );
            throw $e;
        }
    }


   /**
    * Issues a series of GPIO commands.
    *
    * @param $intent    Intention string for logging
    * @param $cmds      An array of gpio commands.
    * @param $mac       Macros to expand into commands.
    * @returns          An array of output from the last command.
    * @throws exception on error.
    */
    public function gpio( $intent, $cmds, $mac = null )
    {
        $me = 'gpio' ;
        if ( $this->simulation ) {
            return $this->simGpio( $intent, $cmds, $mac );
        }

        Logger::logMessage( $me, 1, "---[ $intent ]---" );

        // TODO: Remove this after development
        /***
        if ( $intent=='Version' || $intent=='Init' || $intent=='Door State' ) {
            return $this->simGpio( $intent, $cmds, $mac );
        }
        ***/
          // construct commands and execute
        $out = array();
        foreach( $cmds as $cmd ) {
            $cmd = Macro::replace( $cmd, $mac );
            Logger::logMessage( $me, 1, "Cmd=[$cmd]" );
            unset( $out );
              // Execute external command
            exec( $cmd, $out, $retval );
            if ( $retval != 0 ) {
                $msg = "$me: Error($retval): $cmd" ;
                // Logger::logError( $me, $msg );
                throw new Exception( $msg );
            }
            if ( !isset( $out ) ) {
                throw new Exception( 'No return string from Exec' );
            }
        }
        $firstLine = trim( $out[0] );
        Logger::logMessage( $me, 1, "gpio out=[$firstLine]" );
        return $firstLine;  // Just return first line as a string
    }


   /**
    * Simulates GPIO commands.
    *
    * @param $intent    Intention string for logging
    * @param $cmds      An array of gpio commands.
    * @param $mac       Macros to expand into commands.
    * @returns          An array of output from the last command.
    * @throws exception on error.
    */
    public function simGpio( $intent, $cmds, $mac = null )
    {
        $me = 'simGpio' ;
        Logger::logMessage( $me, 1, "---[ $intent ]---" );
        if ( $intent == 'Version' ) {
            $x = array( "gpio version: 1.00" );
            return "gpio version: 1.00" ;

        } else if ( $intent == 'Init' ) {
            return '';

        } else if ( $intent == 'Button Press' ) {
            $pin = $mac[ 'pin1' ];
            $dx = $this->pin2Door( $pin, 1 );
            $state = $this->simGetState( $dx );
            if ( $dx == 0 ) {
                  // Toggle state
                $state = ($state == self::Closed ? self::Opened : self::Closed);
            }
            $this->simSaveState( $dx, $state );
            return '';

        } else if ( $intent == 'Door State' ) {
            $pin = $mac[ 'pin2' ];
            $dx = $this->pin2Door( $pin, 2 );
            $state = $this->simGetState( $dx );
            $text = $this->mapState2Text( $state );
            Logger::logMessage( $me, 2, "Door $dx state [$state] $text" );
            return $state ;

        } else {
            $msg = 'Unknown intent [' . $intent . ']' ;
            Logger::logError( $me, $msg );
            throw new Exception( $msg );
        }
    }


   /**
    * Saves state of given door.
    */
    public function simSaveState( $dx, $state )
    {
        $me = 'simSaveState';
        if ( $dx == 1 || $dx == 2 ) {
            return ;    // State never changes
        }
        $filename = 'config/door' . $dx ;
        $f = fopen( $filename, 'w' );
        if ( !$f ) {
            Logger::logError( $me, "Cannot open file \"$filename\"" );
        } else {
            $text = $this->mapState2Text( $state );
            Logger::logMessage( $me,2, "Saving dx=[$dx] State [$state] $text" );
            fwrite( $f, '' . $state );
            fclose( $f );
        }
    }


   /**
    * Returns state of given door.
    */
    public function simGetState( $dx )
    {
        $me = 'simGetState';
        if ( $dx == 1 ) {
            return self::Opened ; // Always open
        } else if ( $dx == 2 ) {
            return self::Closed ; // Always Closed
        }

        $filename = 'config/door' . $dx ;
        $f = @fopen( $filename, 'r' );
        if ( !$f ) {
            // echo "Cannot open file \"$filename\"" ;
            Logger::logError( $me, "Cannot open file \"$filename\"" );
            return self::Closed;
        } else {
            $state = fgets( $f,8192 );
            $state = trim($state);
            fclose( $f );
            Logger::logMessage( $me, 0, "Door $dx, returning [$state]" );
            return $state ;
        }
    }


   /**
    * [Simulation] Maps a pin # to door index.
    */
    public function pin2Door( $pin, $type )
    {
        for( $dx=0; $dx<count($this->doors); $dx++ ) {
            $door = $this->doors[ $dx ];
            if ( $type == 1 ) {
                if ( $pin == $door->getPinR() ) return $dx;
            } else if ( $type == 2 ) {
                if ( $pin == $door->getPinM() ) return $dx;
            }
        }
    }


   /**
    * Returns state of given door.
    */
    public function mapState2Text( $state )
    {
        switch( $state ) {
            case self::Opened   : return 'Opened' ;
            case self::Closed   : return 'Closed' ;
            case self::Jammed   : return 'Jammed' ;
            case self::Obstacle : return 'Obstacle' ;
        }
    }


   /**
    * Sets the button action based upon the doors current state.
    *
    * @param $state         Current door state.
    */
    public function setAction( $state )
    {
        $me = "setAction";
        
        switch( $state ) {
            case self::Opened   : return 'Close' ;
            case self::Closed   : return 'Open' ;
            case self::Jammed   : return 'Open' ;
            case self::Obstacle : return 'Close' ;
        }
    }


   /**
    * Testing routine.
    */
    public static function test()
    {
        echo "<p>-----[ MyGarage test " . self::Version . " ]-----" ;
    }
}
?>
