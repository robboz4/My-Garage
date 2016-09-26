//  mg.js 
//  Copyright (C) 2016, Tom Milner (tomkmilner@gmail.com)
//  All Rights Reserved
//  June 30, 2016
//
//  Support for MyGarage project.
//  ---

    var _cur    = -1;   // Current selection number

    window.onload = onLoad;

   /**
    * Any startup goes here.
    */
    function onLoad() {
    }

    /**
     * Send a command (via Ajax) using to perform action on door.
     * The desired action is pulled from the value of the button.
     */
    function onAction( door )
    {
        _cur = -1;
        var action = getBtnLabel( door );
          // Highlight door icon
        actionDoor( door );
          // Change image
        var src = "images/doorOpening.gif" ;
        if ( action == "Close" ) {
            src = "images/doorClosing.gif" ;
        }
        setImage( door, src );

          // Pass the Action as a command, and the Door Id as a parameter.
        var arg = "&id=" + door ;
        ajaxSend( action, arg, onActionDone, door );
    }

    /**
     * Invoked when onAction command done, cookie has door Id.
     * Note: Image Id is "I{{Id}}"
     * Response contains "OK" + state
     */
    function onActionDone( cookie, response )
    {
        var door = cookie ;
          // Just check first 2 chars... 
        if ( response.substring( 0,2 ) != "OK" ) {
              // Its error text
            alert( "Ajax Failure: " + response );
            normalDoor( door );
            return ;
        }
          // Update state
        var state = response.substring( 2,3 );
        // alert( "Door=[" + door + "], State=[" + state + "]" );
          // Determine image based upon new state.
        var src = "images/doorClosed.gif" ;     // State 0
        var val = "Open" ;
        if ( state == "1" ) {
            src = "images/doorOpened.gif" ;
            val = "Close" ;
        } else if ( state == "2" ) {
            src = "images/doorJammed.gif" ;
            val = "Open" ;
        } else if ( state == "3" ) {
            src = "images/doorObstacle.gif" ;
            val = "Close" ;
        }
        setImage( door, src );
        setBtnLabel( door, val );
        normalDoor( door );
    }


    /**
     * Send a command (via Ajax) to delete the log file.
     */
    function onClearLog( eid )
    {
        ajaxSend( "Clear", "", onClearDone, eid );
    }

    /**
     * Invoked when onAction command done, cookie has door Id.
     * Note: Image Id is "I{{Id}}"
     */
    function onClearDone( cookie, response )
    {
          // Just check first 2 chars... 
        if ( response.substring( 0,2 ) != "OK" ) {
              // Its error text
            alert( "Ajax Failure: " + response );
        }
          // Disable button
        document.getElementById( cookie ).disabled = true ;
    }


    /**
     * Toggles monitor property
     */
    function onToggleMonitor( eid )
    {
        var armText    = "<b>Arm Monitor</b>" ;
        var disarmText = "<b>Disarm Monitor</b>" ;

        var label = document.getElementById( eid ).innerHTML ;
        if ( label == disarmText ) {
            ajaxSend( "Monitor", "&s=Passive", onToggleMonitorDone, eid );
              // Toggle label
            document.getElementById( eid ).innerHTML = armText ;
        } else {
            ajaxSend( "Monitor", "&s=Active", onToggleMonitorDone, eid );
              // Toggle label
            document.getElementById( eid ).innerHTML = disarmText ;
        }
    }

    /**
     * Just check for failure.
     */
    function onToggleMonitorDone( cookie, response )
    {
          // Just check first 2 chars... 
        if ( response.substring( 0,2 ) != "OK" ) {
              // Its error text
            alert( "Ajax Failure: " + response );
            return ;
        }
    }


      // Retrieves current button label for the door
    function getBtnLabel( door ) {
        var t = document.getElementById( 'Btn' + door ).innerHTML ;
          // Strip bold text if present
        if ( t.substr( 0,3 ) == "<b>" ) {
            t = t.substr( 3 );
            t = t.substr( 0, t.length-4 );
            t = t.trim();
        }
        return t ;
    }

      // Sets button label to new action for the door
    function setBtnLabel( door, newValue ) {
        var el = document.getElementById( 'Btn' + door );
        el.innerHTML = "<b>" + newValue + "</b>" ;
    }

      // Retrieves current image name for the door
    function getImage( door ) {
        return document.getElementById( "I" + door ).src ;
    }

      // Sets new image for the given door
    function setImage( door, newImgSrc ) {
        document.getElementById( "I" + door ).src = newImgSrc ;
    }

      // Indicate door in motion
    function actionDoor( door ) {
        var el = document.getElementById( 'Door' + door );
        el.style.borderColor = 'red' ;
        el.style.borderStyle = 'dashed' ;
        document.getElementById( 'Btn' + door ).disabled = true ;
    }

      // Indicate door still
    function normalDoor( door ) {
        var el = document.getElementById( 'Door' + door );
        el.style.borderStyle = 'none' ;
        document.getElementById( 'Btn' + door ).disabled = false ;
    }

      // Brings up the settings configuration page.
    function invokeSettings() {
        window.location = "settings.php" ;
    }

      // If either email or sms configured, then need email acct info
      // False return if email acct info needed.
    function validateMonitor() {
        var email  = document.getElementById( 'monitor-email' );
        var sms    = document.getElementById( 'monitor-sms' );
        var emaila = document.getElementById( 'email-from-account' );
        var emailp = document.getElementById( 'email-from-passwd' );
        if ( email.value == "" && sms.value == "" ) {
            return true;
        } else if ( emaila.value != "" && emailp.value != "" ) {
            return true;
        } else {
            alert( "Error: To configured an Email or SMS address requires\n "
                    + "that you also configre an Email Account and Password." );
            return false;
        }
    }
