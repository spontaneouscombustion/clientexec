# --------------------------------------------------------
# VERSION UPDATING
# --------------------------------------------------------
UPDATE `setting` set value='2.8.1' WHERE name='ClientExec Version';

# --------------------------------------------------------
# TYPOS
# --------------------------------------------------------
UPDATE `help` SET detail = '[<font class=bodyhighlight>COMPANYNAME</font>] <br> Company name<br>[<font class=bodyhighlight>ACCOUNTINFORMATION</font>]<br> Includes Domain Name, Username, Password, IP<br>[<font class=bodyhighlight>DOMAINNAME</font>]<br> domain name without http://www.<br>[<font class=bodyhighlight>DOMAINUSERNAME</font>]<br> Domain User Name<br>[<font class=bodyhighlight>DOMAINPASSWORD</font>]<br> Domain Password<br>[<font class=bodyhighlight>DOMAINIP</font>]<br> IP Address to Domain<br>[<font class=bodyhighlight>COMPANYURL</font>]<br> URL to your web site<br>[<font class=bodyhighlight>SUPPORTEMAIL</font>]<br> E-mail to support staff<br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>]<br> URL to ClientExec.<br>[<font class=bodyhighlight>FORGOTPASSWORDURL</font>] URL to retrieve forgotten password.<br>[<font class=bodyhighlight>CLIENTNAME</font>]<br>Both first and last name<br>[<font class=bodyhighlight>CLIENTEMAIL</font>]<br>[<font class=bodyhighlight>NAMESERVERS</font>]<br>lists only hostnames<br>[<font class=bodyhighlight>NAMESERVERSANDIPS</font>]<br>lists both IPs and hostnames<br>[<font class=bodyhighlight>SERVERHOSTNAME</font>]<br>example server1.yourdomain.com<br>[<font class=bodyhighlight>SERVERSHAREDIP</font>]<br>shared IP for server<br>[<font class=bodyhighlight>CUSTOMPROFILE_xxxx</font>]<br>where xxx is custom profile field name<br>[<font class=bodyhighlight>CUSTOMPACKAGE_xxxx</font>]<br>where xxx is custom package field name' WHERE title = 'Welcome Email Tags';
UPDATE `help` SET detail = '[<font class=bodyhighlight>DATE</font>] Date payment is due.<br>[<font class=bodyhighlight>SENTDATE</font>] Date invoice was originally sent.<br>[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>CLIENTEMAIL</font>] <br>[<font class=bodyhighlight>INVOICENUMBER</font>]<br>[<font class=bodyhighlight>INVOICEDESCRIPTION</font>]<br>[<font class=bodyhighlight>TAX</font>]<br>[<font class=bodyhighlight>AMOUNT_EX_TAX</font>] The total price excluding taxes.<br>[<font class=bodyhighlight>AMOUNT</font>] The total price due.<br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>] URL to ClientExec.<br>[<font class=bodyhighlight>FORGOTPASSWORDURL</font>] URL to retrieve forgotten password.<BR>[<font class=bodyhighlight>COMPANYNAME</font>] <BR>[<font class=bodyhighlight>BILLINGEMAIL</font>] E-mail address for billing inquiries<br>[<font class=bodyhighlight>CUSTOMPROFILE_xxxx</font>]<br>where xxx is custom profile field name' WHERE title = 'Invoice Template Tags';
UPDATE `help` SET detail = '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>COMPANYNAME</font>]<br>[<font class=bodyhighlight>REQUESTIP</font>] IP of the machine which requested the password change.<br>[<font class=bodyhighlight>CONFIRMATION URL</font>] URL that user must press to confirm the password change<br>[<font class=bodyhighlight>CUSTOM_xxxx</font>]<br>where xxx is custom field name' WHERE title ='Reset Password Tags';
UPDATE `help` SET detail = '[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>TICKETNUMBER</font>]<br>[<font class=bodyhighlight>DESCRIPTION</font>]<br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>] URL to ClientExec.<BR>[<font class=bodyhighlight>COMPANYNAME</font>] <br>[<font class=bodyhighlight>CUSTOM_xxxx</font>]<br>where xxx is custom field name' WHERE title = 'Ticket Template Tags';
UPDATE `help` SET detail = '[<font class=bodyhighlight>COMPANYNAME</font>] <br>[<font class=bodyhighlight>COMPANYURL</font>]<br> URL to your web site<br>[<font class=bodyhighlight>SUPPORTEMAIL</font>]<br> E-mail to support staff<br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>]<br> URL to ClientExec.' WHERE title = 'Signup Completion Tags';
UPDATE `help` SET detail = '[<font class=bodyhighlight>DATE</font>] Date payment is due.<br>[<font class=bodyhighlight>CLIENTNAME</font>] <br>[<font class=bodyhighlight>CLIENTEMAIL</font>] <br>[<font class=bodyhighlight>AMOUNT</font>] The total price due.<br>[<font class=bodyhighlight>CLIENTAPPLICATIONURL</font>] URL to ClientExec.<br>[<font class=bodyhighlight>FORGOTPASSWORDURL</font>] URL to retrieve forgotten password.<BR>[<font class=bodyhighlight>COMPANYNAME</font>] <BR>[<font class=bodyhighlight>BILLINGEMAIL</font>] E-mail address for billing inquiries<br>[<font class=bodyhighlight>CUSTOMPROFILE_xxxx</font>]<br>where xxx is custom profile field name' WHERE title = 'Batch Invoice Notifier Tags';

