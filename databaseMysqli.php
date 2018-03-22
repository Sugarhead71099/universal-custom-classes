<?php

	// Make sure to include "config.inc.php" in your main file

	if ( !class_exists('DatabaseMysqli') )
	{
		class DatabaseMysqli
		{

			private $host;
			private $user;
			private $pass;
			private $dbName;

			protected function connect()
			{
				$this->host = DB_HOST;
				$this->user = DB_USER;
				$this->pass = DB_PASS;
				$this->dbName = DB_NAME;

				$mysqli = new mysqli($this->host, $this->user, $this->pass,
									 $this->dbName);
				return $mysqli;
			}

			protected function query($query)
			{
				$mysqli = $this->connect();
				$result = $mysqli->query($query);

				$results = array();
				while ( $row = $result->fetch_object() )
				{
					$results[] = $row;
				}

				return $results;
			}

			protected function insert($table, $data, $format)
			{
				if ( empty( $table ) || empty( $data ) )
				{
					return false;
				}

				$mysqli = $this->connect();

				$data = (array) $data;
				$format = (array) $format;

				$format = implode('', $format);

				list( $fields, $placeholders, $values ) = $this->prep_query($data);

				array_unshift($values, $format);

				$stmt = $mysqli->prepare("INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})");

				call_user_func_array( array( $stmt, 'bind_param'), $this->ref_values($values) );

				$stmt->execute();

				if ( $stmt->affected_rows )
				{
					return true;
				}

				return false;
			}

			protected function update($table, $data, $format,
									  $where, $where_format)
			{
				if ( empty( $table ) || empty( $data ) )
				{
					return false;
				}

				$mysqli = $this->connect();

				$data = (array) $data;
				$format = (array) $format;


				$format = implode('', $format); 
				$where_format = implode('', $where_format);
				$format .= $where_format;

				list( $fields, $placeholders, $values ) = $this->prep_query($data, 'update');

				$where_clause = '';
				$where_values = '';
				$count = 0;

				foreach ( $where as $field => $value )
				{
					if ( $count > 0 )
					{
						$where_clause .= ' AND ';
					}

					$where_clause .= $field . '=?';
					$where_values[] = $value;

					$count++;
				}

				array_unshift($values, $format);
				$values = array_merge($values, $where_values);

				$stmt = $mysqli->prepare("UPDATE {$table} SET {$placeholders} WHERE {$where_clause}");

				call_user_func_array( array( $stmt, 'bind_param'), $this->ref_values($values) );

				$stmt->execute();

				if ( $stmt->affected_rows )
				{
					return true;
				}

				return false;
			}

			protected function get_results($query)
			{
				return $this->query($query);
			}

			protected function get_row($query) {
				$results = $this->query($query);

				return $results[0];
			}

			protected function delete($table, $id)
			{
				$mysqli = $this->connect();

				$stmt = $mysqli->prepare("DELETE FROM {$table} WHERE ID = ?");
				$stmt->bind_param('d', $id);
				$stmt->execute();

				if ( $stmt->affected_rows )
				{
					return true;
				}
			}

			private function prep_query($data, $type = 'insert')
			{
				$fields = '';
				$placeholders = '';
				$values = array();

				foreach ( $data as $field => $value )
				{
					$fields .= "{$field},";
					$values[] = $value;

					if ( $type === 'update' )
					{
						$placeholders .= $field . '=?,';
					} else
					{
						$placeholders .= '?,';
					}
				}
				
				// Normalize $fields and $placeholders for inserting
				$fields = substr($fields, 0, -1);
				$placeholders = substr($placeholders, 0, -1);

				return array( $fields, $placeholders, $values );
			}

			private function ref_values($array)
			{
				$refs = array();

				foreach ($array as $key => $value)
				{
					$refs[$key] = &$array[$key]; 
				}

				return $refs; 
			}

		}
	}

?>
