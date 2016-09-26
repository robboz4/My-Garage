<?php
/****************************************************************
    session.php
    Copyright (C) 2016, Tom Milner
    All Rights Reserved
    August 26, 2016
*****************************************************************/

require_once( 'lib/logger.php' );
require_once( 'lib/xmlutil.php' );

/**
 * This class control session-related issues, like login, verifySession,
 * logout, as well as session variables.
 *
 * Sample Usage:
 * 
 *    $u = $_POST[ "UserName" ];
 *    $p = $_POST[ "Password" ];
 *    try {
 *        Session::login( $u, $p ) ; // To login
 *    } catch ( Exception $e ) {
 *        ... login failed
 *    }
 *
 * Revisions:
 *  08/30/2016: Initial
 */

class Session
{
      // Constants
    const Version    = '08/30/2016' ;
    const UserFilename = 'users.xml' ;
    const SessionVar = 'tsession' ;
    const NoBounce   = false ;

      // Idle timeout in seconds: 30 mins = 30*60 = 1800
    const SessionIdleTime = 1800 ; 
    const StaleSeedLimit  = 300 ;  // 5 minutes (300 seconds)
    const LastSeen      = 'LastSeen' ;

      // Permission types
    const SuperUser     = 1 ;
    const User          = 7 ;
    const Inactive      = 9 ;

      // Session variable constants
    const UserId        = 'UserId' ;
    const Permissions   = 'Permissions' ;
    const UserName      = 'UserName' ;
    const Password      = 'Password' ;

      // Session variables
    public $_users = null ;


   /**
    * Encapulates the mechanism to determine if a user is "logged in".
    */
    public static function isLoggedIn()
    {
        $id = self::getVar( self::UserId );
        return isset( $id );
    }


   /**
    * Test current user's permissions.
    * Like: if ( Session::hasPermission( Session::SuperUser ) ) {
    *           // User is super.
    */
    public static function hasPermission( $minPermission )
    {
        return true ;
    }


   /**
    * Test security for account update capability.
    * @returns      True if account update allowed
    */
    public static function isSecure()
    {
        if ( file_exists( "secure.txt" ) ) {
            return true ;       // 1-time back door
        }

          // Determine if running on server
        $serverIp = $_SERVER[ "SERVER_ADDR" ];
        $clientIp = $_SERVER[ "REMOTE_ADDR" ];
        if ( $serverIp == $clientIp ) {
            return true ;
        } else {
            return false ;
        }
    }


   /**
    * Attempts to login as the given user / password.
    *
    * Note: because the result of login is usually a page redirect
    * no output should be generated even if debugging...
    *
    * @param $username  Login of the user in UserInfo table
    * @param $password  Passwd of the user in UserInfo table
    * @param $seed      When form was seeded.
    * @return           Permissions
    * @Exception thrown on failure.
    */
    public static function login( $username, $password, $seed )
    {
        $me = 'login' ;
        $clientIp = $_ENV[ 'REMOTE_ADDR' ] ;
        Logger::logMessage( $me, 0,
            "-------[ Version: " . Session::Version 
            . ", " . $clientIp 
            . ", Seed: " . $seed . " ]-------" );
        $browser = $_SERVER[ "HTTP_USER_AGENT" ];
        Logger::logMessage( $me, 0, "Browser: $browser" );

        try {
              // Check seed
            if ( self::isStaleSeed( $seed ) ) {
                throw new Exception( "Please try again" );
            }
            $ses = new Session();
            $ses->loadUsers();
            $userId = $ses->findUser( $username );
            if ( $userId == null ) {
                $msg = "Username \"$username\" is unknown." ;
                $msg .= " Correct the spelling or create the account." ;
                throw new Exception( $msg );
            }
            Logger::logMessage( $me, 1, "Found user $username" );

            // See if passwords match
            $tupple = $ses->_users[ $userId ];
            if ( trim( $password ) != $tupple[1] ) {
                $msg = "Username / Password mismatch" ;
                throw new Exception( $msg );
            }
	
              // User verified!  Get Permissions based on IP
              //
              // Hardcode SuperUser permissions for now
            $permissions = Session::SuperUser;

	          // Save user info in session
	        self::logout() ; // Clear any old session vars
	        self::setVar( Session::UserId,      $userId );
	        self::setVar( Session::UserName,    $username );
	        self::setVar( Session::Permissions, $permissions );
	          // Save Start time for timeout test
	        self::setVar( Session::LastSeen, time() );
	
	          // Log this login
	        Logger::logMessage( $me, 1, "User $username, logged in" );
	        return $permissions;

        } catch ( Exception $e ) {
            // Logger::logError( $me, 'Exception: ' . $e->getMessage() );
            throw $e ;  // Propagate up
        }
    }


