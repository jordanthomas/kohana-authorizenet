<?php defined('SYSPATH') or die('No direct script access.');

class Authorizenet_Charge extends Authorizenet
{
	protected $type = parent::CHARGE; // AUTH_CAPTURE
	protected $method = parent::CREDITCARD;
	
	public $amount = NULL;
	public $authentication_indicator = NULL;
	public $card_num = NULL;
	public $cardholder_authentication_value = NULL;
	public $exp_date = NULL;
}