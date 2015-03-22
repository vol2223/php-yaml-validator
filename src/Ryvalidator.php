<?php

namespace Vol2223\Ryvalidator;

use Vol2223\Ryvalidator\Context\ValidationPackContext;
use Vol2223\Ryvalidator\Exception\ValidationException;
use Vol2223\Ryvalidator\Validator\EnumValidator;
use Vol2223\Ryvalidator\Validator\IntegerValidator;
use Vol2223\Ryvalidator\Validator\StringValidator;

class Ryvalidator
{
	/**
	 * @var [] バリデーション設定の配列
	 */
	private $config;

	/**
	 * @var [] バリデーションを行う対象の配列
	 */
	private $targets;

	/**
	 * @var \Vol2223\Ryvalidator\Context\ValidationPackContext
	 */
	private $validationPackContext;

	/**
	 * バリデータの情報を配列で渡す
	 *
	 * @param [] $config バリデーションの定義情報
	 * @param [] $targets バリデーションを行う対象の配列
	 * @param \Vol2223\Ryvalidator\Context\ValidationPackContext|null $validationPackContext
	 */
	public function __construct(Array $config, Array $targets, ValidationPackContext $validationPackContext = null)
	{
		$this->config = $config;
		$this->targets = $targets;
		$this->validationPackContext = is_null($validationPackContext) ? new ValidationPackContext() : $validationPackContext;
	}

	/**
	 * バリデーションを実行
	 *
	 * @param [] $config 特に指定がなければコンストラクタで定義したものを使う
	 * @param [] $target 特に指定がなければコンストラクタで定義したものを使う
	 * @throws \Vol2223\Ryvalidator\Exception\ValidationException;
	 */
	public function validate($config = [], $targets = [])
	{
		$config = !empty($config) ? $config : $this->config;
		$targets = !empty($targets) ? $targets : $this->targets;
		foreach ($config as $key => $requirement) {
			if (0 === $key) {
				// keyが0の時は配列を想定しているため例外的な処理
				foreach ($targets as $target) {
					$this->_validate($requirement, $target, 'Array');
				}
			} else {
				if (
					isset($requirement['required'])
					and false === $requirement['required']
					and !isset($targets[$key])
				) {
					// フィールドのセット無しが許可されている場合はスルー
					continue;
				}
				$this->_validate($requirement, $targets[$key], $key);
			}
		}
	}

	/**
	 * 1keyに対したバリデーションの実行
	 *
	 * @param [] $requirement
	 * @param [] $target
	 * @param string $logKey ログ用のキー
	 * @throws \Vol2223\Ryvalidator\Exception\ValidationException;
	 */
	private function _validate($requirement, $target, $logKey)
	{
		switch ($requirement['type']) {
		case ValidatorType::VALIDATOR_TYPE_STRING:
			$this->stringValidate($requirement, $target, $logKey);
			break;
		case ValidatorType::VALIDATOR_TYPE_INTEGER:
			$this->integerValidate($requirement, $target, $logKey);
			break;
		case ValidatorType::VALIDATOR_TYPE_ENUM:
			$this->enumValidate($requirement, $target, $logKey);
			break;
		case ValidatorType::VALIDATOR_TYPE_OBJECT:
			$this->objectValidate($requirement, $target, $logKey);
			break;
		case ValidatorType::VALIDATOR_TYPE_ARRAY:
			$this->arrayValidate($requirement, $target, $logKey);
			break;
		default:
			throw new ValidationException(
				sprintf('validationのチェック:定義外のタイプでした。key=%s,type=%s',$logKey, $requirement['type'])
			);
		}
	}

	/**
	 * 文字列のバリデーション
	 *
	 * @param [] $requirement
	 * @param [] $target
	 * @param string $logKey ログ用のキー
	 * @throws \Vol2223\Ryvalidator\Exception\ValidationException
	 */
	private function stringValidate($requirement, $target, $logKey)
	{
		if (!is_string($target)) {
			throw new ValidationException(
				sprintf('validationのチェック:Stringではありません。key=%s,value=%s',$logKey, $target)
			);
		}
		$this->validationPackContext->stringValidator()->validate($target, $requirement);
		$this->error($this->validationPackContext->stringValidator());
	}

	/**
	 * 数値のバリデーション
	 *
	 * @param [] $requirement
	 * @param [] $target
	 * @param string $logKey ログ用のキー
	 * @throws \Vol2223\Ryvalidator\Exception\ValidationException
	 */
	private function integerValidate($requirement, $target, $logKey)
	{
		if (!is_int($target)) {
			throw new ValidationException(
				sprintf('validationのチェック:Integerではありません。key=%s,value=%s',$logKey, $target)
			);
		}
		$this->validationPackContext->integerValidator()->validate($target, $requirement);
		$this->error($this->validationPackContext->integerValidator());
	}

	/**
	 * Enumのバリデーション
	 *
	 * @param [] $requirement
	 * @param [] $target
	 * @param string $logKey ログ用のキー
	 * @throws \Vol2223\Ryvalidator\Exception\ValidationException
	 */
	private function enumValidate($requirement, $target, $logKey)
	{
		if (is_array($target) or is_object($target)) {
			throw new ValidationException(
				sprintf('validationのチェック:Enumではありません。key=%s,value=%s',$logKey, implode(',', (array)$target))
			);
		}
		$this->validationPackContext->enumValidator()->validate($target, $requirement);
		$this->error($this->validationPackContext->enumValidator());
	}

	/**
	 * オブジェクトのバリデーション
	 *
	 * @param [] $requirement
	 * @param [] $target
	 * @param string $logKey ログ用のキー
	 * @throws \Vol2223\Ryvalidator\Exception\ValidationException
	 */
	private function objectValidate($requirement, $target, $logKey)
	{
		if (!is_object($target)) {
			throw new ValidationException(
				sprintf('validationのチェック:Objectではありません。key=%s,value=%s',$logKey, implode(',', (array)$target))
			);
		}
		$this->validate($requirement['properties'], (array)$target);
	}

	/**
	 * 配列のバリデーション
	 *
	 * @param [] $requirement
	 * @param [] $target
	 * @param string $logKey ログ用のキー
	 * @throws \Vol2223\Ryvalidator\Exception\ValidationException
	 */
	private function arrayValidate($requirements, $targets, $logKey)
	{
		if (!is_array($targets)) {
			throw new ValidationException(
				sprintf('validationのチェック:Arrayではありません。key=%s,value=%s',$logKey, implode(',', (array)$targets))
			);
		}
		try {
			$this->validate($requirements['items'], $targets);
		} catch (ValidationException $e) {
			throw ValidationException($e->getMessage());
		} catch (\Exception $e) {
			throw $e;
		}
	}

	/**
	 * エラーがあれば例外を投げる
	 *
	 * @throws \Vol2223\Ryvalidator\Exception\ValidationException
	 */
	private function error($validator)
	{
		if ($validator->isError()) {
			throw new ValidationException(implode(',', $validator->messages()));
		}
	}
}
