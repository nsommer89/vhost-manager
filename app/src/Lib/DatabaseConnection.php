<?php

namespace App\Lib;

/**
 * Class DatabaseConnection
 */
class DatabaseConnection {

    /**
     * @var DatabaseConnection $instance
     */
    private static $instance = null;

    /**
     * @var \PDO $pdo
     */
    private \PDO $pdo;

    /**
     * @var string $host
     */
    private string $host;

    /**
     * @var string $db
     */
    private string $db;

    /**
     * @var string $user
     */
    private string $user;

    /**
     * @var string $password
     */
    private string $password;

    /**
     * DatabaseConnection constructor.
     * 
     * @throws \Exception
     */
    private function __construct()
    {
      if (!function_exists('getenv')) {
        throw new \Exception('The getenv() function is not available.');
      }

      if (strtolower(getenv('DB_CONNECTION')) === 'mysql') {
        $this->host = getenv('MYSQL_HOSTNAME');
        $this->db = getenv('MYSQL_DATABASE');
        $this->user = getenv('MYSQL_USER');
        $this->password = getenv('MYSQL_PASSWORD');

        // Connect to the mysql-database.
        $this->pdo = new \PDO('mysql:host='.$this->host.';dbname='.$this->db.'', $this->user, $this->password);
      }

      if (strtolower(getenv('DB_CONNECTION')) === 'sqlite') {
        $this->pdo = new \PDO('sqlite:'.getenv('SQLITE_DATABASE'));
      }

      if (empty($this->pdo)) {
        throw new \Exception('No database connection available.');
      }
    }

    /**
     * Returns a new instance of the DatabaseConnection class with the given connection parameters.
     * 
     * @return DatabaseConnection
     * @throws \Exception
     */
    public static function getInstance() : DatabaseConnection
    {
      if (self::$instance == null)
      {
        self::$instance = new DatabaseConnection();
      }
   
      return self::$instance;
    }

    /**
     * Returns the PDO object.
     * @return \PDO
     */
    public function getConnection() : \PDO
    {
      return $this->pdo;
    }
  }