UPDATE `setting` SET description = 'This is the SMTP host information to relay your E-mail through.' WHERE name = 'SMTP Host';
UPDATE `setting` SET description = 'Username required for relaying E-mail through your SMTP Host.<br>NOTE: Only fill this in if your server requires server authentication. Filling this setting in will set SMTP authentication on.' WHERE name = 'SMTP Username';
UPDATE `setting` SET description = 'Password required for relaying E-mail through your SMTP Host.<br>NOTE: Only fill this in if your server requires server authentication.' WHERE name = 'SMTP Password';
UPDATE `setting` SET description = 'This is the E-mail address that is visible to users after receiving an invoice payment request by E-mail.', name = 'Billing E-mail' WHERE name = 'Billing Email';
UPDATE `setting` SET description = 'Subject to be displayed to clients when they receive an invoice payment request by E-mail.' WHERE name = 'Invoice Subject';
UPDATE `setting` SET description = 'Please enter the absolute path to cURL on your system.<br>NOTE: Leaving this blank will instruct CE to assume you have PHP compiled with libCurl.', name = 'Path to cURL' WHERE name = 'Path to Curl';
UPDATE `setting` SET description = 'Select YES if you want the application to send a BCC, Blind Carbon Copy, to your BILLING E-mail each time an invoice is sent.' WHERE name = 'Invoice BCC';
UPDATE `setting` SET description = 'Template for E-mails that get sent out to clients after their tickets have been commented on or closed.' WHERE name = 'Trouble Ticket Template';
UPDATE `setting` SET description = 'Template for the E-mail sent to clients after invoice is paid.' WHERE name = 'Payment Receipt Template';
UPDATE `setting` SET description = 'Template for invoices sent through E-mail.' WHERE name = 'Invoice Template';
UPDATE `setting` SET description = 'E-mail address you want all support questions sent to.  This E-mail address is used as an entry to some template based E-mails, such as the welcome E-mail template.', name = 'Support E-mail' WHERE name = 'Support Email';
UPDATE `setting` SET description = 'Select YES if you want to integrate with a specific hosting control panel. NOTE: You must provide the required information for the control panel you select.' WHERE name = 'Integrate Control Panel';
UPDATE `setting` SET description = 'List the port number used in your installation of the Cpanel Control Panel. NOTE: This is usually set to 2082 as the default setting' WHERE name = 'Cpanel Port';
UPDATE `setting` SET description = 'Initial E-mail customer receives to confirm a password change to their account.' WHERE name = 'Forgot Password Template';
UPDATE `setting` SET description = 'Select the method you want ClientExec to send mail.<br>NOTE: If you have selected SMTP you will need to complete the SMTP settings as well.' WHERE name = 'Mail Type';
UPDATE `setting` SET description = 'Template for the E-mail sent to support staff after a ticket is assigned.' WHERE name = 'Support Ticket Assigned Template';
UPDATE `setting` SET description = 'E-mail addresses ClientExec will send E-mail notification on all new trouble tickets. You can separate E-mails by a comma.<br>Example: support1@domain.com, support2@domain.com', name = 'E-mail For New Trouble Tickets' WHERE name = 'Email For New Trouble Tickets';
UPDATE `setting` SET description = 'Text shown upon signup completion for new customers that are not forwarded to a payment gateway.<br><b>NOTE:</b> HTML is accepted.<br>' WHERE name = 'Signup Completion Template';
UPDATE `setting` SET description = 'Terms and Conditions that new customers need to agree to before receiving your services. The setting, Show Terms and Conditions, will need to be set to YES before this content is displayed in the signup process.<br><b>NOTE:</b> HTML is accepted.' WHERE name = 'Terms and Conditions';
UPDATE `setting` SET description = 'ID used to identify you to 2checkout.com.<br>NOTE: This ID is required if you have selected 2checkout as a payment gateway for any of your clients.' WHERE name = 'plugin_2checkout_Seller ID';
UPDATE `setting` SET description = 'ID used to identify you to PayPal.<br>NOTE: This ID is required if you have selected PayPal as a payment gateway for any of your clients.' WHERE name = 'plugin_paypal_User ID';
UPDATE `setting` SET description = 'ID used to identify you to PaySystems.<br>NOTE: This ID is required if you have selected PaySystems as a payment gateway for any of your clients.' WHERE name = 'plugin_paysystems_Company ID';
UPDATE `setting` SET description = 'ID used to identify you to WorldPay.<br>NOTE: This ID is required if you have selected WorldPay as a payment gateway for any of your clients.' WHERE name = 'plugin_worldpay_Installation ID';
UPDATE `setting` SET description = 'Password used to verify valid transactions FROM WorldPay Callbacks.<br>NOTE: This password has to match the value set in the WorldPay Customer Management System.' WHERE name = 'plugin_worldpay_Callback Password';
UPDATE `setting` SET description = 'MD5 Secret used to verify valid transactions FROM WorldPay.<br>NOTE: This secret has to match the value set in the WorldPay Customer Management System.' WHERE name = 'plugin_worldpay_MD5 Secret';
UPDATE `setting` SET description = 'E-mail addresses ClientExec will send E-mail notifications to after all new signups. You can separate E-mails by a comma.<br>Example: support1@domain.com, support2@domain.com', name = 'E-mail For New Signups' WHERE name = 'Email For New Signups';
UPDATE `setting` SET description = 'Select YES if you want ClientExec to show all open support tickets in dashboard.' WHERE name = 'Show Open Support Tickets';
UPDATE `setting` SET description = 'Select YES if you want ClientExec to show all packages awaiting activation in the dashboard.' WHERE name = 'Show Users and Packages Awaiting Activation';
UPDATE `setting` SET description = 'Select YES if you want ClientExec to show all outstanding invoices in dashboard.' WHERE name = 'Show Outstanding Invoices';
UPDATE `setting` SET description = 'Select YES if you want ClientExec to show all customers with credit cards needing validation in dashboard.' WHERE name = 'Show Credit Cards Needing Validation';
UPDATE `setting` SET description = 'Select YES if you want ClientExec to highlight the entire sorted column when clicking on a new header.' WHERE name = 'Highlight Selected Column';
UPDATE `setting` SET description = 'Select YES if you want ClientExec to show all uninvoiced work in dashboard.' WHERE name = 'Show Uninvoiced Work';
UPDATE `setting` SET description = 'Setting allows you to reject any order using free E-mail services like Hotmail and Yahoo (free E-mail = higher risk).<br><b>NOTE: </b>Requires MaxMind', name = 'Reject Free E-mail Service' WHERE name = 'Reject Free Email Service';
UPDATE `setting` SET description = 'Setting allows you to reject any order where country of IP address does not match the billing address country (mismatch = higher risk).<br><b>NOTE: </b>Requires MaxMind' WHERE name = 'Reject Country Mismatch';
UPDATE `setting` SET description = 'Setting allows you to reject any order where the IP address is an Anonymous Proxy (anonymous proxy = very high risk).<br><b>NOTE: </b>Requires MaxMind' WHERE name = 'Reject Anonymous Proxy';
UPDATE `setting` SET description = 'Setting allows you to reject any order where the country the IP is based from is considered a country where fraudulent order is likely.<br><b>NOTE: </b>Requires MaxMind' WHERE name = 'Reject High Risk Country';
UPDATE `setting` SET description = 'MaxMind risk score is based on known risk factors and their likelihood to indicate possible fraud. Select the threshold you want ClientExec to reject on. ( 0=low risk 10=high risk)<br><b>NOTE:</b> Requires MaxMind<br>To see how the fraud score is obtained visit <br><a href=http://www.maxmind.com/app/web_services_score2?rId=clientexec target=_blank>http://www.maxmind.com/app/web_services_score2</a>' WHERE name = 'MaxMind Fraud Risk Score';
UPDATE `setting` SET description = '<i>For E-mail piping only</i><br>Text used in the bounced E-mail sent by ClientExec when an E-mail is received via the pipe cron that does not belong to one of your customers.' WHERE name = 'Bounce Back Template';
UPDATE `setting` SET description = 'Text shown to customer upon signup rejection.<br><b>NOTE:</b> HTML accepted<br>' WHERE name = 'Signup Rejection Template';
UPDATE `setting` SET description = 'Select YES if you want to view all of your customers in a dropdown for faster navigation.<br><b>NOTE:</b> Performance will suffer with more customers' WHERE name = 'View Customer DropDown';
UPDATE `setting` SET description = 'Temporarily disable login from customers without admin privileges' WHERE name = 'Login Disabled';
UPDATE `setting` SET description = 'System message for explaining to customers why the login is temporary disabled. If it is empty, default message will be displayed.' WHERE name = 'Login disabled system message';
UPDATE `setting` SET description = 'E-mail addresses to CC notification messages for new high priority tickets', name = 'E-mail For New High Priority Trouble Tickets' WHERE name = 'Email For New High Priority Trouble Tickets';
UPDATE `setting` SET description = 'If MaxMind Telephone Verification is enabled, only trigger the verification call if the total bill amount exceeds this amount' WHERE name = 'Minimum Bill Amount to Trigger Telephone Verification';
UPDATE `setting` SET description = 'Select this option if you want to display an Access code to prevent signup flooding and require the form to be filled in by a human' WHERE name = 'Request Access Code';
UPDATE `setting` SET description = 'Select YES if you want to disable the embedded ClientExec support system. <br> Additionally, you can define a URL to redirect your customers to an external support system of your own.' WHERE name = 'Disable CE Tickets Support System';
UPDATE `setting` SET description = 'Selecting YES will enable effects in ClientExec for both administrators and end users' WHERE name = 'Use Effects';
UPDATE `setting` SET description = 'Setting this to YES will show the MaxMind fraud screening logo in the signup footer if credit card fraud detection or phone verification is turned on.' WHERE name = 'Show MaxMind Logo';
UPDATE `setting` SET description = 'Template for E-mails that get sent out to clients after a new ticket is added by E-mail through the support pipe.' WHERE name = 'New Trouble Ticket Autoresponder Template';
UPDATE `setting` SET description = 'Enter an E-mail address if you want to be notified when a catchable error occurs in your ClientExec installation.' WHERE name = 'Application Error Notification';
UPDATE `setting` SET description = 'Enter the URL you want clients to be redirected to after logging out.<br><b>NOTE:</b> Leave this field blank to use the login page.' WHERE name = 'Custom Logout URL';
UPDATE `setting` SET description = 'Set this to YES if you wish to allow customers to upload files to support tickets.' WHERE name = 'Allow Customer File Uploads';
UPDATE `setting` SET description =  'E-mail template used when notifying customer of outstanding invoices using the Invoice Reminder service.' WHERE name = 'Overdue Invoice Template';
UPDATE `setting` SET description = 'Template for the E-mail sent to clients by the upcoming batch invoice notifier service.' WHERE name = 'Batch Invoice Notification Template';
UPDATE `setting` SET description = 'This is the SMTP port that will be used when sending E-mail.  Leaving this blank will use port 25 by default.' WHERE name = 'SMTP Port';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_2checkout_Plugin Name';
UPDATE `setting` SET description = 'Select YES if you want to set 2checkout into Demo Mode for testing. (<b>NOTE:</b> You must set to NO before accepting actual payments through this processor.)' WHERE name = 'plugin_2checkout_Demo Mode';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_2checkout_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_authnet_Plugin Name';
UPDATE `setting` SET description = 'Used to verify valid transactions from Authorize.Net.<br>NOTE: This value has to match the value set in the Authorize.Net Merchant Interface - <i>Optional</i>' WHERE name = 'plugin_authnet_MD5 Hash Value';
UPDATE `setting` SET description = 'Select YES if you want to set this plugin in Demo mode for testing purposes.' WHERE name = 'plugin_authnet_Demo Mode';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_authnet_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_bluepay_Plugin Name';
UPDATE `setting` SET description = 'Select YES if you want to set this plugin in Demo mode for testing purposes.' WHERE name = 'plugin_bluepay_Demo Mode';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_bluepay_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_ccavenue_Plugin Name';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_ccavenue_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_chronopay_Plugin Name';
UPDATE `setting` SET description = 'Product ID configured in your ChronoPay Account.<br>NOTE: This ID is required if you have selected ChronoPay as a payment gateway for any of your clients.' WHERE name = 'plugin_chronopay_Product ID';
UPDATE `setting` SET description = 'Product Name to be displayed on the ChronoPay hosted payment page.' WHERE name = 'plugin_chronopay_Product Name';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_chronopay_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_egold_Plugin Name';
UPDATE `setting` SET description = 'ID used to identify you to E-Gold.<br>NOTE: This ID is required if you have selected E-Gold as a payment gateway for any of your clients.' WHERE name = 'plugin_egold_User ID';
UPDATE `setting` SET description = 'Password used to verify valid transactions from E-Gold Callbacks.<br>NOTE: This password has to match the value set in the E-Gold Account Information.' WHERE name = 'plugin_egold_Alternate Passphrase';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_egold_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_eprocessingnetwork_Plugin Name';
UPDATE `setting` SET description = 'description"=>"Select YES if you want to set this plugin in Demo mode for testing purposes.' WHERE name = 'plugin_eprocessingnetwork_Demo Mode';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_eprocessingnetwork_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_googlecheckout_Plugin Name';
UPDATE `setting` SET description = 'ID used to identify you to Google Checkout.<br>NOTE: This ID is required if you have selected Google Checkout as a payment gateway for any of your clients.' WHERE name = 'plugin_googlecheckout_Merchant ID';
UPDATE `setting` SET description = 'key used to identify you to Google Checkout.<br>NOTE: This key is required if you have selected Google Checkout as a payment gateway for any of your clients.' WHERE name = 'plugin_googlecheckout_Merchant Key';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_internetsecure_Plugin Name';
UPDATE `setting` SET description = 'ID used to identify you to Internet Secure.<br>NOTE: This ID is required if you have selected Internet Secure as a payment gateway for any of your clients.' WHERE name = 'plugin_internetsecure_Company ID';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_internetsecure_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_moneybookers_Plugin Name';
UPDATE `setting` SET description = 'E-mail address used to identify you to Moneybookers.' WHERE name = 'plugin_moneybookers_Merchant e-mail';
UPDATE `setting` SET name = 'plugin_moneybookers_Merchant E-mail' WHERE name = 'plugin_moneybookers_Merchant e-mail';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_moneybookers_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_offlinebanktransfe_Plugin Name';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_offlinebanktransfe_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_offlinecheck_Plugin Name';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_offlinecheck_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_offlinecreditcard_Plugin Name';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_offlinecreditcard_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_offlinemoneyorder_Plugin Name';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_offlinemoneyorder_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_paypal_Plugin Name';
UPDATE `setting` SET description = 'Select YES if you prefer CE to only generate invoices upon notification of payment via the callback supported by this processor.  Setting to NO will generate invoices normally but require you to manually mark them paid as you receive notification from processor.' WHERE name = 'plugin_paypal_Generate Invoices After Callback Notification';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_paypal_Invoice After Signup';
UPDATE `setting` SET name = 'plugin_paypal_Use PayPal Sandbox' WHERE name = 'plugin_paypal_Use Paypal Sandbox';
UPDATE `setting` SET description = 'Select YES if you want to use Paypal&#039;s testing server, so no actual monetary transactions are made. You need to have a developer account with Paypal, and be logged-in in the developer panel in another browser window for the transaction to be successful.' WHERE name = 'plugin_paypal_Use PayPal Sandbox';
UPDATE `setting` SET name = 'plugin_paypal_Force customers to use PayPal subscriptions' WHERE name = 'plugin_paypal_Force customers to use Paypal subscriptions';
UPDATE `setting` SET description = 'Select YES if you want to force customers to use PayPal subscriptions in signup.' WHERE name = 'plugin_paypal_Force customers to use PayPal subscriptions';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_paysystems_Plugin Name';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_paysystems_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_protxform_Plugin Name';
UPDATE `setting` SET description = 'Vendor ID used to identify you to protx.<br>NOTE: This ID is required if you have selected protx as a payment gateway for any of your clients.' WHERE name = 'plugin_protxform_Vendor ID';
UPDATE `setting` SET description = 'Password used to crypt payment information.<br>NOTE: This password has to match the value set by protx.' WHERE name = 'plugin_protxform_Crypt Password';
UPDATE `setting` SET description = 'This E-mail is sent from protx to inform the customer of the transaction.  You need to set this to your E-mail address that you want bills to come from.' WHERE name = 'plugin_protxform_Vendor Email';
UPDATE `setting` SET name = 'plugin_protxform_Vendor E-mail' WHERE description = 'This E-mail is sent from protx to inform the customer of the transaction.  You need to set this to your E-mail address that you want bills to come from.';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_protxform_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_psigate_Plugin Name';
UPDATE `setting` SET description = 'ID used to identify you to PSiGate.<br>NOTE: This ID is required if you have selected PSiGate as a payment gateway for any of your clients.' WHERE name = 'plugin_psigate_Store Name';
UPDATE `setting` SET description = 'Select YES if you want to set this plugin in Demo mode for testing purposes.' WHERE name = 'plugin_psigate_Demo Mode';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_psigate_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_quantum_Plugin Name';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_quantum_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_stormpay_Plugin Name';
UPDATE `setting` SET description = 'ID used to identify you to StormPay.<br>NOTE: This ID is required if you have selected StormPay as a payment gateway for any of your clients.' WHERE name = 'plugin_stormpay_User ID';
UPDATE `setting` SET description = 'Button URL of the StormPay Buy Now Button.' WHERE name = 'plugin_stormpay_buttonurl';
UPDATE `setting` SET description = 'Secret Code as configured in your StormPay Accounts IPN configurations.' WHERE name = 'plugin_stormpay_secretcode';
UPDATE `setting` SET description = 'Test mode Switch for the StormPay Module.' WHERE name = 'plugin_stormpay_test';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_worldpay_Plugin Name';
UPDATE `setting` SET description = 'Select YES if you want an invoice sent to the customer after signup is complete.' WHERE name = 'plugin_worldpay_Invoice After Signup';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_directi_Plugin Name';
UPDATE `setting` SET description = 'This can be found in your Directi profile.' WHERE name = 'plugin_directi_Parent ID';

