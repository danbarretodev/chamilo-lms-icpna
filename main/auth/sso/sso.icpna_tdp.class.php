<?php
/**
 * Created by PhpStorm.
 * User: dbarreto
 * Date: 22/01/14
 * Time: 03:54 PM
 */

use \ChamiloSession as Session;

//CONSTANT name for icpna tdp authentication source
define('ICPNA_TDP_AUTH_SOURCE', 'icpna_tdp');

class ssoicpna_tdp extends sso
{

    /**
     * Validates the received active connection data with the database
     * @return	bool	Return the loginFailed variable value to local.inc.php
     */
    public function check_user() {
        global $_user;
        $loginFailed = false;
        //change the way we recover the cookie depending on how it is formed
        $sso = array(
            'username'=>$_GET['name'],
            'secret'=> $_GET['secret'],
            'target'=> $_GET['target']
        );

        //error_log('check_user');
        //error_log('sso decode cookie: '.print_r($sso,1));

        //lookup the user in the main database
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT user_id, username, password, auth_source, active, expiration_date, status
                FROM $user_table
                WHERE username = '".trim(Database::escape_string($sso['username']))."'";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            //error_log('user exists');
            $uData = Database::fetch_array($result);
            //Check the user's password
            if ($uData['auth_source'] == ICPNA_TDP_AUTH_SOURCE ) {

                //the authentification of this user is managed by Chamilo itself
                // check the user's password
                // password hash comes already parsed in sha1, md5 or none

                /*
                error_log($sso['secret']);
                error_log($uData['password']);
                error_log($sso['username']);
                error_log($uData['username']);
                */

                if ($sso['secret'] === ($uData['password'])
                    && ($sso['username'] == $uData['username'])) {
                    error_log('user n password are ok');
                    //Check if the account is active (not locked)
                    if ($uData['active']=='1') {
                        // check if the expiration date has not been reached
                        if ($uData['expiration_date'] > date('Y-m-d H:i:s') OR $uData['expiration_date']=='0000-00-00 00:00:00') {
                            //If Multiple URL is enabled
                            if (api_get_multiple_access_url()) {
                                //Check the access_url configuration setting if the user is registered in the access_url_rel_user table
                                //Getting the current access_url_id of the platform
                                $current_access_url_id = api_get_current_access_url_id();
                                // my user is subscribed in these
                                //sites: $my_url_list
                                $my_url_list = api_get_access_url_from_user($uData['user_id']);
                            } else {
                                $current_access_url_id = 1;
                                $my_url_list = array(1);
                            }

                            $my_user_is_admin = UserManager::is_admin($uData['user_id']);
                            if ($my_user_is_admin === false) {
                                if (is_array($my_url_list) && count($my_url_list)>0 ) {
                                    if (in_array($current_access_url_id, $my_url_list)) {
                                        // the user has permission to enter at this site
                                        $_user['user_id'] = $uData['user_id'];
                                        $_user = api_get_user_info($_user['user_id']);
                                        Session::write('_user',$_user);
                                        event_login();
                                        // Redirect to homepage
                                        $sso_target = isset($sso['target']) ? $sso['target'] : api_get_path(WEB_PATH) .'.index.php';
                                       // header('Location: '. $sso_target);
                                        //error_log("======TRUE============");
                                        echo 'OOOOOOOOOOOOOOOOOOOO';
                                         exit;
                                    } else {
                                        // user does not have permission for this site
                                        $loginFailed = true;
                                        Session::erase('_uid');
                                        header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
                                        exit;
                                    }
                                } else {
                                    // there is no URL in the multiple
                                    // urls list for this user
                                    $loginFailed = true;
                                    Session::erase('_uid');
                                    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
                                    exit;
                                }
                            } else {
                                //Only admins of the "main" (first) Chamilo
                                // portal can login wherever they want
                                if (in_array(1, $my_url_list)) {
                                    //Check if this admin is admin on the
                                    // principal portal
                                    $_user['user_id'] = $uData['user_id'];
                                    $_user = api_get_user_info($_user['user_id']);
                                    $is_platformAdmin = $uData['status'] == COURSEMANAGER;
                                    Session::write('is_platformAdmin', $is_platformAdmin);
                                    Session::write('_user',$_user);
                                    event_login();
                                    self::redirect_to($sso, $_user['user_id'], true);
                                } else {
                                    //Secondary URL admin wants to login
                                    // so we check as a normal user
                                    if (in_array($current_access_url_id, $my_url_list)) {
                                        $_user['user_id'] = $uData['user_id'];
                                        $_user = api_get_user_info($_user['user_id']);
                                        Session::write('_user',$_user);
                                        event_login();
                                        self::redirect_to($sso, $_user['user_id'], true);
                                    } else {
                                        $loginFailed = true;
                                        Session::erase('_uid');
                                        header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=access_url_inactive');
                                        exit;
                                    }
                                }
                            }
                        } else {
                            // user account expired
                            $loginFailed = true;
                            Session::erase('_uid');
                            header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_expired');
                            exit;
                        }
                    } else {
                        //User not active
                        $loginFailed = true;
                        Session::erase('_uid');
                        header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=account_inactive');
                        exit;
                    }
                } else {
                    //SHA1 of password is wrong
                    $loginFailed = true;
                    Session::erase('_uid');
                    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=wrong_password');
                    exit;
                }
            } else {
                //Auth_source is wrong
                $loginFailed = true;
                Session::erase('_uid');
                header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=wrong_authentication_source');
                exit;
            }
        } else {
            //No user by that login
            $loginFailed = true;
            Session::erase('_uid');
            header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=user_not_found');
            exit;
        }
        return $loginFailed;
    }

    public static function encrypt_decrypt($action, $string) {
        $output = false;

        $encrypt_method = "AES-256-CBC";
        $secret_key = 'jbdsbvbsvdjsjdbvsvd';
        $secret_iv = 'ICPNA';

        // hash
        $key = hash('sha256', $secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if( $action == 'encrypt' ) {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        }
        else if( $action == 'decrypt' ){
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }

        return $output;
    }
}