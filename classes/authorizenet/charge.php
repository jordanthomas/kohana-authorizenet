<?php defined('SYSPATH') or die('No direct script access.');

class Authorizenet_Charge extends Authorizenet
{
	protected $type = parent::CHARGE; // AUTH_CAPTURE
	protected $method = parent::CREDITCARD;

	public $fields = array
	(
		'amount',
		'authentication_indicator',
		'card_num',
		'cardholder_authentication_value',
		'exp_date'
	);

	public static function factory(array $config = array())
	{
		return new Authorizenet_Charge($config);
	}
}