UPDATE `setting` SET description = 'How CE sees this plugin (not to be confused with the Signup Name)' WHERE name = 'plugin_enom_Plugin Name';

UPDATE `setting` SET description = 'When enabled, tickets remaining unresponded to by customers for x amount of days will automatically be closed.' WHERE name = 'plugin_autoclose_Enabled';
UPDATE `setting` SET description = 'Enter number of days to wait before autoclosing a ticket that is in the waiting on customer status.' WHERE name = 'plugin_autoclose_Days to trigger autoclose';
UPDATE `setting` SET description = 'Enter the message you would like entered into the ticket when it is closed.  NOTE: the customer name and company name will automatically be added to the message header and footer.' WHERE name = 'plugin_autoclose_Ticket Message';

UPDATE `setting` SET description = 'When a package requires manual suspension you will be notified at this E-mail address.  If packages are suspended when this service is run, a summary E-mail will be sent to this address.' WHERE name = 'plugin_autosuspend_Email Notifications';
UPDATE `setting` SET name = 'plugin_autosuspend_E-mail Notifications' WHERE description = 'When a package requires manual suspension you will be notified at this E-mail address.  If packages are suspended when this service is run, a summary E-mail will be sent to this address.';
UPDATE `setting` SET description = 'Only suspend packages that are this many days overdue.' WHERE name = 'plugin_autosuspend_Days Overdue Before Suspending';
UPDATE `setting` SET description = 'Used to store package IDs of manually suspended packages whose E-mail has already been sent.' WHERE name = 'plugin_autosuspend_Notified Package List';

