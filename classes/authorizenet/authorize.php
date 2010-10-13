<?php defined('SYSPATH') or die('No direct script access.');

class Authorizenet_Authorize extends Authorizenet
{
	protected $type = parent::AUTHORIZE; // AUTH_ONLY
	protected $method = parent::CREDITCARD;

	protected $fields = array
	(
		'amount',
		'authentication_indicator',
		'card_num',
		'cardholder_authentication_value',
		'exp_date'
	);

	public static function factory(array $config = array())
	{
		return new Authorizenet_Authorize($config);
	}
}