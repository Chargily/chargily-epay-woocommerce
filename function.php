<?php


add_filter( 'woocommerce_available_payment_gateways', 'woocs_filter_gateways', 1);

function woocs_filter_gateways($gateway_list)
{
    $include = array(
        'cib' => array('DZD') , // the payment method will only be selected if the currency is dzd
        'edahabia' => array('DZD')) ;
    foreach ($include as $gateway_key => $currencies)
    {
        if (isset($gateway_list[$gateway_key]) AND !in_array(get_option('woocommerce_currency'), $currencies))
        {
            unset($gateway_list[$gateway_key]);
        }
    }
    return $gateway_list;
}