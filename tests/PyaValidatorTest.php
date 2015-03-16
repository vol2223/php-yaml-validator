<?php

namespace Vol2223\PyaValidator;

use \Mockery as M;

class PyaValidatorTest extends \PHPUnit_Framework_TestCase
{
	public function test_validate_Normal()
	{
		$config = [
			'string' =>
			[
				'type' => 'string',
				'minLength' => 3,
				'maxLength' => 5,
			],
			'integer' =>
			[
				'type' => 'integer',
			],
			'object' =>
			[
				'type' => 'object',
				'properties' => [
					'huga' => [
						'type' => 'string'
					]
				]
			],
			'array' =>
			[
				'type' => 'array',
				'items' => [
					0 => [
						'type' => 'integer'
					]
				]
			],
			'enum' =>
			[
				'type' => 'enum',
				'enum' => [
					0 => 'HOGE',
					1 => 'PIYO'
				]
			]
		];
		$object = new \stdClass;
		$object->huga = 'foga';
		$targets = [
			'string' => 'abc',
			'integer' => 123,
			'object' => $object,
			'array' => [
				1,2,3
			],
			'enum' => 'HOGE'
		];
		$pyaValidator = new PyaValidator($config, $targets);
		$pyaValidator->validate();
	}
}
