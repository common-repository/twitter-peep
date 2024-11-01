<?php
/**
 * Plugin Name: Twitter Peep
 * Plugin URI: 
 * Description: A widget that properly handles twitter feeds, including @username, #hashtag, and link parsing.  It can even display profile images for the users.  Requires PHP5.
 * Version: 1.1
 * Author: Abhishek Saha
 * Author URI: 
 * License: GPLv2 or later
 * Text Domain: twitter-peep
 */
require_once( 'tlc-transients.php' );
require_once( 'ajaxtown-plugin-framework.php' );
define('ATTP_VERSION', '1.1');

/**
 * Twitter_Peep is the class that handles the main widget.
 */
class Twitter_Peep extends WP_Widget {

    public function Twitter_Peep() {
        $this->_slug = 'twitter-peep';
        $wpTwitterPeep = wpTwitterPeep::getInstance();
        $widget_ops = array(
            'classname' => 'widget_ajaxtown_twitter',
            'description' => __('Follow a Twitter Feed', $wpTwitterPeep->get_slug())
        );
        $control_ops = array(
            'width' => 400,
            'height' => 350,
            'id_base' => 'twitter'
        );
        $name = __('Twitter Peep', $wpTwitterPeep->get_slug());

        $this->WP_Widget('twitter', $name, $widget_ops, $control_ops);
    }

    private function _getInstanceSettings($instance) {
        $wpTwitterPeep = wpTwitterPeep::getInstance();
        return $wpTwitterPeep->getSettings($instance);
    }

    public function form($instance) {
        $instance = $this->_getInstanceSettings($instance);
        $wpTwitterPeep = wpTwitterPeep::getInstance();
        $users = $wpTwitterPeep->get_users_list(true);
        $lists = $wpTwitterPeep->get_lists();
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('username'); ?>"><?php _e('Twitter username:', $this->_slug); ?></label>
            <select id="<?php echo $this->get_field_id('username'); ?>" name="<?php echo $this->get_field_name('username'); ?>">
                <option></option>
                <?php
                $selected = false;
                foreach ($users as $u) {
                    ?>
                    <option value="<?php echo esc_attr(strtolower($u['screen_name'])); ?>"<?php $s = selected(strtolower($u['screen_name']), strtolower($instance['username'])) ?>><?php echo esc_html($u['screen_name']); ?></option>
                    <?php
                    if (!empty($s))
                        $selected = true;
                }
                ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('list'); ?>"><?php _e('Twitter list:', $this->_slug); ?></label>
            <select id="<?php echo $this->get_field_id('list'); ?>" name="<?php echo $this->get_field_name('list'); ?>">
                <option></option>
                <?php
                foreach ($lists as $user => $user_lists) {
                    echo '<optgroup label="' . esc_attr($user) . '">';
                    foreach ($user_lists as $list_id => $list_name) {
                        ?>
                        <option value="<?php echo esc_attr($user . '::' . $list_id); ?>"<?php $s = selected($user . '::' . $list_id, strtolower($instance['list'])) ?>><?php echo esc_html($list_name); ?></option>
                        <?php
                    }
                    echo '</optgroup>';
                }
                ?>
            </select>
        </p>
        <?php
        if (!$selected && !empty($instance['username'])) {
            $query_args = array(
                'action' => 'authorize',
                'screen_name' => $instance['username'],
            );
            $authorize_user_url = wp_nonce_url(add_query_arg($query_args, $wpTwitterPeep->get_options_url()), 'authorize');
            ?>
            <p>
                <a href="<?php echo esc_url($authorize_user_url); ?>" style="color:red;">
                    <?php _e('You need to authorize this account.', $this->_slug); ?>
                </a>
            </p>
            <?php
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Give the feed a title ( optional ):', $this->_slug); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php esc_attr_e($instance['title']); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('items'); ?>"><?php _e('How many items would you like to display?', $this->_slug); ?></label>
            <select id="<?php echo $this->get_field_id('items'); ?>" name="<?php echo $this->get_field_name('items'); ?>">
                <?php
                for ($i = 1; $i <= 20; ++$i) {
                    echo "<option value='$i' " . selected($instance['items'], $i, false) . ">$i</option>";
                }
                ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('avatar'); ?>"><?php _e('Display profile image?', $this->_slug); ?></label>
            <select id="<?php echo $this->get_field_id('avatar'); ?>" name="<?php echo $this->get_field_name('avatar'); ?>">
                <option value=""<?php selected($instance['avatar'], '') ?>><?php _e('Do not show', $this->_slug); ?></option>
                <option value="mini"<?php selected($instance['avatar'], 'mini') ?>><?php _e('Mini - 24px by 24px', $this->_slug); ?></option>
                <option value="normal"<?php selected($instance['avatar'], 'normal') ?>><?php _e('Normal - 48px by 48px', $this->_slug); ?></option>
                <option value="bigger"<?php selected($instance['avatar'], 'bigger') ?>><?php _e('Bigger - 73px by 73px', $this->_slug); ?></option>
                <option value="original"<?php selected($instance['avatar'], 'original') ?>><?php _e('Original', $this->_slug); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('stats'); ?>"><?php _e('Display Stats?', $this->_slug); ?></label>
            <select id="<?php echo $this->get_field_id('stats'); ?>" name="<?php echo $this->get_field_name('stats'); ?>">
                <option value="false"<?php selected($instance['stats'], 'false') ?>><?php _e('No', $this->_slug); ?></option>					
                <option value="true"<?php selected($instance['stats'], 'true') ?>><?php _e('Yes', $this->_slug); ?></option>					
            </select>
        </p>
        <p>
            <input type="hidden" value="false" name="<?php echo $this->get_field_name('showretweets'); ?>" />
            <input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id('showretweets'); ?>" name="<?php echo $this->get_field_name('showretweets'); ?>"<?php checked($instance['showretweets'], 'true'); ?> />
            <label for="<?php echo $this->get_field_id('showretweets'); ?>"><?php _e('Include retweets', $this->_slug); ?></label>
        </p>
        <p>
            <input type="hidden" value="false" name="<?php echo $this->get_field_name('showreplies'); ?>" />
            <input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id('showreplies'); ?>" name="<?php echo $this->get_field_name('showreplies'); ?>"<?php checked($instance['showreplies'], 'true'); ?> />
            <label for="<?php echo $this->get_field_id('showreplies'); ?>"><?php _e('Show @replies', $this->_slug); ?></label>
        </p>
        <p>
            <input type="hidden" value="false" name="<?php echo $this->get_field_name('showinreplyto'); ?>" />
            <input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id('showinreplyto'); ?>" name="<?php echo $this->get_field_name('showinreplyto'); ?>"<?php checked($instance['showinreplyto'], 'true'); ?> />
            <label for="<?php echo $this->get_field_id('showinreplyto'); ?>"><?php _e('Show in-reply-to', $this->_slug); ?></label>
        </p>
        <p>
            <input type="hidden" value="false" name="<?php echo $this->get_field_name('showfrom'); ?>" />
            <input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id('showfrom'); ?>" name="<?php echo $this->get_field_name('showfrom'); ?>"<?php checked($instance['showfrom'], 'true'); ?> />
            <label for="<?php echo $this->get_field_id('showfrom'); ?>"><?php _e('Show sending applications', $this->_slug); ?></label>
        </p>
        <p>
            <input type="hidden" value="false" name="<?php echo $this->get_field_name('showintents'); ?>" />
            <input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id('showintents'); ?>" name="<?php echo $this->get_field_name('showintents'); ?>"<?php checked($instance['showintents'], 'true'); ?> />
            <label for="<?php echo $this->get_field_id('showintents'); ?>"><?php _e('Show Tweet Intents (reply, retweet, favorite)', $this->_slug); ?></label>
        </p>
        <p>
            <input type="hidden" value="false" name="<?php echo $this->get_field_name('showfollow'); ?>" />
            <input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id('showfollow'); ?>" name="<?php echo $this->get_field_name('showfollow'); ?>"<?php checked($instance['showfollow'], 'true'); ?> />
            <label for="<?php echo $this->get_field_id('showfollow'); ?>"><?php _e('Show Follow Link', $this->_slug); ?></label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('errmsg'); ?>"><?php _e('What to display when Twitter is down ( optional ):', $this->_slug); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('errmsg'); ?>" name="<?php echo $this->get_field_name('errmsg'); ?>" type="text" value="<?php esc_attr_e($instance['errmsg']); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('showts'); ?>"><?php _e('Show date/time of Tweet ( rather than 2 ____ ago ):', $this->_slug); ?></label>
            <select id="<?php echo $this->get_field_id('showts'); ?>" name="<?php echo $this->get_field_name('showts'); ?>">
                <option value="0" <?php selected($instance['showts'], '0'); ?>><?php _e('Always', $this->_slug); ?></option>
                <option value="3600" <?php selected($instance['showts'], '3600'); ?>><?php _e('If over an hour old', $this->_slug); ?></option>
                <option value="86400" <?php selected($instance['showts'], '86400'); ?>><?php _e('If over a day old', $this->_slug); ?></option>
                <option value="604800" <?php selected($instance['showts'], '604800'); ?>><?php _e('If over a week old', $this->_slug); ?></option>
                <option value="2592000" <?php selected($instance['showts'], '2592000'); ?>><?php _e('If over a month old', $this->_slug); ?></option>
                <option value="31536000" <?php selected($instance['showts'], '31536000'); ?>><?php _e('If over a year old', $this->_slug); ?></option>
                <option value="-1" <?php selected($instance['showts'], '-1'); ?>><?php _e('Never', $this->_slug); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('dateFormat'); ?>"><?php echo sprintf(__('Format to display the date in, uses <a href="%s">PHP date()</a> format:', $this->_slug), 'http://php.net/date'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('dateFormat'); ?>" name="<?php echo $this->get_field_name('dateFormat'); ?>" type="text" value="<?php esc_attr_e($instance['dateFormat']); ?>" />
        </p>
        <p>
            <input type="hidden" value="false" name="<?php echo $this->get_field_name('targetBlank'); ?>" />
            <input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id('targetBlank'); ?>" name="<?php echo $this->get_field_name('targetBlank'); ?>"<?php checked($instance['targetBlank'], 'true'); ?> />
            <label for="<?php echo $this->get_field_id('targetBlank'); ?>"><?php _e('Open links in a new window', $this->_slug); ?></label>
        </p>	
        
        <p>
            <input type="hidden" value="false" name="<?php echo $this->get_field_name('nicescroll'); ?>" />
            <input class="checkbox" type="checkbox" value="true" id="<?php echo $this->get_field_id('nicescroll'); ?>" name="<?php echo $this->get_field_name('nicescroll'); ?>"<?php checked($instance['nicescroll'], 'true'); ?> />
            <label for="<?php echo $this->get_field_id('nicescroll'); ?>"><?php _e('Show nice scroll', $this->_slug); ?></label>
        </p>

        <script type="text/javascript">
            jQuery('#<?php echo $this->get_field_id('username') ?>').on('change', function() {
                jQuery('#<?php echo $this->get_field_id('list') ?>').val(0);
            });
            jQuery('#<?php echo $this->get_field_id('list') ?>').on('change', function() {
                jQuery('#<?php echo $this->get_field_id('username') ?>').val(0);
            });
        </script>
        <?php
        return;
    }

    public function update($new_instance, $old_instance) {
        $instance = $this->_getInstanceSettings($new_instance);

        // Clean up the free-form areas
        $instance['title'] = stripslashes($new_instance['title']);
        $instance['errmsg'] = stripslashes($new_instance['errmsg']);

        $instance['skin'] = stripslashes($new_instance['skin']);

        // If the current user isn't allowed to use unfiltered HTML, filter it
        if (!current_user_can('unfiltered_html')) {
            $instance['title'] = strip_tags($new_instance['title']);
            $instance['errmsg'] = strip_tags($new_instance['errmsg']);
        }

        return $instance;
    }

    public function flush_widget_cache() {
        wp_cache_delete('widget_twitter_widget_pro', 'widget');
    }

    public function widget($args, $instance) {
        $instance = $this->_getInstanceSettings($instance);
        $wpTwitterPeep = wpTwitterPeep::getInstance();
        echo $wpTwitterPeep->display(wp_parse_args($instance, $args));
    }

}

/**
 * wpTwitterPeep is the class that handles everything outside the widget. This
 * includes filters that modify tweet content for things like linked usernames.
 * It also helps us avoid name collisions.
 */
class wpTwitterPeep extends AjaxtownPlugin {

