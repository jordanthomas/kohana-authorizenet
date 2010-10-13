<?php defined('SYSPATH') or die('No direct script access.');

abstract class Authorizenet
{
	// values sent with each transaction
	private $common = array
	(
		'version'        => '3.1',
		'delim_char'     => '|',
		'delim_data'     => 'TRUE',
		'relay_response' => 'FALSE'
	);

	// optional fields that could go with any request
	protected $optional = array
	(
		'address',
		// 'amount',
		// 'auth_code',
		'authentication_indicator',
		'card_code', // optional CVV security
		// 'card_num',
		'cardholder_authentication_value', // AUTH & CAPTURE
		'city',
		'company',
		'country',
		'cust_id',
		'customer_ip',
		// 'delim_char',
		// 'delim_data',
		'description',
		'duplicate_window',
		'duty',
		'email',
		'email_customer',
		'encap_char',
		// 'exp_date',
		'fax',
		'first_name',
		'footer_email_receipt',
		'freight',
		'header_email_receipt',
		'invoice_num',
		'last_name',
		'line_item',
		// 'login',
		'merchant_email',
		'method',
		'phone',
		'po_num',
		'recurring_billing',
		// 'relay_response',
		'ship_to_address',
		'ship_to_company',
		'ship_to_country',
		'ship_to_city',
		'ship_to_first_name',
		'ship_to_last_name',
		'ship_to_state',
		'ship_to_zip',
		'state',
		'tax',
		'taexempt',
		'test_request',
		// 'tran_key',
		// 'trans_id',
		// 'type',
		// 'version',
		'zip'
	);

	const TEST = 'https://test.authorize.net/gateway/transact.dll';
	const LIVE = 'https://secure.authorize.net/gateway/transact.dll';
	
	// Transaction types
	const CHARGE         = 'AUTH_CAPTURE';
	const AUTHORIZE      = 'AUTH_ONLY';
	const CREDIT         = 'CREDIT';
	const PREAUTH_CHARGE = 'PRIOR_AUTH_CAPTURE';
	const VOID           = 'VOID';
	const WEBCHECK       = 'WEB';

	// Transaction methods
	const CREDITCARD = 'CC';
	const CHECK      = 'ECHECK';

	// Value returned by API for approved transactions
	const APPROVED = '1';

	private $_login              = NULL;
	private $_tran_key           = NULL;
	private $_url                = NULL;
	private $_post               = NULL;
	private $_transaction_values = NULL;

	protected $fields = array();

	public $response = NULL;

	/**
	 * undocumented function
	 *
	 * @param array $config 
	 * @author Jordan Thomas
	 */
	public function __construct(array $config = array())
	{
		// Example config:
		// $config = array('login' => [login], 'tran_key' => [tran_key], 'test_mode' => TRUE/FALSE);
		$config = array_merge(Kohana::config('authorize')->default, $config);

		// Make sure we have enough to get started.
		if (!$config['login'] || !$config['tran_key'])
			throw new Kohana_Exception('Payment gateway credentials not found');
		
		$this->_login = $config['login'];
		$this->_tran_key = $config['tran_key'];
		
		// Determine POST target.
		$this->_url = $config['test_mode'] ? self::TEST : self::LIVE;

		// setup common fields sent with every transaction
		foreach ($this->common as $key => $value)
		{
			$this->_transaction_values[$key] = $value;
		}

		$this->_transaction_values['login'] = $this->_login;
		$this->_transaction_values['tran_key'] = $this->_tran_key;
		$this->_transaction_values['type'] = $this->type;
	}

	/**
	 * Magic method used to set API fields as object properties
	 *
	 * @param string $key 
	 * @param mixed $value 
	 * @return void
	 * @author Jordan Thomas
	 */
	public function __set($key, $value)
	{
		if (in_array($key, $this->optional) || in_array($key, $this->fields))
		{
			$this->$key = $value;
		}
		else
		{
			throw new Kohana_Exception('Property :key does not exist in :class.', array(':key' => $key, ':class' => get_class($this)));
		}
	}

	/**
	 * Used for bulk setting of transaction values.
	 *
	 * @param array $fields 
	 * @return object
	 * @author Jordan Thomas
	 */
	public function transaction(array $fields)
	{
		foreach ($fields as $key => $value)
		{
			$key = strtolower($key);
			if (in_array($key, $this->optional) || in_array($key, $this->fields))
			{
				$this->_transaction_values[$key] = $value;
				$this->$key = $value;
			}
		}

		return $this;
	}

	/**
	 * Collects values from object and compiles them
   * into a string that will be POST'd to Authorize.net
	 *
	 * @author Jordan Thomas
	 */
	private function compile_values()
	{
		// add x_ to each of the values that have been set.
		$values = array();
		foreach ($this->_transaction_values as $key => $value)
		{
			$values["x_$key"] = $value;
		}

		// Collects all the values needed to put together a request.
		$this->_post = '';
		foreach ($values as $key => $value)
		{
			// Make sure none of the values contain the delim character.
			if ($key != 'x_delim_char')
				$value = str_replace($this->common['delim_char'], '', $value);

			$this->_post .= "$key=" . urlencode($value) . "&";
		}

		$this->_post = rtrim($this->_post, "& ");
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Jordan Thomas
	 */
	public function send()
	{
		$this->compile_values();

		if (!function_exists('curl_exec'))
			throw new Kohana_Exception('cURL is unavailable');

		$request = curl_init($this->_url);
		curl_setopt($request, CURLOPT_HEADER, 0);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($request, CURLOPT_POSTFIELDS, $this->_post);
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);

		$response = curl_exec($request);
		curl_close($request);

		if (!$response)
			throw new Kohana_Exception('Error connecting to payment gateway');

		$response_array = explode($this->common['delim_char'], $response);
		$this->response = $response_array;

		$approved = $this->response[0] === self::APPROVED;
		
		return $approved;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Jordan Thomas
	 */
	public function approved()
	{
		return $this->response[0] === self::APPROVED;
	}
}