   /**
    * Adds or Updates the given user / password, and logs in.
    *
    * Note: because the result of login is usually a page redirect
    * no output should be generated even if debugging...
    *
    * @param $username  Login of the user in UserInfo table
    * @param $password  Passwd of the user in UserInfo table
    * @param $seed      When form was seeded.
    * @return           Permissions or zero on failure.
    * @Exception thrown on failure.
    */
    public static function createAccount( $username, $password, $seed )
    {
        $me = 'createAccount' ;
        Logger::logMessage( $me, 1, "$username" );

          // Check seed
        if ( self::isStaleSeed( $seed ) ) {
            throw new Exception( "Please try again" );
        }

        $ses = new Session();
        $ses->loadUsers();
        $tupple = array( $username, $password );

        $userId = $ses->findUser( $username );
        if ( $userId == null ) {
            Logger::logMessage( $me, 0, "Adding $username" );
            $ses->_users[] = $tupple ;             // Add new user
        } else {
            Logger::logMessage( $me, 0, "Updating $username" );
            $ses->_users[ $userId ] = $tupple ;    // Update old user
        }
        $ses->saveUsers();
          // Remove back door if it exists
        if ( file_exists( "secure.txt" ) ) {
            rename( "secure.txt", "xsecure.txt" );
        }
        $ses->login( $username, $password, $seed );
    }

   /**
    * Finds user matching UserName.
    * 
    * @param string $username   Username to find.
    * @returns                  UserId or null for failure
    */
    public function findUser( $username )
    {
        $me = "findUser" ;
        Logger::logMessage( $me, 3, "Looking for \"$username\"" );

        $userId = strtolower( $username );
        if ( !array_key_exists( $userId, $this->_users ) ) {
            return null ;
        }
        return $userId;
    }
    

   /**
    * Logs out.
    */
    public static function logout()
    {
        $me = "logout" ;
        try {
              // Check to see if currently logged in.
            if ( self::isLoggedIn() ) {
                $u = self::getVar( self::UserName ) ;
                Logger::logMessage( $me, 0, "User $u logging out" );
            } else {
                Logger::logMessage( $me, 3, 'No Session - Clearing variables' );
            }
            unset( $_SESSION[ self::SessionVar ] );

        } catch ( Exception $e ) {
            Logger::logError( $me, 'Exception: ' . $e->getMessage() );
        }
    }


   /**
    * Checks that there is a valid login with the required permissions.
    *
    * Some pages require session variables which are destroyed if a new
    * login is required.  For those pages, continuing to operate would result
    * in failure, so those pages should set "okToBounce" to false which
    * results in the user being redirected to their Home page.
    *
    * @param $minPermission     Minimum permissions required
    * @param $okToBounce        True if return to original page ok.
    */
    public static function verifySession( $minPermission, $okToBounce = true )
    {
        $me = "verifySession" ;

          // Instruct server on how long to keep session around.
          // Must be called before session_start().
        ini_set( 'session.gc_maxlifetime', self::SessionIdleTime );
          // Tell browser how long to remember SessionId
          // Must be called before session_start().
        session_set_cookie_params( self::SessionIdleTime );

          // Bind to session
        session_start() ;   // Note: start must come before any HTML

          // Catch any PHP errors
        Logger::registerShutdown();

        $client = $_ENV[ "REMOTE_ADDR" ] ;
        $uri = $_ENV[ "REQUEST_URI" ] ;
        $permissions = self::getVar( self::Permissions );

          // Special check -- allow anybody
        if ( $minPermission == "-1" ) {
              // Just get out, permission level below requirement for page
            Logger::logMessage( $me, 3, "$uri [$permissions<=$minPermission]" );
            return ;
        }

          // Check to see if currently logged in.
        if ( !self::isLoggedIn() ) {
            // Not logged in.. bounce
            if ( self::setBounce( $uri, $okToBounce ) ) {
                $act = 'bouncing.' ;
            } else {
                $act = 'No Bounce.' ;
            }
            Logger::logError( $me,"Page $uri, $client, Not logged in.. $act" );
            header( 'Location: login.php' ) ;
            exit ;
        }

          // Check minPermission vs user's permission
        if ( !Session::hasPermission( $minPermission ) ) {
            Logger::logError( $me,"$uri, permissions: $minPermission < $permissions" );
            header( 'Location: permissions.php' );
            exit ;
        }
        
          // Check for timeout
        $lastTime = self::getVar( self::LastSeen );
        $idleTime = time() - $lastTime ;
        if ( $idleTime > self::SessionIdleTime ) {
            $userId = self::getVar( self::UserId );
            self::setBounce( $uri, $okToBounce );
            $d = self::getNow() ;
            $t = $d->format( 'H:i:s' );
            Logger::logError( $me, "$uri, User $userId timing out, $t" );
            self::logout() ;
            header( 'Location: login.php' ) ;
            exit ;
        }
          // Reset idle timer
        Logger::logMessage( $me, 1, "$uri [$permissions<=$minPermission]" );
        self::setVar( self::LastSeen,  time() );
    }


