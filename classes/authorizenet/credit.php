<?php defined('SYSPATH') or die('No direct script access.');

class Authorizenet_Credit extends Authorizenet
{
	protected $_type   = parent::CREDIT; // CREDIT
	protected $_method = parent::CREDITCARD;

	// fields specific to this transaction type
	protected $_fields = array
	(
		'amount',
		'card_num',
		'exp_date',
		'trans_id'
	);
}