<?php defined('SYSPATH') or die('No direct script access.');

class Authorizenet_Void extends Authorizenet
{
	protected $_type   = parent::VOID; // VOID
	protected $_method = parent::CREDITCARD;

	// fields specific to this transaction type
	protected $_fields = array
	(
		'trans_id'
	);
}