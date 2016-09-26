<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<!-- ===============================================================
    garage.xsl:   Defines the user's MyGarage settings.
    Tom Milner ( email: tomkmilner@gmail.com )
    Copyright (c) 2016, Tom Milner, all rights reserved
    June 30, 2016
==================================================================== -->

<xsl:output method='html'/>
<xsl:variable name='title'      select="'MyGarage Definition'" />
<xsl:variable name='darkstripe' select="'#D7D6D4'" />
<xsl:variable name='stripe'     select="'#E8EAEF'" />

<!--===========
    Main
=============-->
<xsl:template match='/'>
    <html>
    <head>
        <title><xsl:value-of select='$title' /></title>
        <link rel="stylesheet" type="text/css" href="garage.css" />
    </head>

    <body>
        <h1><xsl:value-of select='$title' />
            <font class="footnote">
                [Revised <xsl:value-of select="/garage/@revised" />]
            </font>
        </h1>
        <xsl:variable name='mode' >
            <xsl:if test='(/garage/@simulation = "yes")'>
                <xsl:value-of select='"Simulation"' />
            </xsl:if>
            <xsl:if test='not (/garage/@simulation = "yes")'>
                <xsl:value-of select='"Real"' />
            </xsl:if>
        </xsl:variable>
        Mode: <xsl:value-of select='$mode' />
        <br/>LogLevel: <xsl:value-of select='/garage/@loglevel' />
        <br/>Open Wait: <xsl:value-of select='/garage/wait-times/open-wait' />
        <br/>Close Wait: <xsl:value-of select='/garage/wait-times/close-wait' />

    <p/>
    <table cellspacing="0" cellpadding="5" border="1" >
    <tr>
        <th> # </th>
        <th align="left" > Door </th>
        <th> GPIO<br/>PinR </th>
        <th> GPIO<br/>PinM </th>
    </tr>

    <xsl:for-each select='.//door' >
        <!-- Change background color every other line -->
        <xsl:variable name='rowClass' >
            <xsl:choose >
                <xsl:when test='(position() mod 2) = 0' >
                    <xsl:value-of select="'evenrow'" />
                </xsl:when>
                <xsl:otherwise > 
                    <xsl:value-of select="'oddrow'" />
                </xsl:otherwise > 
            </xsl:choose >
        </xsl:variable>

        <tr class="{$rowClass}">
        <td align="center"> <xsl:value-of select='position()'/> </td>
        <td>
            <xsl:value-of select='normalize-space( ./doorname )'/>
        </td>
        <td align="center" >
            <xsl:value-of select='normalize-space( ./gpio-pinR )'/>
        </td>
        <td align="center" >
            <xsl:value-of select='normalize-space( ./gpio-pinM )'/>
        </td>
        </tr>
    </xsl:for-each>
    </table>

    <br/><br/>
    </body>
    </html>
</xsl:template>

</xsl:stylesheet>
