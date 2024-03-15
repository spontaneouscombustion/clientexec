<?php

require_once 'modules/admin/models/GatewayPlugin.php';
require_once 'modules/billing/models/class.gateway.plugin.php';

/**
* @package Plugins
*/
class PluginSquarepayment extends GatewayPlugin
{
    function getVariables()
    {
        $variables = array(
            lang('Plugin Name') => array(
                'type'        => 'hidden',
                'description' => lang('How CE sees this plugin ( not to be confused with the Signup Name )'),
                'value'       => 'Square Payment'
            ),
            lang('Square Payment Application ID') => array(
                'type'        => 'password',
                'description' => lang('Please enter your Square Payment Application ID here.'),
                'value'       => ''
            ),
            lang('Square Payment Location ID') => array(
                'type'        => 'text',
                'description' => lang('Please enter your Square Payment Location ID here.'),
                'value'       => ''
            ),
            lang('Square Payment Access Token') => array(
                'type'        => 'password',
                'description' => lang('Please enter your Square Payment Access Token here.'),
                'value'       => ''
            ),
            lang('Use Sandbox') => array(
                'type'        => 'yesno',
                'description' => lang('Select YES if you want to use Square Payment\'s testing server, so no actual monetary transactions are made..'),
                'value'       => '0'
            ),
            lang('Invoice After Signup') => array(
                'type'        => 'yesno',
                'description' => lang('Select YES if you want an invoice sent to the client after signup is complete.'),
                'value'       => '1'
            ),
            lang('Signup Name') => array(
                'type'        => 'text',
                'description' => lang('Select the name to display in the signup process for this payment type. Example: eCheck or Credit Card.'),
                'value'       => 'Square Payment'
            ),
            lang('Dummy Plugin') => array(
                'type'        => 'hidden',
                'description' => lang('1 = Only used to specify a billing type for a client. 0 = full fledged plugin requiring complete functions'),
                'value'       => '0'
            ),
            lang('Auto Payment') => array(
                'type'        => 'hidden',
                'description' => lang('No description'),
                'value'       => '1'
            ),
            lang('CC Stored Outside') => array(
                'type'        => 'hidden',
                'description' => lang('If this plugin is Auto Payment, is Credit Card stored outside of Clientexec? 1 = YES, 0 = NO'),
                'value'       => '1'
            ),
            lang('Billing Profile ID') => array(
                'type'        => 'hidden',
                'description' => lang('Is this plugin storing a Billing-Profile-ID? 1 = YES, 0 = NO'),
                'value'       => '1'
            ),
            lang('Form') => array(
                'type'        => 'hidden',
                'description' => lang('Has a form to be loaded?  1 = YES, 0 = NO'),
                'value'       => '1'
            ),
            lang('Call on updateGatewayInformation') => array(
                'type'        => 'hidden',
                'description' => lang('Function name to be called in this plugin when given conditions are meet while updateGatewayInformation is invoked'),
                'value'       => serialize(
                    array(
                        'function'                      => 'createFullCustomerProfile',
                        'plugincustomfields conditions' => array( //All conditions must match.
                            array(
                                'field name' => 'squarepaymentCardNonce', //Supported values are the field names used in form.phtml of the plugin, with name="plugincustomfields[field_name]"
                                'operator'   => '!=',                   //Supported operators are: ==, !=, <, <=, >, >=
                                'value'      => ''                      //The value with which to compare
                            )
                        )
                    )
                )
            ),
            lang('Update Gateway') => array(
                'type'        => 'hidden',
                'description' => lang('1 = Create, update or remove Gateway client information through the function UpdateGateway when client choose to use this gateway, client profile is updated, client is deleted or client status is changed. 0 = Do nothing.'),
                'value'       => '1'
            )
        );
        return $variables;
    }

    function credit($params)
    {
        $params['refund'] = true;
        return $this->singlePayment($params);
    }

    function singlepayment($params)
    {
        return $this->autopayment($params);
    }

