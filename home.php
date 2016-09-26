<?php
    require_once( 'lib/session.php' );
    require_once( 'lib/mgserver.php' );
    Session::verifySession( Session::User );
    MgServer::main();
?>
