<?php
/****************************************************************
    config.php
    Copyright (C) 2016, Tom Milner
    All Rights Reserved
    September 7, 2016
*****************************************************************/

require_once( 'lib/door.php' );
require_once( 'lib/logger.php' );
require_once( 'lib/macro.php' );
require_once( 'lib/xmlutil.php' );

/**
 * Manages the configuration saved in XML file.
 * 
 * Usage:
 *        // Returns MyGarage main page.
 *      $cfg = new Config();
 *        // Get array of door objects
 *      $list = $cfg->getDoors();
 *
 * Revisions:
 *  06/30/2016: Initial
 *  09/07/2016: Breakout monitor settings
 *  09/25/2016: Move Passive/Active into settings rather that another file.
 */ 
class Config
{
    const Version   = '09/25/2016' ;
    const CfgFilename = 'config/garage.xml' ;
    const TemFilename = 'templates/garage.tem' ;

      // Attribute keys
    const Sim           = "simulation" ;
    const LogLevel      = "loglevel" ;
    const Revised       = "revised" ;

      // Monitor keys
    const Monitor       = "monitor" ;
    const MonitorState  = "monitor-state" ;
    const MonitorEmail  = "monitor-email" ;
    const MonitorSms    = "monitor-sms" ;
    const EmailFromAcct = "email-from-account" ;
    const EmailFromPass = "email-from-passwd" ;

      // Door keys
    const Door      = "door" ;
    const WaitTimes = "wait-times" ;
    const OpenWait  = "open-wait" ;
    const CloseWait = "close-wait" ;
    const DoorName  = "doorname" ;
    const GpioPinR  = "gpio-pinR" ;
    const GpioPinM  = "gpio-pinM" ;

    public $_attrFields = array (
        self::Sim, self::LogLevel, self::Revised,
    );

    public $_monitorFields = array (
        self::MonitorState ,
        self::MonitorEmail ,
        self::MonitorSms ,
        self::EmailFromAcct ,
        self::EmailFromPass ,
    );

    public $_doorFields = array (
        self::OpenWait,
        self::CloseWait,
        self::DoorName,
        self::GpioPinR,
        self::GpioPinM,
    );

    public $_properties = array() ;
    public $_doors      = array() ;

        
      
   /**
    * Constructor - Automatically loads config
    */
    function __construct()
    {
        $this->loadConfig();
    }


   /**
    * Returns true if simulated environment, or false indicating communication
    * with real hardware.
    */
    public function isSimulation()
    {
        return $this->getProperty( self::Sim ) == 'yes' ;
    }

   /**
    * Returns the named configuration property.
    */
    public function getProperty( $name )
    {
        return $this->_properties[ $name ];
    }

   /**
    * Assigns the named property a value.
    */
    public function setProperty( $name, $value )
    {
        $this->_properties[ $name ] = $value ;
    }


   /**
    * Returns an array of door objects.
    * @exception thrown
    */
    public function getDoors()
    {
        if ( $this->_doors == null ) {
            $this->loadConfig();
        }
        return $this->_doors;
    }

   /**
    * Reads the garage definition and populates globals.
    */
    public function loadConfig()
    {
        $me = "loadConfig";
        $this->_doors = array();
        
        try {
	        if ( !file_exists( self::CfgFilename ) ) {
	            $this->_doors = null ;
	            $msg = 'Missing config file, "' . self::CfgFilename . '".';
	            // Logger::panic( $me, $msg );
	            throw new Exception( $msg );
	        }
	
	        $dom = new DOMDocument() ;
	        $dom->load( self::CfgFilename );
	        $root = $dom->documentElement ;
              // load attributes
            foreach( $this->_attrFields as $attr ) {
                $this->setProperty( $attr, $root->getAttribute( $attr ) );
            }
	        $major = XmlUtil::firstChild( $root );
	        while ( $major ) {
                $door = null ;
                if ( $major->nodeName == self::Monitor ) {
    	            $node = XmlUtil::firstChild( $major );
    	            while ( $node ) {
                        $name = $node->nodeName ;
    	                $value = XmlUtil::normalize( $node->nodeValue );
                        $this->setProperty( $name, $value );
    	                $node = XmlUtil::nextSibling( $node );
                    }
                }
                if ( $major->nodeName == self::WaitTimes ) {
    	            $node = XmlUtil::firstChild( $major );
    	            while ( $node ) {
                        $name = $node->nodeName ;
    	                $value = XmlUtil::normalize( $node->nodeValue );
                        $this->setProperty( $name, $value );
    	                $node = XmlUtil::nextSibling( $node );
                    }
                }
                if ( $major->nodeName == self::Door ) {
    	            $node = XmlUtil::firstChild( $major );
    	            while ( $node ) {
    	                if ( $node->nodeName == self::DoorName ) {
    	                    $name = XmlUtil::normalize( $node->nodeValue );
    	                } else if ( $node->nodeName == self::GpioPinR ) {
    	                    $pinR = XmlUtil::normalize( $node->nodeValue );
    	                } else if ( $node->nodeName == self::GpioPinM ) {
    	                    $pinM= XmlUtil::normalize( $node->nodeValue );
    	                }
    	                $node = XmlUtil::nextSibling( $node );
                    }
	                $door = new Door( $name, $pinR, $pinM );
	            }
                if ( $door != null ) {
	                $this->_doors[] = $door ;
                }
	            $major = XmlUtil::nextSibling( $major );
	        }

        } catch( Exception $e ) {
            Logger::LogError( $me, "Exception: " . $e->getMessage() );
              // ---------------------------------
              // Default configuration set here
              // ---------------------------------
            $this->setProperty( self::Sim,      'yes' );
            $this->setProperty( self::Revised,  '???' );
            $this->setProperty( self::LogLevel, 0 );
              // Monitor
            foreach ( $this->_monitorFields as $field ) {
                $this->setProperty( $field, "" );
            }
            $this->setProperty( self::MonitorState, 'Passive' );
              // Wait times and doors (1 defined)
            $this->setProperty( self::OpenWait,    3 );
            $this->setProperty( self::CloseWait,   8 );
	        $this->_doors[] = new Door( 'Door 1', 18, 24 );
	        $this->_doors[] = new Door( '', 19, 23 );
	        $this->_doors[] = new Door( '', 20, 22 );
        }

          // Debug dump
        foreach( $this->_properties as $name => $value ) {
            if ( $name==self::EmailFromAcct || $name==self::EmailFromPass ) {
                // ignore
            } else {
                Logger::logMessage( $me, 2, "property[$name]=[$value]" );
            }
        }
        for( $dx=0; $dx<3; $dx++ ) {
            $door = $this->_doors[$dx] ;
            $name = $door->getName();
            $pinR = $door->getPinR();
            $pinM = $door->getPinM();
            Logger::logMessage( $me, 2, "door[$dx]=[$name,$pinR,$pinM]" );
        }
    }
    

