<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  version="1.0">

  <xsl:import href="../Component/Navbar.xslt" />
  <xsl:output method="html" indent="yes" omit-xml-declaration="yes" />

  <xsl:template match="/package">
      <xsl:call-template name="navbar"/>
      <h4 style="padding: 10px 20px 10px 20px">System Errors</h4>
      <form action="/community-voices/api/error-log" method="get" id="search-form">
        <!-- empty form; values associated with form attribute on input tags to allow for table structure -->
        <p>
            Usage: /community-voices/errors-log -- view last 500 error messages
        </p>
        <p>
            You can also add more lines or filter by date range (Advanced Options)
        </p>
          <div class="card">
              <div class="dropdown">
                  <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Advanced Options
                  </button>
                  <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" id="byLines">See More Errors</a>
                    <a class="dropdown-item" id="byDates">Filter Following Lines by Dates</a>
                  </div>
              </div>
              <div id="searchLines" style="display: none;" class="form-group">
                <label style="display: block;">How many lines would you like to add?</label>
                <input type="text" form="search-form" name="numLines" id="numLines"/>
                <input type="submit" value="Add Lines" style="display:block;" class="btn btn-secondary" id="linesSubmit"/>
              </div>

              <div id="searchDates" style="display: none;" class="form-group">
                  <label style="display: block;">View Errors Between Dates (ET)</label>
                  <input type="text" name="dateRange" id="dateRange" class="daterange" form="search-form"/>
                  <input type="submit" value="Search File" style="display:block;" class="btn btn-secondary" id="datesSubmitFile"/>
              </div>
          </div>
          </form>
        <ul class="list-group list-group-flush" id="errors">

        </ul>



  </xsl:template>
</xsl:stylesheet>
