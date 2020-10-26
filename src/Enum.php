<?php
namespace Paypal;

class PaymentStates{

	const COMPLETED = 'Completed';
	const CANCELED_REVERSAL = 'Canceled_Reversal';
	const DENIED = 'Denied';
	const EXPIRED = 'Expired';
	const FAILED = 'Failed';
	const PENDING = 'Pending';
	const REFUNDED = 'Refunded';
	const REVERSED = 'Reversed';
	const PROCESSED = 'Processed';
	const VOIDED = 'Voided';

	/**
	 * @var array
	 */
	static protected $_itemsToString = array(
		self::COMPLETED => 'проведен',
		self::CANCELED_REVERSAL => 'возврат отменен',
		self::DENIED => 'в оплате отказано',
		self::EXPIRED => 'срок действия разрешения истек',
		self::FAILED => 'оплата с банковского счета провалилась',
		self::PENDING => 'оплата на рассморении',
		self::REFUNDED => 'возврат платежа',
		self::REVERSED => 'возврат платежа',
		self::PROCESSED => 'оплата принята',
		self::VOIDED => 'разрешение на оплату аннулировано'
	);

	/**
	 * Получение всех констант
	 *
	 * @return array
	 */
	static function getAllConstants() {
		$refl = new \ReflectionClass(get_called_class());
		return $refl->getConstants();
	}

	/**
	 * Проверка корректности значения
	 *
	 * @abstract
	 *
	 * @param {string} $value
	 *
	 * @return bool
	 */
	static function isValid($value) {
		return in_array($value, array_values(self::getAllConstants()));
	}

	/**
	 * Получение кастомных наименований элементов enum
	 *
	 * @return array
	 */
	static function getItemsToString() {

		$_class = get_called_class();
		return $_class::$_itemsToString;
	}

	static function failStates() {
		return [
			self::DENIED,
			self::FAILED,
			self::EXPIRED,
			self::VOIDED
		];
	}



}
