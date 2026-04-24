FROM bitnamilegacy/postgresql-repmgr:16

USER root

RUN apt-get update && \
    apt-get install -y wget gnupg lsb-release ca-certificates && \
    mkdir -p /etc/apt/keyrings && \
    wget -qO- https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /etc/apt/keyrings/postgresql.gpg && \
    echo "deb [signed-by=/etc/apt/keyrings/postgresql.gpg] http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list && \
    apt-get update && \
    apt-get install -y postgresql-16-postgis-3 postgresql-16-postgis-3-scripts && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

COPY ./init-postgis.sh /docker-entrypoint-initdb.d/init-postgis.sh
RUN chmod +x /docker-entrypoint-initdb.d/init-postgis.sh

USER 1001
