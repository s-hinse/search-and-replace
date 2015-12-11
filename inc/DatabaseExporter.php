<?php
/**
 * Handles export of DB
 * adapted from https://github.com/matzko/insr
 */

namespace Inpsyde\SearchReplace\inc;

class DatabaseExporter {

	/**
	 * @Stores all error messages in a WP_Error Object
	 */
	protected $errors;
	/**
	 * @string  The Path to the Backup Directory
	 */
	protected $backup_dir;

	public function  __construct() {

		$this->errors = new \WP_Error();

		$this->backup_dir = get_temp_dir();

	}

	/**
	 * Taken partially from phpMyAdmin and partially from
	 * Alain Wolf, Zurich - Switzerland
	 * Website: http://restkultur.ch/personal/wolf/scripts/db_backup/
	 * Modified by Scott Merrill (http://www.skippy.net/)
	 * to use the WordPress $wpdb object
	 *
	 * @param string $table
	 * @param string $segment
	 *
	 * @return void
	 */
	//TODO:  DB access via DatabaseManager
	public function backup_table( $table, $segment = 'none' ) {

		global $wpdb;

		$table_structure = $wpdb->get_results( "DESCRIBE $table" );
		if ( ! $table_structure ) {
			$this->errors->add( 1, __( 'Error getting table details', 'insr' ) . ": $table" );

			return;
		}

		if ( ( $segment == 'none' ) || ( $segment == 0 ) ) {
			// Add SQL statement to drop existing table
			$this->stow( "\n\n" );
			$this->stow( "#\n" );
			$this->stow( "# " . sprintf( __( 'Delete any existing table %s', 'insr' ),
			                             $this->backquote( $table ) ) . "\n" );
			$this->stow( "#\n" );
			$this->stow( "\n" );
			$this->stow( "DROP TABLE IF EXISTS " . $this->backquote( $table ) . ";\n" );

			// Table structure
			// Comment in SQL-file
			$this->stow( "\n\n" );
			$this->stow( "#\n" );
			$this->stow( "# " . sprintf( __( 'Table structure of table %s', 'insr' ),
			                             $this->backquote( $table ) ) . "\n" );
			$this->stow( "#\n" );
			$this->stow( "\n" );

			$create_table = $wpdb->get_results( "SHOW CREATE TABLE $table", ARRAY_N );
			if ( $create_table === FALSE ) {
				$err_msg = sprintf( __( 'Error with SHOW CREATE TABLE for %s.', 'insr' ), $table );
				$this->errors->add( 2, $err_msg );
				$this->stow( "#\n# $err_msg\n#\n" );
			}
			$this->stow( $create_table[ 0 ][ 1 ] . ' ;' );

			if ( $table_structure === FALSE ) {
				$err_msg = sprintf( __( 'Error getting table structure of %s', 'insr' ), $table );
				$this->errors->add( 3, $err_msg );
				$this->stow( "#\n# $err_msg\n#\n" );
			}

			// Comment in SQL-file
			$this->stow( "\n\n" );
			$this->stow( "#\n" );
			$this->stow( '# ' . sprintf( __( 'Data contents of table %s', 'insr' ),
			                             $this->backquote( $table ) ) . "\n" );
			$this->stow( "#\n" );
		}

		if ( ( $segment == 'none' ) || ( $segment >= 0 ) ) {
			$defs = array();
			$ints = array();
			foreach ( $table_structure as $struct ) {
				if ( ( 0 === strpos( $struct->Type, 'tinyint' ) )
				     || ( 0 === strpos( strtolower( $struct->Type ), 'smallint' ) )
				     || ( 0 === strpos( strtolower( $struct->Type ), 'mediumint' ) )
				     || ( 0 === strpos( strtolower( $struct->Type ), 'int' ) )
				     || ( 0 === strpos( strtolower( $struct->Type ), 'bigint' ) )
				) {
					$defs[ strtolower( $struct->Field ) ] = ( NULL === $struct->Default ) ? 'NULL' : $struct->Default;
					$ints[ strtolower( $struct->Field ) ] = "1";
				}
			}

			// Batch by $row_inc

			if ( $segment == 'none' ) {
				$row_start = 0;
				$row_inc   = ROWS_PER_SEGMENT;
			} else {
				$row_start = $segment * ROWS_PER_SEGMENT;
				$row_inc   = ROWS_PER_SEGMENT;
			}

			do {
				// don't include extra stuff, if so requested
				//TODO: Check if this might be useful later on
				$where = '';
				/*$excs = (array) get_option('wp_db_backup_excs');

				if ( is_array($excs['spam'] ) && in_array($table, $excs['spam']) ) {
					$where = ' WHERE comment_approved != "spam"';
				} elseif ( is_array($excs['revisions'] ) && in_array($table, $excs['revisions']) ) {
					$where = ' WHERE post_type != "revision"';
				}

				if ( !ini_get('safe_mode')) @set_time_limit(15*60);*/
				$table_data = $wpdb->get_results( "SELECT * FROM $table $where LIMIT {$row_start}, {$row_inc}",
				                                  ARRAY_A );

				$entries = 'INSERT INTO ' . $this->backquote( $table ) . ' VALUES (';
				//    \x08\\x09, not required
				$search  = array( "\x00", "\x0a", "\x0d", "\x1a" );
				$replace = array( '\0', '\n', '\r', '\Z' );
				if ( $table_data ) {
					foreach ( $table_data as $row ) {
						$values = array();
						foreach ( $row as $key => $value ) {
							if ( isset ( $ints[ strtolower( $key ) ] ) ) {
								// make sure there are no blank spots in the insert syntax,
								// yet try to avoid quotation marks around integers
								$value    = ( NULL === $value || '' === $value ) ? $defs[ strtolower( $key ) ] : $value;
								$values[] = ( '' === $value ) ? "''" : $value;
							} else {
								$values[] = "'" . str_replace( $search, $replace,
								                               $this->sql_addslashes( $value ) ) . "'";
							}
						}
						$this->stow( " \n" . $entries . implode( ', ', $values ) . ');' );
					}
					$row_start += $row_inc;
				}
			} while ( ( count( $table_data ) > 0 ) and ( $segment == 'none' ) );
		}

		if ( ( $segment == 'none' ) || ( $segment < 0 ) ) {
			// Create footer/closing comment in SQL-file
			$this->stow( "\n" );
			$this->stow( "#\n" );
			$this->stow( "# " . sprintf( __( 'End of data contents of table %s', 'insr' ),
			                             $this->backquote( $table ) ) . "\n" );
			$this->stow( "# --------------------------------------------------------\n" );
			$this->stow( "\n" );
		}
	} // end backup_table()

