-- Migration: Add email verification columns to users table
-- Run this in phpMyAdmin or MySQL CLI

ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email;
ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) NULL AFTER email_verified_at;
