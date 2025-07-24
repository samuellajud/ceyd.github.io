CREATE DATABASE Clientes_db;
USE Clientes_db;

CREATE TABLE Usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    situacion_empresarial ENUM('RI', 'PyME') NOT NULL,
    finanzas TEXT,
    balances TEXT,
    indices_propios TEXT
);