<?xml version="1.0"?>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    version="1.0">

    <xsl:template match="/user">

    Name: <b><xsl:value-of select='firstName' /> <xsl:value-of select='lastName' /></b>
    Email: <b><xsl:value-of select='email' /></b>

    </xsl:template>

</xsl:stylesheet>