	/**
	 * Better addslashes for SQL queries.
	 * Taken from phpMyAdmin.
	 */
	protected function sql_addslashes( $a_string = '', $is_like = FALSE ) {

		if ( $is_like ) {
			$a_string = str_replace( '\\', '\\\\\\\\', $a_string );
		} else {
			$a_string = str_replace( '\\', '\\\\', $a_string );
		}

		return str_replace( '\'', '\\\'', $a_string );
	}

	/**
	 * Add backquotes to tables and db-names in
	 * SQL queries. Taken from phpMyAdmin.
	 */
	protected function backquote( $a_name ) {

		if ( ! empty( $a_name ) && $a_name != '*' ) {
			if ( is_array( $a_name ) ) {
				$result = array();
				reset( $a_name );
				while ( list( $key, $val ) = each( $a_name ) ) {
					$result[ $key ] = '`' . $val . '`';
				}

				return $result;
			} else {
				return '`' . $a_name . '`';
			}
		} else {
			return $a_name;
		}
	}

	protected function open( $filename = '', $mode = 'w' ) {

		if ( $filename == '' ) {
			return FALSE;
		}
		$fp = @fopen( $filename, $mode );

		return $fp;
	}

	function close( $fp ) {

		fclose( $fp );
	}

	/**
	 * Write to the backup file
	 *
	 * @param string $query_line the line to write
	 *
	 * @return null
	 */
	protected function stow( $query_line ) {

		if ( @fwrite( $this->fp, $query_line ) === FALSE ) {
			$this->errors->add( 4, __( 'There was an error writing a line to the backup script:',
			                           'insr' ) . '  ' . $query_line . '  ' . $php_errormsg );
		}
	}

