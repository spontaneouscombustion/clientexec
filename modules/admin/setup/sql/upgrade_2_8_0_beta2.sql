# --------------------------------------------------------
# VERSION UPDATING
# --------------------------------------------------------
UPDATE `setting` set value='2.8.0 beta2' WHERE name='ClientExec Version';

# --------------------------------------------------------
# NEW SETTING TO SHOW EXECUTION TIME
# --------------------------------------------------------
INSERT INTO `setting` VALUES (NULL, 'Show Execution Time', '0', 'Select YES to show the script\'s execution time in the footer.', 1, 1, 1, 0, 0, 3, 0, 0, 0, 0);
