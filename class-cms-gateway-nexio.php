<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CMS_Gateway_Nexio class.
 *
 * @extends WC_Payment_Gateway
 */
class CMS_Gateway_Nexio extends WC_Payment_Gateway_CC {
	/**
	 * API URL
	 *
	 * @var string
	 */
	public $api_url;

	/**
	 * Merchant ID
	 *
	 * @var string
	 */
	public $merchant_id;

	/**
	 * User Name
	 *
	 * @var string
	 */
	public $user_name;	

	/**
	 * PassWord
	 *
	 * @var string
	 */
	public $password;		

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id             = 'nexio';
		$this->method_title   = __( 'Nexio Credit Card', 'cms-gateway-nexio' );
		/* translators: 1) link to nexio register page 2) link to nexio api keys page */
		$this->method_description = sprintf( __( 'Nexio works by adding payment fields on the checkout and then sending the details to Nexio for verification. <a href="%1$s" target="_blank">Connect us</a> for a Nexio account, and get your Nexio account token</a>.', 'cms-gateway-nexio' ), 'https://nexiopay.com/contact/');
		$this->has_fields         = false;

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values.
		$this->title                       = $this->get_option( 'title' );
		$this->description                 = $this->get_option( 'description' );
		$this->enabled                     = $this->get_option( 'enabled' );
		$this->api_url		= $this->get_option('api_url');
		$this->user_name	= $this->get_option('user_name');
		$this->password	= $this->get_option('password');
		$this->order_button_text = __( 'Continue to payment', 'cms-gateway-nexio' );
		

