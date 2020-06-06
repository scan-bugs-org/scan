FROM solr:6

ENV SOLR_BASE_DIR=/opt/solr
ENV SOLR_HOME=${SOLR_BASE_DIR}/server/solr
ENV MYSQL_CONNECTOR_JAVA=mysql-connector-java-8.0.19
ENV JTS_TOPO_SUITE_JAR=jts-core-1.14.0.jar

USER root

# Install mysql connectorj driver
ADD https://downloads.mysql.com/archives/get/p/3/file/${MYSQL_CONNECTOR_JAVA}.zip \
    /tmp/${MYSQL_CONNECTOR_JAVA}.zip

RUN mkdir ${SOLR_BASE_DIR}/contrib/dataimporthandler/lib && \
    unzip -p /tmp/${MYSQL_CONNECTOR_JAVA}.zip \
        ${MYSQL_CONNECTOR_JAVA}/${MYSQL_CONNECTOR_JAVA}.jar > \
        ${SOLR_BASE_DIR}/contrib/dataimporthandler/lib/${MYSQL_CONNECTOR_JAVA}.jar && \
    rm /tmp/${MYSQL_CONNECTOR_JAVA}.zip

# Install JTS Topo suite driver
ADD https://repo1.maven.org/maven2/com/vividsolutions/jts-core/1.14.0/${JTS_TOPO_SUITE_JAR} \
    ${SOLR_BASE_DIR}/server/solr-webapp/webapp/WEB-INF/lib/${JTS_TOPO_SUITE_JAR}

RUN chown -R solr:solr ${SOLR_BASE_DIR}
USER solr

RUN solr start && solr create -c scan && solr stop
COPY --chown=solr:solr solrconfig.xml ${SOLR_HOME}/scan/conf/
COPY --chown=solr:solr data-config.xml ${SOLR_HOME}/scan/conf/
COPY --chown=solr:solr schema.xml ${SOLR_HOME}/scan/conf/
RUN rm -f ${SOLR_HOME}/scan/conf/managed-schema

VOLUME $SOLR_HOME