ALTER TABLE seniors
ADD COLUMN validation_status ENUM('Not Validated', 'Validated') DEFAULT 'Not Validated',
ADD COLUMN validation_date DATETIME DEFAULT NULL;
