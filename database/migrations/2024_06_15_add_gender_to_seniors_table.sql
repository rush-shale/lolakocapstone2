-- Migration to add gender column to seniors table
ALTER TABLE seniors
ADD COLUMN gender VARCHAR(10) DEFAULT NULL;
