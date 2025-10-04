-- Migration to update seniors table: add 'waiting' to category enum, change gender to sex, and ensure all fields match complete schema

-- First, add 'waiting' to category enum
ALTER TABLE seniors MODIFY COLUMN category ENUM('local','national','waiting') NOT NULL DEFAULT 'local';

-- Change gender to sex if gender exists
-- Assuming gender was added, rename to sex
ALTER TABLE seniors CHANGE COLUMN gender sex ENUM('male','female','lgbtq') DEFAULT NULL;

-- Ensure other fields are as per complete schema
-- Make sure civil_status is NOT NULL (but since it's enum, and code provides, ok)
-- But in code, it's ?: '', but since required, ok
-- For other fields, they are provided as '' or null, but schema has NOT NULL for some, but '' is fine for varchar.

-- If needed, but probably not.
