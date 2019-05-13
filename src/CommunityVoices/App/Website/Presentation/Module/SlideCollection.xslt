<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	version="1.0">

	<xsl:output method="html" indent="yes" omit-xml-declaration="yes" />

	<xsl:variable name="isManager" select="package/identity/user/role = 'manager'
		or package/identity/user/role = 'administrator'"/>

	<xsl:variable name="search" select="package/domain/search"/>
  <xsl:variable name="status" select="package/domain/status"/>
	<xsl:variable name="tags" select="package/domain/tags"/>
	<xsl:variable name="photographers" select="package/domain/photographers"/>
	<xsl:variable name="orgs" select="package/domain/orgs"/>
  <xsl:variable name="order" select="package/domain/order"/>
  <xsl:variable name="unused" select="package/domain/unused"/>
  <xsl:variable name="attributions" select="package/domain/attributions"/>
  <xsl:variable name="content_category" select="package/domain/content_category"/>

	<xsl:template match="/package">

		<nav class="navbar navbar-light bg-light" style="justify-content:initial">
      <a class="navbar-brand" href="/community-voices/" style="color:#28a745;font-family:'Multicolore',sans-serif">Community Voices</a>
      <ul class="navbar-nav" style="width:initial">
        <li class="nav-item mr-2">
          <a class="nav-link" href="/community-voices/articles">Articles</a>
        </li>
        <li class="nav-item mr-2 active">
          <a class="nav-link" href="/community-voices/slides">Slides <span class="sr-only">(current)</span></a>
        </li>
        <li class="nav-item mr-2">
          <a class="nav-link" href="/community-voices/images">Images</a>
        </li>
        <li class="nav-item mr-2">
          <a class="nav-link" href="/community-voices/quotes">Quotes</a>
        </li>
      </ul>
      <div style="margin-left:auto">
		  <xsl:if test="$isManager">
			<a class="btn btn-outline-primary mr-2" href="/community-voices/slides/new">+ Add slide</a>
		  </xsl:if>
        <xsl:choose>
          <xsl:when test="identity/user/id &gt; 0">
            <a class="btn btn-outline-primary" href="/community-voices/logout">Logout <xsl:value-of select="identity/user/firstName" /></a>
            <!-- <a>
              <xsl:attribute name="href">user/<xsl:value-of select="identity/user/id" /></xsl:attribute>
              View Account
            </a> -->
          </xsl:when>
          <xsl:otherwise>
            <div class="btn-group">
              <a class="btn btn-outline-primary" href="/community-voices/login">Login</a>
              <a class="btn btn-outline-primary" href="/community-voices/register">Register</a>
            </div>
          </xsl:otherwise>
        </xsl:choose>
      </div>
    </nav>

		<div class="row" style="padding:15px;">
			<div class="col-sm-3">
				<div class="card bg-light mb-3">
          <form action="" method="GET">
            <div class="card-header bg-transparent">
              <button type="button" onclick="this.parentNode.parentNode.reset()" class="btn btn-secondary mr-2">Reset</button>
              <button class="btn btn-primary" type="submit">Search</button>
            </div>
	          <div class="card-body">
	        		<div class="form-group">
	        			<label for="search">Search</label>
                <input type="text" class="form-control" name="search" id="search" placeholder="Enter search terms" value="{$search}" />
	        		</div>

              <div class="form-group">
                <p class="mb-0">Content Category</p>
                <div style="overflow-y:scroll;width:100%;height: 145px;border:none" id="sorted-cc">
                  <xsl:for-each select="domain/contentCategoryCollection/contentCategory">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="content_category[]">
                        <xsl:attribute name="id">cc<xsl:value-of select='position()' /></xsl:attribute>
                        <xsl:if test="contains($content_category, concat(',', position(), ','))">
                          <xsl:attribute name="checked">checked</xsl:attribute>
                        </xsl:if>
                        <xsl:attribute name="value"><xsl:value-of select='position()' /></xsl:attribute>
                      </input>
                      <label class="form-check-label">
                        <xsl:attribute name="for">cc<xsl:value-of select='position()' /></xsl:attribute>
                        <xsl:value-of select="."></xsl:value-of>
                      </label>
                    </div>
                  </xsl:for-each>
                </div>
              </div>

	        		<div class="form-group">
                <p class="mb-0">Tags</p>
                <div style="overflow-y:scroll;width:100%;height: 145px;border:none" id="sorted-tags">
                  <xsl:for-each select="domain/tagCollection/tag">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="tags[]" id="tag{id}">
                        <xsl:if test="contains($tags, concat(',', id, ','))">
                          <xsl:attribute name="checked">checked</xsl:attribute>
                        </xsl:if>
                        <xsl:attribute name="value"><xsl:value-of select='id' /></xsl:attribute>
                      </input>
                      <label class="form-check-label">
                        <xsl:attribute name="for">tag<xsl:value-of select='id' /></xsl:attribute>
                        <xsl:value-of select="label"></xsl:value-of>
                      </label>
                    </div>
                  </xsl:for-each>
                </div>
              </div>

              <div class="form-group">
                <p class="mb-0">Photographer</p>
                <div style="overflow-y:scroll;width:100%;height: 145px;border:none" id="sorted-photographers">
                  <xsl:for-each select="domain/PhotographerCollection/photographer">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="photographers[]">
                        <xsl:attribute name="id">photographer<xsl:value-of select="position()"></xsl:value-of></xsl:attribute>
                        <xsl:if test="contains($photographers, concat(',', ., ','))">
                          <xsl:attribute name="checked">checked</xsl:attribute>
                        </xsl:if>
                        <xsl:attribute name="value"><xsl:value-of select='.' /></xsl:attribute>
                      </input>
                      <label class="form-check-label">
                        <xsl:attribute name="for">photographer<xsl:value-of select='position()' /></xsl:attribute>
                        <xsl:value-of select="."></xsl:value-of>
                      </label>
                    </div>
                  </xsl:for-each>
                </div>
              </div>

              <div class="form-group">
                <p class="mb-0">Image attribution</p>
                <div style="overflow-y:scroll;width:100%;height: 145px;border:none" id="sorted-image-attributions">
                  <xsl:for-each select="domain/OrgCollection/org">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="orgs[]">
                        <xsl:attribute name="id">org<xsl:value-of select="position()"></xsl:value-of></xsl:attribute>
                        <xsl:if test="contains($orgs, concat(',', ., ','))">
                          <xsl:attribute name="checked">checked</xsl:attribute>
                        </xsl:if>
                        <xsl:attribute name="value"><xsl:value-of select='.' /></xsl:attribute>
                      </input>
                      <label class="form-check-label">
                        <xsl:attribute name="for">org<xsl:value-of select='position()' /></xsl:attribute>
                        <xsl:value-of select="."></xsl:value-of>
                      </label>
                    </div>
                  </xsl:for-each>
                </div>
              </div>

              <div class="form-group">
                <p class="mb-0">Quote attribution</p>
                <div style="overflow-y:scroll;width:100%;height: 145px;border:none" id="sorted-quote-attributions">
                  <xsl:for-each select="domain/attributionCollection/attribution">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="attributions[]">
                        <xsl:attribute name="id">attribution<xsl:value-of select="position()"></xsl:value-of></xsl:attribute>
                        <xsl:if test="contains($attributions, concat(',', ., ','))">
                          <xsl:attribute name="checked">checked</xsl:attribute>
                        </xsl:if>
                        <xsl:attribute name="value"><xsl:value-of select='.' /></xsl:attribute>
                      </input>
                      <label class="form-check-label">
                        <xsl:attribute name="for">attribution<xsl:value-of select='position()' /></xsl:attribute>
                        <xsl:value-of select="."></xsl:value-of>
                      </label>
                    </div>
                  </xsl:for-each>
                </div>
              </div>

              <div class="form-group">
                <label for="order">Order by</label>
                <select class="form-control" id="order" name="order">
                  <option value="desc">
                    <xsl:if test="$order = 'desc'">
                      <xsl:attribute name="selected">selected</xsl:attribute>
                    </xsl:if>
                    Newest first
                  </option>
                  <option value="asc">
                    <xsl:if test="$order = 'asc'">
                      <xsl:attribute name="selected">selected</xsl:attribute>
                    </xsl:if>
                    Oldest first
                  </option>
                </select>
              </div>

              <xsl:if test="$isManager">
                <div class="form-group">
                  <label for="status">Status</label>
                  <select class="form-control" id="status" name="status">
                    <option value="approved,pending,rejected">
                      <xsl:if test="$status = ',approved,pending,rejected,'">
                        <xsl:attribute name="selected">selected</xsl:attribute>
                      </xsl:if>
                      All
                    </option>
                    <option value="approved">
                      <xsl:if test="$status = ',approved,'">
                        <xsl:attribute name="selected">selected</xsl:attribute>
                      </xsl:if>
                      Approved
                    </option>
                    <option value="pending">
                      <xsl:if test="$status = ',pending,'">
                        <xsl:attribute name="selected">selected</xsl:attribute>
                      </xsl:if>
                      Pending
                    </option>
                    <option value="rejected">
                      <xsl:if test="$status = ',rejected,'">
                        <xsl:attribute name="selected">selected</xsl:attribute>
                      </xsl:if>
                      Rejected
                    </option>
                  </select>
                </div>
              </xsl:if>

	          </div>
	          <div class="card-footer bg-transparent"><button type="button" onclick="this.parentNode.parentNode.reset()" class="btn btn-secondary mr-2">Reset</button> <button type="submit" class="btn btn-primary">Search</button></div>
          </form>
          <xsl:for-each select="domain/qs">
          	<xsl:value-of select="."></xsl:value-of>
          </xsl:for-each>
          <xsl:value-of select="domain/test"></xsl:value-of>
        </div>
			</div>
      <div class="col-sm-9">
		  <xsl:choose>
			  <xsl:when test="domain/slideCollection/slide != ''">
      	<xsl:for-each select="domain/slideCollection/slide">
      		<xsl:if test="$isManager or status = 'approved'">
            <xsl:choose>
              <xsl:when test="$isManager">
                <a href="slides/{id}/edit">
                  <div class="embed-responsive embed-responsive-16by9 mb-4">
                    <iframe class="embed-responsive-item" id="preview" style="pointer-events: none;" src="/community-voices/slides/{id}"></iframe>
                  </div>
                </a>
              </xsl:when>
              <xsl:otherwise>
                <a href="slides/{id}">
                  <div class="embed-responsive embed-responsive-16by9 mb-4">
                    <iframe class="embed-responsive-item" id="preview" style="pointer-events: none;" src="/community-voices/slides/{id}"></iframe>
                  </div>
                </a>
              </xsl:otherwise>
            </xsl:choose>
					</xsl:if>
				</xsl:for-each>
			</xsl:when>
			<xsl:otherwise>
				<p>No slides found.</p>
			</xsl:otherwise>
		</xsl:choose>
      </div>
		</div>
		<div class="row" style="padding:15px;">
      <div class="col-12">
        <xsl:copy-of select="domain/div"></xsl:copy-of>
      </div>
    </div>


	</xsl:template>

</xsl:stylesheet>
