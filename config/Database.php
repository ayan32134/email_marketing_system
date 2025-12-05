<?php

class dataBase {
    private $servername ;
    private $username ;
    private $password ;
    private  $db_name;
    public $db;

    public function __construct( $db_config = [] ) {

        $this->servername = $db_config[ 'host' ] ?? 'localhost';     
        $this->username   = $db_config[ 'user' ] ?? 'root';
        $this->password   = $db_config[ 'password' ] ?? 'root';
        $this->db_name         = $db_config[ 'database' ] ?? 'email_marketing_system';

        $this->db = new mysqli( $this->servername, $this->username, $this->password, $this->db_name );

        if ( $this->db->connect_error ) {
            die( 'Connection failed: ' . $this->db->connect_error );
        }
    }

}
?>