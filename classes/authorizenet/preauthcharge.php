<?php defined('SYSPATH') or die('No direct script access.');

class Authorizenet_Preauthcharge extends Authorizenet
{
	protected $_type   = parent::PREAUTH_CHARGE; // PRIOR_AUTH_CAPTURE
	protected $_method = parent::CREDITCARD;

	// fields specific to this transaction type
	protected $_fields = array
	(
		'amount',
		'trans_id'
	);
}