    function autopayment($params)
    {
        $cPlugin = new Plugin($params['invoiceNumber'], "squarepayment", $this->user);
        $cPlugin->setAmount($params['invoiceTotal']);

        if (isset($params['refund']) && $params['refund']) {
            $isRefund = true;
            $cPlugin->setAction('refund');
        } else {
            $isRefund = false;
            $cPlugin->setAction('charge');
        }

        $totalAmount = sprintf("%01.2f", round($params['invoiceTotal'], 2));

        //This can look absurd, but if not cast first to string, casting to int can cause a loss of cents. No idea why, but I confirmaed the issue.
        $totalAmountCents = (int)(string)($totalAmount * 100);

        try {
            $user = new User($params['CustomerID']);
            $profile_id_values_array = $this->getBillingProfileID($user);

            $profile_id = $profile_id_values_array[0];
            $payment_method = $profile_id_values_array[1];

            $api_client = $this->getAPIclient();

            if ($isRefund) {
                $request_body = array(
                    'payment_id'      => $params['invoiceRefundTransactionId'],
                    'amount_money'    => array(
                        'amount'   => $totalAmountCents,
                        'currency' => $params['userCurrency']
                    ),
                    'idempotency_key' => uniqid()
                );

                $refunds_api = new \SquareConnect\Api\RefundsApi($api_client);

                try {
                    $result = $refunds_api->refundPayment($request_body);
                    $square_refund = $result->getRefund();
                    $square_refund_id = $square_refund->getId();
                    $square_refund_status = $square_refund->getStatus();    //Indicates whether the refund is `PENDING`, `COMPLETED`, `REJECTED`, or `FAILED`.
                    $square_refund_amount_money = $square_refund->getAmountMoney();
                    $square_refund_amount_money_amount = $square_refund_amount_money->getAmount();

                    if (in_array($square_refund_status, array('PENDING', 'COMPLETED'))) {
                        $chargeAmount = sprintf("%01.2f", round(($square_refund_amount_money_amount / 100), 2));
                        $cPlugin->PaymentAccepted($chargeAmount, "Square Payment refund of {$chargeAmount} was successfully processed.", $square_refund_id);
                        return array('AMOUNT' => $chargeAmount);
                    } else {
                        $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation."));
                        return $this->user->lang("There was an error performing this operation.");
                    }
                } catch (Exception $e) {
                    CE_Lib::log(1, $this->user->lang("Exception when calling RefundsApi->refundPayment: ")." ".$e->getMessage());

                    $detail = $e->getResponseBody()->errors[0]->detail;
                    $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.").' '.$detail);
                    return $this->user->lang("There was an error performing this operation.").' '.$detail;
                }
            } else {
                $customerProfile = $this->createFullCustomerProfile($params);

                if ($customerProfile["error"]) {
                    $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.").' '.$customerProfile['detail']);
                    return $this->user->lang("There was an error performing this operation.").' '.$customerProfile['detail'];
                } else {
                    $profile_id_values_array = $customerProfile["profile_id"];
                }

                $profile_id = $profile_id_values_array[0];
                $payment_method = $profile_id_values_array[1];

                $request_body = array(
                    'source_id'       => $payment_method,
                    'customer_id'     => $profile_id,
                    'amount_money'    => array(
                        'amount'   => $totalAmountCents,
                        'currency' => $params['userCurrency']
                    ),
                    'idempotency_key' => uniqid()
                );

                $payments_api = new \SquareConnect\Api\PaymentsApi($api_client);

                try {
                    $result = $payments_api->createPayment($request_body);
                    $square_payment = $result->getPayment();
                    $square_payment_id = $square_payment->getId();
                    $square_payment_status = $square_payment->getStatus();    //Indicates whether the payment is `APPROVED`, `COMPLETED`, `CANCELED`, or `FAILED`.
                    $square_payment_amount_money = $square_payment->getAmountMoney();
                    $square_payment_amount_money_amount = $square_payment_amount_money->getAmount();

                    if (in_array($square_payment_status, array('APPROVED', 'COMPLETED'))) {
                        $transactionId = $square_payment_id;
                        $amount = sprintf("%01.2f", round(($square_payment_amount_money_amount / 100), 2));
                        $cPlugin->setTransactionID($transactionId);
                        $cPlugin->PaymentAccepted($amount, "Square payment of {$amount} was accepted. (Transaction ID: {$transactionId})", $transactionId);

                        return '';
                    } else {
                        $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation."));
                        return $this->user->lang("There was an error performing this operation.");
                    }
                } catch (Exception $e) {
                    CE_Lib::log(1, $this->user->lang("Exception when calling CustomersApi->createPayment: ")." ".$e->getMessage());

                    $detail = $e->getResponseBody()->errors[0]->detail;
                    $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.").' '.$detail);
                    return $this->user->lang("There was an error performing this operation.").' '.$detail;
                }
            }
        } catch (Exception $e) {
            // Something else happened, completely unrelated to Square Payment
            CE_Lib::log(1, $this->user->lang("There was an error performing this operation.")." ".$e->getMessage());

            $cPlugin->PaymentRejected($this->user->lang("There was an error performing this operation.")." ".$e->getMessage());
            return $this->user->lang("There was an error performing this operation.")." ".$e->getMessage();
        }
    }

