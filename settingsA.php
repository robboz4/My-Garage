<?php
/****************************************************************
    settingsA.php
    Copyright (C) 2016, Tom Milner
    All Rights Reserved
    July 9, 2016
*****************************************************************/

    require_once( 'lib/config.php' );
    require_once( 'lib/session.php' );
    Session::verifySession( Session::User );

    try {
        $cfg = new Config() ;
        $cfg->updateConfig();
          // Just go to main screen
        header( 'Location: home.php' );
        exit();

    } catch( Exception $e ) {
        echo '<p/>Exception: ' . $e->getMessage();
        exit();
    }
?>
