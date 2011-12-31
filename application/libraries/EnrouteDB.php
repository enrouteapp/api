<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class EnrouteDB {

	private $tables = array(
		/* store binary photos for people and cars */
		"photo" => "CREATE TABLE IF NOT EXISTS photo(
				photo_id INT UNSIGNED UNIQUE NOT NULL AUTO_INCREMENT,
				type VARCHAR(25) NOT NULL DEFAULT '',
				mime VARCHAR(25) NOT NULL DEFAULT '',
				width INT UNSIGNED NOT NULL DEFAULT 0, 
				height INT UNSIGNED NOT NULL DEFAULT 0, 
				size VARCHAR(25) NOT NULL DEFAULT '',
				photo MEDIUMBLOB NOT NULL, 
				PRIMARY KEY (photo_id),
				INDEX(photo_id)
			) ENGINE=INNODB",
		/* store information for a phone */
		"phone" => "CREATE TABLE IF NOT EXISTS phone(
				phone_id INT UNSIGNED UNIQUE NOT NULL AUTO_INCREMENT,
				carrier VARCHAR(25) NOT NULL DEFAULT '',
				number INT UNSIGNED UNIQUE NOT NULL,
				PRIMARY KEY (phone_id),
				INDEX(phone_id), INDEX(number)
			) ENGINE=INNODB",
		/* store information for a place, allow for dynamic linking to a phone */
		"place" => "CREATE TABLE IF NOT EXISTS place(
				place_id INT UNSIGNED UNIQUE NOT NULL AUTO_INCREMENT,
				name VARCHAR(25) NOT NULL DEFAULT '',
				address1 VARCHAR(50) NOT NULL DEFAULT '',
				address2 VARCHAR(50) NOT NULL DEFAULT '',
				address3 VARCHAR(50) NOT NULL DEFAULT '',
				city VARCHAR(50) NOT NULL DEFAULT '',
				state VARCHAR(25) NOT NULL DEFAULT '',
				postal INT UNSIGNED NOT NULL DEFAULT 0,
				country VARCHAR(25) NOT NULL DEFAULT 'USA',
				gps_lat FLOAT NOT NULL DEFAULT 0,
				gps_lon FLOAT NOT NULL DEFAULT 0,
				PRIMARY KEY (place_id),
				INDEX(place_id)
			) ENGINE=INNODB",
		/* store account information for a person */
		"person" => "CREATE TABLE IF NOT EXISTS person(
				person_id INT UNSIGNED UNIQUE NOT NULL AUTO_INCREMENT,
				photo_id INT UNSIGNED,
				phone_id INT UNSIGNED,
				place_id INT UNSIGNED, 
				fullname VARCHAR(50) NOT NULL DEFAULT '', 
				email VARCHAR(50) UNIQUE NOT NULL DEFAULT '', 
				password VARCHAR(50) NOT NULL DEFAULT '',
				facebook_token VARCHAR(128) NOT NULL DEFAULT '',
				facebook_username VARCHAR(50) NOT NULL DEFAULT '',
				date_created INT NOT NULL DEFAULT 0,
				date_last_seen INT NOT NULL DEFAULT 0,
				PRIMARY KEY (person_id),
				FOREIGN KEY (photo_id) REFERENCES photo (photo_id) ON UPDATE CASCADE,
				FOREIGN KEY (phone_id) REFERENCES phone (phone_id) ON UPDATE CASCADE,
				FOREIGN KEY (place_id) REFERENCES place (place_id) ON UPDATE CASCADE,
				INDEX(person_id)
			) ENGINE=INNODB",
		/* store list of favorite places for each person */
		"fave_places" => "CREATE TABLE IF NOT EXISTS fave_places(
				person_id INT UNSIGNED NOT NULL, 
				place_id INT UNSIGNED NOT NULL,
				FOREIGN KEY (person_id) REFERENCES person (person_id) ON DELETE CASCADE ON UPDATE CASCADE,
				FOREIGN KEY (place_id) REFERENCES place (place_id) ON DELETE CASCADE ON UPDATE CASCADE,
				INDEX(person_id)
			) ENGINE=INNODB",
		/* store location history for a phone */
		"phone_history" => "CREATE TABLE IF NOT EXISTS phone_history(
				phone_id INT UNSIGNED NOT NULL,
				timestamp INT UNSIGNED NOT NULL,
				gps_lat FLOAT NOT NULL,
				gps_lon FLOAT NOT NULL,
				FOREIGN KEY (phone_id) REFERENCES phone (phone_id) ON DELETE CASCADE ON UPDATE CASCADE,
				INDEX(phone_id)
			) ENGINE=INNODB",
		/* store information for a car */
		"car" => "CREATE TABLE IF NOT EXISTS car(
				car_id INT UNSIGNED UNIQUE NOT NULL AUTO_INCREMENT,
				owner_id INT UNSIGNED NOT NULL, 
				name VARCHAR(25) NOT NULL DEFAULT '',
				make VARCHAR(25) NOT NULL DEFAULT '',
				model VARCHAR(25) NOT NULL DEFAULT '',
				color VARCHAR(25) NOT NULL DEFAULT '',
				photo_id INT UNSIGNED,
				PRIMARY KEY (car_id),
				FOREIGN KEY (owner_id) REFERENCES person (person_id) ON DELETE CASCADE ON UPDATE CASCADE,
				FOREIGN KEY (photo_id) REFERENCES photo (photo_id) ON UPDATE CASCADE,
				INDEX(car_id)
			) ENGINE=INNODB",
		/* store location history for a car */
		"car_history" => "CREATE TABLE IF NOT EXISTS car_history(
				car_id INT UNSIGNED NOT NULL,
				timestamp INT UNSIGNED NOT NULL,
				gps_lat FLOAT NOT NULL,
				gps_lon FLOAT NOT NULL,
				FOREIGN KEY (car_id) REFERENCES car (car_id) ON DELETE CASCADE ON UPDATE CASCADE,
				INDEX(car_id)
			) ENGINE=INNODB",
		/* store information for a route */
		"route" => "CREATE TABLE IF NOT EXISTS route(
				route_id INT UNSIGNED UNIQUE NOT NULL AUTO_INCREMENT,
				owner_id INT UNSIGNED NOT NULL,
				car_id INT UNSIGNED NOT NULL,
				name VARCHAR(50) NOT NULL DEFAULT '',
				description VARCHAR(250) NOT NULL DEFAULT '',
				origin_id INT UNSIGNED NOT NULL, 
				destination_id INT UNSIGNED NOT NULL, 
				PRIMARY KEY (route_id),
				FOREIGN KEY (owner_id) REFERENCES person (person_id) ON DELETE CASCADE ON UPDATE CASCADE,
				FOREIGN KEY (car_id) REFERENCES car (car_id) ON UPDATE CASCADE,
				FOREIGN KEY (origin_id) REFERENCES place (place_id) ON DELETE CASCADE ON UPDATE CASCADE,
				FOREIGN KEY (destination_id) REFERENCES place (place_id) ON DELETE CASCADE ON UPDATE CASCADE,
				INDEX(route_id), INDEX(owner_id), INDEX(car_id)
			) ENGINE=INNODB",
		/* keep track of riders for a particular route */
		"rider" => "CREATE TABLE IF NOT EXISTS rider(
				route_id INT UNSIGNED NOT NULL,
				rider_id INT UNSIGNED NOT NULL,
				place_id INT UNSIGNED, 
				phone_id INT UNSIGNED, 
				updated INT UNSIGNED, 
				active BOOLEAN NOT NULL DEFAULT TRUE,
				position INT UNSIGNED NOT NULL DEFAULT 0,
				FOREIGN KEY (route_id) REFERENCES route (route_id) ON DELETE CASCADE ON UPDATE CASCADE,
				FOREIGN KEY (rider_id) REFERENCES person (person_id) ON DELETE CASCADE ON UPDATE CASCADE,
				FOREIGN KEY (place_id) REFERENCES place (place_id) ON DELETE CASCADE ON UPDATE CASCADE,
				FOREIGN KEY (phone_id) REFERENCES phone (phone_id) ON DELETE CASCADE ON UPDATE CASCADE,
				INDEX(route_id,active), INDEX(rider_id)
			) ENGINE=INNODB",
		/* keep track of stops for a particular route */
		"poi" => "CREATE TABLE IF NOT EXISTS poi(
				route_id INT UNSIGNED NOT NULL,
				place_id INT UNSIGNED NOT NULL,
				active BOOLEAN NOT NULL DEFAULT TRUE,
				position INT UNSIGNED NOT NULL DEFAULT 0,
				FOREIGN KEY (route_id) REFERENCES route (route_id) ON DELETE CASCADE ON UPDATE CASCADE,
				FOREIGN KEY (place_id) REFERENCES place (place_id) ON DELETE CASCADE ON UPDATE CASCADE,
				INDEX(route_id,active), INDEX(route_id,position)
			) ENGINE=INNODB",
		/* keep track of messages passed between people */
		"message" => "CREATE TABLE IF NOT EXISTS message(
				message_id INT UNSIGNED UNIQUE NOT NULL AUTO_INCREMENT,
				sender_id INT UNSIGNED NOT NULL,
				recipient_id INT UNSIGNED NOT NULL,
				message VARCHAR(250) NOT NULL DEFAULT '',
				timestamp INT UNSIGNED NOT NULL DEFAULT 0, 
				FOREIGN KEY (sender_id) REFERENCES person (person_id) ON DELETE CASCADE ON UPDATE CASCADE,
				FOREIGN KEY (recipient_id) REFERENCES person (person_id) ON DELETE CASCADE ON UPDATE CASCADE,
				INDEX(message_id), INDEX(sender_id), INDEX(recipient_id)
			) ENGINE=INNODB",
		/* keep track of debts between people */
		"debt" => "CREATE TABLE IF NOT EXISTS debt(
				debtor_id INT UNSIGNED NOT NULL,
				creditor_id INT UNSIGNED NOT NULL,
				amount FLOAT NOT NULL DEFAULT 0,
				FOREIGN KEY (debtor_id) REFERENCES person (person_id) ON DELETE CASCADE ON UPDATE CASCADE,
				FOREIGN KEY (creditor_id) REFERENCES person (person_id) ON DELETE CASCADE ON UPDATE CASCADE,
				INDEX(debtor_id), INDEX(creditor_id)
			) ENGINE=INNODB",
		/* keep track of api keys */
		"api_key" => "CREATE TABLE IF NOT EXISTS api_key (
				api_key VARCHAR(50) UNIQUE NOT NULL, 
				level INT UNSIGNED NOT NULL,
				ignore_limits BOOLEAN NOT NULL DEFAULT FALSE, 
				date_created INT UNSIGNED NOT NULL, 
				PRIMARY KEY (api_key),
				INDEX(api_key)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
		/* keep track of api key limits */
		"api_limit" => "CREATE TABLE IF NOT EXISTS api_limit (
				id INT UNSIGNED NOT NULL AUTO_INCREMENT,
				uri VARCHAR(255) NOT NULL,
				count INT UNSIGNED NOT NULL,
				hour_started INT UNSIGNED NOT NULL,
				api_key VARCHAR(50) NOT NULL,
				PRIMARY KEY (id),
				FOREIGN KEY (api_key) REFERENCES api_key (api_key) ON DELETE CASCADE ON UPDATE CASCADE,
				INDEX(api_key)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;"
	);
	
	private $views = array(
		"phone_state" => "CREATE VIEW phone_state AS
			SELECT phone.*, phone_history.timestamp, phone_history.gps_lat, phone_history.gps_lon FROM phone, phone_history
			WHERE phone_history.phone_id = phone.phone_id AND phone_history.timestamp IN (
				SELECT timestamp, MAX(timestamp) FROM phone_history
			)",
		"car_state" => "CREATE VIEW car_state AS
			SELECT car.*, car_history.timestamp, car_history.gps_lat, car_history.gps_lon FROM car, car_history 
			WHERE car_history.car_id = car.car_id AND car_history.timestamp IN (
				SELECT timestamp, MAX(timestamp) FROM car_history
			)",
		"route_stops" => "CREATE VIEW route_stops AS
			SELECT route.route_id, stop.position, place.* FROM route, stop, place
			WHERE route.route_id = stop.route_id AND stop.stop_id = place.place_id
			ORDER BY route.route_id ASC, stop.position ASC"
	);
	
	private $constraints = array(
	);
	
	private $ci = NULL;
	private $backup_path = "./application/backup/";
	private $backup_file_prefix = "enroute_db_backup_";
	private $backup_file_type = "zip";
	
	public function __construct() {
		// get an instance of the original CI object
		$this->ci = &get_instance();
		$this->ci->load->database("default");
		$this->ci->load->helper('file');
		$this->ci->load->helper('url');
		$this->ci->load->dbutil();
	}
	
	public function reset() {
		$summary = array();
		// just to be safe, let's backup all tables to a file
		$date = date("d-M-Y_H:i:s");
		$prefs = array(
			"tables" => array_keys($this->tables),
			"ignore" => array_keys($this->views),
			"format" => $this->backup_file_type,
			"filename" => $this->backup_file_prefix.$date,
			"add_drop" => TRUE,
			"add_insert" => TRUE,
			"newline" => "\n"
		);
		
		// determine if it's possible to run a backup
		$run_backup = TRUE;
		$active_tables = $this->ci->db->list_tables();
		foreach ( array_keys($this->tables) as $saved_table ) {
			if ( !in_array($saved_table,$active_tables) ) {
				$run_backup = FALSE;
			}
		}
		foreach ( array_keys($this->views) as $saved_views ) {
			if ( !in_array($saved_views,$active_tables) ) {
				$run_backup = FALSE;
			}
		}

		// perform the backup
		if ( $run_backup ) {
			$backup = &$this->ci->dbutil->backup($prefs);
			// save it to the backup directory
			$file = $this->backup_path.$this->backup_file_prefix.$date.".".$this->backup_file_type;
			$status = write_file($file,$backup);
			if ( $status ) $result = "SUCCESS";
			else $result = "ERROR";
			// get file properties
			$props = get_file_info($file);
			$summary[] = $this->state("BACKUP FULL DATABASE",$file,$result,$props);
		}
		
		// drop all tables from the database
		// this is a bit scary
		$this->ci->db->simple_query("SET foreign_key_checks = 0");
		foreach ( $this->views as $name => $sql ) {
			$sql = "DROP VIEW IF EXISTS $name";
			if ( $this->ci->db->query($sql) ) {
				$summary[] = $this->state("DROP VIEW",$name,"SUCCESS",$sql);
			}
			else $summary[] = $this->state("DROP VIEW",$name,"ERROR",$sql);
		}
		foreach ( $this->tables as $name => $sql ) {
			$sql = "DROP TABLE IF EXISTS $name";
			if ( $this->ci->db->query($sql) ) {
				$summary[] = $this->state("DROP TABLE",$name,"SUCCESS",$sql);
			}
			else $summary[] = $this->state("DROP TABLE",$name,"ERROR",$sql);
		}
		$this->ci->db->simple_query("SET foreign_key_checks = 1");
		// load up to date database schema
		foreach ( $this->tables as $name => $sql ) {
			if ( $this->ci->db->query($sql) ) {
				$summary[] = $this->state("CREATE TABLE",$name,"SUCCESS",$sql);
			}
			else $summary[] = $this->state("CREATE TABLE",$name,"ERROR",$sql);
		}
		foreach ( $this->views as $name => $sql ) {
			if ( $this->ci->db->query($sql) ) {
				$summary[] = $this->state("CREATE VIEW",$name,"SUCCESS",$sql);
			}
			else $summary[] = $this->state("CREATE VIEW",$name,"ERROR",$sql);
		}
		foreach ( $this->constraints as $name => $sql ) {
			if ( $this->ci->db->query($sql) ) {
				$summary[] = $this->state("CREATE CONSTRAINT",$name,"SUCCESS",$sql);
			}
			else $summary[] = $this->state("CREATE CONSTRAINT",$name,"ERROR",$sql);
		}
		// return a summary of all changes
		return $summary;
	}
	
	public function saved_schema() {
		$database = array();
		$database["tables"] = $this->tables;
		$database["views"] = $this->views;
		return $database;
	}
	
	public function current_schema() {
		// get a list of current tables
		$table_listing = $this->ci->db->list_tables();
		$tables = array();
		// get metadata for each table
		foreach ( $table_listing as $table ) {
			if ( in_array($table,array_keys($this->tables)) ) {
				$tables[$table] = array();
				$fields_data = $this->ci->db->field_data($table);
				foreach ( $fields_data as $field ) {
					$tables[$table][$field->name] = $field;
				}
			}
		}
		return $tables;
	}
	
	public function repair() {
		$table_listing = $this->ci->db->list_tables();
		$summary = array();
		foreach ( $table_listing as $table ) {
			$result = $this->ci->dbutil->repair_table($table);
			$summary[$table] = array();
			$summary[$table]["table"] = $table;
			if ( $result ) $summary[$table]["repair_result"] = "SUCCESS";
			else $summary[$table]["repair_result"] = "FAILED";
		}
		return $summary;
	}
	
	public function optimize() {
		$table_listing = $this->ci->db->list_tables();
		$summary = array();
		foreach ( $table_listing as $table ) {
			$result = $this->ci->dbutil->optimize_table($table);
			$summary[$table] = array();
			$summary[$table]["table"] = $table;
			if ( $result ) $summary[$table]["optimization_result"] = "SUCCESS";
			else $summary[$table]["repair_result"] = "FAILED";
		}
		return $summary;
	}
	
	public function restore() {
	
	}
	
	private function state($command,$name,$result,$sql) {
		return array("command"=>$command,"name"=>$name,"result"=>$result,"sql"=>$sql);
	}

}

?>