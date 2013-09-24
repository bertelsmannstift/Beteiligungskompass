<?php defined('SYSPATH') or die('No direct script access.');


$keys = array(
	'alpha',
	'alpha_dash',
	'alpha_numeric',
	'color',
	'credit_card',
	'date',
	'decimal',
	'digit',
	'email',
	'email_domain',
	'equals',
	'exact_length',
	'in_array',
	'ip',
	'matches',
	'min_length',
	'max_length',
	'not_empty',
	'numeric',
	'phone',
	'range',
	'regex',
	'url',
	'validate_login',
	'validate_check_dbloptin',
	'validate_check_email',
	'validate_email_unique',
	'criterion_unique',
	'validate_depends',
	'validate_date_before',
    'comparePassword',
    'validate_depends_empty',
    'validate_datetime'
);

$msgs = array();

foreach($keys as $key) {
	$msgs[$key] = Helper_Message::get('validationmsg.' . $key);
}

return $msgs;
