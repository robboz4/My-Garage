<?php
/****************************************************************
    door.php
    Copyright (C) 2016, Tom Milner
    All Rights Reserved
    June 30, 2016
*****************************************************************/


/**
 * Simple container for all the door properties.
 * 
 * Usage:
 *      $door = new Door( $name, $pinR, $pinM );
 *      $door->getName();
 *      $door->getPinR();       // Retrieves the "relay" pin for the door
 *      $door->getPinM();       // Retrieves the "mag sensor" pin for the door
 *
 * Revisions:
 *  06/30/2016: Initial
 */ 
class Door
{
    const Version   = '06/30/2016' ;

    public $name ;      // Name assigned to door
    public $pinR ;      // Relay Pin
    public $pinM ;      // Sensor Pin

        
      
   /**
    * Constructor - Automatically with all properties
    */
    function __construct( $name, $pinR, $pinM )
    {
        $this->name = $name ;
        $this->pinR = $pinR ;
        $this->pinM = $pinM ;
    }


   /**
    * Returns the door's name.
    */
    public function getName()
    {
        return $this->name ;
    }


   /**
    * Returns the door's relay pin.
    */
    public function getPinR()
    {
        return $this->pinR ;
    }


   /**
    * Returns the door's pinM.
    */
    public function getPinM()
    {
        return $this->pinM ;
    }


   /**
    * Returns the formatted name.
    */
    public function toString()
    {
        return sprintf( '%s [pin%s/%s]', 
                            $this->getName(),
                            $this->getPinR(),
                            $this->getPinM() );
    }
}
?>
