<?php
/**
 * Class WC_Tests_Order_Functions file.
 *
 * @package WooCommerce/Tests
 */

/**
 * Class Functions.
 *
 * @package WooCommerce\Tests\Order
 * @since 2.3
 */
class WC_Tests_Order_Functions extends WC_Unit_Test_Case {

	/**
	 * Test wc_get_order_statuses().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_order_statuses() {

		$order_statuses = apply_filters(
			'wc_order_statuses', array(
				'wc-pending'    => _x( 'Pending payment', 'Order status', 'woocommerce' ),
				'wc-processing' => _x( 'Processing', 'Order status', 'woocommerce' ),
				'wc-on-hold'    => _x( 'On hold', 'Order status', 'woocommerce' ),
				'wc-completed'  => _x( 'Completed', 'Order status', 'woocommerce' ),
				'wc-cancelled'  => _x( 'Cancelled', 'Order status', 'woocommerce' ),
				'wc-refunded'   => _x( 'Refunded', 'Order status', 'woocommerce' ),
				'wc-failed'     => _x( 'Failed', 'Order status', 'woocommerce' ),
			)
		);

		$this->assertEquals( $order_statuses, wc_get_order_statuses() );
	}

	/**
	 * Test wc_is_order_status().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_is_order_status() {
		$this->assertTrue( wc_is_order_status( 'wc-pending' ) );
		$this->assertFalse( wc_is_order_status( 'wc-another-status' ) );
	}

	/**
	 * Test wc_get_order_status_name().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_order_status_name() {

		$this->assertEquals( _x( 'Pending payment', 'Order status', 'woocommerce' ), wc_get_order_status_name( 'wc-pending' ) );
		$this->assertEquals( _x( 'Pending payment', 'Order status', 'woocommerce' ), wc_get_order_status_name( 'pending' ) );
	}

	/**
	 * Test wc_processing_order_count().
	 *
	 * @since 2.4
	 */
	public function test_wc_processing_order_count() {
		$this->assertEquals( 0, wc_processing_order_count() );
	}

	/**
	 * Test wc_orders_count().
	 *
	 * @since 2.4
	 */
	public function test_wc_orders_count() {
		foreach ( wc_get_order_statuses() as $status ) {
			$this->assertEquals( 0, wc_orders_count( $status ) );
		}

		// Invalid status returns 0.
		$this->assertEquals( 0, wc_orders_count( 'unkown-status' ) );
	}

	/**
	 * Test wc_ship_to_billing_address_only().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_ship_to_billing_address_only() {

		$default = get_option( 'woocommerce_ship_to_destination' );

		update_option( 'woocommerce_ship_to_destination', 'shipping' );
		$this->assertFalse( wc_ship_to_billing_address_only() );

		update_option( 'woocommerce_ship_to_destination', 'billing_only' );
		$this->assertTrue( wc_ship_to_billing_address_only() );

		update_option( 'woocommerce_ship_to_destination', $default );
	}

	/**
	 * Test wc_get_order().
	 *
	 * @since 2.4.0
	 * @group test
	 */
	public function test_wc_get_order() {

		$order = WC_Helper_Order::create_order();

		// Assert that $order is a WC_Order object.
		$this->assertInstanceOf( 'WC_Order', $order );

		// Assert that wc_get_order() accepts a WC_Order object.
		$this->assertInstanceOf( 'WC_Order', wc_get_order( $order ) );

		// Assert that wc_get_order() accepts a order post id.
		$this->assertInstanceOf( 'WC_Order', wc_get_order( $order->get_id() ) );

		// Assert that a non-shop_order post returns false.
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$this->assertFalse( wc_get_order( $post->ID ) );

		// Assert the return when $the_order args is false.
		$this->assertFalse( wc_get_order( false ) );

		// Assert the return when $the_order args is a random (incorrect) id.
		$this->assertFalse( wc_get_order( 123456 ) );
	}

	/**
	 * Test getting an orders payment tokens
	 *
	 * @since 2.6
	 */
	public function test_wc_order_get_payment_tokens() {
		$order = WC_Helper_Order::create_order();
		$this->assertEmpty( $order->get_payment_tokens() );

		$token = WC_Helper_Payment_Token::create_cc_token();
		update_post_meta( $order->get_id(), '_payment_tokens', array( $token->get_id() ) );

		$this->assertCount( 1, $order->get_payment_tokens() );
	}