    // Create customer Square Payment profile
    function createFullCustomerProfile($params)
    {
        $user = new User($params['CustomerID']);
        $profile_id_values_array = $this->getBillingProfileID($user);

        if (isset($params['plugincustomfields']['squarepaymentCardNonce']) && $params['plugincustomfields']['squarepaymentCardNonce'] != "") {
            $profile_id = $profile_id_values_array[0];
            $payment_method = $profile_id_values_array[1];

            $api_client = $this->getAPIclient();
            $customers_api = new \SquareConnect\Api\CustomersApi($api_client);

            if ($profile_id == '') {
                $request_body = array(
                    'idempotency_key' => uniqid(),
                    'given_name'      => $params['userFirstName'],
                    'family_name'     => $params['userLastName'],
                    'company_name'    => $params['userOrganization'],
                    'email_address'   => $params['userEmail'],
                    'address'         => array(
                        'address_line_1'                  => $params['userAddress'],
                        'administrative_district_level_1' => $params["userState"],
                        'postal_code'                     => $params['userZipcode'],
                        'country'                         => $params['userCountry'],
                        'first_name'                      => $params['userFirstName'],
                        'last_name'                       => $params['userLastName'],
                        'organization'                    => $params['userOrganization']
                    ),
                    'phone_number'    => $params['userPhone'],
                    'reference_id'    => ''.$params['CustomerID'].''    //This value was giving an error about not been a string, so needed to apend strings to it.
                );

                try {
                    //Create Client
                    $result = $customers_api->createCustomer($request_body);
                    $square_customer = $result->getCustomer();
                    $profile_id = $square_customer->getId();
                } catch (Exception $e) {
                    CE_Lib::log(1, $this->user->lang("Exception when calling CustomersApi->createCustomer: ")." ".$e->getMessage());

                    return array(
                        'error'  => true,
                        'detail' => $e->getResponseBody()->errors[0]->detail
                    );
                }
            } elseif ($payment_method != '') {
                try {
                    //Delete existing Credit Card
                    $result = $customers_api->deleteCustomerCard($profile_id, $payment_method);
                } catch (Exception $e) {
                    CE_Lib::log(1, $this->user->lang("Exception when calling CustomersApi->deleteCustomerCard: ")." ".$e->getMessage());

                    return array(
                        'error'  => true,
                        'detail' => $e->getResponseBody()->errors[0]->detail
                    );
                }
            }

            $request_body = array(
                'card_nonce'      => $params['plugincustomfields']['squarepaymentCardNonce'],
                'billing_address' => array(
                    'address_line_1'                  => $params['userAddress'],
                    'administrative_district_level_1' => $params["userState"],
                    'postal_code'                     => $params['userZipcode'],
                    'country'                         => $params['userCountry'],
                    'first_name'                      => $params['userFirstName'],
                    'last_name'                       => $params['userLastName'],
                    'organization'                    => $params['userOrganization']
                )
            );

            try {
                //Add new Credit Card to the Client
                $result = $customers_api->createCustomerCard($profile_id, $request_body);
                $square_card = $result->getCard();
                $payment_method = $square_card->getId();
            } catch (Exception $e) {
                CE_Lib::log(1, $this->user->lang("Exception when calling CustomersApi->createCustomerCard: ")." ".$e->getMessage());

                return array(
                    'error'  => true,
                    'detail' => $e->getResponseBody()->errors[0]->detail
                );
            }

            $profile_id_values_array[0] = $profile_id;
            $profile_id_values_array[1] = $payment_method;

            $this->setBillingProfileID($user, $profile_id_values_array);
        }

        return array(
            'error'               => false,
            'profile_id'          => $profile_id_values_array
        );
    }