    /**
     * @var wpTwitter
     */
    private $_wp_twitter_oauth;

    /**
     * @var wpTwitterPeep - Static property to hold our singleton instance
     */
    static $instance = false;

    protected function _init() {
        require_once( 'lib/wp-twitter.php' );

        $this->_hook = 'twitterWidget';
        $this->_file = plugin_basename(__FILE__);
        $this->_pageTitle = __('Twitter Peep', $this->_slug);
        $this->_menuTitle = __('Twitter Peep', $this->_slug);
        $this->_accessLevel = 'manage_options';
        $this->_optionGroup = 'attp-options';
        $this->_optionNames = array('attp');
        $this->_optionCallbacks = array();
        $this->_slug = 'twitter-peep';
        $this->_paypalButtonId = '9993090';

        /**
         * Add filters and actions
         */
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_notices', array($this, 'show_messages'));
        add_action('widgets_init', array($this, 'register'), 11);
        add_filter('widget_twitter_content', array($this, 'linkTwitterUsers'));
        add_filter('widget_twitter_content', array($this, 'linkUrls'));
        add_filter('widget_twitter_content', array($this, 'linkHashtags'));
        add_filter('widget_twitter_content', 'convert_chars');
        add_filter($this->_slug . '-opt-attp', array($this, 'filterSettings'));
        add_filter($this->_slug . '-opt-attp-authed-users', array($this, 'authed_users_option'));
        add_shortcode('twitter-peep', array($this, 'handleShortcodes'));
        wp_register_style('light-css', plugins_url('/skins/light.css', __FILE__));
        wp_register_style('font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css');
        wp_register_style('jquery-ui-css', '//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css');
        wp_register_script('jquery-ui-js', '//code.jquery.com/ui/1.10.4/jquery-ui.js');
        wp_register_script('nice-scroll', plugins_url('/js/jquery.nicescroll.js', __FILE__));
        wp_register_style('admin-style',plugins_url('/skins/admin.css', __FILE__));
        wp_register_script('custom-js', plugins_url('/js/script.js', __FILE__));
        
        $attp_version = get_option('attp_version');
        if (ATTP_VERSION != $attp_version)
            update_option('attp_version', ATTP_VERSION);
    }

    protected function _post_settings_init() {
        $oauth_settings = array(
            'consumer-key' => $this->_settings['attp']['consumer-key'],
            'consumer-secret' => $this->_settings['attp']['consumer-secret'],
        );
        $this->_wp_twitter_oauth = new wpTwitter($oauth_settings);

        // We want to fill 'attp-authed-users' but not overwrite them when saving
        $this->_settings['attp-authed-users'] = apply_filters($this->_slug . '-opt-attp-authed-users', get_option('attp-authed-users'));
    }

    /**
     * Function to instantiate our class and make it a singleton
     */
    public static function getInstance() {
        if (!self::$instance)
            self::$instance = new self;

        return self::$instance;
    }

    public function get_slug() {
        return $this->_slug;
    }

