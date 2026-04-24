#!/bin/bash
set -e

if [ "${POSTGRESQL_POSTGIS_ENABLED}" = "true" ] && [ "${REPMGR_NODE_ID}" = "1" ]; then
  echo "Initializing PostGIS extensions on primary node..."

  export PGPASSWORD="${POSTGRESQL_POSTGRES_PASSWORD:-$POSTGRESQL_PASSWORD}"

  # Wait until local postgres accepts connections.
  until pg_isready -h 127.0.0.1 -p 5432 -U postgres -d "${POSTGRESQL_DATABASE}" >/dev/null 2>&1; do
    sleep 1
  done

  psql -v ON_ERROR_STOP=1 -h 127.0.0.1 -U postgres -d "${POSTGRESQL_DATABASE}" <<-EOSQL
    CREATE EXTENSION IF NOT EXISTS postgis;
    CREATE EXTENSION IF NOT EXISTS postgis_topology;
EOSQL
fi
