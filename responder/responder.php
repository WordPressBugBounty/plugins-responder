<?php defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );

/*
  Plugin Name: Responder
  Plugin URI: https://wordpress.org/plugins/responder/
  Author: Rav Messer
  Author URI: https://responder.co.il
  Text Domain: responder
  Domain Path: /languages
  License: GPLv3
  Version: 4.3.4
  Description: תוסף רב מסר מאפשר חיבור קל ופשוט עם התוספים הפופולארים- אלמנטור, אלמנטור פרו, Contact form 7 ו- Pojo Forms.
הנמענים באתר הוורדפרס שלכם, שירשמו דרך הטפסים שתצרו, יועברו באופן אוטומטי לרשימה שתבחרו ברב מסר.
חיבור זה ישדרג את מערך השיווק שלכם ויאפשר לכם לשלוח מסרים רלוונטיים לכל לקוח.
 */

define( 'RAV_MESSER_MIN_PHP_VER', '7.4' );
define( 'RAV_MESSER_VERSION', '4.3.4' );
define( 'RAV_MESSER_MENU_SLUG', 'Responder_PluginSettings' );
define( 'RAV_MESSER_OPTIONS_GROUP', 'Responder_Plugin-settings-group' );
define( 'RAV_MESSER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'RAV_MESSER_PLUGIN_DIR', untrailingslashit( __DIR__ ) );
define( 'RAV_MESSER_PLUGIN_NAME', trim( dirname( plugin_basename( __FILE__ ) ), '/' ) );
define( 'RAV_MESSER_PLUGIN_PATH', realpath( dirname( __FILE__ ) . '/../' ) . '/' );
define( 'RAV_MESSER_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );
define( 'RAV_MESSER_TEMPLATES_DIR', RAV_MESSER_PLUGIN_DIR . '/templates' );

require_once RAV_MESSER_PLUGIN_DIR . '/check_support.php';
require_once RAV_MESSER_PLUGIN_DIR . '/vendor/autoload.php';

// For legacy reasons they have to be included here.
require_once RAV_MESSER_PLUGIN_DIR . '/api/Responder/sdk/OAuthResponder.php';
require_once RAV_MESSER_PLUGIN_DIR . '/api/Responder/sdk/responder_sdk.php';

RavMesser\Plugin\Main::register();
