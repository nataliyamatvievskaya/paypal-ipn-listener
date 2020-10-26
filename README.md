# paypal-ipn-listener
paypal listener

## Usage

```php
$P = new \Paypal\Listener('test');

		switch($P->process()) {
			case $P::TO_INCOME:
				//@todo  add transaction to database
				header("HTTP/1.1 200 OK");
				break;
			case $P::TO_WARNING:
				//@todo send warning to administrator
				header("HTTP/1.1 200 OK");
				break;
			case $P::TO_ERROR:
				header("HTTP/1.1 500 Script Generated Error");
				break;
		}
		die;
  
