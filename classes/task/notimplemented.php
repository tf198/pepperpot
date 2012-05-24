<?php

class Task_NotImplemented extends RuntimeException {
	function __construct($message=null, $code=null, $previous=null) {
		if(!$message) $message = "Method not implemented for your system";
		parent::__construct($message, $code, $previous);
	}
}