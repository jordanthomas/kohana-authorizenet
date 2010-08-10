<?php defined('SYSPATH') or die('No direct script access.');

class Authorizenet_Authorize extends Authorizenet
{
	protected $type = parent::AUTHORIZE; // AUTH_ONLY
	protected $method = parent::CREDITCARD;

	public $amount = NULL;
	public $authentication_indicator = NULL;
	public $card_num = NULL;
	public $cardholder_authentication_value = NULL;
	public $exp_date = NULL;

	private $reqd = array
	(
		'x_version',
		'x_relay_response',
		'x_delim_data',
		'x_delim_char',
		'x_method',
		'x_login',
		'x_tran_key',
		'x_delim_char',
		'x_type',
		'x_amount',
		'x_card_num',
		'x_exp_date',
	);
}