    function UpdateGateway($params)
    {
        switch ($params['Action']) {
            case 'update':  // When updating customer profile or changing to use this gateway
                $statusAliasGateway = StatusAliasGateway::getInstance($this->user);

                if (in_array($params['Status'], $statusAliasGateway->getUserStatusIdsFor(array(USER_STATUS_INACTIVE, USER_STATUS_CANCELLED, USER_STATUS_FRAUD)))) {
                    $this->CustomerRemove($params);
                }

                break;
            case 'delete':  // When deleting the customer, changing to use another gateway, or updating the Credit Card
                $this->CustomerRemove($params);
                break;
        }
    }

    function CustomerRemove($params)
    {
        $user = new User($params['User ID']);
        $profile_id_values_array = $this->getBillingProfileID($user);

        $profile_id = $profile_id_values_array[0];

        $api_client = $this->getAPIclient();
        $customers_api = new \SquareConnect\Api\CustomersApi($api_client);

        if ($profile_id != '') {
            try {
                //Delete Client
                $result = $customers_api->deleteCustomer($profile_id);
                return $this->deleteBillingProfileID($user);
            } catch (Exception $e) {
                CE_Lib::log(1, $this->user->lang("Exception when calling CustomersApi->deleteCustomer: ")." ".$e->getMessage());

                return array(
                    'error'  => true,
                    'detail' => $e->getResponseBody()->errors[0]->detail
                );
            }
        } else {
            return array(
                'error'  => true,
                'detail' => $this->user->lang("There was an error performing this operation.")." ".$this->user->lang("profile_id is empty.")
            );
        }
    }

    public function getForm($params)
    {
        if ($this->getVariable('Square Payment Application ID') == '') {
            return '';
        }

        $this->view->from = $params['from'];
        $this->view->userZipcode = $params['userZipcode'];

        if ($params['from'] == 'signup') {
            $fakeForm = '<a style="margin-left:0px;cursor:pointer;" class="app-btns primary customButton center-on-mobile" onclick="cart.submit_form('.$params['loggedIn'].');"  id="submitButton"></a>';
        } else {
            $fakeForm = '<button class="app-btns primary" id="submitButton">'.$this->user->lang('Pay Invoice').'</button>';
        }

        $profile_id_values_array = $this->getBillingProfileID($this->user);

        $profile_id = $profile_id_values_array[0];
        $payment_method = $profile_id_values_array[1];

        $params['profile_id'] = $profile_id;
        $params['payment_method'] = $payment_method;

        if ($profile_id != '' && $payment_method != '' && $params['from'] != 'paymentmethod') {
            return $fakeForm;
        } else {
            if ($this->getVariable('Use Sandbox') == 1) {
                $this->view->SqPaymentWebPaymentsSDKLibrary = 'https://sandbox.web.squarecdn.com/v1/square.js';
            } else {
                $this->view->SqPaymentWebPaymentsSDKLibrary = 'https://web.squarecdn.com/v1/square.js';
            }

            $this->view->applicationid = $this->getVariable('Square Payment Application ID');
            $this->view->locationid = $this->getVariable('Square Payment Location ID');

            return $this->view->render('form.phtml');
        }
    }