		// Hooks.
		add_action( 'init', array( $this, 'nexio_checkout_return_handler' ) );
		add_action( 'woocommerce_api_woocommerce_nexio', array( $this, 'successful_request' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_nexio', array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'nexio_checkout_return_handler' ) );
		add_action( 'woocommerce_api_callback', array( $this, 'nexio_checkout_return_failure_handler' ) );
	}
	
	
	/**
	 * Checks if gateway should be available to use.
	 *
	 * @since 0.0.1
	 */
	public function is_available() {
		if ( is_add_payment_method_page() && ! $this->saved_cards ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * payment_fields function.
	 *
	 * @since 0.0.1
	 * @version 1.0.0
	 * @return null
	 */
	public function payment_fields() {
		echo wpautop(wptexturize('Please click below button to continue payment'));
	}

	public function payment_scripts() {
		
	}
	/**
	 * Get_icon function.
	 *
	 * @since 1.0.0
	 * @version 4.0.0
	 * @return string
	 */
	/*
	public function get_icon() {
		$icons = $this->payment_icons();

		$icons_str = '';

		$icons_str .= isset( $icons['visa'] ) ? $icons['visa'] : '';
		$icons_str .= isset( $icons['amex'] ) ? $icons['amex'] : '';
		$icons_str .= isset( $icons['mastercard'] ) ? $icons['mastercard'] : '';

		if ( 'USD' === get_woocommerce_currency() ) {
			$icons_str .= isset( $icons['discover'] ) ? $icons['discover'] : '';
			$icons_str .= isset( $icons['jcb'] ) ? $icons['jcb'] : '';
			$icons_str .= isset( $icons['diners'] ) ? $icons['diners'] : '';
		}

		return apply_filters( 'woocommerce_gateway_icon', $icons_str, $this->id );
	}
	*/

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = require( dirname( __FILE__ ) . '/nexio-settings.php' );
	}

	/**
	 * Handles the return from processing the payment.
	 *
	 * @since 0.0.1
	 */
	public function nexio_checkout_return_handler() {
		error_log('CALLBACK WORKS!!!!!',0);
		$data = file_get_contents('php://input');
		error_log('data:'.$data);
		$callbackdata = json_decode($data);
		//error_log('callback data:'.json_encode($callbackdata->data));
		if(isset($callbackdata->data) && !empty($callbackdata->data) &&
		   isset($callbackdata->gatewayResponse) && !empty($callbackdata->gatewayResponse) &&
		   isset($callbackdata->data->customer->orderNumber) && !empty($callbackdata->data->customer->orderNumber))
		{
			error_log("HEY!!!!it's the condition");
			try
			{
				$order_id = $callbackdata->data->customer->orderNumber;
				
				error_log('ORDER Number:'.$order_id);
				
				$order    = wc_get_order( $order_id );
				// Remove cart.
				$order->add_order_note(sprintf(__('Nexio Payment Completed.', 'cms-gateway-nexio')));
				$order->payment_complete();
				//WC()->cart->empty_cart();
				
				wp_redirect(get_permalink($this->get_return_url( $order )));
				exit;
				
			}
			catch(Exception $e)
			{
				//echo $e->getMessage();
				error_log('CALL BACK get exception:'.$e->getMessage(),0);
				wp_redirect( get_permalink(get_option( 'woocommerce_checkout_page_id' )) );
				exit;
			}
			
			
		} 
		
	}

	/**
	 * Handles the failure return from processing the payment.
	 *
	 * @since 0.0.1
	 */
	public function nexio_checkout_return_failure_handler() {
		error_log('failure CALLBACK WORKS!!!!!',0);
		$data = file_get_contents('php://input');
		error_log('failure data:'.$data,0);
		$callbackdata = json_decode($data);
		wp_redirect( get_permalink(get_option( 'woocommerce_checkout_page_id' )) );
		exit;	
	}

	/**
	 * Generate the form of pre-order page.
	 *
	 * @since 0.0.1
	 */
	public function generate_nexio_form( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		//get one time token first
		$onetimetoken = $this->get_iframe_src($this->get_creditcard_token($order_id));


		$gateway_url = $this->get_iFrameURL();//dirname( __FILE__ ) . '/iframe_CreditCardTransactioin.php';
		
		$redirect_url_success = $this->get_return_url( $order );
		$redirect_url_fail = get_permalink(get_option( 'woocommerce_checkout_page_id' ));
		$phpurl = $this->get_callback_url();
		//wp_redirect($redirect_url_fail);
		//$iframeDomain = $onetimetoken.match(/^http(s?):\/\/.*?(?=\/)/)[0];
		
		wc_enqueue_js('
				cms_payment_form.addEventListener("submit", function processPayment(event) {
				event.preventDefault();
				iframe1.contentWindow.postMessage("posted", "'.$onetimetoken.'");
				return false;
			});

			window.addEventListener("message", function messageListener(event) {
				if (event.origin === "'.rtrim($this->api_url, '/\\').'") {
					if (event.data.event === "loaded") {
						window.document.getElementById("iframe1").style.display = "block";
						window.document.getElementById("loader").style.display = "none";
					}
					if (event.data.event === "processed") {
						var jsonStr = JSON.stringify(event.data.data, null, 1);
						window.document.getElementById("cms_payment_form").innerHTML = "<p>Successfully Processed Credit Card Transaction.</p><code><br/>" + jsonStr + "</code>";
						window.location = "'.$this->get_return_url( $order ).'";
					}
					if (event.data.event === "error"){
						window.document.getElementById("cms_payment_form").innerHTML = "<p>Failed to Process Credit Card Transaction.</p>";
						window.location = "'.get_permalink(get_option( 'woocommerce_checkout_page_id' )).'";
					}
				}
			});
		');

		
		
		return '<form id="cms_payment_form" height="900px" width="400px" action="'.esc_url( $onetimetoken ).'" method="post">
		<iframe type="iframe" id="iframe1" src="'.$onetimetoken.'" style="border:0" height="750px"></iframe>
		<input type="submit" class="button" id="submit_cms_payment_form" value="'.__('Pay via CMS Nexio', 'cms-gateway-nexio').'" />
		</form>
		<div id="loader">Loading Form...</div>';

	}

	/**
	 * Generate the json string for getting one time token.
	 *
	 * @since 0.0.1
	 */
	function build_gettoken_json($order_id)
	{
		$order = new WC_Order( $order_id );
		//1. get data array first
			//1.1 get customer array first.
		$customer = array(
			'orderNumber' => $order_id,
			'firstName' => $order->get_billing_first_name(),
			'lastName' => $order->get_billing_last_name(),
			'billToAddressOne' => $order->get_billing_address_1(),
			'billToAddressTwo' => $order->get_billing_address_2(),
			'billToCity' => $order->get_billing_city(),
			'billToState' => $order->get_billing_state(),
			'billToPostal' => $order->get_billing_postcode(),
			'billToCountry' => $order->get_billing_country()
		);

			//1.2 build data array
		$data = array(
			'paymentMethod'=>'creditCard',
			'allowedCardTypes'=>[ 'visa', 'mastercard','discover','amex' ],
			'amount' => $order->get_total(),
			'currency' => get_woocommerce_currency(),
			'customer' => $customer
		);

		//2. processingOptions
		$processingOptions = array(
			'webhookUrl' => $this->get_callback_url(),
			'webhookFailUrl' => $this->get_failure_callback_url(),
			'checkFraud' => true,
			'verifyCvc' => false,
			'verifyAvs' => 0,
			'verboseResponse' => true
		);

		//3. uiOptions
		$uiOptions = array(
			'customTextUrl' => '',
			'displaySubmitButton' => false,
			'hideCvc' => false,
			'requireCvc' => true,
			'hideBilling' => false,
			);
		
		//4. card
		$card = array(
			'cardHolderName' => $order->get_billing_first_name().' '.$order->get_billing_last_name()
		);

		//5. TODO cart

		//build the whole array
		$request = array(
			'data' => $data,
			'processingOptions' => $processingOptions,
			'uiOptions' => $uiOptions,
			'card' => $card
		);

		//convert to json
		$jsondata = json_encode($request);
		//echo $jsondata;
		return $jsondata;
	}

	/**
	 * get_callback_url function.
	 *
	 * @since 0.0.1
	 * @version 1.0.0
	 * @return string
	 */
	public function get_callback_url()
	{
		$callbackurl = get_site_url(null,null,'https').'/wc-api/'.strtolower( get_class( $this ) );
		error_log('callback url:'.$callbackurl,0);
		
		return $callbackurl;
	}

	/**
	 * get_failure_callback_url function.
	 *
	 * @since 0.0.1
	 * @version 1.0.0
	 * @return string
	 */
	public function get_failure_callback_url()
	{
		$callbackurl = get_site_url(null,null,'https').'/wc-api/CALLBACK/';
		
		error_log('failure callback url:'.$callbackurl,0);
		return $callbackurl;
	}

	/**
	 * Get one time token for fetch iFrame.
	 *
	 * @since 0.0.1
	 */
	public function get_creditcard_token($order_id)
	{
		//$this->WriteLog('begin get one time token');
		
		$order = new WC_Order( $order_id );
		try {
			$data = $this->build_gettoken_json($order_id);
			$basicauth = "Basic ". base64_encode($this->user_name . ":" . $this->password);
			$ch = curl_init($this->api_url.'pay/v3/token');
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"Authorization: $basicauth",
				"Content-Type: application/json",
				"Content-Length: " . strlen($data)));
			$result = curl_exec($ch);
			$error = curl_error($ch);
			curl_close($ch);
		
			if ($error) {
				//echo "CURL Error #: $error";
				return "error";
			} else {
				//echo $result;
				$onetimetoken = json_decode($result)->token;
				if(json_decode($result)->error)
				{
					//echo 'there is something wrong';
					return "error";
				}
				$this->token = $onetimetoken;
				//echo "Get One Time Token #: $onetimetoken";
				error_log("Get One Time Token:".$onetimetoken,0);
				return json_decode($result)->token;
			}
		} catch (Exception $e) {
			//echo "Get token failed #: $e->getMessage()";
			error_log("Get One Time Token:".$e->getMessage(),0);
			return "error";//$e->getMessage();
		}
	}

	/**
	 * Get separate iFrame page URL.
	 *
	 * @since 0.0.1
	 */
	public function get_iFrameURL()
	{
		
		$siteurl = get_site_url();
		$wpconfigfilepath = $this->fs_get_wp_config_path();
		if($wpconfigfilepath !=false)
		{
			$wordpressroot = dirname($wpconfigfilepath);
			$pluginurl = plugin_dir_path( __FILE__ );
	
	
	
			//$finallink = str_replace( 'my-account/', 'iframe_CreditCardTransaction.php', $myaccount_page_url );
	
			$finallink = str_replace( $wordpressroot, $siteurl, $pluginurl );
	
			$path = str_replace("\\", "/", $finallink);
	
			return $path."iframe_CreditCardTransaction.php";
		}
		else
			return false;
	}

	/*
	 * Process the payment
	 * 
	 */
	function receipt_page( $order_id ) {
		
		echo '<p>'.__('Thank you for your order, please input your payment information in blow Nexio Form and click the button.', 'cms-gateway-nexio').'</p>';
		echo $this->generate_nexio_form( $order_id );
	}

	public function fs_get_wp_config_path()
	{
		$base = dirname(__FILE__);
		$path = false;

		if (@file_exists(dirname(dirname($base))."\wp-config.php"))
		{
			$path = dirname(dirname($base))."\wp-config.php";
		}
		else
		if (@file_exists(dirname(dirname(dirname($base)))."\wp-config.php"))
		{
			$path = dirname(dirname(dirname($base)))."\wp-config.php";
		}
		else
			$path = false;

		return $path;
	}

	/**
	 * Get iFrame src url.
	 *
	 * @since 0.0.1
	 * @return string
	 */
	public function get_iframe_src($newvalue)
	{
		//echo 'ontimetoken: '.$newvalue;
		//todo if get error, need show notification
		$src = $this->api_url."pay/v3/?token=".$newvalue;
		return $src;
	}
	

	public function process_payment( $order_id, $retry = true, $force_save_source = false, $previous_error = false ) {
		$order = new WC_Order( $order_id );
		
		//$GLOBALS['token'] = $this->get_creditcard_token($order_id);
		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url(true),//$this->get_return_url( $order ),
		);
	}
}