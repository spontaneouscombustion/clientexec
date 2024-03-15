# --------------------------------------------------------
# VERSION UPDATING
# --------------------------------------------------------
UPDATE `setting` set value='2.8.0' WHERE name='ClientExec Version';

# --------------------------------------------------------
# CHANGE IN SOME DESCRIPTIONS
# --------------------------------------------------------

UPDATE `setting` SET description = 'This setting will\r\nenable the Maxmind telephone verification plugin on signup for new\r\ncustomers. (Phone credits are bought separate from regular credit card\r\nfraud detection services)<br><a\r\nhref=\"http://www.maxmind.com/app/telephone_buynow?rId=clientexec\">http://www.maxmind.com/app/telephone_buynow</a>' WHERE name = 'Enable MaxMind Telephone Verification';
UPDATE `setting` SET description = 'This is the SMTP host information to relay your Email through.' WHERE name = 'SMTP Host';
UPDATE `setting` SET description = 'Username required for relaying Email through your SMTP Host.<br>NOTE: Only fill this in if your server requires server authentication. Filling this setting in will set SMTP authentication on.' WHERE name = 'SMTP Username';
UPDATE `setting` SET description = 'Password required for relaying Email through your SMTP Host.<br>NOTE: Only fill this in if your server requires server authentication.' WHERE name = 'SMTP Password';
UPDATE `setting` SET description = 'This is the Email address that is visible to users after receiving an invoice payment request by Email.' WHERE name = 'Billing Email';
UPDATE `setting` SET description = 'Subject to be displayed to clients when they receive an invoice payment request by Email.' WHERE name = 'Invoice Subject';
UPDATE `setting` SET description = 'The URL to this application. This value is used in many places where the client is sent a link or where any 3rd party billing might return to give control back to this site.<br>NOTE: Make sure you include http:// in front of the domain name<br>(example: http://www.clientexec.com)' WHERE name = 'ClientExec URL';
UPDATE `setting` SET description = 'Select the date format you want sitewide.' WHERE name = 'Date Format';
UPDATE `setting` SET description = 'Emails that will be emailed when thresholds are passed. Separate multiple emails with commas.<br/>ex: email1@domain.com,email2@domain.com' WHERE name = 'plugin_serverstatus_Admin E-mail';