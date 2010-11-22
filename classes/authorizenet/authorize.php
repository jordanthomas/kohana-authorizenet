<?php defined('SYSPATH') or die('No direct script access.');

class Authorizenet_Authorize extends Authorizenet
{
	protected $_type   = parent::AUTHORIZE; // AUTH_ONLY
	protected $_method = parent::CREDITCARD;

	// fields specific to this transaction type
	protected $_fields = array
	(
		'amount',
		'authentication_indicator',
		'card_num',
		'cardholder_authentication_value',
		'exp_date'
	);
}