	protected function backup_fragment( $table, $segment, $filename ) {

		if ( $table == '' ) {
			$msg = __( 'Creating backup file...', 'insr' );
		} else {
			if ( $segment == - 1 ) {
				$msg = sprintf( __( 'Finished backing up table \\"%s\\".', 'insr' ), $table );
			} else {
				$msg = sprintf( __( 'Backing up table \\"%s\\"...', 'insr' ), $table );
			}
		}

		if ( is_writable( $this->backup_dir ) ) {
			$this->fp = $this->open( $this->backup_dir . $filename, 'a' );
			if ( ! $this->fp ) {
				$this->errors->add( 5, __( 'Could not open the backup file for writing!', 'insr' ) );
				$this->errors->add( 6,
				                    __( 'The backup file could not be saved.  Please check the permissions for writing to your backup directory and try again.',
				                        'insr' ) );
			} else {
				if ( $table == '' ) {
					//Begin new backup of MySql
					$this->stow( "# " . __( 'WordPress MySQL database backup', 'insr' ) . "\n" );
					$this->stow( "#\n" );
					$this->stow( "# " . sprintf( __( 'Generated: %s', 'insr' ),
					                             date( "l j. F Y H:i T" ) ) . "\n" );
					$this->stow( "# " . sprintf( __( 'Hostname: %s', 'insr' ), DB_HOST ) . "\n" );
					$this->stow( "# " . sprintf( __( 'Database: %s', 'insr' ),
					                             $this->backquote( DB_NAME ) ) . "\n" );
					$this->stow( "# --------------------------------------------------------\n" );
				} else {
					if ( $segment == 0 ) {
						// Increase script execution time-limit to 15 min for every table.
						if ( ! ini_get( 'safe_mode' ) ) {
							@set_time_limit( 15 * 60 );
						}
						// Create the SQL statements
						$this->stow( "# --------------------------------------------------------\n" );
						$this->stow( "# " . sprintf( __( 'Table: %s', 'insr' ),
						                             $this->backquote( $table ) ) . "\n" );
						$this->stow( "# --------------------------------------------------------\n" );
					}
					$this->backup_table( $table, $segment );
				}
			}
		} else {
			$this->errors->add( 7,
			                    __( 'The backup directory is not writeable!  Please check the permissions for writing to your backup directory and try again.',
			                        'insr' ) );
		}

		if ( $this->fp ) {
			$this->close( $this->fp );
		}

		return;
	}

