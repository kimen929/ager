<?php

/*
  Plugin Name: Auto-Get-Exchange-Rate
  Plugin URI: http://www.github.com/kimen929/ager/
  Description: This plugin displays the exchange rate of JPY/CNY from Bank Of China automatically.
  Author: kimen929
  Version: 1.0
  Author URI: http://www.github.com/kimen929/
 */

if ( ! defined('ABSPATH') ) {
	die('Please do not load this file directly.');
}


require_once( 'simple_html_dom.php' );

/**
 * Main bootstrap class for Auto Get Exchange Rate
 *
 * @package Auto Get Exchange Rate
 */

class Auto_Get_Exchange_Rate {
    
    private static $_instance;
    
    /**
     * init a new Auto_Get_Exchange_Rate class if not exist.
     * @return instance
     */
    public static function init() {
        if ( !self::$_instance ) {
            self::$_instance = new Auto_Get_Exchange_Rate();
        }

        return self::$_instance;
    }
    
    
    /**
     * construct function of this plugin. 
     * register install, uninstall function, filters, actions and shortcodes.
     * 
     * @return null
     */
    function __construct() {

        register_activation_hook( __FILE__, array($this, 'install') );
        register_deactivation_hook( __FILE__, array($this, 'uninstall') );
        
        add_filter('cron_schedules', array($this, 'cron_update_schedules'));
        
        add_shortcode( 'cny_jpy_rate', array($this, 'get_cny_jpy_rates'));
        
        add_action('input_exchange_rate', array($this, 'input_exchange_rate'));
        //$this->hourly_check();
    }
    
    
    /**
     * Install database and time trigger.
     *
     * @return void
     */
    function install() {
        global $wpdb;
        
        flush_rewrite_rules( false );
        $sql_exchange_rate = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ager_exchange_rate (
                `exchange_rate_id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
                `currency_pair` varchar(60) NOT NULL DEFAULT '',
                `bid_rate` decimal(8,4) NOT NULL, 
                `ask_rate` decimal(8,4) NOT NULL, 
                `cash_bid_rate` decimal(8,4) NOT NULL, 
                `cash_ask_rate` decimal(8,4) NOT NULL,  
                `middle_rate` decimal(8,4) NOT NULL,
                `published_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                `record_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY (`exchange_rate_id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
        $wpdb->query($sql_exchange_rate);
        
        //set hourly checking 
        if( !wp_next_scheduled( 'input_exchange_rate' ) ) {
            wp_schedule_event( time(), 'in_per_ten_minute', 'input_exchange_rate' );
        }
    }
    
    /**
     * Manage task on plugin deactivation
     * Delete database and time trigger.
     * 
     * @return void
     */
    function uninstall() {
        
        global $wpdb;
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix .'ager_exchange_rate'  );
        
        wp_clear_scheduled_hook( 'input_exchange_rate' );
    }
    
    /**
     * This function is for the shortcode of cny_jpy_rate, can use with the parameter "rate".
     * Example [cny_jpy_rate rate="sell"]
     *
     * @return asked exchange rate of CNY/JPY
     */
    function get_cny_jpy_rates($atts){
    
        extract( shortcode_atts( array('rate' => ''), $atts ) );
        global $wpdb;
        $rates = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ager_exchange_rate WHERE currency_pair = 'CNY/JPY' ORDER BY exchange_rate_id DESC LIMIT 1", ARRAY_A);
        
        switch($rate)
        {
            case "buy":
                return $rates['bid_rate'];
                break;
            case "sell":
                return $rates['ask_rate'];
                break;
            case "middle":
                return $rates['middle_rate'];
                break;
            case "cash_buy":
                return $rates['cash_bid_rate'];
                break;
            case "cash_sell":
                return $rates['cash_ask_rate'];
                break;
            default: 
                return $rates['bid_rate']." ".$rates['ask_rate'];
                break;
        }
        
    }
    
    
    