UPDATE `setting` SET description = 'Generates a file with an SQL dump of your ClientExec database on a periodic basis, and delivers it to any of the locations set below.' WHERE name = 'plugin_backup_Enabled';
UPDATE `setting` SET description = 'To send the file as an E-mail attachment, enter the address here.' WHERE name = 'plugin_backup_Deliver to e-mail address';
UPDATE `setting` SET name = 'plugin_backup_Deliver to E-mail address' WHERE description = 'To send the file as an E-mail attachment, enter the address here.';

UPDATE `setting` SET description = 'When enabled, alert credit card autopayment customers a set number of days before an invoice is processed.<br><b>NOTE:</b> Only run once per day to avoid duplicate E-mails.' WHERE name = 'plugin_batchnotifier_Enabled';
UPDATE `setting` SET description = 'E-mail address to which a summary of each service run will be sent.  (Leave blank if you do not wish to receive a summary)' WHERE name = 'plugin_batchnotifier_Summary Email';
UPDATE `setting` SET name = 'plugin_batchnotifier_Summary E-mail' WHERE description = 'E-mail address to which a summary of each service run will be sent.  (Leave blank if you do not wish to receive a summary)';
UPDATE `setting` SET description = 'Number of days before due date to send the E-mail notification.' WHERE name = 'plugin_batchnotifier_Days Offset';
UPDATE `setting` SET description = 'The E-mail subject sent to the client.' WHERE name = 'plugin_batchnotifier_Email Subject';
UPDATE `setting` SET name = 'plugin_batchnotifier_E-mail Subject' WHERE description = 'The E-mail subject sent to the client.';

