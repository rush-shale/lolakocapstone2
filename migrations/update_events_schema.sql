-- Update events table to include contact number and exact location
-- Run this migration to add new fields to the events table

-- Add new event information fields
ALTER TABLE `events` 
ADD COLUMN `contact_number` VARCHAR(50) NULL AFTER `event_time`,
ADD COLUMN `exact_location` TEXT NULL AFTER `contact_number`;
