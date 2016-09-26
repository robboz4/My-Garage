<?php
/****************************************************************
    macro.php
    Copyright (C) 2012-2015, Tom Milner
    All Rights Reserved
    February 25, 2012
*****************************************************************/

// require_once( 'lib/logger.php' );

/**
 * String macro utilities.
 * 
 * Usage:
 *     $macros = array( ... );  // Name value pairs
 *     $macros[ 'color' ] = 'Blue';
 *
 *     Macro::file2Stdout( 'template.html', $macros );
 *     $s = Macro::file2String( 'template.html', $macros );
 *     $a = Macro::file2Array( 'template.html', $macros );
 *     $s = Macro::replace( 'Dark {{color}}', $macros );
 *       // $s now == 'Dark Blue'
 *
 * Revisions:
 *  02/25/2012: Initial
 *  07/15/2015: Added file2Array()
 */ 
class Macro
{
    const Version = "07/15/2012" ;
    const MacPattern = "/\{\{([^}}]+)}}/" ;

    public static $macros = array();


   /**
    * Opens a file and echos to stdout after expanding macros.
    *
    * @param string $filename    Name of the template to open
    * @param string $tbl         Associative array of macro keys/values.
    */
    public static function file2Stdout( $filename, $tbl )
    {
        $f = fopen( $filename, 'r' );
        if ( !$f ) {
            echo "Cannot open file \"$filename\"" ;
        } else {
            while ( ( $b = fgets( $f,8192 ) ) != false ) {
                echo self::replace( $b, $tbl );
            }
            fclose( $f );
        }
    }


   /**
    * Opens a file and builds a resultant string after
    * expanding macros.
    *
    * @param string $filename   Name of the template to open
    * @param string $tbl        Associative array of macro keys/values.
    * @return                   A text string.
    */
    public static function file2String( $filename, $tbl )
    {
        $result = '' ;
        $f = fopen( $filename, 'r' );
        if ( !$f ) {
            echo "Cannot open file \"$filename\"" ;
        } else {
            while ( ( $b = fgets( $f,8192 ) ) != false ) {
                $result .= self::replace( $b, $tbl );
            }
            fclose( $f );
        }
        return $result ;
    }


   /**
    * Opens a file and returns all text lines in an array, with
    * macros expanded.
    *
    * @param string $filename   Name of the template to open
    * @param string $tbl        Associative array of macro keys/values.
    * @return                   An array of text strings.
    */
    public static function file2Array( $filename, $tbl )
    {
        $me = 'file2Array';
        $result = array();
        if ( !file_exists( $filename ) ) {
            // Logger::logError( $me, "No such file [$filename]" );
            return ;
        }
        $f = fopen( $filename, 'r' );
        while ( ( $b = fgets( $f,8192 ) ) != false ) {
            $result[] = self::replace( $b, $tbl );
        }
        fclose( $f );
        return $result ;
    }


   /**
    * Attempts to find a macro within the string.
    *
    * @param $str           The string to search, like "Do this {{text}}".
    * @param $matches       Before/After matched strings.
    * @returns              Byte offset where found or -1.
    */
    public static function find( $str, &$matches )
    {
        $n = preg_match( self::MacPattern, $str, $matches );
        if ( $n == 0 ) {
            return -1;
        } else {
            return strpos( $str, $matches[0] );
        }
    }


   /**
    * Replaces macro values for the macro names in a string.
    *
    * Accepts a string and an associative array which is a translation table. 
    * Macros take the form of "{{key}}", where "key" is a key in the translation
    * table and its value is substituted in the resultant string.
    *
    * @param string $source    The text to replace, like "Do this {{text}}".
    * @param string $tbl       Array of key / replacement strings, 
    *                          like array { "text" => "Data" };
    * @returns                 The resultant string, like "Do this Data"
    */
    public static function replace( $source, $tbl )
    {
        if ( $tbl == null ) {
            return $source ;
        }
        self::$macros = $tbl ;  // Save away for callback
        $source = preg_replace_callback( self::MacPattern,
            create_function( '$match', 'return Macro::replaceCB( $match );' ),
            $source );
        return $source ;
    }


   /**
    * Macro replacement handler - do not call directly.
    *
    * match[0]     Contains original string, like: "{{blue}}".
    * match[1]     Contains key text within match[0], like "blue".
    * @returns     Macro replacement text from $_tbl for the key "blue",
    *              or the original text if key "blue" is unassigned.
    */
    public static function replaceCB( $match )
    {
        if ( isset( self::$macros[ $match[1] ] ) ) {
            return stripSlashes( self::$macros[ $match[1] ] );
        } else {
            // On failure leave orignal text as is.
            return $match[0] ;
        }
    }


   /**
    * Testing routine.
    */
    public static function test()
    {
        echo "<p>-----[ Macro test " . self::Version . " ]-----" ;
        echo '<pre>';
        $before = "This is a {{big}} ({{noMacroForMe}}) {{test}}." ;
        echo '<br/>Before: ' . $before ;
        $mac = array();
        $mac[ 'big' ] = 'little' ;
        $mac[ 'test' ] = 'exercise' ;
        $after = Macro::replace( $before, $mac );
        echo '<br/>After:  ' . $after ;

        $before = "This is a ]{{#1}}[ test." ;
        echo '<p/>Buffer: ' . $before ;
        $n = self::find( $before, $matches );
        echo '<br/>Return value: ' . $n;
        echo '<br/>' . substr( $before, 0, $n );
        echo '<br/>' . substr( $before, $n+strlen($matches[0]) );
        echo '<br/>';
        print_r ( $matches );
        echo '</pre>';
    }
}
?>
