<?php
/*
*Plugin Name: Chargily ePay Gateway
*Plugin URI: https://epay.chargily.com/
*Description: Accept CIB and EDAHABIA cards on your WooCommerce store..
*Author: Chargily
*Version: 2.1.3
*/


//include ( plugin_dir_path( __FILE__ ) . 'function.php');
include ( plugin_dir_path( __FILE__ ) . 'payment_methods/cib.php');
include ( plugin_dir_path( __FILE__ ) . 'payment_methods/edahabia.php');

