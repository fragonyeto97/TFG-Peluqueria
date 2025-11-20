<?php
class Database {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $db_name = "peluqueria_db"; // 👈 asegúrate de que este nombre sea EXACTAMENTE igual al de phpMyAdmin
    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db_name);

            if ($this->conn->connect_error) {
                throw new Exception("Error de conexión: " . $this->conn->connect_error);
            }

        } catch (Exception $e) {
            echo "<div style='padding:10px; background:#fee; color:#900;'>
                    ❌ No se pudo conectar: " . $e->getMessage() . "
                  </div>";
            return false;
        }

        return $this->conn;
    }
}
?>
