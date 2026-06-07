-- Visual Design Journey anti-spam hardening helpers
-- Safe to run after the main schema. Existing unique email/username keys already block duplicates.

ALTER TABLE `users`
  ADD INDEX IF NOT EXISTS `idx_users_created_at` (`created_at`);

ALTER TABLE `newsletter_subscribers`
  ADD INDEX IF NOT EXISTS `idx_newsletter_created_at` (`created_at`);

ALTER TABLE `sponsor_leads`
  ADD INDEX IF NOT EXISTS `idx_sponsor_email_created` (`email`, `created_at`);

-- Manual review helper examples:
-- SELECT id, username, email, created_at
-- FROM users
-- WHERE created_at >= NOW() - INTERVAL 7 DAY
--   AND (
--     username REGEXP '[0-9]{5,}'
--     OR email REGEXP '(mailinator|tempmail|yopmail|casino|crypto|forex|viagra|porn)'
--   )
-- ORDER BY created_at DESC;
