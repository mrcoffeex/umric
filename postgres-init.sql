-- The  laravel_db database is already created by POSTGRES_DB env var
-- Just configure PostgREST roles and permissions

-- Create an anon role for PostgREST if it doesn't exist
CREATE ROLE IF NOT EXISTS anon NOLOGIN;

-- Grant privileges to anon role
GRANT USAGE ON SCHEMA public TO anon;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO anon;

-- Set default privileges for future tables
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO anon;