    public function handle_actions() {
        if (empty($_GET['action']) || empty($_GET['page']) || $_GET['page'] != $this->_hook)
            return;

        if ('clear-locks' == $_GET['action']) {
            check_admin_referer('clear-locks');
            $redirect_args = array('message' => strtolower($_GET['action']));
            global $wpdb;
            $locks_q = "DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '_transient_tlc_up__attp%'";
            $redirect_args['locks_cleared'] = $wpdb->query($locks_q);
            wp_safe_redirect(add_query_arg($redirect_args, remove_query_arg(array('action', '_wpnonce'))));
            exit;
        }
        
        if ('clear-cache' == $_GET['action']) {
            check_admin_referer('clear-cache');
            $redirect_args = array('message' => strtolower($_GET['action']));
            global $wpdb;
            //saha
            $cache_q = "DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '_transient_tlc__%'";
            $redirect_args['cache_cleared'] = $wpdb->query($cache_q);
            wp_safe_redirect(add_query_arg($redirect_args, remove_query_arg(array('action', '_wpnonce'))));
            exit;
        }
        
        if ('remove' == $_GET['action']) {
            check_admin_referer('remove-' . $_GET['screen_name']);

            $redirect_args = array(
                'message' => 'removed',
                'removed' => '',
            );
            unset($this->_settings['attp-authed-users'][strtolower($_GET['screen_name'])]);
            if (update_option('attp-authed-users', $this->_settings['attp-authed-users']))
                ;
            $redirect_args['removed'] = $_GET['screen_name'];

            wp_safe_redirect(add_query_arg($redirect_args, $this->get_options_url()));
            exit;
        }
        if ('authorize' == $_GET['action']) {
            check_admin_referer('authorize');
            $auth_redirect = add_query_arg(array('action' => 'authorized'), $this->get_options_url());
            $token = $this->_wp_twitter_oauth->getRequestToken($auth_redirect);
            if (is_wp_error($token)) {
                $this->_error = $token;
                return;
            }
            update_option('_attp_request_token_' . $token['nonce'], $token);
            $screen_name = empty($_GET['screen_name']) ? '' : $_GET['screen_name'];
            wp_redirect($this->_wp_twitter_oauth->get_authorize_url($screen_name));
            exit;
        }
        if ('authorized' == $_GET['action']) {
            $redirect_args = array(
                'message' => strtolower($_GET['action']),
                'authorized' => '',
            );
            if (empty($_GET['oauth_verifier']) || empty($_GET['nonce']))
                wp_safe_redirect(add_query_arg($redirect_args, $this->get_options_url()));

            $this->_wp_twitter_oauth->set_token(get_option('_attp_request_token_' . $_GET['nonce']));
            delete_option('_attp_request_token_' . $_GET['nonce']);

            $token = $this->_wp_twitter_oauth->get_access_token($_GET['oauth_verifier']);
            if (!is_wp_error($token)) {
                $this->_settings['attp-authed-users'][strtolower($token['screen_name'])] = $token;
                update_option('attp-authed-users', $this->_settings['attp-authed-users']);

                $redirect_args['authorized'] = $token['screen_name'];
            }
            wp_safe_redirect(add_query_arg($redirect_args, $this->get_options_url()));
            exit;
        }
    }

    public function show_messages() {
        if (!empty($_GET['message'])) {
            if ('clear-locks' == $_GET['message']) {
                if (empty($_GET['locks_cleared']) || 0 == $_GET['locks_cleared'])
                    $msg = __('There were no locks to clear!', $this->_slug);
                else
                    $msg = sprintf(_n('Successfully cleared %d lock.', 'Successfully cleared %d locks.', $_GET['locks_cleared'], $this->_slug), $_GET['locks_cleared']);
            } elseif('clear-cache' == $_GET['message']) {
                if (empty($_GET['cache_cleared']) || 0 == $_GET['cache_cleared'])
                    $msg = __('Cache was already empty!', $this->_slug);
                else
                    $msg = sprintf(_n('Successfully cleared cache of %d instance.', 'Successfully cleared cache of %d instances.', $_GET['cache_cleared'], $this->_slug), $_GET['cache_cleared']);
            } elseif ('authorized' == $_GET['message']) {
                if (!empty($_GET['authorized']))
                    $msg = sprintf(__('Successfully authorized @%s', $this->_slug), $_GET['authorized']);
                else
                    $msg = __('There was a problem authorizing your account.', $this->_slug);
            } elseif ('removed' == $_GET['message']) {
                if (!empty($_GET['removed']))
                    $msg = sprintf(__('Successfully removed @%s', $this->_slug), $_GET['removed']);
                else
                    $msg = __('There was a problem removing your account.', $this->_slug);
            }
            if (!empty($msg))
                echo "<div class='updated'><p>" . esc_html($msg) . '</p></div>';
        }

        if (!empty($this->_error) && is_wp_error($this->_error)) {
            $msg = '<p>' . implode('</p><p>', $this->_error->get_error_messages()) . '</p>';
            echo '<div class="error">' . $msg . '</div>';
        }

        if (empty($this->_settings['attp']['consumer-key']) || empty($this->_settings['attp']['consumer-secret'])) {
            $msg = sprintf(__('You need to <a href="%s">set up your Twitter app keys</a>.', $this->_slug), $this->get_options_url());
            echo '<div class="error"><p>' . $msg . '</p></div>';
        }

        if (empty($this->_settings['attp-authed-users'])) {
            $msg = sprintf(__('You need to <a href="%s">authorize your Twitter accounts</a>.', $this->_slug), $this->get_options_url());
            echo '<div class="error"><p>' . $msg . '</p></div>';
        }
    }

    public function add_options_meta_boxes() {

        add_meta_box($this->_slug . '-meta', __('Twitter Peep Settings', $this->_slug), array($this, 'meta_box'), 'ajaxtown-' . $this->_slug, 'main');

    }

