<?php
class db {
	/*The project's pdo attribute holds an instance of PHP's PDO class.  It's public, which allows
	developers to access PDO's methods directly.  See http://us3.php.net/manual/en/book.pdo.php
	for more information on PDO.*/
	public $pdo;

	private $error;
	private $sql;
	private $bind;

	/*The constructor uses PHP's built-in PDO class to establish a database connection. See
	http://us3.php.net/manual/en/pdo.connections.php for more information on the $dsn parameter.*/
	public function __construct($dsn, $user="", $passwd="") {
		$options = array(
			PDO::ATTR_PERSISTENT => true, 
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);

		try {
			$this->pdo = new PDO($dsn, $user, $passwd, $options);
		} catch (PDOException $e) {
			$this->error = $e->getMessage();
		}
	}

	public function __toString() {
		$this->debug();
	}

	public function debug() {
		echo "<pre>", print_r($this), "</pre>";	
	}

	/*DELETE statement.*/
	public function delete($table, $where, $bind="") {
		$sql = "DELETE FROM " . $table . " WHERE " . $where . ";";
		$this->run($sql, $bind);
	}

	/*INSERT and UPDATE make use of this function to ensure that only existing field names within the appropriate table 
	are being populated in the sql.*/
	private function filter($table, $info) {
		$driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
		if($driver == 'sqlite') {
			$sql = "PRAGMA table_info('" . $table . "');";
			$key = "name";
		}
		elseif($driver == 'mysql') {
			$sql = "DESCRIBE " . $table . ";";
			$key = "Field";
		}
		else {	
			$sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '" . $table . "';";
			$key = "column_name";
		}	

		if(false !== ($list = $this->run($sql))) {
			$fields = array();
			foreach($list as $record)
				$fields[] = $record[$key];
			return array_values(array_intersect($fields, array_keys($info)));
		}
		return array();
	}

	private function cleanup($bind) {
		if(!is_array($bind)) {
			if(!empty($bind))
				$bind = array($bind);
			else
				$bind = array();
		}
		return $bind;
	}

	/*INSERT statement.*/
	public function insert($table, $info) {
		$fields = $this->filter($table, $info);
		$sql = "INSERT INTO " . $table . " (" . implode($fields, ", ") . ") VALUES (:" . implode($fields, ", :") . ");";
		$bind = array();
		foreach($fields as $field)
			$bind[":$field"] = $info[$field];
		return $this->run($sql, $bind);
	}

	/*This method handles the actual execution of all sql statements within this class.  Because it is public, the run
	method can also be used to process custom sql statements.  Results will automatically be returned for the appropriate
	statement types.*/
	public function run($sql, $bind="") {
		if(isset($this->pdo)) {
			$this->sql = trim($sql);
			$this->bind = $this->cleanup($bind);
			$this->error = "";

			try {
				$pdostmt = $this->pdo->prepare($this->sql);
				if($pdostmt->execute($this->bind) !== false) {
					/*If appropriate, return the results in an associative array, or return the number of records that were
					affected by the query.*/
					if(preg_match("/^(" . implode("|", array("select", "describe", "pragma")) . ") /i", $this->sql))
						return $pdostmt->fetchAll(PDO::FETCH_ASSOC);
					elseif(preg_match("/^(" . implode("|", array("delete", "insert", "update")) . ") /i", $this->sql))
						return $pdostmt->rowCount();
				}	
			} catch (PDOException $e) {
				$this->error = $e->getMessage();	
				return false;
			}
		}
	}

	/*SELECT statement.*/
	public function select($table, $where="", $bind="", $fields="*") {
		$sql = "SELECT " . $fields . " FROM " . $table;
		if(!empty($where))
			$sql .= " WHERE " . $where;
		$sql .= ";";
		return $this->run($sql, $bind);
	}

	/*UPDATE statement.*/
	public function update($table, $info, $where, $bind="") {
		$fields = $this->filter($table, $info);
		$fieldSize = sizeof($fields);

		$sql = "UPDATE " . $table . " SET ";
		for($f = 0; $f < $fieldSize; ++$f) {
			if($f > 0)
				$sql .= ", ";
			$sql .= $fields[$f] . " = :update_" . $fields[$f]; 
		}
		$sql .= " WHERE " . $where . ";";

		$bind = $this->cleanup($bind);
		foreach($fields as $field)
			$bind[":update_$field"] = $info[$field];
		
		return $this->run($sql, $bind);
	}
}	
?>
