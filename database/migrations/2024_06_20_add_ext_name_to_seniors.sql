-- Migration to add ext_name column to seniors table

ALTER TABLE seniors
ADD COLUMN ext_name VARCHAR(50) NULL AFTER last_name;
