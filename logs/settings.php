<?php
/****************************************************************
    settings.php
    Copyright (C) 2016, Tom Milner
    All Rights Reserved
    July 29, 2016
*****************************************************************/

    require_once( 'lib/config.php' );
    require_once( 'lib/session.php' );
    Session::verifySession( Session::User );

    $_cfg = new Config();
    $_doors = $_cfg->getDoors();
    $_rev   = $_cfg->getProperty( 'revised' );
    $_sim   = ( $_cfg->isSimulation() ? "checked" : "" );
    $_logl  = $_cfg->getProperty( 'loglevel' );
    $_openwait  = $_cfg->getProperty( 'open-wait' );
    $_closewait = $_cfg->getProperty( 'close-wait' );
    $_monitor   = $_cfg->getProperty( 'monitor' );
    $_monitor_email = $_cfg->getProperty( 'monitor-email' );
    $_monitor_sms = $_cfg->getProperty( 'monitor-sms' );
    $_email_from_account = $_cfg->getProperty( 'email-from-account' );
    $_email_from_passwd  = $_cfg->getProperty( 'email-from-passwd' );

    function genPins( $cfg, $dx, $type )
    {
          // Useable GPIO pins, from http://pinout.xyz/
        $gpio_pins = array(     // Note: Using BCM numbers
            2, 3, 4, 17, 27, 22, 10, 9, 11, 0, 5, 6, 13, 19, 26,
            14, 15, 18, 23, 24, 25, 8, 7, 1, 12, 16, 20, 21,
        );
        $door = $cfg->getDoors()[$dx];
        $current = ($type==1 ? $door->getPinR() : $door->getPinM() );
        sort( $gpio_pins );
        foreach( $gpio_pins as $p ) {
            echo '<option value="' . $p . '" ';
            if ( $p == $current ) echo 'selected ' ;
            echo '> ' . $p . ' </option>' . "\n";
        }
    }

    function genLogl( $logl )
    {
        $names = array( 0 => "Normal", 1 => 'Show GPIO', 2 => 'Verbose' );
        for( $i=0; $i<=2; $i++ ) {
            echo '<option value="' . $i . '" ';
            if ( $i == $logl ) echo 'selected ' ;
            echo '> ' . $names[$i] . ' </option>' . "\n";
        }
    }
    function genWait( $cur )
    {
        for( $i=1; $i<=15; $i++ ) {
            echo '<option value="' . $i . '" ';
            if ( $i == $cur ) echo 'selected ' ;
            echo '> ' . $i . ' </option>' . "\n";
        }
    }

    function normalize( $name )
    {
        return htmlspecialchars( $name );
    }
?>
<html>
<head>
<title> Settings </title>
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
    <a title="Home" href="index.php" ><i class="fa fa-home fa-2x fa-fw" ></i></a>
    <h1 style="display: inline;" >&nbsp;&nbsp;&nbsp; Settings</h1> 
</header>

