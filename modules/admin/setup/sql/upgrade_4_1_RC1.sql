DELETE from `setting` WHERE `name` = 'Access Via Domain Username';

# Fix for issues with email piping and orphaned staff IDs after upgrading
DELETE FROM `departments_members` WHERE `member_id` NOT IN (SELECT `id` FROM `users`);