    public function meta_box() {
        $authorize_url = wp_nonce_url(add_query_arg(array('action' => 'authorize')), 'authorize');


        /* oAuth Metabox */
        ?>
        <div id="tabs">
            <ul>
                <li><a href="#general-meta">Add</a></li>
                <li><a href="#oAuth-meta">Active Accounts</a></li>                
                <li><a href="#shortcodes-meta">Shortcodes</a></li>
            </ul>
            <div id="oAuth-meta">
                <table class="widefat">
                    <thead>
                        <tr valign="top">
                            <th scope="row">
                                <?php _e('Username', $this->_slug); ?>
                            </th>
                            <th scope="row">
                                <?php _e('Lists Rate Usage', $this->_slug); ?>
                            </th>
                            <th scope="row">
                                <?php _e('Statuses Rate Usage', $this->_slug); ?>
                            </th>
                        </tr>
                    </thead>
                    <?php
                    foreach ($this->_settings['attp-authed-users'] as $u) {
                        $this->_wp_twitter_oauth->set_token($u);
                        $rates = $this->_wp_twitter_oauth->send_authed_request('application/rate_limit_status', 'GET', array('resources' => 'statuses,lists'));
                        $style = $auth_link = '';
                        if (is_wp_error($rates)) {
                            $query_args = array(
                                'action' => 'authorize',
                                'screen_name' => $u['screen_name'],
                            );
                            $authorize_user_url = wp_nonce_url(add_query_arg($query_args), 'authorize');
                            $style = 'color:red;';
                            $auth_link = ' - <a href="' . esc_url($authorize_user_url) . '">' . __('Reauthorize', $this->_slug) . '</a>';
                        }
                        $query_args = array(
                            'action' => 'remove',
                            'screen_name' => $u['screen_name'],
                        );
                        $remove_user_url = wp_nonce_url(add_query_arg($query_args), 'remove-' . $u['screen_name']);
                        ?>
                        <tr valign="top">
                            <th scope="row" style="<?php echo esc_attr($style); ?>">
                                <strong>@<?php echo esc_html($u['screen_name']) . $auth_link; ?></strong>
                                <br /><a href="<?php echo esc_url($remove_user_url) ?>"><?php _e('Remove', $this->_slug) ?></a>
                            </th>
                            <?php
                            if (!is_wp_error($rates)) {
                                $display_rates = array(
                                    __('Lists', $this->_slug) => $rates->resources->lists->{'/lists/statuses'},
                                    __('Statuses', $this->_slug) => $rates->resources->statuses->{'/statuses/user_timeline'},
                                );
                                foreach ($display_rates as $title => $rate) {
                                    ?>
                                    <td>
                                        <strong><?php echo esc_html($title); ?></strong>
                                        <p>
                                            <?php echo sprintf(__('Used: %d', $this->_slug), $rate->limit - $rate->remaining); ?><br />
                                            <?php echo sprintf(__('Remaining: %d', $this->_slug), $rate->remaining); ?><br />
                                            <?php
                                            $minutes = ceil(( $rate->reset - gmdate('U') ) / 60);
                                            echo sprintf(_n('Limits reset in: %d minutes', 'Limits reset in: %d minutes', $minutes, $this->_slug), $minutes);
                                            ?><br />
                                            <small><?php _e('This is overall usage, not just usage from Twitter Peep', $this->_slug); ?></small>
                                        </p>
                                    </td>
                                    <?php
                                }
                            } else {
                                ?>
                                <td>
                                    <p><?php _e('There was an error checking your rate limit.', $this->_slug); ?></p>
                                </td>
                                <td>
                                    <p><?php _e('There was an error checking your rate limit.', $this->_slug); ?></p>
                                </td>
                                <?php
                            }
                            ?>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                <?php
                if (empty($this->_settings['attp']['consumer-key']) || empty($this->_settings['attp']['consumer-secret'])) {
                    ?>
                    <p>
                        <strong><?php _e('You need to fill in the Consumer key and Consumer secret before you can authorize accounts.', $this->_slug) ?></strong>
                    </p>
                    <?php
                } else {
                    ?>
                    <p>
                        <a href="<?php echo esc_url($authorize_url); ?>" class="button button-small secondary"><?php _e('Authorize New Account', $this->_slug); ?></a>
                    </p>

                    <?php
                }
                ?>
            </div>
            <?php
            /* General settings */
            
            $clear_locks_url = wp_nonce_url(add_query_arg(array('action' => 'clear-locks')), 'clear-locks');
            $clear_cache_url = wp_nonce_url(add_query_arg(array('action' => 'clear-cache')), 'clear-cache');
            ?>
            <div id="general-meta">

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="attp_consumer_key"><?php _e('Consumer key', $this->_slug); ?></label>
                        </th>
                        <td>
                            <input id="attp_consumer_key" name="attp[consumer-key]" type="text" class="regular-text code" value="<?php esc_attr_e($this->_settings['attp']['consumer-key']); ?>" size="40" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="attp_consumer_secret"><?php _e('Consumer secret', $this->_slug); ?></label>
                        </th>
                        <td>
                            <input id="attp_consumer_secret" name="attp[consumer-secret]" type="text" class="regular-text code" value="<?php esc_attr_e($this->_settings['attp']['consumer-secret']); ?>" size="40" />
                        </td>
                    </tr>
        <?php
        if (empty($this->_settings['attp']['consumer-key']) || empty($this->_settings['attp']['consumer-secret'])) {
            ?>
                        <tr valign="top">
                            <th scope="row">&nbsp;</th>
                            <td>
                                <strong><?php _e('Directions to get the Consumer Key and Consumer Secret', $this->_slug) ?></strong>
                                <ol>
                                    <li><a href="https://dev.twitter.com/apps/new"><?php _e('Add a new Twitter application', $this->_slug) ?></a></li>
                                    <li><?php _e("Fill in Name, Description, Website, and Callback URL (don't leave any blank) with anything you want") ?></a></li>
                                    <li><?php _e("Agree to rules, fill out captcha, and submit your application") ?></a></li>
                                    <li><?php _e("Copy the Consumer key and Consumer secret into the fields above") ?></a></li>
                                    <li><?php _e("Click the Update Options button at the bottom of this page") ?></a></li>
                                </ol>
                            </td>
                        </tr>
            <?php
        }
        ?>
    <!-- This is still in beta. In my server this gave problems. Maybe i am using shared server.
         Will uncomment this if other users are facing problems.
                    <tr>
                        <th scope="row">
        <?php _e("Clear Update Locks", $this->_slug); ?>
                        </th>
                        <td>
                            <a href="<?php echo esc_url($clear_locks_url); ?>"><?php _e('Clear Update Locks', $this->_slug); ?></a><br />
                            <small><?php _e("A small percentage of servers seem to have issues where an update lock isn't getting cleared.  If you're experiencing issues with your feed not updating, try clearing the update locks.", $this->_slug); ?></small>
                        </td>
                    </tr>-->
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e("Clear Cache", $this->_slug); ?></label>
                        </th>
                        <td>
                            <a class="button button-small secondary" href="<?php echo esc_url($clear_cache_url); ?>"><?php _e('Clear Cache', $this->_slug); ?></a><br />
                            <small><?php _e("This will be usefull while setting up Twitter Peep. You will have to remove cache to see the changes. After everything is set, this wont be required at all.", $this->_slug); ?></small>
                        </td>
                    </tr>
                </table>
            </div>
        <?php
        
        /* Shortcodes */
        
        $users = $this->get_users_list(true);
        $lists = $this->get_lists();
        ?>
            <div id="shortcodes-meta">
                <p><?php _e('These settings are the default for the shortcodes and all of them can be overridden by specifying a different value in the shortcode itself.  All settings for widgets are locate in the individual widget.', $this->_slug) ?></p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="attp_username"><?php _e('Twitter username:', $this->_slug); ?></label>
                        </th>
                        <td>
                            <select id="attp_username" name="attp[username]">
                                <option></option>
        <?php
        $selected = false;
        foreach ($users as $u) {
            ?>
                                    <option value="<?php echo esc_attr(strtolower($u['screen_name'])); ?>"<?php $s = selected(strtolower($u['screen_name']), strtolower($this->_settings['attp']['username'])) ?>><?php echo esc_html($u['screen_name']); ?></option>
                                    <?php
                                    if (!empty($s))
                                        $selected = true;
                                }
                                ?>
                            </select>
                                <?php
                                if (!$selected && !empty($this->_settings['attp']['username'])) {
                                    $query_args = array(
                                        'action' => 'authorize',
                                        'screen_name' => $this->_settings['attp']['username'],
                                    );
                                    $authorize_user_url = wp_nonce_url(add_query_arg($query_args, $this->get_options_url()), 'authorize');
                                    ?>
                                <p>
                                    <a href="<?php echo esc_url($authorize_user_url); ?>" style="color:red;">
            <?php _e('You need to authorize this account.', $this->_slug); ?>
                                    </a>
                                </p>
            <?php
        }
        ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="attp_list"><?php _e('Twitter list:', $this->_slug); ?></label>
                        </th>
                        <td>
                            <select id="attp_list" name="attp[list]">
                                <option></option>
        <?php
        foreach ($lists as $user => $user_lists) {
            echo '<optgroup label="' . esc_attr($user) . '">';
            foreach ($user_lists as $list_id => $list_name) {
                ?>
                                        <option value="<?php echo esc_attr($user . '::' . $list_id); ?>"<?php $s = selected($user . '::' . $list_id, strtolower($this->_settings['attp']['list'])) ?>><?php echo esc_html($list_name); ?></option>
                                        <?php
                                    }
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="attp_title"><?php _e('Give the feed a title ( optional ):', $this->_slug); ?></label>
                        </th>
                        <td>
                            <input id="attp_title" name="attp[title]" type="text" class="regular-text code" value="<?php esc_attr_e($this->_settings['attp']['title']); ?>" size="40" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="attp_items"><?php _e('How many items would you like to display?', $this->_slug); ?></label>
                        </th>
                        <td>
                            <select id="attp_items" name="attp[items]">
        <?php
        for ($i = 1; $i <= 20; ++$i) {
            echo "<option value='$i' " . selected($this->_settings['attp']['items'], $i, false) . ">$i</option>";
        }
        ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="attp_avatar"><?php _e('Display profile image?', $this->_slug); ?></label>
                        </th>
                        <td>
                            <select id="attp_avatar" name="attp[avatar]">
                                <option value=""<?php selected($this->_settings['attp']['avatar'], '') ?>><?php _e('Do not show', $this->_slug); ?></option>
                                <option value="mini"<?php selected($this->_settings['attp']['avatar'], 'mini') ?>><?php _e('Mini - 24px by 24px', $this->_slug); ?></option>
                                <option value="normal"<?php selected($this->_settings['attp']['avatar'], 'normal') ?>><?php _e('Normal - 48px by 48px', $this->_slug); ?></option>
                                <option value="bigger"<?php selected($this->_settings['attp']['avatar'], 'bigger') ?>><?php _e('Bigger - 73px by 73px', $this->_slug); ?></option>
                                <option value="original"<?php selected($this->_settings['attp']['avatar'], 'original') ?>><?php _e('Original', $this->_slug); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="attp_stats"><?php _e('Display stats ?', $this->_slug); ?></label>
                        </th>
                        <td>
                            <select id="attp_stats" name="attp[stats]">
                                <option value="false"<?php selected($this->_settings['attp']['stats'], 'false') ?>><?php _e('No', $this->_slug); ?></option>
                                <option value="true"<?php selected($this->_settings['attp']['stats'], 'true') ?>><?php _e('Yes', $this->_slug); ?></option>                                
                            </select>
                            
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="attp_errmsg"><?php _e('What to display when Twitter is down ( optional ):', $this->_slug); ?></label>
                        </th>
                        <td>
                            <input id="attp_errmsg" name="attp[errmsg]" type="text" class="regular-text code" value="<?php esc_attr_e($this->_settings['attp']['errmsg']); ?>" size="40" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="attp_showts"><?php _e('Show date/time of Tweet ( rather than 2 ____ ago ):', $this->_slug); ?></label>
                        </th>
                        <td>
                            <select id="attp_showts" name="attp[showts]">
                                <option value="0" <?php selected($this->_settings['attp']['showts'], '0'); ?>><?php _e('Always', $this->_slug); ?></option>
                                <option value="3600" <?php selected($this->_settings['attp']['showts'], '3600'); ?>><?php _e('If over an hour old', $this->_slug); ?></option>
                                <option value="86400" <?php selected($this->_settings['attp']['showts'], '86400'); ?>><?php _e('If over a day old', $this->_slug); ?></option>
                                <option value="604800" <?php selected($this->_settings['attp']['showts'], '604800'); ?>><?php _e('If over a week old', $this->_slug); ?></option>
                                <option value="2592000" <?php selected($this->_settings['attp']['showts'], '2592000'); ?>><?php _e('If over a month old', $this->_slug); ?></option>
                                <option value="31536000" <?php selected($this->_settings['attp']['showts'], '31536000'); ?>><?php _e('If over a year old', $this->_slug); ?></option>
                                <option value="-1" <?php selected($this->_settings['attp']['showts'], '-1'); ?>><?php _e('Never', $this->_slug); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="attp_dateFormat"><?php echo sprintf(__('Format to display the date in, uses <a href="%s">PHP date()</a> format:', $this->_slug), 'http://php.net/date'); ?></label>
                        </th>
                        <td>
                            <input id="attp_dateFormat" name="attp[dateFormat]" type="text" class="regular-text code" value="<?php esc_attr_e($this->_settings['attp']['dateFormat']); ?>" size="40" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label><?php _e("Other Setting:", $this->_slug); ?></label>
                        </th>
                        <td>
                            <input type="hidden" value="false" name="attp[showretweets]" />
                            <input class="checkbox" type="checkbox" value="true" id="attp_showretweets" name="attp[showretweets]"<?php checked($this->_settings['attp']['showretweets'], 'true'); ?> />
                            <label for="attp_showretweets"><?php _e('Include retweets', $this->_slug); ?></label>
                            <br />
                            <input type="hidden" value="false" name="attp[showreplies]" />
                            <input class="checkbox" type="checkbox" value="true" id="attp_showreplies" name="attp[showreplies]"<?php checked($this->_settings['attp']['showreplies'], 'true'); ?> />
                            <label for="attp_showreplies"><?php _e('Show @replies', $this->_slug); ?></label>
                            <br />
                            <input type="hidden" value="false" name="attp[showfrom]" />
                            <input class="checkbox" type="checkbox" value="true" id="attp_showfrom" name="attp[showfrom]"<?php checked($this->_settings['attp']['showfrom'], 'true'); ?> />
                            <label for="attp_showfrom"><?php _e('Show sending applications', $this->_slug); ?></label>
                            <br />
                            <input type="hidden" value="false" name="attp[showintents]" />
                            <input class="checkbox" type="checkbox" value="true" id="attp_showintents" name="attp[showintents]"<?php checked($this->_settings['attp']['showintents'], 'true'); ?> />
                            <label for="attp_showintents"><?php _e('Show Tweet Intents (reply, retweet, favorite)', $this->_slug); ?></label>
                            <br />
                            <input type="hidden" value="false" name="attp[showfollow]" />
                            <input class="checkbox" type="checkbox" value="true" id="attp_showfollow" name="attp[showfollow]"<?php checked($this->_settings['attp']['showfollow'], 'true'); ?> />
                            <label for="attp_showfollow"><?php _e('Show Follow Link', $this->_slug); ?></label>
                            <br />
                            <input type="hidden" value="false" name="attp[targetBlank]" />
                            <input class="checkbox" type="checkbox" value="true" id="attp_targetBlank" name="attp[targetBlank]"<?php checked($this->_settings['attp']['targetBlank'], 'true'); ?> />
                            <label for="attp_targetBlank"><?php _e('Open links in a new window', $this->_slug); ?></label>

                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Replace @username with a link to that twitter user
     *
     * @param string $text - Tweet text
     * @return string - Tweet text with @replies linked
     */
    public function linkTwitterUsers($text) {
        $text = preg_replace_callback('/(^|\s)@(\w+)/i', array($this, '_linkTwitterUsersCallback'), $text);
        return $text;
    }

    private function _linkTwitterUsersCallback($matches) {
        $linkAttrs = array(
            'href' => 'http://twitter.com/' . urlencode($matches[2]),
            'class' => 'twitter-user'
        );
        return $matches[1] . $this->_buildLink('@' . $matches[2], $linkAttrs);
    }

    /**
     * Replace #hashtag with a link to twitter.com for that hashtag
     *
     * @param string $text - Tweet text
     * @return string - Tweet text with #hashtags linked
     */
    public function linkHashtags($text) {
        $text = preg_replace_callback('/(^|\s)(#[\w\x{00C0}-\x{00D6}\x{00D8}-\x{00F6}\x{00F8}-\x{00FF}]+)/iu', array($this, '_linkHashtagsCallback'), $text);
        return $text;
    }

    /**
     * Replace #hashtag with a link to twitter.com for that hashtag
     *
     * @param array $matches - Tweet text
     * @return string - Tweet text with #hashtags linked
     */
    private function _linkHashtagsCallback($matches) {
        $linkAttrs = array(
            'href' => 'http://twitter.com/search?q=' . urlencode($matches[2]),
            'class' => 'twitter-hashtag'
        );
        return $matches[1] . $this->_buildLink($matches[2], $linkAttrs);
    }

    /**
     * Turn URLs into links
     *
     * @param string $text - Tweet text
     * @return string - Tweet text with URLs repalced with links
     */
    public function linkUrls($text) {
        $text = " {$text} "; // Pad with whitespace to simplify the regexes

        $url_clickable = '~
			([\\s(<.,;:!?])                                        # 1: Leading whitespace, or punctuation
			(                                                      # 2: URL
				[\\w]{1,20}+://                                # Scheme and hier-part prefix
				(?=\S{1,2000}\s)                               # Limit to URLs less than about 2000 characters long
				[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]*+         # Non-punctuation URL character
				(?:                                            # Unroll the Loop: Only allow puctuation URL character if followed by a non-punctuation URL character
					[\'.,;:!?)]                            # Punctuation URL character
					[\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]++ # Non-punctuation URL character
				)*
			)
			(\)?)                                                  # 3: Trailing closing parenthesis (for parethesis balancing post processing)
		~xS';
        // The regex is a non-anchored pattern and does not have a single fixed starting character.
        // Tell PCRE to spend more time optimizing since, when used on a page load, it will probably be used several times.

        $text = preg_replace_callback($url_clickable, array($this, '_make_url_clickable_cb'), $text);

        $text = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]+)#is', array($this, '_make_web_ftp_clickable_cb'), $text);
        $text = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', array($this, '_make_email_clickable_cb'), $text);

        $text = substr($text, 1, -1); // Remove our whitespace padding.

        return $text;
    }

    function _make_web_ftp_clickable_cb($matches) {
        $ret = '';
        $dest = $matches[2];
        $dest = 'http://' . $dest;
        $dest = esc_url($dest);
        if (empty($dest))
            return $matches[0];

        // removed trailing [.,;:)] from URL
        if (in_array(substr($dest, -1), array('.', ',', ';', ':', ')')) === true) {
            $ret = substr($dest, -1);
            $dest = substr($dest, 0, strlen($dest) - 1);
        }
        $linkAttrs = array(
            'href' => $dest
        );
        return $matches[1] . $this->_buildLink($dest, $linkAttrs) . $ret;
    }

    private function _make_email_clickable_cb($matches) {
        $email = $matches[2] . '@' . $matches[3];
        $linkAttrs = array(
            'href' => 'mailto:' . $email
        );
        return $matches[1] . $this->_buildLink($email, $linkAttrs);
    }

    private function _make_url_clickable_cb($matches) {
        $linkAttrs = array(
            'href' => $matches[2]
        );
        return $matches[1] . $this->_buildLink($matches[2], $linkAttrs);
    }

    private function _notEmpty($v) {
        return !( empty($v) );
    }

    private function _buildLink($text, $attributes = array(), $noFilter = false) {
        $attributes = array_filter(wp_parse_args($attributes), array($this, '_notEmpty'));
        $attributes = apply_filters('widget_twitter_link_attributes', $attributes);
        $attributes = wp_parse_args($attributes);

        $text = apply_filters('widget_twitter_link_text', $text);
        $noFilter = apply_filters('widget_twitter_link_nofilter', $noFilter);
        $link = '<a';
        foreach ($attributes as $name => $value) {
            $link .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
        }
        $link .= '>';
        if ($noFilter)
            $link .= $text;
        else
            $link .= esc_html($text);

        $link .= '</a>';
        return $link;
    }

    public function register() {
        // Fix conflict with Jetpack by disabling their Twitter widget
        unregister_widget('Wickett_Twitter_Widget');
        register_widget('Twitter_Peep');
    }

    public function targetBlank($attributes) {
        $attributes['target'] = '_blank';
        return $attributes;
    }

    public function display($args) {

        wp_enqueue_style('font-awesome');
        wp_enqueue_script('jquery');
        wp_enqueue_script('nice-scroll');
        wp_enqueue_style('light-css');


        $args = wp_parse_args($args);

        if ('true' == $args['targetBlank'])
            add_filter('widget_twitter_link_attributes', array($this, 'targetBlank'));

        // Validate our options
        $args['items'] = (int) $args['items'];
        if ($args['items'] < 1 || 20 < $args['items'])
            $args['items'] = 10;

        if (!isset($args['showts']))
            $args['showts'] = 86400;

        $tweets = $this->_getTweets($args);
//               
        if (false === $tweets)
            return '';

        $widgetContent = $args['before_widget'] . '<div>';

        if (empty($args['title']))
            $args['title'] = sprintf(__('Twitter: %s', $this->_slug), $args['username']);

        $args['title'] = apply_filters('twitter-peep-title', $args['title'], $args);
        $args['title'] = "<span class='twitterwidget twitterwidget-title'>{$args['title']}</span>";
        $widgetContent .= $args['before_title'] . $args['title'] . $args['after_title'];
        if (!empty($tweets[0]) && is_object($tweets[0]) && !empty($args['avatar'])) {
            $widgetContent .= '<div class="twitter-avatar">';
            $widgetContent .= $this->_getProfileImage($tweets[0]->user, $args);
            $widgetContent .= '</div>';
        }

        if (!empty($tweets[0]) && is_object($tweets[0]) && $args['stats'] == 'true') {
            $widgetContent .= '<div class="twitter-stats">';
            $widgetContent .= "<ul>
                                    <li>
                                        <span class='tp-left'><i class='fa fa-user'></i><i class='fa fa-user'></i> {$tweets[0]->user->friends_count}</span>
                                        <span><i class='fa fa-users'></i> {$tweets[0]->user->followers_count}</span>
                                        <span class='tp-right'><i class='fa fa-twitter'></i> {$tweets[0]->user->statuses_count}</span>
                                    </li>
                               </ul>";
            $widgetContent .= '</div>';
        }
        $class = ($args['nicescroll'] == 'true')?' class = "nicescroll" ':'';
        $widgetContent .= '<ul '.$class.'>';
        if (!is_array($tweets) || count($tweets) == 0) {
            $widgetContent .= '<li class="wpTwitterPeepEmpty">' . __('No Tweets Available', $this->_slug) . '</li>';
        } else {
            $count = 0;
            
            /* if either intents or reply-to is enabled we will apply a class in li. This
             * will be responsible for hover effect.
             */
            $li_hover_class = ($args['showinreplyto'] == 'true' || $args['showintents'] == 'true')?' class="hoveractive" ':'';
            
            foreach ($tweets as $tweet) {
                // Set our "ago" string which converts the date to "# ___(s) ago"
                
                $tweet->ago = $this->_timeSince(strtotime($tweet->created_at), $args['showts'], $args['dateFormat']);
                $entryContent = apply_filters('widget_twitter_content', $tweet->text, $tweet);
                $widgetContent .= "<li $li_hover_class>";

                /*                 * ********** TOP META START************** */
                $widgetContent .= "<span class='top-meta'>";

                /*                 * ******** TIME META ********* */
                $widgetContent .= "<span class='time-meta tp-left'>";
                $linkAttrs = array(
                    'href' => "http://twitter.com/{$tweet->user->screen_name}/statuses/{$tweet->id_str}"
                );
                $widgetContent .= '<i class="fa fa-clock-o"></i>' . $this->_buildLink($tweet->ago, $linkAttrs);
                $widgetContent .= '</span>';

                /*                 * ********VIA META********* */

                if ('true' == $args['showfrom']) {
                    $from = sprintf(__('via %s', $this->_slug), str_replace('&', '&amp;', $tweet->source));

                    $widgetContent .= " <span class='from-meta tp-right'>{$from}</span>";
                }

                /*                 * ********** TOP META END************** */
                $widgetContent .= '<span class="clearfix"></span>';
                $widgetContent .= "</span>";

                /*                 * ********* TWEET ******** */
                $widgetContent .= "<span class='entry-content'>{$entryContent}</span>";


                /*                 * ********** BOT META START************** */
                $widgetContent .= " <span class='bot-meta'>";

                /*                 * ******** IN-REPLY-TO SCREEN NAME ********* */
                if (!empty($tweet->in_reply_to_screen_name) && $args['showinreplyto'] == 'true') {
                    $rtLinkText = sprintf(__('in reply to %s', $this->_slug), $tweet->in_reply_to_screen_name);
                    $widgetContent .= ' <span class="in-reply-to-meta tp-right">';
                    $linkAttrs = array(
                        'href' => "http://twitter.com/{$tweet->in_reply_to_screen_name}/statuses/{$tweet->in_reply_to_status_id_str}",
                        'class' => 'reply-to'
                    );
                    $widgetContent .= $this->_buildLink($rtLinkText, $linkAttrs);
                    $widgetContent .= '</span>';
                }



                /*                 * ********INTENTS********* */
                if ('true' == $args['showintents']) {
                    $widgetContent .= ' <span class="intent-meta tp-left">';
                    $lang = $this->_getTwitterLang();
                    if (!empty($lang))
                        $linkAttrs['data-lang'] = $lang;

                    $linkText = '<i class="fa fa-reply"></i>'; //'<img src="'.plugins_url('/images/reply.svg', __FILE__).'">';//__( 'Reply', $this->_slug );
                    $linkAttrs['href'] = "http://twitter.com/intent/tweet?in_reply_to={$tweet->id_str}";
                    $linkAttrs['class'] = 'in-reply-to';
                    $linkAttrs['title'] = __('Reply', $this->_slug);
                    $widgetContent .= $this->_buildLink($linkText, $linkAttrs, true);

                    $linkText = '<i class="fa fa-retweet"></i>'; //'<img src="'.plugins_url('/images/retweet.svg', __FILE__).'">';//__( 'Retweet', $this->_slug );
                    $linkAttrs['href'] = "http://twitter.com/intent/retweet?tweet_id={$tweet->id_str}";
                    $linkAttrs['class'] = 'retweet';
                    $linkAttrs['title'] = __('Retweet', $this->_slug);
                    $widgetContent .= $this->_buildLink($linkText, $linkAttrs, true);

                    $linkText = '<i class="fa fa-star"></i>'; //'<img src="'.plugins_url('/images/fav.svg', __FILE__).'">';//__( 'Favorite', $this->_slug );
                    $linkAttrs['href'] = "http://twitter.com/intent/favorite?tweet_id={$tweet->id_str}";
                    $linkAttrs['class'] = 'favorite';
                    $linkAttrs['title'] = __('Favorite', $this->_slug);
                    $widgetContent .= $this->_buildLink($linkText, $linkAttrs, true);
                    $widgetContent .= '</span>';
                }



                $widgetContent .= '</span>';

                $widgetContent .= '<span class="clearfix"></span>';

                $widgetContent .= '</li>';

                if (++$count >= $args['items'])
                    break;
            }
        }

        $widgetContent .= '</ul>';
        if ('true' == $args['showfollow'] && !empty($args['username'])) {
            $widgetContent .= '<div class="follow-button">';
            $linkText = "@{$args['username']}";
            $linkAttrs = array(
                'href' => "http://twitter.com/{$args['username']}",
                'class' => 'twitter-follow-button',
                'title' => sprintf(__('Follow %s', $this->_slug), "@{$args['username']}"),
            );
            $lang = $this->_getTwitterLang();
            if (!empty($lang))
                $linkAttrs['data-lang'] = $lang;

            $widgetContent .= $this->_buildLink($linkText, $linkAttrs);
            $widgetContent .= '</div>';
        }

        $widgetContent .= '</div>' . $args['after_widget'];

        if ('true' == $args['showintents'] || 'true' == $args['showfollow']) {
            $script = 'http://platform.twitter.com/widgets.js';
            if (is_ssl())
                $script = str_replace('http://', 'https://', $script);
            wp_enqueue_script('twitter-peeps', $script, array(), '1.0.0', true);

            if (!function_exists('_wp_footer_scripts')) {
                // This means we can't just enqueue our script (fixes in WP 3.3)
                add_action('wp_footer', array($this, 'add_twitter_js'));
            }
        }
        return $widgetContent;
    }

    private function _getTwitterLang() {
        $valid_langs = array(
            'en', // English
            'it', // Italian
            'es', // Spanish
            'fr', // French
            'ko', // Korean
            'ja', // Japanese
        );
        $locale = get_locale();
        $lang = strtolower(substr(get_locale(), 0, 2));
        if (in_array($lang, $valid_langs))
            return $lang;

        return false;
    }

    public function add_twitter_js() {
        wp_print_scripts('twitter-peeps');
    }

    /**
     * Gets tweets, from cache if possible
     *
     * @param array $widgetOptions - options needed to get feeds
     * @return array - Array of objects
     */
    private function _getTweets($widgetOptions) {
        $key = 'attp_' . md5(maybe_serialize($this->_get_feed_request_settings($widgetOptions)));
        return tlc_transient($key)
                        ->expires_in(300) // cache for 5 minutes
                        ->extend_on_fail(120) // On a failed call, don't try again for 2 minutes
                        ->updates_with(array($this, 'parseFeed'), array($widgetOptions))
                        ->get();
    }

    /**
     * Pulls the JSON feed from Twitter and returns an array of objects
     *
     * @param array $widgetOptions - settings needed to get feed url, etc
     * @return array
     */
    public function parseFeed($widgetOptions) {
        $parameters = $this->_get_feed_request_settings($widgetOptions);
        $response = array();

        if (!empty($parameters['screen_name'])) {
            if (empty($this->_settings['attp-authed-users'][strtolower($parameters['screen_name'])])) {
                if (empty($widgetOptions['errmsg']))
                    $widgetOptions['errmsg'] = __('Account needs to be authorized', $this->_slug);
            } else {
                $this->_wp_twitter_oauth->set_token($this->_settings['attp-authed-users'][strtolower($parameters['screen_name'])]);
                $response = $this->_wp_twitter_oauth->send_authed_request('statuses/user_timeline', 'GET', $parameters);
                if (!is_wp_error($response))
                    return $response;
            }
        } elseif (!empty($parameters['list_id'])) {
            $list_info = explode('::', $widgetOptions['list']);
            $user = array_shift($list_info);
            $this->_wp_twitter_oauth->set_token($this->_settings['attp-authed-users'][strtolower($user)]);

            $response = $this->_wp_twitter_oauth->send_authed_request('lists/statuses', 'GET', $parameters);
            if (!is_wp_error($response))
                return $response;
        }

        if (empty($widgetOptions['errmsg']))
            $widgetOptions['errmsg'] = __('Invalid Twitter Response.', $this->_slug);
        do_action('widget_twitter_parsefeed_error', $response, $parameters, $widgetOptions);
        throw new Exception($widgetOptions['errmsg']);
    }

    /**
     * Gets the parameters for the desired feed.
     *
     * @param array $widgetOptions - settings needed such as username, feet type, etc
     * @return array - Parameters ready to pass to a Twitter request
     */
    private function _get_feed_request_settings($widgetOptions) {
        /**
         * user_id
         * screen_name *
         * since_id
         * count
         * max_id
         * page
         * trim_user
         * include_rts *
         * include_entities
         * exclude_replies *
         * contributor_details
         */
        $parameters = array(
            'count' => $widgetOptions['items'],
        );

        if (!empty($widgetOptions['username'])) {
            $parameters['screen_name'] = $widgetOptions['username'];
        } elseif (!empty($widgetOptions['list'])) {
            $list_info = explode('::', $widgetOptions['list']);
            $parameters['list_id'] = array_pop($list_info);
        }

        if ('false' == $widgetOptions['showreplies'])
            $parameters['exclude_replies'] = 'true';

        if ('true' != $widgetOptions['showretweets'])
            $parameters['include_rts'] = 'false';

        return $parameters;
    }

    /**
     * Twitter displays all tweets that are less than 24 hours old with
     * something like "about 4 hours ago" and ones older than 24 hours with a
     * time and date. This function allows us to simulate that functionality,
     * but lets us choose where the dividing line is.
     *
     * @param int $startTimestamp - The timestamp used to calculate time passed
     * @param int $max - Max number of seconds to conver to "ago" messages.  0 for all, -1 for none
     * @return string
     */
    private function _timeSince($startTimestamp, $max, $dateFormat) {
        // array of time period chunks
        $chunks = array(
            'year' => 60 * 60 * 24 * 365, // 31,536,000 seconds
            'month' => 60 * 60 * 24 * 30, // 2,592,000 seconds
            'week' => 60 * 60 * 24 * 7, // 604,800 seconds
            'day' => 60 * 60 * 24, // 86,400 seconds
            'hour' => 60 * 60, // 3600 seconds
            'minute' => 60, // 60 seconds
            'second' => 1                   // 1 second
        );

        $since = time() - $startTimestamp;

        if ($max != '-1' && $since >= $max)
            return date_i18n($dateFormat, $startTimestamp + get_option('gmt_offset') * 3600);


        foreach ($chunks as $key => $seconds) {
            // finding the biggest chunk ( if the chunk fits, break )
            if (( $count = floor($since / $seconds) ) != 0)
                break;
        }

        $messages = array(
            'year' => _n('%sy', '%sy', $count, $this->_slug),
            'month' => _n('%sm', '%sm', $count, $this->_slug),
            'week' => _n('%sw', '%sw', $count, $this->_slug),
            'day' => _n('%sd', '%sd', $count, $this->_slug),
            'hour' => _n('%sh', '%sh', $count, $this->_slug),
            'minute' => _n('%sm', '%sm', $count, $this->_slug),
            'second' => _n('%ss', '%ss', $count, $this->_slug),
        );

        return sprintf($messages[$key], $count);
    }

    /**
     * Returns the Twitter user's profile image, linked to that user's profile
     *
     * @param object $user - Twitter User
     * @param array $args - Widget Arguments
     * @return string - Linked image ( XHTML )
     */
    private function _getProfileImage($user, $args = array()) {
        $linkAttrs = array(
            'href' => "http://twitter.com/{$user->screen_name}",
            'title' => $user->name
        );
        $replace = ( 'original' == $args['avatar'] ) ? '.' : "_{$args['avatar']}.";
        $img = str_replace('_normal.', $replace, $user->profile_image_url_https);

        return $this->_buildLink("<img alt='{$user->name}' src='{$img}' />", $linkAttrs, true);
    }

    /**
     * Replace our shortCode with the "widget"
     *
     * @param array $attr - array of attributes from the shortCode
     * @param string $content - Content of the shortCode
     * @return string - formatted XHTML replacement for the shortCode
     */
    public function handleShortcodes($attr, $content = '') {
        $defaults = array(
            'before_widget' => '<div class="widget_ajaxtown_twitter">',
            'after_widget' => '</div>',
            'before_title' => '<h2>',
            'after_title' => '</h2>',
            'title' => '',
            'errmsg' => '',
            'username' => '',
            'list' => '',
            'showreplies' => 'true',
            'showretweets' => 'true',
            'showinreplyto' => 'true',
            'showfrom' => 'true',
            'showintents' => 'true',
            'showfollow' => 'true',
            'avatar' => '', 
            'stats' => 'false',
            'targetBlank' => 'false',
            'items' => 10,
            'showts' => 60 * 60 * 24,
            'dateFormat' => __('Y-m-d H:i:s', $this->_slug),
        );

        /**
         * Attribute names are strtolower'd, so we need to fix them to match
         * the names used through the rest of the plugin
         */
        if (array_key_exists('targetblank', $attr)) {
            $attr['targetBlank'] = $attr['targetblank'];
            unset($attr['targetblank']);
        }
        if (array_key_exists('dateformat', $attr)) {
            $attr['dateFormat'] = $attr['dateformat'];
            unset($attr['dateformat']);
        }


        if (!empty($content) && empty($attr['title']))
            $attr['title'] = $content;
        
        /* Parameters are feeded from 2 places. One of admin panel, the default shortcode attributes
         * and another from shortcodes code. First priority is given to code and then options from admin
         * panel. So default options are first replaced by admin panel options and then by shortcodes code.
         */
        
        $default_admin_options = $this->_settings['attp'];
        
        $shortcode_admin_options = array('showretweets','showfrom','showintents','showts','showreplies','stats','showfollow');
        
        foreach($shortcode_admin_options as $key) {            
            if(!array_key_exists($key, $attr)) {                
                $defaults[$key] = $default_admin_options[$key];    
             //echo $key;//echo $defaults[$key] .'='. $default_admin_options[$key];    
            }            
        }
        
        //$attr['showts'] = !array_key_exists($key, $attr)?$default_admin_options['showts']:$attr['showts'];
        $attr = shortcode_atts($defaults, $attr);

        if ($attr['showreplies'] && $attr['showreplies'] != 'false' && $attr['showreplies'] != '0')
            $attr['showreplies'] = 'true';
        
        if ($attr['stats'] && $attr['stats'] != 'false' && $attr['stats'] != '0')
            $attr['stats'] = 'true';

        if ($attr['showretweets'] && $attr['showretweets'] != 'false' && $attr['showretweets'] != '0')
            $attr['showretweets'] = 'true';

        if ($attr['showfrom'] && $attr['showfrom'] != 'false' && $attr['showfrom'] != '0')
            $attr['showfrom'] = 'true';

        if ($attr['showintents'] && $attr['showintents'] != 'true' && $attr['showintents'] != '1')
            $attr['showintents'] = 'false';

        if ($attr['showfollow'] && $attr['showfollow'] != 'true' && $attr['showfollow'] != '1')
            $attr['showfollow'] = 'false';

        if (!in_array($attr['avatar'], array('bigger', 'normal', 'mini', 'original', '')))
            $attr['avatar'] = 'normal';

        if ($attr['targetBlank'] && $attr['targetBlank'] != 'false' && $attr['targetBlank'] != '0')
            $attr['targetBlank'] = 'true';

        
        return $this->display($attr);
    }

    public function authed_users_option($settings) {
        if (!is_array($settings))
            return array();
        return $settings;
    }

    public function filterSettings($settings) {
        $defaultArgs = array(
            'consumer-key' => '',
            'consumer-secret' => '',
            'title' => '',
            'errmsg' => '',
            'username' => '',
            'list' => '',
            'http_vs_https' => 'https',
            'showreplies' => 'true',
            'showretweets' => 'true',
            'showinreplyto' => 'true',
            'showfrom' => 'true',
            'showintents' => 'true',
            'showfollow' => 'true',
            'avatar' => '',
            'targetBlank' => 'false',
            'skin' => 'light',
            'items' => 10,
            'showts' => 60 * 60 * 24,
            'nicescroll' => 'false',
            'dateFormat' => __('Y-m-d H:i:s', $this->_slug),// __('h:i:s A F d, Y', $this->_slug),
        );

        return $this->fixAvatar(wp_parse_args($settings, $defaultArgs));
    }

    /**
     * Now that we support all the profile image sizes we need to convert
     * the old true/false to a size string
     */
    private function fixAvatar($settings) {
        if (false === $settings['avatar'])
            $settings['avatar'] = '';
        elseif (!in_array($settings['avatar'], array('bigger', 'normal', 'mini', 'original', false)))
            $settings['avatar'] = 'normal';

        return $settings;
    }

    public function getSettings($settings) {
        return $this->fixAvatar(wp_parse_args($settings, $this->_settings['attp']));
    }

    public function get_users_list($authed = false) {
        $users = $this->_settings['attp-authed-users'];
        if ($authed) {
            if (!empty($this->_authed_users))
                return $this->_authed_users;
            foreach ($users as $key => $u) {
                $this->_wp_twitter_oauth->set_token($u);
                $rates = $this->_wp_twitter_oauth->send_authed_request('application/rate_limit_status', 'GET', array('resources' => 'statuses,lists'));
                if (is_wp_error($rates))
                    unset($users[$key]);
            }
            $this->_authed_users = $users;
        }
        return $users;
    }

    public function get_lists() {
        if (!empty($this->_lists))
            return $this->_lists;
        $this->_lists = array();
        foreach ($this->_settings['attp-authed-users'] as $key => $u) {
            $this->_wp_twitter_oauth->set_token($u);
            $user_lists = $this->_wp_twitter_oauth->send_authed_request('lists/list', 'GET', array('resources' => 'statuses,lists'));

            if (!empty($user_lists) && !is_wp_error($user_lists)) {
                $this->_lists[$key] = array();
                foreach ($user_lists as $l) {
                    $this->_lists[$key][$l->id] = $l->name;
                }
            }
        }
        return $this->_lists;
    }
    

}

// Instantiate our class
$wpTwitterPeep = wpTwitterPeep::getInstance();
