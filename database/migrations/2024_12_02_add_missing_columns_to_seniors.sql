-- Migration to add missing columns to seniors table: osca_id_no, remarks, health_condition, purok, cellphone

ALTER TABLE seniors ADD COLUMN osca_id_no VARCHAR(50) NOT NULL DEFAULT '';
ALTER TABLE seniors ADD COLUMN remarks TEXT NOT NULL;
ALTER TABLE seniors ADD COLUMN health_condition VARCHAR(255) NOT NULL;
ALTER TABLE seniors ADD COLUMN purok VARCHAR(100) NOT NULL;
ALTER TABLE seniors ADD COLUMN cellphone VARCHAR(50) DEFAULT NULL;

-- Update existing records
UPDATE seniors SET remarks = '', health_condition = '', purok = '' WHERE 1;

-- Make other columns NOT NULL
ALTER TABLE seniors MODIFY COLUMN civil_status ENUM('single','married','widowed','divorced','separated') NOT NULL;
ALTER TABLE seniors MODIFY COLUMN educational_attainment ENUM('no_formal_education','elementary','high_school','vocational','college','graduate','post_graduate') NOT NULL;
ALTER TABLE seniors MODIFY COLUMN other_skills TEXT NOT NULL;
