<?php


namespace App\Exception;

use Throwable;

class EMailServiceException extends \RuntimeException
{
	public function __construct($message = "")
	{
		parent::__construct($message);
	}
}