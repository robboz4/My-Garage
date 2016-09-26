    // ajax.js
    // Asynchonous Ajax library.
    //
    //  Copyright (C) 2015, Tom Milner
    //  All Rights Reserved
    //  May 11, 2015
    //  ---------------------------------

    // Change as appropriate
    var _url = "ajax.php";


    /**
     * Asynchronously send a command (via Ajax) using Http Get URL.  The
     * URL is constructed like the following:
     *     var url = _url + "?cmd=" + cmd + (args != mull ? args : "" );
     *
     *  cmd         The Command parameter (like "UpdateNote") for the server.
     *  args        Key/Value pairs w/ delimiter ("&"), like "&id=27&xyz=7"
     *  callback    The function to invoke on successful completion
     *  cookie      A parameter to pass to the callback (like a form field Id).
     * 
     * To Use:
     *      1. Define an "action" function and an "onCompletion()" function.
     *      2. The action function will call ajaxSend().
     *      3. Update Ajax.php to execute the new command and return result.
     *
     * Sample Usage:
     *     var args = "";   // Not used
     *     ajaxSend( "GetVersion", args, onVersion, "versionId" );
     *
     * On completion, the Callback will be invoked like: 
     *     myCB( cookie, responseText );
     */
    function ajaxSend( cmd, args, callback, cookie )
    {
        var xmlhttp;
        if ( window.XMLHttpRequest ) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp=new XMLHttpRequest();
        } else { 
            // code for IE6, IE5
            xmlhttp = new ActiveXObject( "Microsoft.XMLHTTP" );
        }

          // inline function called when state changes
        xmlhttp.onreadystatechange = function() 
        {
            if ( xmlhttp.readyState == 4 ) {
                response = xmlhttp.responseText ;
                if ( xmlhttp.status == 200 ) {
                      // Bingo.. our server code responded
                    // alert( "Callback[" + cookie + "] " + response );
                    callback( cookie, response );

                } else if ( xmlhttp.status == 0 ) {
                      // Special test for this cross-domain issue
                    alert( "Failure: Status=0, client page not loaded from server... Possible Cross-Domain issue.");

                } else {
                      // Completion status is bad, meaning it did
                      // not get to our server code successfully.
                    alert( "Failure: xmlhttp.status=" + xmlhttp.status
                            + " on Url=" + _url );
                }
            }
        }
          // Issue the http request
        var url = _url ;
        url += "?cmd" + "=" + cmd;
        if ( args && args != "" ) {
            url += args;
        }
        // alert( "URL=" + url );
        xmlhttp.open( "GET", url, true );
        xmlhttp.send();
    }

    // -- end --