   /**
    * Sets the URL to return to if we had to stop and login.
    *
    * @param $url               The URL to remember.
    * @param $okToBounce        True if return to original page ok.
    * @return                   $okToBounce
    */
    public static function setBounce( $url, $okToBounce )
    {
        $me = 'setBounce' ;
        if ( isset( $_SESSION ) ) {
            if ( $okToBounce ) {
                  // Remember where we came from
                Logger::logMessage( $me, 1, "Recording url=$url" );
                $_SESSION[ 'bounce' ] = $url ;      
            } else if ( isset( $_SESSION[ 'bounce' ] ) ) {
                Logger::logMessage( $me, 1, "Clearing..." );
                unset( $_SESSION[ 'bounce' ] );
            }
        }
        return $okToBounce;
    }


   /**
    * If a session expires and a new login is forced, this routine is
    * called to return to the original page that required the login.
    * If there is no "bounce back" page, then this function returns.
    */
    public static function bounceBack()
    {
        $me = 'bounceBack' ;

          // See if there is a place to bounce back to...
        $url = $_SESSION[ 'bounce' ];
        if ( isset( $url ) && $url != "" ) {
            unset( $_SESSION[ 'bounce' ] );
                  // redirect back to previous location
            Logger::logMessage( $me, 1, "Returning to url=$url" );
            header( 'location: ' . $url );
            exit;   // No return

        } else {
            Logger::logMessage( $me, 1, "No bounce back" );
        }
    }


   /**
    * Redirects to users home page based upon their permissions.
    */
    public static function goHome()
    {
        $me = 'goHome' ;
        header( 'Location: ' . 'index.php' );
    }


   /**
    * Returns pathname of user file.
    */
    public function getUserFilename()
    {
        $me = "getUserFilename";
        $dir = "config" ;
        $path = $dir . '/' . self::UserFilename;
        return $path ;

        /** Note: This mechanism just did not work on all file systems... :-(
        $dir = $_SERVER[ 'DOCUMENT_ROOT' ] ;
        Logger::logMessage( $me, 1, "DocRoot=$dir" );
        $dir .= "/../etc/PiGMi" ;
        Logger::logMessage( $me, 1, "etc=$dir" );
        if ( !file_exists( $dir ) ) {
            mkdir( $dir );
        }
        $path = $dir . '/' . self::UserFilename ;
        Logger::logMessage( $me, 1, "filename=$dir" );
        return $path ;
        **/
    }


   /**
    * Reads the user name/password list and sets the _users array.
    * _users[ strtolower(UserName) ] = array( UserName, Password );
    */
    public function loadUsers()
    {
        $me = "loadUsers";
        $this->_users = array();
        $filename = $this->getUserFilename();
        Logger::logMessage( $me, 1, "Loading Users, $filename" );
        
        try {
            if ( !file_exists( $filename ) ) {
                $msg = 'No user accounts exits, you must create a Username.';
                throw new Exception( $msg );
            }
    
            $dom = new DOMDocument() ;
            $dom->load( $filename );
            $root = $dom->documentElement ;
            $user = XmlUtil::firstChild( $root );
            while ( $user ) {
                $node = XmlUtil::firstChild( $user );
                while ( $node ) {
                    if ( $node->nodeName == 'username' ) {
                        $username = trim( $node->nodeValue );
                    } else if ( $node->nodeName == 'password' ) {
                        $password = trim( $node->nodeValue );
                    }
                    $node = XmlUtil::nextSibling( $node );
                }
                if ( $username != "" ) {
                    $tupple = array( $username, $password );
                    $this->_users[ strtolower($username) ] = $tupple ;
                }
                $user = XmlUtil::nextSibling( $user );
            }

        } catch( Exception $e ) {
            Logger::LogError( $me, "Exception: " . $e->getMessage() );
        }
    }


