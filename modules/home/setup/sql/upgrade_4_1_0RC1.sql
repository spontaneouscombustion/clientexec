# ---------------------------------------------------------
# DELETE ClientExec RSS Reader link so we properly use https
# ---------------------------------------------------------
DELETE FROM `plugin_custom_data` WHERE `plugin_name`='rssreader' AND `value` LIKE '%clientexec.com%'; 