<p/>
<div class="w3-container" >
    <div class="w3-container w3-padding-medium" >
        Last Update: <?php echo $_rev; ?>
        <br/>
        <button class="w3-btn-block w3-round-large w3-blue" id="x" 
            title="Clear activity log"
            style="width: 38%;"
            onClick="onClearLog( 'x' );" 
        ><b>Clear Log</b></button>

        <button class="w3-btn-block w3-round-large w3-blue" id="monitor" 
            title="Toggle monitor state"
            style="width: 58%;"
            onClick="onToggleMonitor( 'monitor' );" 
        ><?php 
            if ( $_monitor == 'Passive' ) {
                echo '<b>Arm Monitor</b>' ;
            } else {
                echo '<b>Disarm Monitor</b>' ;
            } 
        ?></button>
    </div>

    <!-- Form -->
    <form name="mg" method="Post" action="settingsA.php" >
    <!--
	<form name="mg" method="Post" action="/echo.php" >
     -->
    <!-- Monitor Section -->
    <div class="w3-container w3-section w3-border w3-light-grey w3-round-xlarge" >
        <b>Monitor Settings</b>
        <br/>
        <label for="monitor-email">Monitor Email</label>
        <input class="w3-input w3-border" type="text" 
            name="monitor-email" id="monitor-email"
	        value="<?php echo normalize( $_monitor_email ); ?>" 
	    />
        <br/>
        <label for="monitor-sms">Monitor SMS</label>
        <input class="w3-input w3-border" type="text" 
            name="monitor-sms" id="monitor-sms"
	        value="<?php echo normalize( $_monitor_sms ); ?>" 
	    />
        <br/>
        <label for="email-from-account">Email Account</label>
        <input class="w3-input w3-border" type="text" 
            name="email-from-account" id="email-from-account"
	        value="<?php echo normalize( $_email_from_account ); ?>" 
	    />
        <br/>
        <label for="email-from-passwd">Email Password</label>
        <input class="w3-input w3-border" type="text" 
            name="email-from-passwd" id="email-from-passwd"
	        value="<?php echo normalize( $_email_from_passwd ); ?>" 
	    />
        <br/>
    </div>

    <!-- Door Section -->
    <div class="w3-container w3-section w3-border w3-light-grey w3-round-xlarge" >
        <b>Door Settings</b>
        <div "w3-container w3-border" >
		    <input class="w3-check" type="checkbox" name="sim" id="sim"
	            <?php echo $_sim; ?>
		    />
		    <label for="sim">Simulation Mode</label>
	
	        <p/>
	        <select name="logl" id="logl" autocomplete="off" >
	            <?php genLogl( $_logl ); ?>
	        </select>
		    <label for="logl">Logging Level</label>
	
            <p/>
	        <select name="openwait" id="openwait" autocomplete="off" >
	            <?php genwait( $_openwait ); ?>
	        </select>
		    <label for="openwait">Open Wait</label>
	
	        &nbsp;&nbsp;&nbsp;&nbsp;
	        <select name="closewait" id="closewait" autocomplete="off" >
	            <?php genwait( $_closewait ); ?>
	        </select>
		    <label for="closewait">Close Wait</label>
        </div>
	
	    <hr width="90%" style="color:blue;" >
		
	    <table border="0" cellpadding="5" >
	    <!-- Door0 -->
	    <tr>
	        <td>Door 0 Name</td>
	        <td>Pin R</td>
	        <td>Pin M</td>
	    </tr><tr>
	        <td>
                <input class="w3-input w3-border" type="text" 
                    name="door0name" id="door0name"
	                value="<?php echo normalize($_doors[0]->getName()); ?>" 
	            />
	        </td>
	        <td>
	            <select name="door0pinR" id="door0pinR" autocomplete="off" >
	                <?php genPins( $_cfg, 0, 1 ); ?>
	            </select>
	        </td>
	        <td>
	            <select name="door0pinM" id="door0pinM" autocomplete="off" >
	                <?php genPins( $_cfg, 0, 2 ); ?>
	            </select>
	        </td>
	    </tr>
	    <tr><td colspan="3">&nbsp;</td></tr> <!-- spacer -->
	
	    <!-- Door1 -->
	    <tr>
	        <td>Door 1 Name</td>
	        <td>Pin R</td>
	        <td>Pin M</td>
	    </tr><tr>
	        <td>
                <input class="w3-input w3-border" type="text" 
                    name="door1name" id="door1name"
	                value="<?php echo normalize($_doors[1]->getName()); ?>" 
	            />
	        </td>
	        <td>
	            <select name="door1pinR" id="door1pinR" autocomplete="off" >
	                <?php genPins( $_cfg, 1, 1 ); ?>
	            </select>
	        </td>
	        <td>
	            <select name="door1pinM" id="door1pinM" autocomplete="off" >
	                <?php genPins( $_cfg, 1, 2 ); ?>
	            </select>
	        </td>
	    </tr>
	    <tr><td colspan="3">&nbsp;</td></tr> <!-- spacer -->
	
	    <!-- Door2 -->
	    <tr>
	        <td>Door 2 Name</td>
	        <td>Pin R</td>
	        <td>Pin M</td>
	    </tr><tr>
	        <td>
	            <input class="w3-input w3-border" type="text" 
                    name="door2name" id="door2name"
	                value="<?php echo normalize($_doors[2]->getName()); ?>" 
	            />
	        </td>
	        <td>
	            <select name="door2pinR" id="door2pinR" autocomplete="off" >
	                <?php genPins( $_cfg, 2, 1 ); ?>
	            </select>
	        </td>
	        <td>
	            <select name="door2pinM" id="door2pinM" autocomplete="off" >
	                <?php genPins( $_cfg, 2, 2 ); ?>
	            </select>
	        </td>
	    </tr>
	    <tr><td colspan="3">&nbsp;</td></tr> <!-- spacer -->
	
	    <tr>
	        <td colspan="3">
	            <input type="submit" class="w3-btn-block w3-round-large w3-blue" 
	                value="Update" 
	            />
	        </td>
	    </tr>
	    </table>
	</div>
    </form>
</div>

</body>
</html>

