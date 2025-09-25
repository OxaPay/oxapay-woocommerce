<?php

namespace Oxapay\Woocommerce;

use WC_Payment_Gateway;
final class oxapayGateway extends WC_Payment_Gateway
{
    /**
     * @var string
     */
    public $id = 'oxapay';
    /**
     * @var bool
     */
    public $has_fields = true;
    /**
     * @var string
     */
    public $plugin_name = 'OxaPay Payment Method for WooCommerce';
    /**
     * @var string
     */
    public $title = 'Pay with OxaPay';
    /**
     * @var string
     */
    public $method_title = 'OxaPay';
    /**
     * @var string
     */
    public $method_description = "";
    /**
     * @var string
     */
    public $merchant;
    /**
     * @var string
     */
    public $lifetime;

    /**
     * @var string
     */
    public $under_paid_coverage;

    /**
     * @var string
     */
    public $mixed_payment;

    /**
     * @var string
     */
    public $fee_paid_by_payer;

    /**
     * @var string
     */
    public $sandbox;



    public function __construct()
    {
        $this->title = $this->get_option('title');
        $this->method_description = "OxaPay Crypto Payment Gateway";
        $this->plugin_name = $this->get_option('plugin_name');
        $this->method_title = "OxaPay";
        $this->form_fields = $this->adminFields();
        $this->has_fields = false;
        $this->init_settings();
        $this->description = $this->get_option('description');
        $this->merchant = $this->get_option('merchant');
        $this->lifetime = $this->get_option('lifetime');
        $this->fee_paid_by_payer = $this->get_option('fee_paid_by_payer');
        $this->mixed_payment = $this->get_option('mixed_payment');
        $this->under_paid_coverage = $this->get_option('under_paid_coverage');
        $this->sandbox = $this->get_option('sandbox');
		$this->icon = plugin_dir_url(dirname(__FILE__)) . "images/logo.svg";

        add_action('woocommerce_api_oxapay', array($this, 'check_oxapay_response'));
        add_action('woocommerce_update_options_payment_gateways_oxapay', array($this, 'process_admin_options'));

    }
    /**
     * @return array
     */

