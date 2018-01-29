<?php
//refer : http://culttt.com/2012/10/01/roll-your-own-pdo-php-class/

class Database {
	private $dbh;	
	private $stmt;
	private $dbServer=DB_HOST;
	private $dbName=DB_NAME;
	private $dbUser=DB_USER;
	private $dbPass=DB_PASS;

	public function __construct(){
        // Set DSN
        $dsn = 'mysql:host=' . $this->dbServer . ';dbname=' . $this->dbName;
		
        // Set options
        $options = array(
            PDO::ATTR_PERSISTENT    => true,
            PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION
        );
        // Create a new PDO instanace
        try{
            $this->dbh = new PDO($dsn, $this->dbUser, $this->dbPass, $options);
			$this->dbh->exec("set names utf8");
        }
        // Catch any errors
        catch(PDOException $e){
            $this->error = $e->getMessage();
        }
    }	
	
	public function query($query){
		$this->stmt = $this->dbh->prepare($query);
	}
	
	public function bind($param, $value, $type = null){
		if (is_null($type)) {
			switch (true) {
				case is_int($value):
					$type = PDO::PARAM_INT;
					break;
				case is_bool($value):
					$type = PDO::PARAM_BOOL;
					break;
				case is_null($value):
					$type = PDO::PARAM_NULL;
					break;
				default:
					$type = PDO::PARAM_STR;
			}
		}
		switch($type) {
			case "int":
				$type = PDO::PARAM_INT;
				break;
			case "string":
			default:
				$type = PDO::PARAM_STR;				
				break;
		}
		$this->stmt->bindValue($param, $value, $type);
	}
	
	public function execute(){
		return $this->stmt->execute();
	}
	
	public function resultset(){
		$this->execute();
		return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function single(){
		$this->execute();
		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	public function rowCount(){
		return $this->stmt->rowCount();
	}
	
	public function lastInsertId(){
		return $this->dbh->lastInsertId();
	}
}
?>