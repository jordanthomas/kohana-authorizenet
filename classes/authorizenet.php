<?php defined('SYSPATH') or die('No direct script access.');

abstract class Authorizenet
{
	public $address = NULL;
	//public $amount = NULL;
	//public $auth_code = NULL; // Not implemented
	//public $authentication_indicator = NULL;
	public $card_code = NULL; // Depends upon merch settings
	//public $card_num = NULL;
	//public $cardholder_authentication_value = NULL;
	public $city = NULL;
	public $company = NULL;
	public $country = NULL;
	public $cust_id = NULL;
	public $customer_ip = NULL; // Required for Fraud Detection Suite
	public $delim_char = '|';
	public $delim_data = 'TRUE';
	public $description = NULL;
	public $duplicate_window = NULL;
	public $duty = NULL;
	protected $echeck_type = NULL;
	public $email = NULL;
	public $email_customer = NULL;
	public $encap_char = NULL;
	//public $exp_date = NULL;
	public $fax = NULL;
	public $first_name = NULL;
	public $footer_email_receipt = NULL;
	public $freight = NULL;
	public $header_email_receipt = NULL;
	public $invoice_num = NULL;
	public $last_name = NULL;
	public $line_item = NULL;
	public $login = NULL;
	public $merchant_email = NULL;
	protected $method = NULL;
	public $phone = NULL;
	public $po_num = NULL;
	public $recurring_billing = NULL;
	private $relay_response = 'FALSE';
	public $ship_to_address = NULL;
	public $ship_to_company = NULL;
	public $ship_to_country = NULL;
	public $ship_to_city = NULL;
	public $ship_to_first_name = NULL;
	public $ship_to_last_name = NULL;
	public $ship_to_state = NULL;
	public $ship_to_zip = NULL;
	public $state = NULL;
	public $tax = NULL;
	public $tax_exempt = NULL;
	public $test_request = NULL;
	public $tran_key = NULL;
	//public $trans_id = NULL;
	protected $type = NULL;
	private $version = '3.1';
	public $zip = NULL;

	protected $api_fields = array
	(
		'address', 'amount', 'authentication_indicator', 'card_code', 'card_num', 'cardholder_authentication_value', 'city', 'company', 'country', 'cust_id', 'customer_ip', 'delim_char', 'delim_data', 'description', 'duplicate_window', 'duty', 'email', 'email_customer', 'encap_char', 'exp_date', 'fax', 'first_name', 'footer_email_receipt', 'freight', 'header_email_receipt', 'invoice_num', 'last_name', 'line_item', 'login', 'merchant_email', 'method', 'phone', 'po_num', 'recurring_billing', 'relay_response', 'ship_to_address', 'ship_to_company', 'ship_to_country', 'ship_to_city', 'ship_to_first_name', 'ship_to_last_name', 'ship_to_state', 'ship_to_zip', 'state', 'tax', 'tax_exempt', 'test_request', 'tran_key', 'trans_id', 'type', 'version', 'zip', 'bank_aba_code', 'bank_acct_num', 'bank_acct_type', 'bank_name', 'bank_acct_name', 'echeck_type', 'bank_check_number', 'recurring_billing', 
	);
	
	const TEST = 'https://test.authorize.net/gateway/transact.dll';
	const LIVE = 'https://secure.authorize.net/gateway/transact.dll';
	
	// Transaction types for credit cards
	const CHARGE = 'AUTH_CAPTURE';
	const AUTHORIZE = 'AUTH_ONLY';
	const CREDIT = 'CREDIT';
	const PREAUTH_CHARGE = 'PRIOR_AUTH_CAPTURE';
	const VOID = 'VOID';
	
	// Transaction type for checks
	const WEBCHECK = 'WEB';

	// Transaction methods
	const CREDITCARD = 'CC';
	const CHECK = 'ECHECK';
	
	protected $url = NULL;
	protected $post = '';
	
	public $transaction_values = NULL;
	public $response = NULL;
	
	public function __construct(array $config = NULL)
	{
		// Example config:
		// $config = array('login' => [login], 'tran_key' => [tran_key], 'test_mode' => TRUE/FALSE);

		// Look for config file if nothing is provided.
		if (empty($config))
		{
			$config = Kohana::config('authorize');
		}

		// Make sure we have enough to get started.
		if (!$config['login'] || !$config['tran_key'])
			throw new Kohana_Exception('Payment gateway credentials not found');
		
		$this->login = $config['login'];
		$this->tran_key = $config['tran_key'];
		
		// Determine POST target.
		if (!$config['test_mode'])
		{
			$this->url = Authorizenet::LIVE;
		}
		else
		{
			$this->url = Authorizenet::TEST;
		}
	}

	// Enables bulk setting of transaction values.
	public function transaction(array $fields = NULL)
	{
		foreach ($fields as $field => $value)
		{
			$field = strtolower($field);
			if (in_array($field, $this->api_fields) && property_exists($this, $field))
			{
				$this->$field = $value;
				$this->transaction_values[$field] = $value;
			}
		}

		return $this->transaction_values;
	}

	// Collects values from object and compiles them
	// into a string that will be POST'd to Authorize.net
	protected function compile_values()
	{
		foreach ($this->api_fields as $field)
		{
			if (property_exists($this, $field) && isset($this->$field))
			{
				$api_name = "x_$field";
				$values[$api_name] = $this->$field;
			}
		}

		// Collects all the values needed to put together a request.
		$this->post = '';
		foreach ($values as $key => $value)
		{
			// Make sure none of the values contain the delim character.
			if ($key != 'x_delim_char')
				$value = str_replace($this->delim_char, '', $value);

			$this->post .= "$key=" . urlencode($value) . "&";
		}

		$this->post = rtrim($this->post, "& ");

		return $this->post;
	}

	public function send()
	{
		$this->compile_values();

		if (!function_exists('curl_exec'))
			throw new Kohana_Exception('cURL is unavailable');

		$request = curl_init($this->url);
		curl_setopt($request, CURLOPT_HEADER, 0);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($request, CURLOPT_POSTFIELDS, $this->post);
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);

		$response = curl_exec($request);
		curl_close ($request);

		if (!$response)
			throw new Kohana_Exception('Error connecting to payment gateway');

		$response_array = explode($this->delim_char, $response);
		$this->response = $response_array;

		$approved = $this->response[0] === '1';
		
		return $approved;
	}
}