   /**
    * Saves the Users file.  Note: loadUsers() must be called first.
    * @Exception thrown on file errors.
    */
    public function saveUsers()
    {
        $me = "saveUsers";
        $NL = "\n";
        $filename = $this->getUserFilename();
        Logger::logMessage( $me, 0, "Saving Users, $filename" );

        $fh = fopen( $filename, 'w' );
        if ( !$fh ) {
            $msg = "Cannot open file \"$filename\"" ;
            Logger::logError( $me, $msg );
            throw new Exception( $me . ": " . $msg );
        }
        fwrite( $fh, '<?xml version="1.0" encoding="UTF-8"?>' . $NL );
        fwrite( $fh, '<users>' . $NL );
        foreach ( $this->_users as $key => $tupple ) {
            $username = $tupple[ 0 ];
            $password = $tupple[ 1 ];
            Logger::logMessage( $me, 3, "$username=$password" );
            fwrite( $fh, '  <user>' . $NL );
            fwrite( $fh, '    <username>' . $username . '</username>' . $NL );
            fwrite( $fh, '    <password>' . $password . '</password>' . $NL );
            fwrite( $fh, '  </user>' . $NL );
        }
        fwrite( $fh, '</users>' . $NL );
        fclose( $fh );
    }


   /**
    * Clears a variable.
    *
    * @param string $name   Name of the variable whose value is returned.
    */
    public static function unSetVar( $name )
    {
        if ( !isset( $_SESSION[ self::SessionVar ] ) ) {
            return ;
        }
        unset( $_SESSION[ self::SessionVar ][ $name ] );
    }

   /**
    * Returns the value of a session variable by name.
    * (Opposite of setVar).  Typically use the form element name or ID.
    *
    * @param string $name   Name of the variable whose value is returned.
    */
    public static function getVar( $name )
    {
        if ( !isset( $_SESSION[ self::SessionVar ] ) ) {
            return null ;
        }
        $v = $_SESSION[ self::SessionVar ][ $name ];
        if ( !isset( $v ) ) {
            Logger::logError( "getVar", "\"$name\" is undefined" );
        }
        return $v ;
    }


   /**
    * Saves away the named variable in the session.
    * (Opposite of getVar). Typically use the form element name or ID.
    *
    * @param string $name   Name of the variable.
    * @param string $value  Value to associate with the variable.
    */
    public static function setVar( $name, $value )
    {
        Logger::logMessage( "setVar", 3, "$name=[$value]" );
        $_SESSION[ self::SessionVar ][ $name ] = $value ;
    }

   /**
    * Returns all of the self::SessionVar keys, sorted by key.
    */
    public static function sessionKeys()
    {
        if ( !isset( $_SESSION[ self::SessionVar ] ) ) {
            echo "<p><b>No SESSION started</b>" ;
            return null ;
        } else {
            $keys = array_keys( $_SESSION[ self::SessionVar ] );
            sort( $keys );
            return $keys ;
        }
    }


   /**
    * Returns UTC time.
    */
    public static function getSeed()
    {
        return strval( time() - date('Z') );  // UTC time
    }


   /**
    * Returns true if seed too old
    */
    public static function isStaleSeed( $seed )
    {
        $limit = self::StaleSeedLimit ;
        $now = time() - date('Z');  // UTC time
        $then = (int)$seed ;
        if ( ($now - $then) > $limit ) {
            return true ;
        } else {
            return false ;
        }
    }
    

   /**
    * Returns current DateTime object in the default timezone.
    * Note: Returns DateTime object.  For text string use getDate().
    */
    public static function getNow()
    {
        $tz = new DateTimeZone( 'America/Los_Angeles' );
        return new DateTime( 'now', $tz );
    }


   /**
    * Testing routine.
    */
    public static function test()
    {
        echo "<p>-----[ Session test " . self::Version . " ]-----" ;
    }
}
?>
