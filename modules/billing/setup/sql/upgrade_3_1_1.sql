# ---------------------------------------------------------
# IMPROVE PAYPAL SUBSCRIPTIONS SETTING
# ---------------------------------------------------------
UPDATE setting SET name='plugin_paypal_Paypal Subscriptions Option In Signup', description='This setting allows you to determine if a customer will be able to opt for subscriptions when using Paypal in signup, or if he or she will be forced into using or not using subscriptions.', istruefalse=0 WHERE name = 'plugin_paypal_Force customers to use PayPal subscriptions';
