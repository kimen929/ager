# Auto-Get-Exchange-Rate
A light wordpress plugin that can automatically get exchange rate from Bank of China

## Contents

The Auto-Get-Exchange-Rate includes the following files:

* `README.md`. The file that you’re currently reading.
* `auto_get_exchange_rate.php`. This file is main wordpress plugin file that extract the exchange rate from the website of [Bank of China](http://www.boc.cn/sourcedb/whpj/enindex.html). 
* `simple_html_dom.php`. This file is an open source library that can extract contents from HTML in a single line. See details by click [here](http://simplehtmldom.sourceforge.net/). 

## Features

* The ager plugin is based on the [Simple Html Dom Parser](http://simplehtmldom.sourceforge.net/), which is A HTML DOM parser written in PHP5+ let you manipulate HTML in a very easy way!.
* After this plugin installed in wordpress and activated, the program will automatically check the newest foreign exchange rate *hourly* from [Bank of China](http://www.boc.cn/sourcedb/whpj/enindex.html), and add the result of `CNY/JPY` to the wordpress database table named `$wpdb->prefix.ager_exchange_rate`.
* In controlling the scheduled event, this plugin use [Wp_cron](http://codex.wordpress.org/Function_Reference/wp_cron). Wp_cron is not strictly apply with the time schedule, because it only triggered when some one browse your site and then check the time schedule to decide whether excute the programmed event. However, scheduling hourly triggered event, Wp_cron is enough to perform well.   
* Right now, this plugin only insert CNY/JPY rate to the database. if you need to make more currency pairs usable, you can just copy `get_jpy_rate()` function in auto_get_exchange_rate.php and make another one, let say `get_usd_rate()`, then add one line of insert command in function `input_exchange_rate()`.

## Installation

The Auto-Get-Exchange-Rate plugin can be installed by the default wordpress plugin install tool or the way documented below. 

1. Copy the `ager` directory into your `wp-content/plugins` directory.
2. In the WordPress admin area, navigation to the *Plugins* page
Locate the menu item that reads “Auto-Get-Exchange-Rate”
3. Click on *Activate.*

## Usage

After your plugin installation, simply put shorcode `[cny_jpy_rate]` in your post or any other place you'd like it to displayed. Wordpress will automaticaly change the shorcode to the latest exchange rate of CNY/JPY.

## Good Luck

This plugin is made by [kimen929](http://github.com/kimen929).
Enjoy it and give me feedback. If you find any bugs (in fact I am new to web dev), do not hesitate to point them out, and a pull request would be appreciated!

## License

Because the exchange rate data is from Bank of China, it is strictly prohibited to use the data in *Commercial Purpose*.
If you need to quote the exchange data in your website, please make a statement of the data source.
