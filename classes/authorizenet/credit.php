<?php defined('SYSPATH') or die('No direct script access.');

class Authorizenet_Credit extends Authorizenet
{
	protected $type = parent::CREDIT; // CREDIT
	protected $method = parent::CREDITCARD;

	public $amount = NULL;
	public $card_num = NULL;
	public $exp_date = NULL;
	public $trans_id = NULL;

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
		'x_trans_id',
		'x_card_num',
	);
}