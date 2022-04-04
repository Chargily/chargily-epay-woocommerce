<?php

add_action( 'plugins_loaded', 'cib_init_gateway_class', 0 );
function cib_init_gateway_class() {
    //if condition use to do nothin while WooCommerce is not installed
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
    include_once('cib.php');
    // class add it too WooCommerce
    add_filter( 'woocommerce_payment_gateways', 'cib_add_gateway_class' );
    function cib_add_gateway_class( $methods ) {
        $methods[] = 'WC_Cib_Gateway';
        return $methods;
    }



    class WC_Cib_Gateway extends WC_Payment_Gateway
    {

        function __construct()
        {

            $this->id = 'cib';
            $this->icon = plugins_url( '../assets/cib-logo.png', __FILE__ );
            $this->has_fields = true;
            $this->method_title = 'CIB';
            $this->method_description = 'Payment using CIB card';
            $this->init_form_fields();


            $this->init_settings();
            $this->title = sanitize_textarea_field($this->get_option( 'title' ));
            $this->description = sanitize_textarea_field($this->get_option( 'description' ));
            $this->enabled = sanitize_key($this->get_option( 'enabled' ));
            $this->discount = intval( $this->get_option( 'discount' ) );
            $this->auth_key = sanitize_text_field($this->get_option( 'auth_key' ));
            $this->signature = sanitize_text_field($this->get_option( 'signature' ));
            $this->client = sanitize_text_field($this->get_option( 'client' ));
            $this->back_url =  esc_url_raw($this->get_option( 'back_url' ));
            $this->response_type = $this->get_option( 'response_type' );




            if (is_admin()) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            }
            add_action( 'woocommerce_api_chargily', array( $this, 'webhook' ) );

            if (!function_exists('write_log')) {

                function write_log($log) {
                    if (true === WP_DEBUG) {
                        if (is_array($log) || is_object($log)) {
                            error_log(print_r($log, true));
                        } else {
                            error_log($log);
                        }
                    }
                }

            }

            write_log('THIS IS THE START OF MY CUSTOM DEBUG');


        }

        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable / Disable', 'cib'),
                    'label' => __('Enable this payment gateway', 'cib'),
                    'type' => 'checkbox',
                    'default' => 'no',
                ),
                'title' => array(
                    'title' => __('Title', 'cib'),
                    'type' => 'text',
                    'desc_tip' => __('Payment method title.', 'cib'),
                    'default' => __('Carte CIB', 'cib'),
                    'custom_attributes' => array(
                        'required'       =>  true
                    ),
                ),
                'description' => array(
                    'title' => __('Description', 'cib'),
                    'type' => 'textarea',
                    'desc_tip' => __('sub title of this paiement method', 'cib'),
                    'default' => __('Paiement par la Carte CIB.', 'cib'),
                    'css' => 'max-width:450px;'
                ),
                'auth_key' => array(
                    'title' => __('API key', 'cib'),
                    'type' => 'password',
                    'desc_tip' => __('Get the API Key from your account profil in the platform ePay by Chargily.', 'cib'),
                    'custom_attributes' => array(
                        'required'       =>  true
                    ),
                ),
                'signature' => array(
                    'title' => __('Secret key', 'cib'),
                    'type' => 'password',
                    'desc_tip' => __('Get the Secret Key from your account profil in the platform ePay by Chargily.', 'cib'),
                    'custom_attributes' => array(
                        'required'       =>  true
                    ),
                ),
                /*'client' => array(
                    'title'       => 'Client',
                    'type'        => 'text',
                    'description' => 'This controls the user name which the user send during checkout.',
                ),*/
                'discount' => array(
                    'title'       => 'Discount in %',
                    'type'        => 'number',
                    'default'     =>  0,
                    'custom_attributes' => array(
                        'min'       =>  0,
                        'max'       =>  99,
                    ),
                    'description' => 'If you offer discount for your sales, set the discount number in % (0-99).',
                ),
                'back_url' => array(
                    'title'       => 'Return url',
                    'type'        => 'text',
                    'description' => 'Set the URL to where you will redirect users when the payment finished.',
                    'custom_attributes' => array(
                        'required'       =>  true
                    ),
                ),
                'response_type' => array(
                    'title'       => 'Confirmation status',
                    'type'        => 'select',
                    'options'     => array(
                                        'completed'  => 'completed',
                                        'on-hold' => 'on hold'                                   
                                    ),
                    'description' => 'This Status you will get if payment Succeed.',
                )
            );
        }

        public function process_payment($order_id)
        {
            global $woocommerce;

            $customer_order = new WC_Order($order_id);
            $current_user = wp_get_current_user();
            $environment_url = 'http://epay.chargily.com.dz/api/invoice';

            if (isset($current_user->user_login)
                && isset($order_id)
                && isset($customer_order->order_total)
                && isset($this->discount)
                && isset($this->back_url)){
                $payload = array(
                    "client" => $current_user->user_login,
                    "amount" =>  $customer_order->order_total ,
                    "invoice_number" => $order_id,
                    'discount' => $this->discount,
//                'mode' => WC()->checkout->get_value('billing_payment_method'),
                    'mode' => 'CIB',
                    'comment' => 'Recouverement de facture.',
                    'back_url' => $this->back_url,
                    'webhook_url' => home_url() . '/wc-api/chargily'
                );

                $response = wp_remote_post($environment_url, array(
                    'method' => 'POST',
                    'headers'     => array(
                        'X-Authorization' => $this->auth_key,
                        'Accept' => 'application/json'
                    ),
                    'body' => http_build_query($payload),
                    'timeout' => 90,
                ));
            }
            else{
                $response = '';
                throw new Exception(__('There is missing information in payment parameters. please inform the provider. Sorry for the inconvenience.', 'chargily'));
            }

            if (is_wp_error($response))

                throw new Exception(__('There is issue for connecting payment gateway. Sorry for the inconvenience.', 'chargily'));

            if (empty($response))

                throw new Exception(__('Chargily\'s Response does not contain any data.', 'chargily'));

            $response_body = wp_remote_retrieve_body( $response );
            $data  = json_decode($response_body);

            if (empty($data->checkout_url))

                throw new Exception(__('Chargily\'s Response does not contain any data.', 'chargily'));

            return array(
                'result' => 'success',
                'other' => $order_id,
                'redirect' => $data->checkout_url,
            );


        }


        public function do_ssl_check()
        {
            if ($this->enabled == "yes") {
                if (get_option('woocommerce_force_ssl_checkout') == "no") {
                    $arr = array( 'div' => array(), 'p' => array(), 'strong' => array(), 'a' => array() );
                    echo wp_kses("<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>", $arr);
                }
            }
        }

        public function webhook() {


            $data = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', file_get_contents('php://input')), true );
            //        $data = json_decode(file_get_contents('php://input'));
            $headers = getallheaders();

            $hashedData =  hash_hmac('sha256', json_encode($data) , $this->signature);


                $order_id = isset($data['invoice']['invoice_number']) ? $data['invoice']['invoice_number'] : null;
                $order = wc_get_order( $order_id );

                if($data['invoice']['status'] == 'paid'){
                    $order->payment_complete();
                    $order->reduce_order_stock();
                    $order->update_status( $this->response_type );
                    WC()->cart->empty_cart();
                }

                elseif($data['invoice']['status'] == 'failed'){
                    $order->update_status( 'failed' );
                }elseif( $data['invoice']['status'] == 'canceled'){
        	        $order->update_status( 'cancelled' );
                }


                $status_code = 200;
                if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
                    _doing_it_wrong(
                        __FUNCTION__,
                        sprintf(
                        /* translators: 1: WP_REST_Response, 2: WP_Error */
                            __( 'Return a %1$s or %2$s object from your callback when using the REST API.' ),
                            'WP_REST_Response',
                            'WP_Error'
                        ),
                        '5.5.0'
                    );
                }

                if ( ! headers_sent() ) {
                    header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
                    if ( null !== $status_code ) {
                        status_header( $status_code );
                    }
                }


                if ( wp_doing_ajax() ) {
                    wp_die(
                        '',
                        '',
                        array(
                            'response' => null,
                        )
                    );
                } else {
                    die;
                }

                header( 'HTTP/1.1 200 OK' );

        }

    }
}