	/**
	 * Test adding a payment token to an order
	 *
	 * @since 2.6
	 */
	public function test_wc_order_add_payment_token() {
		$order = WC_Helper_Order::create_order();
		$this->assertEmpty( $order->get_payment_tokens() );

		$token = WC_Helper_Payment_Token::create_cc_token();
		$order->add_payment_token( $token );

		$this->assertCount( 1, $order->get_payment_tokens() );
	}

	/**
	 * Test the before and after date parameters for wc_get_orders.
	 *
	 * @since 3.0
	 */
	public function test_wc_get_orders_customer_params() {
		$order1  = WC_Helper_Order::create_order( 0 );
		$order2  = WC_Helper_Order::create_order( 0 );
		$order3  = WC_Helper_Order::create_order( 1 );
		$order4  = WC_Helper_Order::create_order( 1 );
		$order_1 = $order1->get_id();
		$order_2 = $order2->get_id();
		$order_3 = $order3->get_id();
		$order_4 = $order4->get_id();

		$orders   = wc_get_orders(
			array(
				'customer' => 0,
				'return'   => 'ids',
			)
		);
		$expected = array( $order_1, $order_2 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'customer' => 1,
				'return'   => 'ids',
			)
		);
		$expected = array( $order_3, $order_4 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'customer' => '',
				'return'   => 'ids',
			)
		);
		$expected = array( $order_1, $order_2, $order_3, $order_4 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the legacy before and after date parameters for wc_get_orders.
	 *
	 * @since 3.0
	 */
	public function test_wc_get_orders_date_params() {
		$order1 = WC_Helper_Order::create_order();
		$order1->set_date_created( '2015-01-01 05:20:30' );
		$order1->save();
		$order_1 = $order1->get_id();
		$order2  = WC_Helper_Order::create_order();
		$order2->set_date_created( '2017-01-01' );
		$order2->save();
		$order_2 = $order2->get_id();
		$order3  = WC_Helper_Order::create_order();
		$order3->set_date_created( '2017-01-01' );
		$order3->save();
		$order_3 = $order3->get_id();

		$orders   = wc_get_orders(
			array(
				'date_before' => '2017-01-15',
				'return'      => 'ids',
			)
		);
		$expected = array( $order_1, $order_2, $order_3 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'date_before' => '2017-01-01',
				'return'      => 'ids',
			)
		);
		$expected = array( $order_1 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'date_before' => '2016-12-31',
				'return'      => 'ids',
			)
		);
		$expected = array( $order_1 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'date_after' => '2015-01-01 00:00:00',
				'return'     => 'ids',
			)
		);
		$expected = array( $order_1, $order_2, $order_3 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'date_after' => '2016-01-01',
				'return'     => 'ids',
			)
		);
		$expected = array( $order_2, $order_3 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'date_before' => '2017-01-15',
				'date_after'  => '2015-01-01 00:00:00',
				'return'      => 'ids',
			)
		);
		$expected = array( $order_1, $order_2, $order_3 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the status parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_status_param() {
		$order1 = WC_Helper_Order::create_order();
		$order1->set_status( 'pending' );
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->set_status( 'completed' );
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'status' => 'pending',
				'return' => 'ids',
			)
		);
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'status' => 'completed',
				'return' => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the type parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_type_param() {
		$order = WC_Helper_Order::create_order();
		$order->save();
		$refund = new WC_Order_Refund();
		$refund->save();

		$orders   = wc_get_orders(
			array(
				'type'   => 'shop_order_refund',
				'return' => 'ids',
			)
		);
		$expected = array( $refund->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'type'   => 'shop_order',
				'return' => 'ids',
			)
		);
		$expected = array( $order->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the version parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_version_param() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$orders   = wc_get_orders(
			array(
				'version' => WC_VERSION,
				'return'  => 'ids',
			)
		);
		$expected = array( $order->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'version' => '2.1.0',
				'return'  => 'ids',
			)
		);
		$expected = array();
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the created_via parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_created_via_param() {
		$order1 = WC_Helper_Order::create_order();
		$order1->set_created_via( 'rest-api' );
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->set_created_via( 'checkout' );
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'created_via' => 'rest-api',
				'return'      => 'ids',
			)
		);
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'created_via' => 'checkout',
				'return'      => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the parent parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_parent_param() {
		$parent = WC_Helper_Order::create_order();
		$parent->save();

		$order1 = WC_Helper_Order::create_order();
		$order1->set_parent_id( $parent->get_id() );
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'parent' => $parent->get_id(),
				'return' => 'ids',
			)
		);
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the parent_exclude parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_parent_exclude_param() {
		$parent = WC_Helper_Order::create_order();
		$parent->save();

		$order1 = WC_Helper_Order::create_order();
		$order1->set_parent_id( $parent->get_id() );
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->save();

		$orders = wc_get_orders(
			array(
				'parent_exclude' => array( $parent->get_id() ),
				'return'         => 'ids',
			)
		);
		sort( $orders );
		$expected = array( $parent->get_id(), $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the exclude parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_exclude_param() {
		$order1 = WC_Helper_Order::create_order();
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'exclude' => array( $order1->get_id() ),
				'return'  => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the limit parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_limit_param() {
		$order1 = WC_Helper_Order::create_order();
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->save();

		$orders = wc_get_orders( array( 'limit' => 1 ) );
		$this->assertEquals( 1, count( $orders ) );
	}

	/**
	 * Test the paged parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_paged_param() {
		$order1 = WC_Helper_Order::create_order();
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'paged'   => 1,
				'orderby' => 'ID',
				'order'   => 'ASC',
				'limit'   => 1,
				'return'  => 'ids',
			)
		);
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'paged'   => 2,
				'orderby' => 'ID',
				'order'   => 'ASC',
				'limit'   => 1,
				'return'  => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the offset parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_offset_param() {
		$order1 = WC_Helper_Order::create_order();
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'offset'  => 1,
				'orderby' => 'ID',
				'order'   => 'ASC',
				'return'  => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the paginate parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_paginate_param() {
		$order1 = WC_Helper_Order::create_order();
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->save();

		$orders = wc_get_orders( array( 'paginate' => true ) );
		$this->assertEquals( 2, $orders->total );
		$this->assertEquals( 2, count( $orders->orders ) );
		$this->assertInstanceOf( 'WC_Order', $orders->orders[0] );
		$this->assertEquals( 1, $orders->max_num_pages );
	}

	/**
	 * Test the paginate parameter with return id for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_paginate_id_param() {
		$order = WC_Helper_order::create_order();
		$order->save();

		$orders = wc_get_orders(
			array(
				'paginate' => true,
				'return'   => 'ids',
			)
		);
		$this->assertEquals( 1, $orders->total );
		$this->assertEquals( 1, $orders->max_num_pages );
		$this->assertEquals( $order->get_id(), $orders->orders[0] );
	}

	/**
	 * Test the order parameters for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_order_param() {
		$order1 = WC_Helper_Order::create_order();
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'orderby' => 'ID',
				'order'   => 'DESC',
				'return'  => 'ids',
			)
		);
		$expected = array( $order2->get_id(), $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'orderby' => 'ID',
				'order'   => 'ASC',
				'return'  => 'ids',
			)
		);
		$expected = array( $order1->get_id(), $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the currency parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_currency_param() {
		$order1 = WC_Helper_Order::create_order();
		$order1->set_currency( 'BRL' );
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->set_currency( 'USD' );
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'currency' => 'BRL',
				'return'   => 'ids',
			)
		);
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'currency' => 'USD',
				'return'   => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the prices_include_tax parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_prices_include_tax_param() {
		$order1 = WC_Helper_Order::create_order();
		$order1->set_prices_include_tax( true );
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->set_prices_include_tax( false );
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'prices_include_tax' => 'yes',
				'return'             => 'ids',
			)
		);
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'prices_include_tax' => 'no',
				'return'             => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the payment_method parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_payment_method_param() {
		$order1 = WC_Helper_Order::create_order();
		$order1->set_payment_method( 'cheque' );
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->set_payment_method( 'cod' );
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'payment_method' => 'cheque',
				'return'         => 'ids',
			)
		);
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'payment_method' => 'cod',
				'return'         => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the payment_method_title parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_get_order_payment_method_title_param() {
		$order1 = WC_Helper_Order::create_order();
		$order1->set_payment_method_title( 'Check payments' );
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->set_payment_method_title( 'PayPal' );
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'payment_method_title' => 'Check payments',
				'return'               => 'ids',
			)
		);
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'payment_method_title' => 'PayPal',
				'return'               => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the price parameters for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_price_price_params() {
		$order1 = new WC_Order();
		$order1->set_discount_total( 5.50 );
		$order1->set_discount_tax( 0.50 );
		$order1->set_shipping_total( 3.99 );
		$order1->set_shipping_tax( 0.25 );
		$order1->set_cart_tax( 0.10 );
		$order1->set_total( 10.34 );
		$order1->save();
		$order2 = new WC_Order();
		$order2->set_discount_total( 2.50 );
		$order2->set_discount_tax( 0.20 );
		$order2->set_shipping_total( 2.99 );
		$order2->set_shipping_tax( 0.15 );
		$order2->set_cart_tax( 0.05 );
		$order2->set_total( 5.89 );
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'discount_total' => 5.50,
				'return'         => 'ids',
			)
		);
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'discount_tax' => 0.20,
				'return'       => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'shipping_total' => 3.99,
				'return'         => 'ids',
			)
		);
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'shipping_tax' => 0.15,
				'return'       => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'cart_tax' => 0.10,
				'return'   => 'ids',
			)
		);
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'total'  => 5.89,
				'return' => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the customer parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_customer_param() {
		$customer1 = WC_Helper_Customer::create_customer( 'customer1', 'password', 'test1@test.com' );
		$customer1->set_billing_email( 'customer1@test.com' );
		$customer1->save();

		$customer2 = WC_Helper_Customer::create_customer( 'customer2', 'password', 'test2@test.com' );
		$customer2->set_billing_email( 'customer2@test.com' );
		$customer2->save();

		$order1 = WC_Helper_Order::create_order();
		$order1->set_customer_id( $customer1->get_id() );
		$order1->set_billing_email( $customer1->get_billing_email() );
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->set_customer_id( $customer2->get_id() );
		$order2->set_billing_email( $customer2->get_billing_email() );
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'customer' => $customer1->get_billing_email(),
				'return'   => 'ids',
			)
		);
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'customer' => $customer2->get_id(),
				'return'   => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders = wc_get_orders( array( 'customer' => 'invalid' ) );
		$this->assertEmpty( $orders );

		$orders = wc_get_orders( array( 'customer' => array( 'invalid' ) ) );
		$this->assertEmpty( $orders );

		$orders = wc_get_orders( array( 'customer' => array( '' ) ) );
		$this->assertEmpty( $orders );

		$orders = wc_get_orders( array( 'customer' => 'doesnt@exist.com' ) );
		$this->assertEmpty( $orders );
	}

	/**
	 * Test the customer_id parameter for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_customer_id_param() {
		$customer1 = WC_Helper_Customer::create_customer( 'customer1', 'password', 'test1@test.com' );
		$customer1->set_billing_email( 'customer1@test.com' );
		$customer1->save();

		$customer2 = WC_Helper_Customer::create_customer( 'customer2', 'password', 'test2@test.com' );
		$customer2->set_billing_email( 'customer2@test.com' );
		$customer2->save();

		$order1 = WC_Helper_Order::create_order();
		$order1->set_customer_id( $customer1->get_id() );
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->set_customer_id( $customer2->get_id() );
		$order2->save();

		$orders   = wc_get_orders(
			array(
				'customer_id' => $customer1->get_id(),
				'return'      => 'ids',
			)
		);
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'customer_id' => $customer2->get_id(),
				'return'      => 'ids',
			)
		);
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test the address parameters for wc_get_orders.
	 *
	 * @since 3.1
	 */
	public function test_wc_get_order_address_params() {
		$order1 = WC_Helper_Order::create_order();
		$order1->set_props(
			array(
				'billing_email'       => 'test1@test.com',
				'billing_first_name'  => 'Bill',
				'billing_last_name'   => 'Powers',
				'billing_company'     => 'TestCo.',
				'billing_address_1'   => '1234 Cool St.',
				'billing_address_2'   => 'Apt 2',
				'billing_city'        => 'Portland',
				'billing_state'       => 'OR',
				'billing_postcode'    => '97266',
				'billing_country'     => 'US',
				'billing_phone'       => '503-123-4567',
				'shipping_first_name' => 'Jane',
				'shipping_last_name'  => 'Powers',
				'shipping_company'    => '',
				'shipping_address_1'  => '4321 Cool St.',
				'shipping_address_2'  => 'Apt 1',
				'shipping_city'       => 'Milwaukie',
				'shipping_state'      => 'OR',
				'shipping_postcode'   => '97222',
				'shipping_country'    => 'US',
			)
		);
		$order1->save();
		$order1_id = $order1->get_id();

		$order2 = WC_Helper_Order::create_order();
		$order2->set_props(
			array(
				'billing_email'       => 'test2@test.com',
				'billing_first_name'  => 'Joe',
				'billing_last_name'   => 'Thunder',
				'billing_company'     => 'ThunderCo.',
				'billing_address_1'   => '1234 Thunder St.',
				'billing_address_2'   => '',
				'billing_city'        => 'Vancouver',
				'billing_state'       => 'WA',
				'billing_postcode'    => '97267',
				'billing_country'     => 'US',
				'billing_phone'       => '503-333-3333',
				'shipping_first_name' => 'Joseph',
				'shipping_last_name'  => 'Thunder',
				'shipping_company'    => 'Thunder Inc',
				'shipping_address_1'  => '1 Thunder Blvd',
				'shipping_address_2'  => '',
				'shipping_city'       => 'Vancouver',
				'shipping_state'      => 'WA',
				'shipping_postcode'   => '97233',
				'shipping_country'    => 'US',
			)
		);
		$order2->save();
		$order2_id = $order2->get_id();

		$orders   = wc_get_orders(
			array(
				'billing_email' => 'test1@test.com',
				'return'        => 'ids',
			)
		);
		$expected = array( $order1_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'billing_first_name' => 'Joe',
				'return'             => 'ids',
			)
		);
		$expected = array( $order2_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'billing_last_name' => 'Powers',
				'return'            => 'ids',
			)
		);
		$expected = array( $order1_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'billing_company' => 'ThunderCo.',
				'return'          => 'ids',
			)
		);
		$expected = array( $order2_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'billing_address_1' => '1234 Cool St.',
				'return'            => 'ids',
			)
		);
		$expected = array( $order1_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'billing_address_2' => 'Apt 2',
				'return'            => 'ids',
			)
		);
		$expected = array( $order1_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'billing_city' => 'Vancouver',
				'return'       => 'ids',
			)
		);
		$expected = array( $order2_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'billing_state' => 'OR',
				'return'        => 'ids',
			)
		);
		$expected = array( $order1_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'billing_postcode' => '97267',
				'return'           => 'ids',
			)
		);
		$expected = array( $order2_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'billing_country' => 'US',
				'orderby'         => 'ID',
				'order'           => 'ASC',
				'return'          => 'ids',
			)
		);
		$expected = array( $order1_id, $order2_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'billing_phone' => '503-333-3333',
				'return'        => 'ids',
			)
		);
		$expected = array( $order2_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'shipping_first_name' => 'Jane',
				'return'              => 'ids',
			)
		);
		$expected = array( $order1_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'shipping_last_name' => 'Thunder',
				'return'             => 'ids',
			)
		);
		$expected = array( $order2_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'shipping_company' => 'Thunder Inc',
				'return'           => 'ids',
			)
		);
		$expected = array( $order2_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'shipping_address_1' => '1 Thunder Blvd',
				'return'             => 'ids',
			)
		);
		$expected = array( $order2_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'shipping_address_2' => 'Apt 1',
				'return'             => 'ids',
			)
		);
		$expected = array( $order1_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'shipping_city' => 'Vancouver',
				'return'        => 'ids',
			)
		);
		$expected = array( $order2_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'shipping_state' => 'OR',
				'return'         => 'ids',
			)
		);
		$expected = array( $order1_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'shipping_postcode' => '97233',
				'return'            => 'ids',
			)
		);
		$expected = array( $order2_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'shipping_country' => 'US',
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'return'           => 'ids',
			)
		);
		$expected = array( $order1_id, $order2_id );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders(
			array(
				'billing_first_name' => 'Joe',
				'billing_last_name'  => 'Thunder',
				'return'             => 'ids',
			)
		);
		$expected = array( $order2_id );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test wc_get_order date queries combined with meta queries.
	 *
	 * @since 3.2
	 */
	public function test_wc_get_order_mixed_date_shipping_country() {
		// Set up dates.
		$start      = time() - 1; // Start from one second ago.
		$yesterday  = strtotime( '-1 day', $start );
		$end        = strtotime( '+1 day', $start );
		$date_range = $start . '...' . $end;

		// Populate orders.
		$us_now = WC_Helper_Order::create_order();
		$us_now->set_props(
			array(
				'shipping_country' => 'US',
			)
		);
		$us_now->save();

		$us_old = WC_Helper_Order::create_order();
		$us_old->set_props(
			array(
				'date_created'     => $yesterday,
				'shipping_country' => 'US',
			)
		);
		$us_old->save();

		$mx_now = WC_Helper_Order::create_order();
		$mx_now->set_props(
			array(
				'shipping_country' => 'MX',
			)
		);
		$mx_now->save();

		$mx_old = WC_Helper_Order::create_order();
		$mx_old->set_props(
			array(
				'date_created'     => $yesterday,
				'shipping_country' => 'MX',
			)
		);
		$mx_old->save();

		// Test just date range.
		$args     = array(
			'return'       => 'ids',
			'date_created' => $date_range,
			'orderby'      => 'ID',
			'order'        => 'ASC',
		);
		$orders   = wc_get_orders( $args );
		$expected = array( $us_now->get_id(), $mx_now->get_id() );
		$this->assertEquals( $expected, $orders );

		// Test just US orders.
		$args     = array(
			'return'           => 'ids',
			'shipping_country' => 'US',
			'orderby'          => 'ID',
			'order'            => 'ASC',
		);
		$orders   = wc_get_orders( $args );
		$expected = array( $us_now->get_id(), $us_old->get_id() );
		$this->assertEquals( $expected, $orders );

		// Test US orders with date range.
		$args     = array(
			'return'           => 'ids',
			'date_created'     => $date_range,
			'shipping_country' => 'US',
			'orderby'          => 'ID',
			'order'            => 'ASC',
		);
		$orders   = wc_get_orders( $args );
		$expected = array( $us_now->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	/**
	 * Test wc_sanitize_order_id().
	 */
	public function test_wc_sanitize_order_id() {
		$this->assertSame( 123, wc_sanitize_order_id( '123' ) );
		$this->assertSame( 123, wc_sanitize_order_id( '#123' ) );
		$this->assertSame( 123, wc_sanitize_order_id( '# 123' ) );
		$this->assertSame( 123, wc_sanitize_order_id( 'Order #123' ) );
	}

	/**
	 * Test wc_get_order_note().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_order_note() {
		$note_content = 'Note content';
		$order        = WC_Helper_Order::create_order();
		$note_id      = (int) $order->add_order_note( $note_content );
		$expected     = array(
			'id'            => $note_id,
			'content'       => $note_content,
			'customer_note' => false,
			'added_by'      => 'system',
		);
		$note         = (array) wc_get_order_note( $note_id );
		unset( $note['date_created'] );

		$this->assertEquals( $expected, $note );
	}

	/**
	 * Test wc_get_order_notes().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_order_notes() {
		$order = WC_Helper_Order::create_order();
		$order->add_order_note( 'Customer note', 1 );
		$order->add_order_note( 'Internal note' );
		$order->add_order_note( 'Another internal note' );

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$this->assertEquals( 3, count( $notes ) );

		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'customer',
			)
		);
		$this->assertEquals( 1, count( $notes ) );

		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'internal',
			)
		);
		$this->assertEquals( 2, count( $notes ) );
	}

	/**
	 * Test wc_create_order_note().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_create_order_note() {
		$order = WC_Helper_Order::create_order();

		$note_id = wc_create_order_note( $order->get_id(), 'Note content', false, false );
		$this->assertTrue( 0 < $note_id );
	}

	/**
	 * Test wc_delete_order_note().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_delete_order_note() {
		$order   = WC_Helper_Order::create_order();
		$note_id = $order->add_order_note( 'Note content' );

		$this->assertTrue( wc_delete_order_note( $note_id ) );
	}

	/**
	 * Test wc_order_search()
	 *
	 * @since 3.3.0
	 */
	public function test_wc_order_search() {
		$order = WC_Helper_Order::create_order();

		// Should find order searching by billing address name.
		$this->assertEquals( array( $order->get_id() ), wc_order_search( 'Jeroen' ) );

		// Should find order searching by order item name.
		$this->assertEquals( array( $order->get_id() ), wc_order_search( 'Dummy Product' ) );

		// Should return nothing when searching for nonexistent term.
		$this->assertEmpty( wc_order_search( 'Nonexistent term' ) );
	}
}