   /**
    * Updates the garage definition from settingsA.php page into current
    * properties.
    */
    public function updateConfig()
    {
        $me = "updateConfig";
          // Attributes
        $this->setProperty( self::Sim, 'no' );
        if ( isset( $_POST[self::Sim] ) && $_POST[self::Sim] == 'on' ) {
            $this->setProperty( self::Sim, 'yes' );
        }
        $value = XmlUtil::normalize( $_POST[ self::LogLevel ] );
        $this->setProperty( self::LogLevel, $value );
          // Monitor section (ignore state)
        foreach ( $this->_monitorFields as $field ) {
            if ( $field != self::MonitorState ) {
                $value = XmlUtil::normalize( $_POST[ $field ] );
                $this->setProperty( $field,  $value );
            }
        }
          // Wait times
        $value = XmlUtil::normalize( $_POST[ self::OpenWait ] );
        $this->setProperty( self::OpenWait, $value );
        $value = XmlUtil::normalize( $_POST[ self::CloseWait ] );
        $this->setProperty( self::CloseWait, $value );
          // Doors
        $this->_doors = array();
        for( $dx=0; $dx<3; $dx++ ) {
            $doorX= self::Door . $dx;
            $name = XmlUtil::normalize( $_POST[ $doorX . "name" ] );
            $pinR = $_POST[ $doorX . "pinR" ];
            $pinM = $_POST[ $doorX . "pinM" ];
	        $this->_doors[] = new Door( $name, $pinR, $pinM );
        }
          // Post
        $this->saveConfig();
    }
    

   /**
    * Flushes the garage definition from properties back
    * into config file.
    */
    public function saveConfig()
    {
        $me = "saveConfig";

        Logger::setPriority( $this->getProperty( Config::LogLevel ) );
        $filename = self::CfgFilename;
        try {
            Logger::logMessage( $me, 2, "Begin Save" );
            $mac = array() ;
              // Attributes
            foreach( $this->_attrFields as $field ) {
                $mac[ $field ] = $this->getProperty( $field );
            }
	        $d = new DateTime( 'America/Los_Angeles' );
	        $mac[ self::Revised ] = $d->format( 'm-d-Y' );
              // Monitor
            foreach ( $this->_monitorFields as $field ) {
                $mac[ $field ] = $this->getProperty( $field );
            }
              // Wait times
            $mac[ self::OpenWait ]  = $this->getProperty( self::OpenWait );
            $mac[ self::CloseWait ] = $this->getProperty( self::CloseWait );

              // Doors
            for( $dx=0; $dx<3; $dx++ ) {
                $doorX = "door" . $dx;
                $door = $this->_doors[ $dx ];
                $mac[ $doorX . "name" ] = $door->getName();
                $mac[ $doorX . "pinR" ] = $door->getPinR();
                $mac[ $doorX . "pinM" ] = $door->getPinM();
            }
              // Debug dump
            foreach( $mac as $key => $value ) {
                if ( $key==self::EmailFromAcct || $key==self::EmailFromPass ) {
                    // ignore
                } else {
                    Logger::logMessage( $me, 2, "mac[$key]=[$value]" );
                }
            }

              // Read/Write template
	        $fi = fopen( self::TemFilename, "r" );
	        $fo = fopen( $filename, 'w' );
	        while (( $line = fgets( $fi )) !== false ) {
	            $oline = Macro::replace( $line, $mac );
	            fwrite( $fo, $oline );
	        }
	        fclose( $fi );
	        fclose( $fo );
            Logger::logMessage( $me, 0, 'Configuration Updated Successfully' );

        } catch( Exception $e ) {
            Logger::logError( $me, $e->getMessage() );
            throw $e ;  // Propagate
        }
    }


   /**
    * Testing routine.
    */
    public static function test()
    {
        echo "<p>-----[ Config test " . self::Version . " ]-----" ;
        $cfg = new Config();
        echo '<br/>Revision: ' . $cfg->getProperty( 'revision' );
        echo '<br/>Mode: ' . ($cfg->isSimulation() ? 'Simulation' : 'Real' );
        foreach( $cfg->getDoors() as $door ) {
            echo '<br/>Door: ' . $door->toString() ;
        }
    }
}
?>
