<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  version="1.0">

  <xsl:import href="../Component/Navbar.xslt" />
  <xsl:output method="html" indent="yes" omit-xml-declaration="yes" />

  <xsl:variable name="isManager" select="package/identity/user/role = 'manager'
    or package/identity/user/role = 'administrator'"/>
  <xsl:variable name="search" select="package/domain/search"/>
  <xsl:variable name="status" select="package/domain/status"/>
  <xsl:variable name="contentCategories" select="package/domain/contentCategories"/>
  <xsl:variable name="tags" select="package/domain/tags"/>
  <xsl:variable name="attributions" select="package/domain/attributions"/>
  <xsl:variable name="subattributions" select="package/domain/subattributions"/>
  <xsl:variable name="order" select="package/domain/order"/>
  <xsl:variable name="unused" select="package/domain/unused"/>

  <xsl:template match="/package">
      <xsl:call-template name="navbar">
          <xsl:with-param name="active">
              Quotes
          </xsl:with-param>
          <xsl:with-param name="rightButtons">
              <xsl:if test="$isManager">
                <a class="btn btn-outline-primary mr-2" href="/community-voices/quotes/new">+ Add quote</a>
                <!-- https://stackoverflow.com/questions/1084925/input-type-file-show-only-button?page=1&tab=votes#tab-top -->
                <form action='/community-voices/quotes/confirm' method='post' enctype='multipart/form-data' id="batchUploadForm">
                    <input class="custom-file-input" id="file" type='file' name='file[]' multiple="" accept='.csv' style="display: none;"/>
                    <input type="button" class="btn btn-outline-primary mr-2" value="Batch Upload" id="fileUploadButton"></input>
                </form>
              </xsl:if>

              <xsl:call-template name="userButtons" />
          </xsl:with-param>
      </xsl:call-template>

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
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="unused" name="unused" value="1">
                  <xsl:if test="$unused = '1'">
                    <xsl:attribute name="checked">checked</xsl:attribute>
                  </xsl:if>
                </input>
                <label class="form-check-label" for="unused">
                  Show only unpaired quotes
                </label>
              </div>
              <xsl:if test="$isManager">
                  <div class="form-group">
                    <p class="mb-0">Potential Content Categories</p>
                    <div style="overflow-y:scroll;width:100%;height: 145px;border:none" id='sorted-contentCategories'>
                      <xsl:for-each select="domain/contentCategoryCollection/contentCategory">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="contentCategories[]" id="contentCategory{id}">
                            <xsl:if test="contains($contentCategories, concat(',', id, ','))">
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
              </xsl:if>
              <div class="form-group">
                <p class="mb-0">Tags</p>
                <div style="overflow-y:scroll;width:100%;height: 145px;border:none" id='sorted-tags'>
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
                <p class="mb-0">Attribution</p>
                <div style="overflow-y:scroll;width:100%;height: 145px;border:none" id='sorted-attribution'>
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
                <p class="mb-0">Sub-attribution</p>
                <div style="overflow-y:scroll;width:100%;height: 145px;border:none" id='sorted-subattribution'>
                  <xsl:for-each select="domain/subattributionCollection/subattribution">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="subattributions[]">
                        <xsl:attribute name="id">subattribution<xsl:value-of select="position()"></xsl:value-of></xsl:attribute>
                        <xsl:if test="contains($subattributions, concat(',', ., ','))">
                          <xsl:attribute name="checked">checked</xsl:attribute>
                        </xsl:if>
                        <xsl:attribute name="value"><xsl:value-of select='.' /></xsl:attribute>
                      </input>
                      <label class="form-check-label">
                        <xsl:attribute name="for">subattribution<xsl:value-of select='position()' /></xsl:attribute>
                        <xsl:value-of select="."></xsl:value-of>
                      </label>
                    </div>
                  </xsl:for-each>
                </div>
              </div>
              <div class="form-group">
                <label for="order">Order by</label>
                <select class="form-control" id="order" name="order">
                  <option value="date_recorded_desc">
                    <xsl:if test="$order = 'date_recorded_desc'">
                      <xsl:attribute name="selected">selected</xsl:attribute>
                    </xsl:if>
                    Newest first
                  </option>
                  <option value="date_recorded_asc">
                    <xsl:if test="$order = 'date_recorded_asc'">
                      <xsl:attribute name="selected">selected</xsl:attribute>
                    </xsl:if>
                    Oldest first
                  </option>
                  <option value="photographer_desc">
                    <xsl:if test="$order = 'attribution_desc'">
                      <xsl:attribute name="selected">selected</xsl:attribute>
                    </xsl:if>
                    Attribution
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
        </div>
      </div>
      <div class="col-sm-9">
        <div class="card">
          <div class="card-header">Quotes</div>
          <ul class="list-group list-group-flush">
              <xsl:choose>
              <xsl:when test="domain/quoteCollection/quote != ''">
            <xsl:for-each select="domain/quoteCollection/quote">
              <xsl:if test="$isManager or status = 'approved'">

                <li class="list-group-item">
                  <xsl:choose>
                    <xsl:when test="$isManager">
                        <blockquote class="blockquote mb-0">
                          <p contenteditable="true" id="text{id}">
                              <xsl:if test="quotationMarks != ''">
                                  <xsl:attribute name="class">quoted</xsl:attribute>
                              </xsl:if>
                              <xsl:value-of select="text"></xsl:value-of>
                          </p>
                          <footer class="blockquote-footer">
                            <xsl:value-of select="attribution"></xsl:value-of>
                            <xsl:if test="subAttribution != '' and attribution != subAttribution">
                              <xsl:if test="attribution != ''">, </xsl:if>
                              <xsl:value-of select='subAttribution'></xsl:value-of>
                            </xsl:if>
                          </footer>
                        </blockquote>
                      <div class="mt-2">
                        <a class="btn btn-outline-info btn-sm d-inline mr-2" href="quotes/{id}">View quote</a>
                        <a class="btn btn-outline-primary btn-sm d-inline mr-2 save-quote-text" href="#" data-id="{id}">Save text changes</a>
                        <a class="btn btn-outline-secondary btn-sm d-inline mr-2" href="quotes/{id}/edit">Edit meta data</a>
                        <xsl:choose>
                          <xsl:when test="relatedSlide = ''">
                            <a data-action="/community-voices/api/quotes/{id}/delete/authenticate" class="btn btn-outline-danger btn-sm d-inline delete-btn" href="#">Delete quote</a>
                          </xsl:when>
                          <xsl:otherwise>
                            <a data-action="/community-voices/api/quotes/{id}/unpair/{relatedSlide}" class="btn btn-outline-warning btn-sm d-inline unpair-btn" href="#">Unpair slide</a>
                          </xsl:otherwise>
                        </xsl:choose>
                        <div class="form-check form-check-inline d-inline mr-2">
                            <input class="form-check-input approve-checkbox" type="checkbox" id="approve-checkbox{id}" data-id="{id}">
                                <xsl:if test="status = 'approved'">
                                    <xsl:attribute name="checked">checked</xsl:attribute>
                                </xsl:if>
                            </input>
                            <label class="form-check-label" for="approve-checkbox{id}">Approved</label>
                            <xsl:text> </xsl:text>
                            <i id="modify-status{id}"></i>
                        </div>
                      </div>
                    </xsl:when>
                    <xsl:otherwise>
                      <a href='quotes/{id}' style="color: inherit; text-decoration: inherit;">
                        <blockquote class="blockquote mb-0">
                          <p>
                              <xsl:if test="quotationMarks != ''">
                                  <xsl:attribute name="class">quoted</xsl:attribute>
                              </xsl:if>
                              <xsl:value-of select="text"></xsl:value-of>
                          </p>
                          <footer class="blockquote-footer">
                            <xsl:value-of select="attribution"></xsl:value-of>
                            <xsl:if test="subAttribution != '' and attribution != subAttribution">
                              <xsl:if test="attribution != ''">, </xsl:if>
                              <xsl:value-of select='subAttribution'></xsl:value-of>
                            </xsl:if>
                          </footer>
                        </blockquote>
                      </a>
                    </xsl:otherwise>
                  </xsl:choose>
                </li>

              </xsl:if>
            </xsl:for-each>
        </xsl:when>
        <xsl:otherwise>
            <li class="list-group-item">No quotes found.</li>
        </xsl:otherwise>
    </xsl:choose>
          </ul>
        </div>
      </div>
    </div>
    <div class="row" style="padding:15px;">
      <div class="col-12">
        <!-- <xsl:value-of select="domain/count"></xsl:value-of> -->
        <xsl:copy-of select="domain/div"></xsl:copy-of>
      </div>
    </div>

  </xsl:template>

</xsl:stylesheet>
