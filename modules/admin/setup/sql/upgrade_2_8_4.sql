# --------------------------------------------------------
# VERSION UPDATING
# --------------------------------------------------------
UPDATE `setting` set value='2.8.4' WHERE name='ClientExec Version';

# ---------------------------------------------------------
# ADDED SFTP SUPPORT TO THE AUTOMATIC BACKUP SERVICE
# CHANGED PLUGIN NAME AND DESCRIPTION
# ---------------------------------------------------------
UPDATE `setting` SET description = 'To send the file to a remote FTP or SFTP account enter the host and your credentials in the format <b>ftp://username:password@host.com/subdirectory</b> for FTP<br>or<br><b>sftp://username:password@host.com/subdirectory</b> for SFTP<br>SFTP is only possible if you have the ssh2 extension in your PHP installation', name = 'plugin_backup_Deliver to remote FTP or SFTP account' WHERE  name = 'plugin_backup_Deliver to remote FTP account';