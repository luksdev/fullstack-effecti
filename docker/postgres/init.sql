-- Runs on the first initialization of the Postgres data volume
-- (Docker's /docker-entrypoint-initdb.d). The application database is created
-- by POSTGRES_DB; this adds the separate database used by the test suite
-- (see DB_DATABASE in phpunit.xml).
CREATE DATABASE teste_effecti_testing;
