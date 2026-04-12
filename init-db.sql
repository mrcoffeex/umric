-- Create laravel database for the application
CREATE DATABASE laravel_db OWNER postgres;

-- Connect to the database
\connect laravel_db

-- Create public schema
CREATE SCHEMA IF NOT EXISTS public;

-- Create an anon role for PostgREST
CREATE ROLE IF NOT EXISTS anon NOLOGIN;
GRANT USAGE ON SCHEMA public TO anon;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO anon;

-- Set default privileges
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO anon;
