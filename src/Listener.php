<?php

namespace Paypal;

use GuzzleHttp\Client as Client;
use Monolog;
use Monolog\Handler\StreamHandler;


/**
 * Обработчик IPN запроса PayPal
 * https://developer.paypal.com/docs/ipn/#
 * Class Listener
 */
class Listener {

	public $paymentData;

	public $logFile;
	public $debug = false;

	/** url to verify request */
	const VERIFY_URI = 'https://ipnpb.paypal.com/cgi-bin/webscr';
	const SANDBOX_VERIFY_URI = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';

	/** @var string verified IPN responce */
	const VALID_IPN = 'VERIFIED';

	/**  listener responces */
	const TO_INCOME = 1;
	const TO_WARNING = 2;
	const TO_ERROR = 4;

	public function __construct($mode) {

		$this->__configure($mode);

		$this->Downloader = new Client(
			['base_uri' => $this->_verifyUrl]
		);

		$this->Logger = new Monolog\Logger('paypal_logger');
		$this->Logger->pushHandler(new StreamHandler($this->logFile));

	}

	/**
	 * Configure listener
	 * @param $mode
	 */
	private function __configure($mode) {

		if (!in_array($mode, ['live', 'test'])) {
			throw new Exception('not valid listener mode');
		}

		$this->_debug = false;
		$this->_verifyUrl = self::VERIFY_URI;
		$this->logFile = 'pp_log'; // path to log file


		if ($mode === 'test') {
			$this->_debug = true;
			$this->_verifyUrl = self::SANDBOX_VERIFY_URI;
		}
	}

	/**
	 * get from body
	 * @return bool|string
	 */
	protected function getBody() {

		$postData = file_get_contents('php://input');
		$res = [];
		parse_str($postData, $res);
		return $res;
	}

	/**
	 * Processing
	 * @return int
	 */
	public function process(){

		try {
			$request = $this->getRequest();

			$this->__validateResponce();

			if ($this->__checkUnusuals()) {
				return self::TO_WARNING;
			}

			return self::TO_INCOME;
		} catch (Exception $e) {
			$this->Logger->error($e->getMessage());
			return self::TO_ERROR;
		}

	}

	/**
	 * Get IPN request from body
	 * @throws Exception
	 */
	public function getRequest() {

		$this->paymentData = $this->getBody();
	}


	/**
	 * IPN request validation
	 * @return bool
	 * @throws Exception
	 */
	private function __validateResponce() {

		$this->Logger->info($this->paymentData['custom'] . ' Обработка платежа, параметры ' . var_export($this->paymentData, true));

		if(!$this->paymentData['mc_gross']){
			throw new Exception('Undefined amount');
		}

		if(
			!PaymentStates::isValid($this->paymentData['payment_status']) ||
			in_array($this->paymentData['payment_status'], PaymentStates::failStates())
		){
			throw new Exception('No valid payment state');
		}

		if(!$this->paymentData['mc_currency']){
			throw new Exception('No valid currency');
		}

		if (!$this->paymentData['custom']){
			throw new Exception('Undefined customer');
			return false;
		}

		if (!$this->__sendVerifyRequest()) {
			throw new Exception('Fail IPN security check');
		}

		return true;
	}

	/**
	 * Send request to verification url
	 * @return bool
	 * @throws Exception
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	private function __sendVerifyRequest(){

		$req = 'cmd=_notify-validate';
		$get_magic_quotes_exists = false;
		if (function_exists('get_magic_quotes_gpc')) {
			$get_magic_quotes_exists = true;
		}
		foreach ($this->paymentData as $key => $value) {
			if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
				$value = urlencode(stripslashes($value));
			} else {
				$value = urlencode($value);
			}
			$req .= "&$key=$value";
		}

		try {
			$Responce = $this->Downloader->request('Post', $this->_verifyUrl,
				[
					'headers' => [
						'User-Agent: PHP-IPN-Verification-Script',
						'Connection: Close',
					],
					'query' => $req
				]);

			if ($Responce->getStatusCode() != 200) {
				throw new Exception("PayPal responded with http code " . $Responce->getHeader());
			}
		} catch (AppException $e) {
			L::error($e->getMessage(), $this->log);
			return false;
		}

		if ($Responce->getBody()->getContents() == self::VALID_IPN) {
			return true;
		} else {
			return false;
		}
	}


	/** Processing not completed states
	 * @return bool
	 */
	private function __checkUnusuals(){

		if(in_array($this->paymentData['payment_status'], [PaymentStates::CANCELED_REVERSAL, PaymentStates::REFUNDED, PaymentStates::REVERSED])){
			return true;
		}

		return false;
	}
}
?>