UPDATE `setting` SET description = 'When enabled, notify customers with expiring cards at the end of the month.  When run it will E-mail customers with a credit card expiring that month and generate a ticket for credit cards expired in the previous month.<br><b>NOTE:</b> This service is intended to run only once per month.' WHERE name = 'plugin_expiringcc_Enabled';
UPDATE `setting` SET description = 'Subject to be displayed on E-mail notifications.' WHERE name = 'plugin_expiringcc_Email Subject';
UPDATE `setting` SET  name = 'plugin_expiringcc_E-mail Subject' WHERE description = 'Subject to be displayed on E-mail notifications.';

UPDATE `setting` SET description = 'When enabled, the support E-mail account will be periodically consulted via POP3, and the relevant messages will be imported as tickets. Useful as a complement or replacement to E-mail piping. Only USER authentication mechanism and non-SSL connections are supported. Be aware that messages on the account will get erased after being imported, or bounced if invalid.' WHERE name = 'plugin_fetchticket_Enabled';

UPDATE `setting` SET description = 'When a domain requires manual registration or transfer, or an account requires manual setup you will be notified at this E-mail address.' WHERE name = 'plugin_order_Email Notifications';
UPDATE `setting` SET name = 'plugin_order_E-mail Notifications' WHERE description = 'When a domain requires manual registration or transfer, or an account requires manual setup you will be notified at this E-mail address.';