    function getBillingProfileID($user)
    {
        $profile_id = '';
        $payment_method = '';
        $Billing_Profile_ID = '';

        if ($user->getCustomFieldsValue('Billing-Profile-ID', $Billing_Profile_ID) && $Billing_Profile_ID != '') {
            $profile_id_array = unserialize($Billing_Profile_ID);

            if (is_array($profile_id_array) && isset($profile_id_array[basename(dirname(__FILE__))])) {
                $profile_id = $profile_id_array[basename(dirname(__FILE__))];
            }
        }

        $profile_id_values_array = explode('|', $profile_id);
        $profile_id = $profile_id_values_array[0];

        if (isset($profile_id_values_array[1])) {
            $payment_method = $profile_id_values_array[1];
        } else {
            if ($profile_id != '') {
                try {
                    $api_client = $this->getAPIclient();
                    $customers_api = new \SquareConnect\Api\CustomersApi($api_client);

                    try {
                        $result = $customers_api->retrieveCustomer($profile_id);
                        $square_customer = $result->getCustomer();
                        $square_cards = $square_customer->getCards();

                        foreach ($square_cards as $square_card) {
                            $square_card_id = $square_card->getId();
                            $payment_method = $square_card_id;
                        }
                    } catch (Exception $e) {
                        $profile_id = '';
                    }
                } catch (Exception $e) {
                    $profile_id = '';
                }
            }
        }

        $profile_id_values_array[0] = $profile_id;
        $profile_id_values_array[1] = $payment_method;

        $this->setBillingProfileID($user, $profile_id_values_array);

        return $profile_id_values_array;
    }

    function setBillingProfileID($user, $profile_id_values_array)
    {
        $profile_id = $profile_id_values_array[0];
        $payment_method = $profile_id_values_array[1];
        $Billing_Profile_ID = '';
        $profile_id_array = array();

        if ($user->getCustomFieldsValue('Billing-Profile-ID', $Billing_Profile_ID) && $Billing_Profile_ID != '') {
            $profile_id_array = unserialize($Billing_Profile_ID);
        }

        if (!is_array($profile_id_array)) {
            $profile_id_array = array();
        }

        if ($profile_id != '' && $payment_method != '') {
            $profile_id_array[basename(dirname(__FILE__))] = $profile_id.'|'.$payment_method;
        } else {
            unset($profile_id_array[basename(dirname(__FILE__))]);
        }

        $user->updateCustomTag('Billing-Profile-ID', serialize($profile_id_array));
        $user->save();
    }

    function deleteBillingProfileID($user)
    {
        $profile_id = '';
        $Billing_Profile_ID = '';
        $profile_id_array = array();

        if ($user->getCustomFieldsValue('Billing-Profile-ID', $Billing_Profile_ID) && $Billing_Profile_ID != '') {
            $profile_id_array = unserialize($Billing_Profile_ID);

            if (is_array($profile_id_array) && isset($profile_id_array[basename(dirname(__FILE__))])) {
                $profile_id = $profile_id_array[basename(dirname(__FILE__))];
            }
        }

        $profile_id_values_array = explode('|', $profile_id);
        $profile_id = $profile_id_values_array[0];

        if (is_array($profile_id_array)) {
            unset($profile_id_array[basename(dirname(__FILE__))]);
        } else {
            $profile_id_array = array();
        }

        $user->updateCustomTag('Billing-Profile-ID', serialize($profile_id_array));
        $user->save();

        $eventLog = Client_EventLog::newInstance(false, $user->getId(), $user->getId());
        $eventLog->setSubject($this->user->getId());
        $eventLog->setAction(CLIENT_EVENTLOG_DELETEDBILLINGPROFILEID);
        $params = array(
            'paymenttype' => $this->settings->get("plugin_".basename(dirname(__FILE__))."_Plugin Name"),
            'profile_id'  => $profile_id
        );
        $eventLog->setParams(serialize($params));
        $eventLog->save();

        return array(
            'error'      => false,
            'profile_id' => $profile_id
        );
    }

    function getAPIclient()
    {
        $api_config = new \SquareConnect\Configuration();

        if ($this->getVariable('Use Sandbox') == 1) {
            $api_config->setHost("https://connect.squareupsandbox.com");
            $api_config->setSSLVerification(false);
        } else {
            $api_config->setHost("https://connect.squareup.com");
        }

        $access_token = $this->getVariable('Square Payment Access Token');
        $api_config->setAccessToken($access_token);

        $api_client = new \SquareConnect\ApiClient($api_config);

        return $api_client;
    }
}
