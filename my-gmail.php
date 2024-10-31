<?php
/*
Plugin Name: My GMail
Plugin URI: http://www.yriase.fr/plugins
Author: Hugo Giraud
Author URI: http://www.yriase.fr/
Description: Add a widget to the dashboard to display your unread mails
Version: 0.2
Text Domain: mygmail
*/

if (!class_exists("MyGmail")) {
	class MyGmail {

		public function __construct() {
                    if ( is_admin() ) {
                            add_action('wp_dashboard_setup', array('MyGmail', 'addWidget') );
                            add_action( 'admin_menu',  array('MyGmail', 'addAdminPage') );
                            add_action('wp_ajax_mygmail_widget', array('MyGmail', 'myGmailWidget'));

                            register_activation_hook( __FILE__, array('MyGmail', 'setup') );
                    }
		}

                public function setup() {
                    $options = get_option('mygmail');
                    if($options) {
                        $options['number'] = 5; $options['showSummary'] = true;
                        $options['widgetTitle'] = 'My GMail';
                    } else {
                        $options = array('number'=>5, 'showSummary'=>true, 'widgetTitle'=>'My GMail');
                    }
                    update_option( 'mygmail', $options );
                }

                public function addAdminPage() {
                    $page = add_options_page(__( 'My GMail', 'mygmail' ), __( 'MyGmail', 'mygmail' ), 10, 'mygmail',
                             array('MyGmail', 'adminPage'));
                    add_action( 'admin_head', array('MyGmail', 'addAdminHead') );
                    add_filter( 'ozh_adminmenu_icon_mygmail', array('MyGmail', 'addOzhIcon') );
                }

                public function addAdminHead() { ?>
                    <link rel="stylesheet" href="<?php echo get_bloginfo( 'home' ) . '/' . PLUGINDIR . '/my-gmail/style/admin.css' ?>" type="text/css" media="all" />
                    <script src="<?php echo get_bloginfo( 'home' ) . '/' . PLUGINDIR . '/my-gmail/js/admin.js' ?>" type="text/javascript"></script>
                    <?php
                }

                public function addOzhIcon() {
                   return get_bloginfo( 'home' ) . '/' . PLUGINDIR . '/my-gmail/style/ozh_icon.gif';
                }

                public function adminPage() {
                   ?> <div id="mygmail" class="wrap" >

                       <div id="mygmail-icon" class="icon32"><br/></div>
			<h2><?php _e( 'My GMail Configuration', 'mygmail' ) ?></h2>
                        <?php
                        if(isset($_POST['save'])) {
                            update_option( 'mygmail', $_POST['config'] );
                            ?>
                        <div id="message" class="updated fade">
                            <p>
                                <strong><?php echo __('Your settings have been saved.', 'mygmail') ?></strong>
                            </p>
                        </div>
                        <?php
                        } elseif(isset($_POST['reset'])) {
                            MyGmail::setup();
                        }
                        $options = get_option( 'mygmail' );
                        ?>
                        <p>
                            <?php _e( 'You can configure the maximum number of mail you want to display, your credentials etc...', 'mygmail' ) ?>
                        </p>

                        <form action="" method="post">
                            <h4>Credentials</h4>
                            <table>
                                <tr>
                                    <td><?php _e( 'Mail', 'mygmail' ) ?> : </td>
                                    <td><input type="text" name="config[mail]" value="<?php if($options['mail']) echo $options['mail']; ?>" size="30" /></td>
                                </tr>
                                <tr>
                                    <td><?php _e( 'Password', 'mygmail' ) ?> : </td>
                                    <td><input type="password" name="config[password]" value="<?php if($options['password']) echo $options['password']; ?>" size="30" /></td>
                                </tr>
                            </table>
                            <br /><br />

                            <h4>Options</h4>
                            <?php _e( 'Maximum number of mails', 'mygmail' ) ?> :
                            <select name="config[number]">
                                <?php
                                    for($i = 1; $i <= 20; $i++) {
                                        echo '<option value="'.$i.'"';
                                        if($options['number'] == $i) echo 'selected="selected"';
                                        echo ">".$i."</option>";
                                    }
                                ?>
                            </select>

                            <br />

                            <?php _e( 'Show summary', 'mygmail' ) ?> : <input type="checkbox" name="config[showSummary]" <?php if($options['showSummary']) echo 'checked="checked"' ?> />

                            <br />

                            <?php _e( 'Widget\'s title', 'mygmail' ) ?> : <input type="text" name="config[widgetTitle]" value="<?php if($options['widgetTitle']) echo $options['widgetTitle'] ?>" />

                            <br /><br />

                            <input type="submit" class="button-primary" name="save" value="<?php _e('Save', 'mygmail') ?>" /><br />
                            <input type="submit" class="button-secondary" name="reset" value="<?php _e('Reset', 'mygmail') ?>" />
                          </form>
                        </div>
                  <?php
                }

                public function myGmailWidget() {
                        $options = get_option('mygmail');
                        $content = ''; $unread = 0;
                        if(empty($options['mail']) || empty($options['password'])) {
                            $content = __('You haven\'t configured your account yet.<br />
                                <a href="'.get_bloginfo('home').'/wp-admin/options-general.php?page=mygmail">Configure</a>', 'mygmail');
                        } else {
                        try {
                            $output = wp_remote_retrieve_body(wp_remote_get('https://'.$options['mail'].':'.$options['password'].'@mail.google.com/mail/feed/atom'));
                            $xml = simplexml_load_string($output);
                            $unread = (int) $xml->fullcount;
                            if($unread > 0) {
                                $content .= '<ul>'; $max = 0;
                                foreach($xml->entry as $entry) {
                                    if($max < $options['number']) { $max++;
                                    $content .= '<li><a target="_blank" href="'.$entry->link['href'].'">'.$entry->author->name.' ('.$entry->author->email.') : '.$entry->title.'</a></li>';
                                    if($options['showSummary']) $content .= '<li>'.$entry->summary.'</li>';
                                    $content .= '<li>';
                                    if($max != $unread) $content .= '<hr />';
                                    $content .= '</li>';
                                    } }
                                $content .= '</ul>';
                            } else {
                                $content = __('<p>You haven\'t any unread mail.</p>', 'mygmail');
                            }
                        } catch (Exception $e) {
                           $content = __('An error occured while retrieving your mails.');
                        }
                        }
                      echo json_encode(array('content'=>$content, 'unread'=>$unread));
                      die();
                }

                public function loading() {
                    echo _e('Loading ... ', 'mygmail');
                }

                public function addWidget() {
                    $options = get_option('mygmail');
                    wp_add_dashboard_widget('mygmail_widget', __($options['widgetTitle'], 'mygmail'), array('MyGmail', 'loading'));
                }
	}
}

if (class_exists("MyGmail")) {
	$pa = new MyGmail();
}