-- Update existing image URLs to include /travel-booking/ prefix
-- Run this script to fix existing image paths in the database

UPDATE dbo.users 
SET user_profile_image = '/travel-booking' + user_profile_image 
WHERE user_profile_image IS NOT NULL 
AND user_profile_image NOT LIKE '/travel-booking%';

UPDATE dbo.hotels 
SET image_url = '/travel-booking' + image_url 
WHERE image_url IS NOT NULL 
AND image_url NOT LIKE '/travel-booking%';

UPDATE dbo.flights 
SET image_url = '/travel-booking' + image_url 
WHERE image_url IS NOT NULL 
AND image_url NOT LIKE '/travel-booking%';

UPDATE dbo.activities 
SET image_url = '/travel-booking' + image_url 
WHERE image_url IS NOT NULL 
AND image_url NOT LIKE '/travel-booking%';

UPDATE dbo.transfers 
SET image_url = '/travel-booking' + image_url 
WHERE image_url IS NOT NULL 
AND image_url NOT LIKE '/travel-booking%';

UPDATE dbo.ads 
SET image_url = '/travel-booking' + image_url 
WHERE image_url IS NOT NULL 
AND image_url NOT LIKE '/travel-booking%';
