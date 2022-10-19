<?php

namespace App\Lib;

/**
 * Class SSHWrapper
 */
class SSHWrapper {
    /**
     * Returns a new instance of the SSHWrapper class.
     * 
     * @return SSHWrapper
     * @throws \Exception
     */
    public static function getInstance() : SSHWrapper
    {
      if (self::$instance == null)
      {
        self::$instance = new SSHWrapper();
      }
   
      return self::$instance;
    }

    /**
     * Gets a userid by username.
     * 
     * @param string $username
     * @return int
     * @throws \Exception
     */
    public static function getUidByUsername(string $username) : int|null
    {
      if (!$username) {
        throw new \Exception('Username is required.');
      }
      $uid = null;
      $getUid = exec(
        sprintf('id -u %s', $username),
        $getUidOutput,
        $return
      );
      $uid = (int) $getUid;

      if ($return !== 0) {
        throw new \Exception('User with username ' . $username . ' does not exist.');
      }

      return $uid ? $uid : null;
    }

    /**
     * Gets a userid by username.
     * 
     * @param string $username
     * @return bool
     * @throws \Exception
     */
    public static function isExecutingUserSudo() : bool
    {
      exec('sudo -v 2> /dev/null', $output, $return);
      if ($return === 0) {
        return true;
      }
      exec('sudo id -u 2> /dev/null', $output, $return);
      if ($return === 0) {
        return true;
      }
      
      return false;
    }

    /**
     * Adds a new user to the system.
     * Creates a home directory and sets the correct permissions.
     * Create /home/<username>/.ssh directory and a config and authorized_keys file.
     * 
     * @param string $username
     * @param string $shell
     * @param int $uid
     * @param bool $home
     * @return bool
     * @throws \Exception
     */
    public static function adduser(string $username, bool $shell = false, $uid = null, bool $home = true) : bool
    {
      if (!$username) {
        throw new \Exception('Username is required.');
      }
      $command = 'useradd ' . $username . ' --shell=' . ($shell ? '/bin/bash' : '/sbin/nologin');
      if ($home) {
        $command .= ' --create-home';
      }
      if ($uid) {
        $command .= ' --uid=' . $uid;
      }

      exec($command, $output, $return);
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not create user %s.', $username));
      }

