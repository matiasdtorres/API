<?php
class DatabaseSetup
{
    private $host = 'localhost';
    private $db = 'api';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4';
    public $pdo;

    public function __construct()
    {
        $dsn = "mysql:host=$this->host;charset=$this->charset";
        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try
        {
            $this->pdo = new \PDO($dsn, $this->user, $this->pass, $options);
        }
        catch (\PDOException $e)
        {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function createDatabase()
    {
        $this->pdo->exec("CREATE DATABASE IF NOT EXISTS $this->db");
    }

    public function connectToDatabase()
    {
        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try
        {
            $this->pdo = new \PDO($dsn, $this->user, $this->pass, $options);
        }
        catch (\PDOException $e)
        {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function createTables() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS mesas (
                numero INT AUTO_INCREMENT PRIMARY KEY,
                max_comensales INT NOT NULL,
                estado VARCHAR(255) NOT NULL,
                cuenta VARCHAR(255) NOT NULL,
                cerrada VARCHAR(255) NOT NULL
            )
        ");
    
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS pedidos (
                id_pedido INT AUTO_INCREMENT PRIMARY KEY,
                id_mesa INT NOT NULL,
                empleado_a_cargo VARCHAR(255) NOT NULL,
                producto VARCHAR(255) NOT NULL,
                cantidad INT NOT NULL,
                tiempo_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                tiempo_preparacion VARCHAR(255) NOT NULL
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS productos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tipo VARCHAR(255) NOT NULL,
                nombre VARCHAR(255) NOT NULL
            )
        ");
    
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                mail VARCHAR(255) NOT NULL,
                usuario VARCHAR(255) NOT NULL,
                contraseña VARCHAR(255) NOT NULL,
                perfil VARCHAR(255) NOT NULL
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS encuestas (
                encuenta INT AUTO_INCREMENT PRIMARY KEY,
                id_mesa VARCHAR(255) NOT NULL,
                id_pedido VARCHAR(255) NOT NULL,
                experiencia VARCHAR(255) NOT NULL
            )
        ");
    }
    
}