    public function adminFields()
    {
        if (empty($this->get_option('merchant'))) {
            $merchant = 'sandbox';
        }
        else  {
            $merchant = $this->get_option('merchant');

        }      

        return [
            'oxapay_register' => array(
            'description' => 
                '<img src="' . plugin_dir_url(__FILE__) . 'oxapay_picture.png" style="max-width: 500px; height: auto;">' .
                '<p>To start using OxaPay Crypto Payment Gateway, you need to follow a few simple steps.</p>' .
                '<p>1. Create an account on <a href="https://oxapay.com" target="_blank">OxaPay.com</a>.</p>' .
                '<p>2. Navigate to the Merchants API tab in your account.</p>' .
                '<p>3. Click on the "Generate Merchant API Key" button.</p>' .
                '<p>4. Give your Merchant API key a meaningful name for identification purposes.</p>' .
                '<p><a href="https://app.oxapay.com/signup" class="button" target="_blank">' .'Create an account' . '</a></p>',
            'type' => 'title',
            'desc_tip' => false,
            ),
            'enabled' => [
                'title' => 'OxaPay Method',
                'label' => 'Enable',
                'type' => 'checkbox',
                'default' => 'yes',
                'desc_tip' => true
            ],
            'title' => [
                'title' => 'Title',
                'type' => 'text',
                'default' => 'Pay with Oxapay'
            ],
            'description' => [
                'title' => 'Description',
                'type' => 'text',
                'default' => 'Crypto Payment Gateway'
            ],
            'merchant' => [
                'title' => 'Merchant API Key',
                // 'description' => 'You can find your OxaPay Merchant Key in the settings of your personal account.',
                'type' => 'text',
                'custom_attributes' => [
                    'required' => 'required',
                ],

            ],

            'lifetime' => [
                'title' => 'Lifetime',
                'description' => 'Set the expiration time for the payment link in minutes (15-2880).',
                'type' => 'number',
                'desc_tip' => true,
                'default' => '60',
                'custom_attributes' => [
                    'min' => 15,
                    'max' => 2880,
                ],
            ],
            
            'under_paid_coverage' => [
                'title' => 'Underpaid Coverage',
                'description' => 'Underpaid means that if the payer submits a payment that\'s slightly less than the invoiced amount, the invoice will still be accepted (0-60%).',
                'type' => 'number',
                'desc_tip' => true,
                'default' => '0',
                'custom_attributes' => [
                    'min' => 0,
                    'max' => 60,
                ],
            ],
            
            'mixed_payment' => [
                'title' => 'Mixed Payment',
                'description' => 'Enable Mixed Payment to let customers split invoice payments across different cryptocurrencies.',
                'type' => 'select',
                'desc_tip' => true,
                'default' => '0',
                'options' => array(
                    0 => 'No',
                    1 => 'Yes',
                )
            ],
            
            'fee_paid_by_payer' => [
                'title' => 'Fee Paid By Payer',
                'description' => 'If you enable it, it means your customer will cover all the fees for this payment.',
                'type' => 'select',
                'desc_tip' => true,
                'default' => '0',
                'options' => array(
                    '0' => 'No',
                    '1' => 'Yes',
                )
            ],
            
            'sandbox' => [
                'title' => 'Sandbox Mode',
                'description' => 'You can enable this option to test the payment gateway in sandbox mode.',
                'type' => 'select',
                'desc_tip' => true,
                'default' => '0',
                'options' => array(
                    '0' => 'No',
                    '1' => 'Yes',
                )
            ],
        ];

    }
    public function admin_options()
    {
        echo '<h3>'.'OxaPay Payment Gateway'.'</h3>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    /**
     * @param $order_id
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $order->update_status(paymentStatus::WC_STATUS_PENDING);
        $order->save();
        wc_reduce_stock_levels($order_id);
        $returnUrl = $this->get_return_url($order);
        $data = array(
            'lifetime'    => (int)$this->lifetime, 
            "fee_paid_by_payer" => sanitize_text_field($this->fee_paid_by_payer),
            "mixed_payment"=>sanitize_text_field($this->mixed_payment),
            "under_paid_coverage"   => sanitize_text_field($this->under_paid_coverage),
            "sandbox"     => sanitize_text_field($this->sandbox),
            'amount'      => $order->get_total(),                 
            'currency'    => sanitize_text_field(get_woocommerce_currency()), 
            'order_id'     => strval($order_id),                     
            'callback_url' => esc_url(get_site_url(null, "wc-api/oxapay")),  
            'return_url'   => esc_url($returnUrl),                   
            );

        $result = wp_remote_post('https://api.oxapay.com/v1/payment/invoice', [
            'timeout' => 25,
            'body' => wp_json_encode($data),
            'headers' => [
                'merchant_api_key' =>  sanitize_text_field($this->merchant),
                'Content-Type' => 'application/json',
                'origin' => 'oxa-wp-woo-plugin-v-1.0.0',
                'Accept' => 'application/json',
            ],
            'sslverify' => false,
        ]);

        if (is_wp_error($result)) {
            $message = 'Error connecting to OxaPay API: ' . $result->get_error_message() ;
            $order->add_order_note($message);
            $order->update_status(paymentStatus::WC_STATUS_FAIL);
            $order->save();
            return ['result' => 'failure', 'redirect' => $this->get_return_url($order)];
        }
        $pay = json_decode(wp_remote_retrieve_body($result));
        if ($pay->status == 200)
        {
            update_post_meta($order_id, 'oxapay_trackid', $pay->data->payment_url);
            session_write_close();
            return ['result' => 'success', 'redirect' => $pay->data->payment_url];
        } 
        else {
            $message = 'Payment initiation failed: ' . (isset($pay->message) ? $pay->message : 'Unknown error');
            $order->add_order_note($message);
            $order->update_status(paymentStatus::WC_STATUS_FAIL);
            $order->save();
            return ['result' => 'failure', 'redirect' => $this->get_return_url($order)];
        }
    }
    function check_oxapay_response()
		{

			$postData = file_get_contents('php://input');
			$data = json_decode($postData);
            if ($data->type !== 'invoice') {
                http_response_code(400);
                echo 'Invalid data.type';
                exit;
            }

            $apiSecretKey = $this->merchant;
            if (empty($apiSecretKey)) {
                wp_die('Merchant key is missing.');
            }
        
            $hmacHeader = isset($_SERVER['HTTP_HMAC']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HMAC'])) : '';

            if (empty($hmacHeader) || !preg_match('/^[a-f0-9]{128}$/', $hmacHeader)) {
                wp_die('Invalid HMAC header.');
            }

            $calculatedHmac = hash_hmac('sha512', $postData, $apiSecretKey);
            if ($calculatedHmac === $hmacHeader) {
                if (empty($data->order_id)) {
                    http_response_code(400);
                    echo 'orderId is required';
                    exit;
                }
                if (empty($data->status)) {
                    http_response_code(400);
                    echo 'status is required';
                    exit;
                }

                $order = wc_get_order($data->order_id);
                $status = paymentStatus::convertToWoocommerceStatus($data->status);
                $order->set_status($status);
                $order->save();
                if (in_array($status, ['completed'], true)) {
                    $order->add_order_note(
                        "Track Id: {$data->track_id}\nTransaction Hash: {$data->txs[0]->tx_hash}"
                    );
                    WC()->cart->empty_cart();
                }
                                
                echo 'Received payment callback: ' . wp_json_encode($data);
                http_response_code(200);
                echo 'OK';
                exit;

            }
            else {
                http_response_code(400);
                echo 'Invalid HMAC signature';
            }
                                
		}

}