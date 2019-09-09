<?php
/**
 * Created by PhpStorm.
 * User: Radim
 * Date: 02.08.2018
 * Time: 16:11
 */

namespace App\Utils;

class RandomStringGenerator
{
	public static function generate($length = 4) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
}