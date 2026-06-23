-- PostgreSQL Schema for WAHA PHP Library
-- Generated on 2026-06-23

-- Enable UUID extension if needed
-- CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Table: waha_sessions
CREATE TABLE IF NOT EXISTS "waha_sessions" (
  "id" SERIAL PRIMARY KEY,
  "session_name" VARCHAR(255) NOT NULL,
  "status" VARCHAR(50) NOT NULL DEFAULT 'inactive',
  "qr_code" TEXT,
  "connected_at" TIMESTAMP WITHOUT TIME ZONE,
  "disconnected_at" TIMESTAMP WITHOUT TIME ZONE,
  "metadata" JSONB,
  "created_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS "idx_waha_sessions_session_name" ON "waha_sessions" ("session_name");
CREATE INDEX IF NOT EXISTS "idx_waha_sessions_status" ON "waha_sessions" ("status");
CREATE INDEX IF NOT EXISTS "idx_waha_sessions_created_at" ON "waha_sessions" ("created_at");

-- Table: waha_contacts
CREATE TABLE IF NOT EXISTS "waha_contacts" (
  "id" SERIAL PRIMARY KEY,
  "session_id" INTEGER NOT NULL,
  "contact_id" VARCHAR(255) NOT NULL,
  "phone_number" VARCHAR(50) NOT NULL,
  "name" VARCHAR(255),
  "is_blocked" BOOLEAN NOT NULL DEFAULT FALSE,
  "is_business" BOOLEAN NOT NULL DEFAULT FALSE,
  "metadata" JSONB,
  "created_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_waha_contacts_session_id" ON "waha_contacts" ("session_id");
CREATE UNIQUE INDEX IF NOT EXISTS "idx_waha_contacts_contact_id" ON "waha_contacts" ("contact_id");
CREATE INDEX IF NOT EXISTS "idx_waha_contacts_phone_number" ON "waha_contacts" ("phone_number");
CREATE INDEX IF NOT EXISTS "idx_waha_contacts_name" ON "waha_contacts" ("name");
CREATE INDEX IF NOT EXISTS "idx_waha_contacts_created_at" ON "waha_contacts" ("created_at");

-- Table: waha_messages
CREATE TABLE IF NOT EXISTS "waha_messages" (
  "id" SERIAL PRIMARY KEY,
  "session_id" INTEGER NOT NULL,
  "message_id" VARCHAR(255) NOT NULL,
  "chat_id" VARCHAR(255) NOT NULL,
  "from_me" BOOLEAN NOT NULL DEFAULT FALSE,
  "message_type" VARCHAR(50) NOT NULL DEFAULT 'text',
  "content" TEXT,
  "timestamp" BIGINT NOT NULL,
  "metadata" JSONB,
  "created_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_waha_messages_session_id" ON "waha_messages" ("session_id");
CREATE INDEX IF NOT EXISTS "idx_waha_messages_message_id" ON "waha_messages" ("message_id");
CREATE INDEX IF NOT EXISTS "idx_waha_messages_chat_id" ON "waha_messages" ("chat_id");
CREATE INDEX IF NOT EXISTS "idx_waha_messages_timestamp" ON "waha_messages" ("timestamp");
CREATE INDEX IF NOT EXISTS "idx_waha_messages_from_me" ON "waha_messages" ("from_me");
CREATE INDEX IF NOT EXISTS "idx_waha_messages_created_at" ON "waha_messages" ("created_at");

-- Table: waha_message_logs
CREATE TABLE IF NOT EXISTS "waha_message_logs" (
  "id" SERIAL PRIMARY KEY,
  "session_id" INTEGER NOT NULL,
  "chat_id" VARCHAR(255) NOT NULL,
  "message_type" VARCHAR(50) NOT NULL DEFAULT 'text',
  "content" TEXT,
  "status" VARCHAR(50) NOT NULL DEFAULT 'pending',
  "error_message" TEXT,
  "sent_at" TIMESTAMP WITHOUT TIME ZONE,
  "metadata" JSONB,
  "created_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS "idx_waha_message_logs_session_id" ON "waha_message_logs" ("session_id");
CREATE INDEX IF NOT EXISTS "idx_waha_message_logs_chat_id" ON "waha_message_logs" ("chat_id");
CREATE INDEX IF NOT EXISTS "idx_waha_message_logs_status" ON "waha_message_logs" ("status");
CREATE INDEX IF NOT EXISTS "idx_waha_message_logs_sent_at" ON "waha_message_logs" ("sent_at");
CREATE INDEX IF NOT EXISTS "idx_waha_message_logs_created_at" ON "waha_message_logs" ("created_at");