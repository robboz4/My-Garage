<?php
/****************************************************************
    login.php
    Copyright (C) 2016, Tom Milner (tomkmilner@gmail.com)
    All Rights Reserved
    August 26, 2016
*****************************************************************/
    require_once( 'lib/session.php' );

?>
<html>
<head>
<title> Login </title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" href="mg.css" />
<link rel="stylesheet" href="w3.css" />
<link rel="stylesheet" href="font-awesome-4.6.3/css/font-awesome.css" />
<link rel="stylesheet" href="font-awesome-4.6.3/css/font-awesome.min.css" />
<script src="lib/webtoolkit.md5.js" ></script>
<script src="lib/ajax.js"></script>
<script src="lib/mg.js"></script>
<script>
    function doSubmit( name ) {
        var f = document.forms[name] ;
        var pass = f.password.value ;
        f.Mpassword.value = MD5( pass );
        f.password.value = "" ;
        f.submit();
        return true;
    }
</script>
<style>
</style>
</head>

<body>
<header class="w3-container w3-blue" >
    <a title="Home" href="home.php" ><i class="fa fa-home fa-2x fa-fw" ></i></a>
    <h1 style="display: inline;" >&nbsp;&nbsp;&nbsp; Login</h1> 
</header>

<div class="w3-container w3-row w3-half" >
    <h2>Login</h2>
    <p/>
    <!-- Form -->
    <div class="w3-container w3-border w3-light-grey w3-round-xlarge" >
<!--
        <form name="login" method="Post" action="/echo.php" >
-->
        <form name="login" method="Post" autocomplete="on" 
            action="loginverify.php" 
        >
        <input type="hidden" name="action" value="login" />
        <input type="hidden" name="seed" value="<?php echo Session::getSeed();?>" />
        <input type="hidden" name="Mpassword" value="" />
        <label class="w3-label w3-text-blue" >Username</label>
        <input class="w3-input w-3-border w3-light-grey" 
            type="text" name="username" id="username" 
        />
        <p/>
        <label class="w3-label w3-text-blue" >Password</label>
        <input class="w3-input w-3-border w3-light-grey" 
            type="password" name="password" id="password" 
        />
        <p/>
        <button class="w3-btn w3-blue-grey" 
            onClick="doSubmit( 'login' );" 
        >Login</button>
        </form>
    </div>
</div>

<?php
if ( Session::isSecure() ) {
    $seed = Session::getSeed();
echo '<div class="w3-container w3-row w3-half" >';
echo '    <h2>Create Account</h2>';
echo '    <p/>';
echo '    <!-- Form -->';
echo '    <div class="w3-container w3-border w3-light-grey w3-round-xlarge" >';
echo '        <form name="ca" method="Post" action="loginverify.php" >';
echo '        <!--';
echo '        <form name="ca" method="Post" action="/echo.php" >';
echo '        -->';
echo '        <input type="hidden" name="action" value="ca" />';
echo '        <input type="hidden" name="seed" value="' . $seed . '" />' ;
echo '        <input type="hidden" name="Mpassword" value="" />';
echo '        <label class="w3-label w3-text-blue" >Username</label>';
echo '        <input class="w3-input w-3-border w3-light-grey" type="text" ';
echo '            name="username" id="username" ';
echo '        />';
echo '        <p/>';
echo '        <label class="w3-label w3-text-blue" >Password</label>';
echo '        <input class="w3-input w-3-border w3-light-grey" type="text" ';
echo '            name="password" id="password" ';
echo '        />';
echo '        <p/>';
echo '        <button class="w3-btn w3-blue-grey" ';
echo '            onClick="doSubmit( \'ca\' );" ';
echo '        >Create Account</button>';
echo '        </form>';
echo '    </div>';
echo '</div>';
}?>

</body>
</html>

