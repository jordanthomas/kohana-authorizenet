<?php defined('SYSPATH') or die('No direct script access.');

class Authorizenet_Check extends Authorizenet
{
	protected $echeck_type  = parent::WEBCHECK;
	protected $method = parent::CHECK;

	public $amount = NULL;
	public $bank_aba_code = NULL;
	public $bank_acct_num = NULL;
	public $bank_acct_type = NULL;
	public $bank_name = NULL;
	public $bank_acct_name = NULL;
	public $bank_check_number = NULL;
	public $recurring_billing = NULL;

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
		'x_bank_aba_code',
		'x_bank_acct_num',
		'x_bank_acct_type',
		'x_bank_name',
		'x_bank_acct_name',
		'x_echeck_type',
		'x_recurring_billing',
	);
}