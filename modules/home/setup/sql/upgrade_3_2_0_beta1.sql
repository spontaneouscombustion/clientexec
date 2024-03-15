# ---------------------------------------------------------
# DELETE UNUSED PERMISSION
# ---------------------------------------------------------
DELETE FROM permissions WHERE permission='home_view_recommend_us';

# ---------------------------------------------------------
# NEW SETTING FOR REMOVING ARTICLE DASHBOARD TAB
# ---------------------------------------------------------
INSERT INTO `setting` VALUES (NULL,'Show Articles','1',NULL,'','11','1','0','0','0','0','0','0','0','0','0');