    /**
     * This function is adding the time intervals that can be triggered in every same intervals with Wp_cron.
     * @param type $schedules
     * @return array 
     */
    function cron_update_schedules($schedules ) {
        
        $schedules['in_per_minute'] = array(
            'interval' => 60,
            'display' => __('In every minute')
        );  

        $schedules['in_per_ten_minute'] = array(
            'interval' => 600,
            'display' => __('Once in Ten minutes')
        );  
        return $schedules;
    }
    
    /**
    *Check the website: http://www.boc.cn/sourcedb/whpj/enindex.html to get the real-time exchange rate of Bank Of China
    *
    *This function get the data from website and insert the data to ager_exchange_rate database
    */
    function input_exchange_rate() {
        global $wpdb;
        
        $jpy_exchange_rate = $this->get_jpy_rate();
        
        $sql_insert = "INSERT INTO {$wpdb->prefix}ager_exchange_rate ( `currency_pair`, `bid_rate`, `ask_rate`, `cash_bid_rate`, `cash_ask_rate`, `middle_rate`, `published_time`, `record_time`)"
    . "VALUES ('CNY/JPY', ".$jpy_exchange_rate['buying_rate'].", ".$jpy_exchange_rate['selling_rate'].", ".$jpy_exchange_rate['cash_buying_rate'].", ".$jpy_exchange_rate['cash_selling_rate'].", ".$jpy_exchange_rate['middle_rate'].", '".$jpy_exchange_rate['pub_time']."', now());";
    
        $wpdb->query($sql_insert);
        
    } 

    /**
    *Retrieve info from boc website using simple html dom.
    *
    *Simple Html Dom Parser document and download: http://simplehtmldom.sourceforge.net/
    */
   function get_boc_exchange_rate_table(){
	
	//Get web page by simple html dom parser
	$html = file_get_html( 'http://www.boc.cn/sourcedb/whpj/enindex.html' );
	
	//Control the items in the currency list.
	$allowed_currency = array( 'TWD', 'GBP', 'HKD', 'USD', 'CHF', 'SGD', 'SEK', 'DKK', 'NOK', 'JPY', 'CAD', 'AUD', 'MYR', 'EUR', 'MOP', 'PHP', 'THB', 'NZD', 'KRW', 'RUB' );
	
	//Stores the final data
	$exchange_rates = array();
	
	foreach( $html->find('table tr[align=center]') as $tr ){
            $currency_name = $tr->children(0)->plaintext;
            if( in_array( $currency_name, $allowed_currency ) ){
		$exchange_rates[ $currency_name ]['currency_name'] = $currency_name;
		$exchange_rates[ $currency_name ]['buying_rate'] = $tr->children(1)->plaintext;
		$exchange_rates[ $currency_name ]['cash_buying_rate'] = $tr->children(2)->plaintext;
		$exchange_rates[ $currency_name ]['selling_rate'] = $tr->children(3)->plaintext;
		$exchange_rates[ $currency_name ]['cash_selling_rate'] = $tr->children(4)->plaintext;
		$exchange_rates[ $currency_name ]['middle_rate'] = $tr->children(5)->plaintext;
		$exchange_rates[ $currency_name ]['pub_time'] = str_replace("&nbsp;", '',$tr->children(6)->plaintext);
		try {
			$datetime = new DateTime( $exchange_rates[ $currency_name ]['pub_time'] );
		} catch( Exception $e ){
			echo $e->getMessage();
		}
                    $exchange_rates[ $currency_name ]['pub_time'] = $datetime->format('Y-m-d H:i:s');
            }                    
	}
	return $exchange_rates;
    }
    
    /**
     * This function extract the jpy rate and return it from the rates array of Bank of China.
     * @return CNY/JPY exchange rate
     */
    function get_jpy_rate(){
        $exchange_rates = $this->get_boc_exchange_rate_table();
        return $exchange_rates['JPY'];
    }
}

Auto_Get_Exchange_Rate::init();


?>