      return true;
    }

    /**
     * Check if a given user exists.
     * 
     * @param string $username
     * @throws \Exception
     * @return bool
     */
    public static function userExists(string $username) : bool
    {
      if (!$username) {
        throw new \Exception('Username is required.');
      }
      exec('grep -c \'^' . $username . ':\' /etc/passwd', $output, $return); 

      return (bool) $output[0] ?? false;
    }

    /**
     * Check which linux user is executing the script.
     * 
     * @throws \Exception
     * @return string
     */
    public static function whoami() : string
    {
      exec('whoami', $output, $return); 
      if ($return !== 0) {
        throw new \Exception('Could not get executing user.');
      }

      return $output[0];
    }

    /**
     * Creates a .ssh directory in the home directory of the user.
     * 
     * @param string $username
     * @throws \Exception
     * @return bool
     */
    public static function createDotSshDir(string $username) : bool
    {
      if (!$username) {
        throw new \Exception('Username is required.');
      }

      exec('mkdir /home/' . $username . '/.ssh', $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not create directory /home/%s/.ssh.', $username));
      }

      exec('touch /home/' . $username . '/.ssh/config', $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not create file /home/%s/.ssh/config.', $username));
      }

      exec('touch /home/' . $username . '/.ssh/authorized_keys', $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not create file /home/%s/.ssh/authorized_keys.', $username));
      }

      exec('chown -R '.$username.':'.$username.' /home/'.$username.'/.ssh', $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not set permissions on /home/%s/.ssh.', $username));
      }

      return true;
    }

    /**
     * Creates a folder for the user to store websites in and sets the correct permissions.
     * 
     * @param string $www_root
     * @param string $username
     * @return bool
     * @throws \Exception
     */
    public static function createWWWDir($www_root = null, string $username = null) : bool
    {
      if (!$www_root || !$username) {
        throw new \Exception('Missing parameters.');
      }

      exec('mkdir '.$www_root.'/'.$username, $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not create directory %s/%s.', $www_root, $username));
      }

      if (true) {
        exec('echo "<?php phpinfo();" > '.$www_root.'/'.$username.'/index.php', $output, $return); 
      }

      exec('chown -R '.$username.':'.$username.' ' . $www_root . '/' . $username, $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not set permissions on %s/%s.', $www_root, $username));
      }

      exec('chmod 775 -R '.$www_root.'/'.$username, $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not set permissions on %s/%s.', $www_root, $username));
      }

      return true;
    }

    /**
     * Remove www directory for user.
     * 
     * @param string $username
     * @return bool
     * @throws \Exception
     */
    public static function removeWwwDirectory(string $username) : bool
    {
      // Checking if WWW_ROOT is set. It should not delete anything critical, but if it should contain a path
      // to a critical directory, it should not delete it. Just for extra security measures.
      if (empty(getenv('WWW_ROOT')) || in_array(getenv('WWW_ROOT'), ['/', '/var'])) {
        throw new \Exception('WWW_ROOT is not set.');
      }
      if (empty($username)) {
        // We should be good when we are checking the username, but just to be sure.
        throw new \Exception('Username is required.');
      }
      exec('rm -rf '.getenv('WWW_ROOT').'/'.$username, $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not delete %s/%s.', getenv('WWW_ROOT'), $username));
      }

      return true;
    }

    /**
     * Remove HTTP-server configuration file for user.
     * 
     * @param string $username
     * @return bool
     * @throws \Exception
     */
    public static function removeHttpConfig(string $username) : bool
    {
      exec('rm /etc/nginx/sites-enabled/'.$username.'.conf /etc/nginx/sites-available/'.$username.'.conf', $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not remove HTTP-server configuration file for %s.', $username));
      }

      return true;
    }

    /**
     * Remove PHP-FPM configuration file for user.
     * 
     * @param string $username
     * @return bool
     * @throws \Exception
     */
    public static function removeFpmConfig(string $username) : bool
    {
      exec('rm /etc/php/'.getenv('PHP_VERSION').'/fpm/pool.d/'.$username.'.conf', $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not remove PHP-FPM configuration file for %s.', $username));
      }

      return true;
    }

    /**
     * Creates a new nginx|apache config file in /etc/nginx/sites-available/<username>.conf
     * 
     * @param string $username
     * @param string $str_domains
     * @param string $nginx_config_path
     * @param int    $next_fastcgi_port
     * @return bool
     * @throws \Exception
     */
    public static function createHttpServerConfig(string $username, string $str_domains, string $nginx_config_path, int $next_fastcgi_port) : bool
    {
      $_TEMPLATE_PATH = getenv('APP_SYSTEM_PATH').'/src/Templates/nginx-vhost.tmpl';

      exec('tmpl_fastcgi_host=127.0.0.1 tmpl_fastcgi_port=' . $next_fastcgi_port . ' tmpl_domains="' . $str_domains . '" tmpl_website_path="'.getenv('WWW_ROOT').'/'.$username.'" tmpl_php_version="'.getenv('PHP_VERSION').'" tmpl_username="'.$username.'" sh -c \'echo "\'"$(cat ' . $_TEMPLATE_PATH . ')"\'"\' > ' . $nginx_config_path, $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not set permissions on %s/%s.', $www_root, $username));
      }

      exec('ln -s /etc/nginx/sites-available/'.$username.'.conf /etc/nginx/sites-enabled/'.$username.'.conf', $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not create symlink for nginx config: %s.', $username));
      }

      return true;
    }

    /**
     * Creates a new php-fpm config file in /etc/php/<version>/fpm/pool.d/<username>.conf
     * 
     * @param string $username
     * @param string $php_config_path
     * @param int    $next_fastcgi_port
     * @return bool
     * @throws \Exception
     */
    public static function createFpmConfig(string $username, string $fpm_config_path, int $next_fastcgi_port) : bool
    {
      $_TEMPLATE_PATH = getenv('APP_SYSTEM_PATH').'/src/Templates/php-fpm-vhost.tmpl';

      exec('tmpl_chdir="'.getenv('WWW_ROOT').'/'.$username.'" tmpl_fastcgi_host=127.0.0.1 tmpl_fastcgi_port=' . $next_fastcgi_port . ' tmpl_php_version="'.getenv('PHP_VERSION').'" tmpl_username="'.$username.'" sh -c \'echo "\'"$(cat ' . $_TEMPLATE_PATH . ')"\'"\' > ' . $fpm_config_path, $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not create php fpm config: %s', $fpm_config_path));
      }

      return true;
    }

    /**
     * Creates a nginx upstream config file and substituting the template variables.
     * 
     * @param string    $upstream_name
     * @param string    $www_root
     * @param array     $servers
     * @return bool
     * @throws \Exception
     */
    public static function createNginxUpstreamConfig(string $upstream_name, string $www_root, array $servers = null) : bool
    {
      $_TEMPLATE_PATH = getenv('APP_SYSTEM_PATH').'/src/Templates/upstream.tmpl';
      $config_file_path = '/etc/nginx/sites-available/upstream_' . $upstream_name . '.conf';
      $symlink_destination = '/etc/nginx/sites-enabled/upstream_' . $upstream_name . '.conf';

      if (empty($servers)) {
        throw new \Exception('No servers provided.');
      }

      $str_servers = '';
      foreach ($servers as $server) {
        if (empty($server['host']) || empty($server['port'])) {
          throw new \Exception('Invalid server configuration.');
        }
        $str_servers .= "\t".'server '.$server['host'].':'.$server['port'].';' . PHP_EOL;
      }

      exec('upstream_name="'.$upstream_name.'" www_root="'.$www_root.'" servers="'.$str_servers.'" sh -c \'echo "\'"$(cat ' . $_TEMPLATE_PATH . ')"\'"\' > ' . $config_file_path, $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not create nginx upstream config: %s', $fpm_config_path));
      }

      exec('ln -s '.$config_file_path.' '.$symlink_destination, $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not create symlink for nginx config: %s.', $config_file_path));
      }

      return true;
    }


    /**
     * Creates a nginx upstream child/node config file and substituting the template variables.
     * 
     * @param string    $host
     * @param string    $port
     * @param string    $www_root
     * @return bool
     * @throws \Exception
     */
    public static function createNginxUpstreamChildConfig(string $host, string $port, string $www_root) : bool
    {
      $_TEMPLATE_PATH = getenv('APP_SYSTEM_PATH').'/src/Templates/upstream_child.tmpl';
      $config_file_path = '/etc/nginx/sites-available/node_' . $host . '.conf';
      $symlink_destination = '/etc/nginx/sites-enabled/node_' . $host . '.conf';

      exec('host="'.$host.'" port="'.$port.'" www_root="'.$www_root.'" sh -c \'echo "\'"$(cat ' . $_TEMPLATE_PATH . ')"\'"\' > ' . $config_file_path, $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not create nginx upstream config: %s', $fpm_config_path));
      }

      exec('ln -s '.$config_file_path.' '.$symlink_destination, $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not create symlink for nginx config: %s.', $config_file_path));
      }

      return true;
    }

    /**
     * Restarts the HTTP server.
     * 
     * @return bool
     * @throws \Exception
     */
    public static function restartHttpServer() : bool
    {
      exec('service nginx restart', $output, $return); 
      if ($return !== 0) {
        // The return code is 1 if nginx is already running.
        if ($return !== 1) {
          throw new \Exception('Could not restart nginx.');
        }
      }

      return true;
    }

    /**
     * Get and installs LetsEncrypt certificates for domain(s).
     * 
     * @param array $domains
     * @param bool $redirect
     * @param string $httpServer
     * 
     * @throws \Exception
     * @return bool
     */
    public static function runCertbot(array $domains, bool $redirect = true, string $httpServer = 'nginx') : bool
    {
      // Check if certbot is installed.

      $addwww = true; // hardcoded for now

      exec('which certbot', $output, $return);
      if ($return !== 0) {
        throw new \Exception('Certbot is not installed.');
      }
      if (empty($domains)) {
        throw new \Exception('No domains given.');
      }
      if (!in_array($httpServer, ['nginx'])) {
        throw new \Exception('Invalid HTTP server given.');
      }
      $flags = '--quiet';
      if ($redirect) {
        $flags .= ' --redirect';
      }
      $flags .= ' --' . $httpServer;

      foreach ($domains as $domain) {
        $flags .= ' -d ' . $domain;
        if ($addwww) {
          $flags .= ' -d www.' . $domain;
        }
      }

      exec('certbot run ' . $flags, $output, $return);

      if ($return !== 0) {
        return false;
      }

      return true;
    }

    /**
     * Restarts PHP-FPM.
     * 
     * @return bool
     * @throws \Exception
     */
    public static function restartFpm() : bool
    {
      exec('service php' . getenv('PHP_VERSION') . '-fpm restart', $output, $return); 
      if ($return !== 0) {
        throw new \Exception('Could not restart php-fpm.');
      }

      return true;
    }

    /**
     * Adds a public key to the authorized_keys file.
     * 
     * @param string $username
     * @param string $public_key
     * @return bool
     * @throws \Exception
     */
    public static function addAuthorizedKey(string $username, string $public_key, string $key_identifier) : bool
    {
      if (!$username || !$public_key) {
        throw new \Exception('Missing parameters.');
      }
      $_TEMPLATE_PATH = getenv('APP_SYSTEM_PATH').'/src/Templates/authorized_key.tmpl';

      // filters
      $public_key  = trim($public_key); // remove whitespace from the beginning and end of the key

      exec('tmpl_key_id="'.$key_identifier.'" tmpl_public_key="'.$public_key.'" sh -c \'echo "\'"$(cat ' . $_TEMPLATE_PATH . ')"\'"\' >> /home/'.$username.'/.ssh/authorized_keys', $output, $return); 
      if ($return !== 0) {
        throw new \Exception(sprintf('Could not add public key to /home/%s/.ssh/authorized_keys.', $username));
      }
      return true;
    }

    /**
     * Generates a new SSH key pair.
     * 
     * @param string $username
     * @return bool
     * @throws \Exception
     */
    public static function generateSSHKey($username = null, $fileName = null) : bool
    {
      exec('yes y | sudo -u '.$username.' ssh-keygen -t rsa -N "" -f /home/'.$username.'/.ssh/'.$fileName.' > /dev/null', $output, $return);

      if ($return !== 0) {
        throw new \Exception('Could not generate SSH key pair.');
      }

      exec('chown '.$username.':'.$username.' /home/'.$username.'/.ssh/'.$fileName . ' > /dev/null', $output, $return);
      if ($return !== 0) {
        throw new \Exception('Could not chown SSH key pair.');
      }

      exec('chown '.$username.':'.$username.' /home/'.$username.'/.ssh/'.$fileName . '.pub' . ' > /dev/null', $output, $return);
      if ($return !== 0) {
        throw new \Exception('Could not chown SSH key pair.');
      }

      return true;
    }
}