<?php defined('SYSPATH') or die('No direct script access.');

class Authorizenet_Credit extends Authorizenet
{
	protected $type = parent::CREDIT; // CREDIT
	protected $method = parent::CREDITCARD;

	protected $fields = array
	(
		'amount',
		'card_num',
		'exp_date',
		'trans_id'
	);

	public static function factory(array $config = array())
	{
		return new Authorizenet_Credit($config);
	}
}