UPDATE `setting` SET description = 'When enabled, late invoice reminders will be sent out to customers. This service should only run once per day to avoid sending reminders twice in the same day.' WHERE name = 'plugin_rebiller_Enabled';
UPDATE `setting` SET description = 'Subject to be displayed on E-mail notifications of overdue invoices' WHERE name = 'plugin_rebiller_Email Subject';
UPDATE `setting` SET name = 'plugin_rebiller_E-mail Subject' WHERE description = 'Subject to be displayed on E-mail notifications of overdue invoices';

UPDATE `setting` SET description = 'When enabled and server plugin has server stats script URL, ClientExec will notify you if the server is not responding or when certain thresholds are met.' WHERE name = 'plugin_serverstatus_Enabled';
UPDATE `setting` SET description = 'E-mails that will be E-mailed when thresholds are passed. Separate multiple E-mails with commas.<br/>Ex: e-mail1@domain.com, e-mail2@domain.com' WHERE name = 'plugin_serverstatus_Admin Email';
UPDATE `setting` SET name = 'plugin_serverstatus_Admin E-mail' WHERE name = 'plugin_serverstatus_Admin Email';
UPDATE `setting` SET description = 'Add server load threshold you want this service to E-mail if passed.<br/>Ex: 1.5, will E-mail when load goes over 1.5' WHERE name = 'plugin_serverstatus_Server Load';
UPDATE `setting` SET description = 'Add mount and percentage threshold that you want this service to E-mail you on. Use ; as separator if you want to monitor more than one mount.<br/>Ex: /home,75;/tmp,50' WHERE name = 'plugin_serverstatus_Mount Space Available';

UPDATE `setting` SET description = 'If E-mail piping is in use set this to true so the reply above this line can be added to support ticket E-mails.' WHERE name = 'Email Piping In Use';

UPDATE `troubleticket_type` SET name = 'E-mail Problems' WHERE name = 'Email Problems';

UPDATE `setting` SET name = 'plugin_cpanel_Failure E-mail' WHERE name = 'plugin_cpanel_Failure Email';
UPDATE `setting` SET description = 'E-mail address Cpanel error messages will be sent to' WHERE name = 'plugin_cpanel_Failure E-mail';

UPDATE `setting` SET name = 'plugin_interworx_Failure E-mail' WHERE name = 'plugin_interworx_Failure Email';
UPDATE `setting` SET description = 'E-mail address InterWorx-CP plugin error messages will be sent to.' WHERE name = 'plugin_interworx_Failure E-mail';