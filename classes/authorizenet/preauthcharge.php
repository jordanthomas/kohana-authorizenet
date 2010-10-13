<?php defined('SYSPATH') or die('No direct script access.');

class Authorizenet_Preauthcharge extends Authorizenet
{
	protected $type = parent::PREAUTH_CHARGE; // PRIOR_AUTH_CAPTURE
	protected $method = parent::CREDITCARD;

	protected $fields = array
	(
		'amount',
		'trans_id'
	);

	public static function factory(array $config = array())
	{
		return new Authorizenet_Preauthcharge($config);
	}
}