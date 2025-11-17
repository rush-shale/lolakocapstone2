-- Migration: Add document flags for waiting list seniors
-- Adds boolean columns to track submitted documents required while on the waiting list

ALTER TABLE seniors
	ADD COLUMN waiting_birth_certificate TINYINT(1) NOT NULL DEFAULT 0 AFTER validation_date,
	ADD COLUMN waiting_marriage_contract TINYINT(1) NOT NULL DEFAULT 0 AFTER waiting_birth_certificate,
	ADD COLUMN waiting_valid_id TINYINT(1) NOT NULL DEFAULT 0 AFTER waiting_marriage_contract;


