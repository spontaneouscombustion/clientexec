# ---------------------------------------------------------
# PLESK'S SHELL VARIABLE SHOULD BE OF TEXT TYPE, NOT TRUE/FALSE
# ---------------------------------------------------------
UPDATE package_variable SET value="/bin/bash" WHERE varname="plugin_plesk_package_vars_shell" AND value="1";

# ------------------------------------------------------------------------------
# UPDATE ENOM SNAPIN DESCRIPTION
# (snapins still use the setting description column, unlike regular settings)
# ------------------------------------------------------------------------------
UPDATE `setting` SET `description` = 'Enter your username for your Enom reseller account. If you are using Newedge\'s account, you can leave this field empty.' WHERE name='plugin_enomform_Login' ;
UPDATE `setting` SET `description` = 'Enter the password for your Enom reseller account. If you are using Newedge\'s account, you can leave this field empty.' WHERE name='plugin_enomform_Password';