	function db_backup( $tables ) {

		global $table_prefix, $wpdb;

		$table_prefix          = ( isset( $table_prefix ) ) ? $table_prefix : $wpdb->prefix;
		$datum                 = date( "Ymd_B" );
		$this->backup_filename = DB_NAME . "_$table_prefix$datum.sql";

		if ( is_writable( $this->backup_dir ) ) {
			$this->fp = $this->open( $this->backup_dir . $this->backup_filename );
			if ( ! $this->fp ) {
				$this->errors->add( 8, __( 'Could not open the backup file for writing!', 'insr' ) );

				return FALSE;
			}
		} else {
			$this->errors->add( 9, __( 'The backup directory is not writeable!', 'insr' ) );

			return FALSE;
		}

		//Begin new backup of MySql
		$this->stow( "# " . __( 'WordPress MySQL database backup', 'insr' ) . "\n" );
		$this->stow( "#\n" );
		$this->stow( "# " . sprintf( __( 'Generated: %s', 'insr' ), date( "l j. F Y H:i T" ) ) . "\n" );
		$this->stow( "# " . sprintf( __( 'Hostname: %s', 'insr' ), DB_HOST ) . "\n" );
		$this->stow( "# " . sprintf( __( 'Database: %s', 'insr' ), $this->backquote( DB_NAME ) ) . "\n" );
		$this->stow( "# --------------------------------------------------------\n" );

		foreach ( $tables as $table ) {
			// Increase script execution time-limit to 15 min for every table.
			if ( ! ini_get( 'safe_mode' ) ) {
				@set_time_limit( 15 * 60 );
			}
			// Create the SQL statements
			$this->stow( "# --------------------------------------------------------\n" );
			$this->stow( "# " . sprintf( __( 'Table: %s', 'insr' ), $this->backquote( $table ) ) . "\n" );
			$this->stow( "# --------------------------------------------------------\n" );
			$this->backup_table( $table );
		}

		$this->close( $this->fp );

		if ( count( $this->errors->get_error_codes() ) ) {
			return $this->errors;
		} else {
			return $this->backup_filename;
		}

	}

	/**
	 * @param string $filename The name of the file to be downloaded
	 * @param bool   $compress If TRUE, gz compression is used
	 *
	 * @return bool TRUE if delivery was successful
	 */
	function deliver_backup( $filename = '', $compress = FALSE ) {

		if ( $filename == '' ) {
			return FALSE;
		}

		$diskfile = $this->backup_dir . $filename;
		//compress file if set
		if ( $compress ) {
			$gz_diskfile = "{$diskfile}.gz";

			/**
			 * Try upping the memory limit before gzipping
			 */
			if ( function_exists( 'memory_get_usage' ) && ( (int) @ini_get( 'memory_limit' ) < 64 ) ) {
				@ini_set( 'memory_limit', '64M' );
			}

			if ( file_exists( $diskfile ) && empty( $_GET[ 'download-retry' ] ) ) {
				/**
				 * Try gzipping with an external application
				 */
				if ( file_exists( $diskfile ) && ! file_exists( $gz_diskfile ) ) {
					@exec( "gzip $diskfile" );
				}

				if ( file_exists( $gz_diskfile ) ) {
					if ( file_exists( $diskfile ) ) {
						unlink( $diskfile );
					}
					$diskfile = $gz_diskfile;
					$filename = "{$filename}.gz";

					/**
					 * Try to compress to gzip, if available
					 */
				} else {
					if ( function_exists( 'gzencode' ) ) {
						if ( function_exists( 'file_get_contents' ) ) {
							$text = file_get_contents( $diskfile );
						} else {
							$text = implode( "", file( $diskfile ) );
						}
						$gz_text = gzencode( $text, 9 );
						$fp      = fopen( $gz_diskfile, "w" );
						fwrite( $fp, $gz_text );
						if ( fclose( $fp ) ) {
							unlink( $diskfile );
							$diskfile = $gz_diskfile;
							$filename = "{$filename}.gz";
						}
					}
				}
				/*
				 *
				 */
			} elseif ( file_exists( $gz_diskfile ) && empty( $_GET[ 'download-retry' ] ) ) {
				$diskfile = $gz_diskfile;
				$filename = "{$filename}.gz";
			}
		}

		//provide file for download
		if ( file_exists( $diskfile ) ) {
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Length: ' . filesize( $diskfile ) );
			header( "Content-Disposition: attachment; filename=$filename" );
			$success = readfile( $diskfile );
			if ( $success ) {
				unlink( $diskfile );
			}
		}

		return $this->errors;
	}

	/**
	 * @return string
	 */
	public function getBackupDir() {

		return $this->backup_dir;
	}

	/**
	 * @string  $backup_dir
	 */
	public function setBackupDir( $backup_dir ) {

		$this->backup_dir = $backup_dir;
	}

}