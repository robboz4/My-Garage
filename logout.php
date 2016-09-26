<?php
/****************************************************************
    logout.php
    Copyright (C) 2016, Tom Milner (tomkmilner@gmail.com)
    All Rights Reserved
    August 26, 2016
*****************************************************************/
    session_start() ;   // Note: start must come before any HTML
    require_once( 'lib/logger.php' );
    require_once( 'lib/session.php' );
    Session::logout();
?>
<html>
<head>
<title> Logout </title>
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
    <h1 style="display: inline;" >&nbsp;&nbsp;&nbsp; logout</h1> 
</header>

<div class="w3-container" >
    <h2>Logout</h2>
    <p/>
    You are now logged out!
    <a href="home.php">Click here to return to main page</a>.
</div>

</body>
</html>

