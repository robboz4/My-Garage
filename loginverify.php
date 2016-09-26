<?php
/****************************************************************
    loginverify.php
    Copyright (C) 2016, Tom Milner (tomkmilner@gmail.com)
    All Rights Reserved
    August 26, 2016
*****************************************************************/
    session_start() ;   // Note: start must come before any HTML
    require_once( 'lib/logger.php' );
    require_once( 'lib/session.php' );

    $_errorMsg = "none" ;
    try {
        $a = $_POST[ "action"    ];
        $u = $_POST[ "username"  ];
        $p = $_POST[ "Mpassword" ];
        $s = $_POST[ "seed" ];
        if ( $a == "login" ) {
            $r = Session::login( $u, $p, $s ) ;
        } else if ( $a == "ca" ) {
            $r = Session::createAccount( $u, $p, $s );
        } else {
            throw new Exception( "Unknown action \"$a\". Contact developers." );
        }
        // Check to see if need to direct elsewhere
        if ( $r > 0 ) {
            // Note: this function ONLY returns if no bounceback
            Session::bounceBack();
        }
        Session::goHome();
        exit();

    } catch( Exception $e ) {
        $_errorMsg = $e->getMessage();
        Logger::LogError( 'loginVerify', "Exception: " . $e->getMessage() );
    }
?>

<html>
<head>
<title> Login Failed </title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" href="mg.css" />
<link rel="stylesheet" href="w3.css" />
<link rel="stylesheet" href="font-awesome-4.6.3/css/font-awesome.css" />
<link rel="stylesheet" href="font-awesome-4.6.3/css/font-awesome.min.css" />
<script src="lib/ajax.js"></script>
<script src="lib/mg.js"></script>
<style>
</style>
</head>

<body>
<header class="w3-container w3-blue" >
    <a title="Home" href="home.php" ><i class="fa fa-home fa-2x fa-fw" ></i></a>
    <h1 style="display: inline;" >&nbsp;&nbsp;&nbsp; Login Failed</h1> 
</header>

<div class="w3-container" >
    <p/>
    Your login attempt failed: <?php echo $_errorMsg; ?>
<?php
if ( !Session::isSecure() ) {
    echo '<font color="red"><br/>' ;
    echo 'Note: In order to update your account, ';
    echo 'you will need to run the browser ';
    echo 'directly from the web server (for security).';
    echo '</font>';
}
?>

    <p/>To try again, <a href="home.php">click here</a>.
</div>
</body>
</html>
