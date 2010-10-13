<?php defined('SYSPATH') or die('No direct script access.');

class Authorizenet_Void extends Authorizenet
{
	protected $type = parent::VOID; // VOID
	protected $method = parent::CREDITCARD;
	
	protected $fields = array
	(
		'trans_id'
	);

	public static function factory(array $config = array())
	{
		return new Authorizenet_Void($config);
	}
}