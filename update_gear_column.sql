-- Add gear column if it doesn't exist
ALTER TABLE `voiture` 
ADD COLUMN `gear` ENUM('automatique', 'manuel') DEFAULT NULL AFTER `type`;

-- Update existing cars with mixed gear types
UPDATE `voiture` SET `gear` = 'automatique' WHERE `id_voiture` IN (1, 3, 5);
UPDATE `voiture` SET `gear` = 'manuel' WHERE `id_voiture` IN (2, 4);

-- Set any remaining NULL values to 'manuel' as default
UPDATE `voiture` SET `gear` = 'manuel' WHERE `gear` IS NULL;

-- Verify the update
SELECT id_voiture, marque, modele, gear FROM `voiture`;
