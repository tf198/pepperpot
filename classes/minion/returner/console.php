<?php
class Minion_Returner_Console {
	function write($minion, $result) {
		$minion->log("RESULT> " . var_export($result, true));
	}
}