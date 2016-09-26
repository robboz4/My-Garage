<?php
/****************************************************************
    xmlutil.php
    Copyright (C) 2014-2016, Tom Milner
    All Rights Reserved
    April 7, 2014
*****************************************************************/

require_once( 'lib/logger.php' );

/**
 * Simple encapsulation of XML routines.
 * Primarily used for archiving of Job XML files.
 * 
 * Sample Usage:
 *     require_once( 'lib/xmlutil.php' );
 *      try {
 *         $xml = new XmlUtil();
 *         $xml->createXML() ;
 *         $xml->addXML( 'Uno', 1 );
 *         $xml->addXML( 'Dos', 2 );
 *         $xml->addXML( 'Tres', 3 );
 *         $xml->saveXML( 'test' );
 *
 *      } catch( Exception $e ) {
 *         // Error Message in $e->getMessage()
 *      }
 *
 * Sample of Reading an XML document:
 *      $table = array();
 *      $dom = new DOMDocument() ;
 *      $dom->load( "states.xml" );
 *      $root = $dom->documentElement ;
 *      $node = XmlUtil::firstChild( $root );
 *        // Read a node like <state tag="CA">California</state>
 *      while ( $node ) {
 *          $name  = $node->nodeName ;             // like 'state'
 *          $tag   = $node->getAttribute( 'tag' ); // like 'CA'
 *          $value = XmlUtil.normalize( $node->nodeValue ); // like California
 *          $table[ $tag ] = $value ;
 *          $node = XmlUtil::nextSibling( $node );
 *      }
 *      return $table ;
 *
 *
 * Revisions:
 *  04/07/2014: Initial
 */ 
class XmlUtil
{
    const Version   = '04/07/2014' ;
        
    public $_dom ;          // The DOM Object
    public $_root ;         // The Root element
        

   /**
    * Wrapper to DomDocument::loadXML(). 
    *
    * @param $dom   A DomDocument object
    * @param $data  A string of XML data.
    * @return       Parsed XML element
    * @throws       Exception on error.
    */
    public static function loadXML( $dom, $data)
    {
        $me = 'loadXML';
        
        if ( !isset( $data ) || trim( $data ) == '' ) {
            $msg = 'Missing XML data' ;
            Logger::logError( $me, $msg );
            throw new Exception( $me . ': ' . $msg );
        }
        @$dom->loadXML( trim( $data ) );
    }
        

   /**
    * Creates the XML preamble, like
    *   <?xml version="1.0" ?> 
    *   <formulas ... >  (See saveXML for root attribute)
    */
    public function createXML()
    {
        $this->_dom = new DOMDocument() ;
        $this->_dom->formatOutput = true ;
        /***
        $xslt = $this->_dom->createProcessingInstruction( 
                            'xml-stylesheet', 
                            'type="text/xsl" href="/lib/job.xsl"' );
        $this->_dom->appendChild( $xslt );
        ***/
        $this->_root = $this->_dom->createElement( "formulas" );
          // Create root with no attributes
        $this->_dom->appendChild( $this->_root );
    }


   /**
    * Adds to XML DOM object, like
    *   <T1.TA> 12 </T1.TA>
    *
    * @param $name  The DOM element name (like "T1.TA").
    * @param $value The DOM value (like "12").
    */
    public function addXML( $name, $value )
    {
        /** old way <var name="T1.TA" value="12" />
        $var = $this->_dom->createElement( $name );
        $var->setAttribute( "name", $name );
        $var->setAttribute( "value", $value );
        **/
        $var = $this->_dom->createElement( $name, $this->escapeXML( $value ) );
        $this->_root->appendChild( $var );
    }


   /**
    * Escapes any special characters in XML data.
    */
    public static function escapeXML( $value )
    {
        return str_replace( '&', '&amp;', $value );
    }


   /**
    * Deprecated.
    *
    * Saves the XML as a file after setting the root attribute.
    *
    * @param $jobId     Attribute for the root element.
    */
    public function saveXMLold( $jobId )
    {
        $this->_root->setAttribute( "JobId", $jobId );
        $filename = "xml/job-$jobId.xml" ;
        $this->_dom->save( $filename );
        return $filename ;
    }


   /**
    * Saves the XML as a file.
    *
    * @param $filename      The fully qualified file path.
    */
    public function saveXML( $filename )
    {
        $this->_dom->save( $filename );
    }
    

   /**
    * Returns a string with multiple white space removed and trimmed.
    */
    public static function normalize( $str )
    {
        $patterns = array( "/\s+/", "/\s([?.!])/" );
        $replacer = array( " ", "$1" );
        $str = preg_replace( $patterns, $replacer, $str );
        return trim( $str );
    }


   /**
    * Skips over comments and other XML gotchas to find first
    * real child node.
    *
    * @param $node  The current node, including the root node.
    * @return       The first true child node.
    */
    public static function firstChild( $node )
    {
        $node = $node->firstChild ;
        // while ( $node != null && $node->nodeType == XML_TEXT_NODE ) {
        while ( $node != null && $node->nodeType != XML_ELEMENT_NODE ) {
            $node = $node->nextSibling ;
        }
        return $node ;
    }

   /**
    * Skips over comments and other XML gotchas to find next
    * real sibling.
    *
    * @param $node  The current sibling node.
    * @return       The first true sibling node.
    */
    public static function nextSibling( $node )
    {
        $node = $node->nextSibling ;
        // while ( $node != null && $node->nodeType == XML_TEXT_NODE ) {
        while ( $node != null && $node->nodeType != XML_ELEMENT_NODE ) {
            $node = $node->nextSibling ;
        }
        return $node ;
    }



   /**
    * Testing routine.
    */
    public static function test()
    {
        echo "<p>-----[ XmlUtil test " . self::Version . " ]-----" ;
        $filename = "test.xml";
        try {
            $xml = new XmlUtil();
            $xml->createXML() ;
            $xml->addXML( 'Uno', 1 );
            $xml->addXML( 'Dos', 2 );
            $xml->addXML( 'Tres', 3 );
            $xml->saveXML( $filename );
  
        } catch( Exception $e ) {
           // Error Message in $e->getMessage()
        }
        echo "<p> File $filename created" ;

        echo "<pre>";
        $str = "    X Y       Z        \r\n   A   BC  D  ";
        echo "<p/>Before:[" . $str . "]";
        echo "<br/>After :[" . XmlUtil::normalize( $str ) . "]";
        echo "</pre>";
    }
}
?>
