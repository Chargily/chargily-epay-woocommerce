<?php
/*
*Plugin Name: Chargily ePay Gateway
*Plugin URI: https://epay.chargily.com/
*Description: Accept CIB and EDAHABIA cards on your WooCommerce store..
*Author: Chargily
*Version: 2.0.9
*/


include ( plugin_dir_path( __FILE__ ) . 'payment_methods/cib.php');
include ( plugin_dir_path( __FILE__ ) . 'payment_methods/edahabia.php');

