-- Migration: Add expiration_date field to items table
-- Date: 2024-02-24
-- Purpose: Track expiration dates for medical supplies and other perishable items

-- Add expiration_date column to items table
ALTER TABLE items 
ADD COLUMN expiration_date DATE AFTER location;

-- Add index for better query performance
ALTER TABLE items 
ADD INDEX idx_expiration_date (expiration_date);

-- Note: This migration adds an expiration_date field to track items that expire
-- This is especially important for medical supplies and perishable items
