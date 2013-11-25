<?php

require_once(dirname(__FILE__) . '/3rdparty/nganluong.php');
require_once(dirname(__FILE__) . '/3rdparty/nusoap.php');

class bdPaygateNganLuong_Processor extends bdPaygate_Processor_Abstract
{
	const CURRENCY_VND = 'vnd';

	public static $transactionId = '';
	public static $paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_OTHER;
	public static $transactionDetails = array();
	public static $itemId = '';

	public function isAvailable()
	{
		$options = XenForo_Application::getOptions();

		$id = $options->get('bdPaygateNganLuong_id');
		$pass = $options->get('bdPaygateNganLuong_pass');

		if (empty($id) OR empty($pass))
		{
			return false;
		}

		return parent::isAvailable();
	}

	public function getSupportedCurrencies()
	{
		return array(self::CURRENCY_VND);
	}

	public function isRecurringSupported()
	{
		return false;
	}

	public function validateCallback(Zend_Controller_Request_Http $request, &$transactionId, &$paymentStatus, &$transactionDetails, &$itemId)
	{
		$server = new nusoap_server();
		$server->configureWSDL('WS_WITH_SMS', 'NS');
		$server->wsdl->schemaTargetNamespace = 'NS';

		$server->register('UpdateOrder',
				array(
						'transaction_info' => 'xsd:string',
						'order_code' => 'xsd:string',
						'payment_id' => 'xsd:int',
						'payment_type' => 'xsd:int',
						'secure_code' => 'xsd:string'
				),
				array(
						'result'=>'xsd:int'
				),
				'NS'
		);
		$server->register('RefundOrder',
				array(
						'transaction_info' => 'xsd:string',
						'order_code' => 'xsd:string',
						'payment_id' => 'xsd:int',
						'refund_payment_id' => 'xsd:int',
						'payment_type' => 'xsd:int',
						'secure_code' => 'xsd:string'
				),
				array(
						'result'=>'xsd:int'
				),
				'NS'
		);

		$HTTP_RAW_POST_DATA = ((isset($HTTP_RAW_POST_DATA)) ? $HTTP_RAW_POST_DATA : file_get_contents("php://input"));
		$server->service($HTTP_RAW_POST_DATA);

		if ($server->methodreturn === -1)
		{
			$this->_setError('Request not validated');
			return false;
		}

		$transactionId = self::$transactionId;
		$paymentStatus = self::$paymentStatus;
		$transactionDetails = self::$transactionDetails;
		$itemId = self::$itemId;
		$processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');

		return true;
	}

	public function generateFormData($amount, $currency, $itemName, $itemId, $recurringInterval = false, $recurringUnit = false, array $extraData = array())
	{
		$this->_assertAmount($amount);
		$this->_assertCurrency($currency);
		$this->_assertItem($itemName, $itemId);
		$this->_assertRecurring($recurringInterval, $recurringUnit);

		$nlc = new NL_Checkout();

		$nlc->nganluong_url = $this->_sandboxMode()
		? 'http://sandbox.nganluong.vn/checkout.php'
				: 'https://www.nganluong.vn/checkout.php';
		$options = XenForo_Application::getOptions();
		$nlc->merchant_site_code = $options->get('bdPaygateNganLuong_id');
		$nlc->secure_pass = $options->get('bdPaygateNganLuong_pass');
		$email = $options->get('bdPaygateNganLuong_email');

		$callToAction = new XenForo_Phrase('bdpaygatenganluong_call_to_action');
		$returnUrl = $this->_generateReturnUrl($extraData);

		$redirectUrl = $nlc->buildCheckoutUrl($returnUrl, $email, $itemName, $itemId, $amount);

		$form = <<<EOF
<div>
	<a href="{$redirectUrl}" class="button">{$callToAction}</a>
</div>
EOF;

		return $form;
	}
}

function UpdateOrder($itemName, $itemId, $transactionId, $paymentType, $secureCode)
{
	$options = XenForo_Application::getOptions();
	$securePass = $options->get('bdPaygateNganLuong_pass');

	$calculatedSecureCode = md5($itemName . ' ' . $itemId . ' ' . $transactionId . ' ' . $paymentType . ' ' . $securePass);

	if($calculatedSecureCode != $secureCode)
	{
		return -1;
	}

	switch ($paymentType)
	{
		case 1: // instant payment
		case 2: // on hold
			bdPaygateNganLuong_Processor::$transactionId = 'nganluong_' . $transactionId;
			bdPaygateNganLuong_Processor::$paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_ACCEPTED;
			bdPaygateNganLuong_Processor::$transactionDetails = array(
			'item_name' => $itemName,
			'item_id' => $itemId,
			'transaction_id' => $transactionId,
			'payment_type' => $paymentType,
			'secure_code' => $secureCode
			);
			bdPaygateNganLuong_Processor::$itemId = $itemId;
			return 0;
		default:
			// unknown payment type, assume this is an invalid request
			return -1;
	}
}

function RefundOrder($itemName, $itemId, $transactionId, $refundTransactionId, $paymentType, $secureCode)
{
	$options = XenForo_Application::getOptions();
	$securePass = $options->get('bdPaygateNganLuong_pass');

	$calculatedSecureCode = md5($itemName . ' ' . $itemId . ' ' . $transactionId . ' ' . $refundTransactionId . ' ' . $securePass);

	if($calculatedSecureCode != $secureCode)
	{
		return -1;
	}

	bdPaygateNganLuong_Processor::$transactionId = 'nganluong_' . $refundTransactionId;
	bdPaygateNganLuong_Processor::$paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_REJECTED;
	bdPaygateNganLuong_Processor::$transactionDetails = array(
	'item_name' => $itemName,
	'item_id' => $itemId,
	'transaction_id' => $transactionId,
	'refund_transaction_id' => $refundTransactionId,
	'payment_type' => $paymentType,
	'secure_code' => $secureCode
	);
	bdPaygateNganLuong_Processor::$itemId = $itemId;
	return 0;
}
