<?php defined('SYSPATH') or die('No direct script access.');

abstract class Authorizenet {

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

	// values sent with each transaction
	private $_common = array
	(
		'version',
		'delim_char',
		'delim_data',
		'relay_response'
	);

	// optional fields that could go with any request
	protected $_optional = array
	(
		'address',
		'authentication_indicator',
		'card_code', // optional CVV security
		'cardholder_authentication_value', // AUTH & CAPTURE
		'city',
		'company',
		'country',
		'cust_id',
		'customer_ip',
		'description',
		'duplicate_window',
		'duty',
		'email',
		'email_customer',
		'encap_char',
		'fax',
		'first_name',
		'footer_email_receipt',
		'freight',
		'header_email_receipt',
		'invoice_num',
		'last_name',
		'line_item',
		'merchant_email',
		'method',
		'phone',
		'po_num',
		'recurring_billing',
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
		'tax_exempt',
		'test_request',
		'zip'
	);

	protected $_version            = '3.1';
	protected $_delim_char         = '|';
	protected $_delim_data         = 'TRUE';
	protected $_relay_response     = 'FALSE';
	protected $_login              = NULL;
	protected $_tran_key           = NULL;
	protected $_url                = NULL;
	protected $_values             = NULL;
	protected $_fields             = array();

	public $response = NULL;

	/**
	 * Init configs, add default fields to transaction.
	 *
	 * @param array $config 
	 * @author Jordan Thomas
	 */
	public function __construct(array $config = array())
	{
		// Example config:
		// $config = array('login' => [login], 'tran_key' => [tran_key], 'test_mode' => TRUE/FALSE 'duplicate_window' => 30);
		$config = array_merge(Kohana::config('authorizenet')->default, $config);

		// Make sure we have enough to get started.
		if (!$config['login'] || !$config['tran_key'])
			throw new Kohana_Exception('Payment gateway credentials not found');
		
		$this->_login = $config['login'];
		$this->_tran_key = $config['tran_key'];
		
		// Determine POST target.
		$this->_url = $config['test_mode'] ? self::TEST : self::LIVE;

		// User provided specific dupe window.
		if ($config['duplicate_window'])
			$this->duplicate_window = $config['duplicate_window'];
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
		if (in_array($key, $this->_optional) || in_array($key, $this->_fields))
		{
			$this->$key = $value;
		}
		else
		{
			throw new Kohana_Exception('Property :key does not exist in :class.', array(':key' => $key, ':class' => get_class($this)));
		}
	}

	public static function factory($type, array $config = array())
	{
		$class = 'Authorizenet_'.ucfirst($type);
		return new $class($config);
	}

	/**
	 * Used for bulk setting of transaction values.
	 *
	 * @param array $fields 
	 * @return object
	 * @author Jordan Thomas
	 */
	public function values(array $fields)
	{
		foreach ($fields as $key => $val)
		{
			$key = strtolower($key);
			if (in_array($key, $this->_optional) || in_array($key, $this->_fields))
			{
				$this->$key = $val;
			}
		}

		return $this;
	}

/**
 * Compiles set values into format suitable for API.
 *
 * @return string
 * @author Jordan Thomas
 */
	protected function compile_values()
	{
		// Non-transaction values.
		$values = array
		(
			'x_login'          => $this->_login,
			'x_tran_key'       => $this->_tran_key,
			'x_version'        => $this->_version,
			'x_delim_char'     => $this->_delim_char,
			'x_delim_data'     => $this->_delim_data,
			'x_relay_response' => $this->_relay_response
		);
		
		// Prepend x_ to each of the values that have been set.
		$all = array_merge($this->_optional, $this->_fields);
		foreach ($all as $field)
		{
			if (isset($this->$field))
				$values["x_$field"] = $this->$field;
		}

		// Collects all the values needed to put together a request.
		$post = '';
		foreach ($values as $key => $value)
		{
			// Make sure none of the values contain the delim character.
			if ($key != 'x_delim_char')
				$value = str_replace($this->_delim_char, '', $value);

			$post .= "$key=" . urlencode($value) . "&";
		}

		$post = rtrim($post, "& ");

		return $post;
	}

	/**
	 * Sends currently set values to API and stores response.
	 *
	 * @return object
	 * @author Jordan Thomas
	 */
	public function send()
	{
		$post = $this->compile_values();

		if (!function_exists('curl_exec'))
			throw new Kohana_Exception('cURL is unavailable');

		$request = curl_init($this->_url);
		curl_setopt($request, CURLOPT_HEADER, 0);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($request, CURLOPT_POSTFIELDS, $post);
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);

		$response = curl_exec($request);
		curl_close($request);

		if (!$response)
			throw new Kohana_Exception('Error connecting to payment gateway');

		$this->response = explode($this->_delim_char, $response);

		return $this;
	}

	/**
	 * Returns whether the transaction has been approved
	 *
	 * @return bool
	 * @author Jordan Thomas
	 */
	public function approved()
	{
		return $this->response[0] === self::APPROVED;
	}

	/**
	 * Returns whether the transaction has been declined
	 *
	 * @return bool
	 * @author Jordan Thomas
	 */
	public function declined()
	{
		return !$this->approved();
	}
}