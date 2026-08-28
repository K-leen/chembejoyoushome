<?php
/**
 * dbconnection.php
 *
 * Provides a getDbConnection() function that returns a PDO connected to the
 * MySQL database named `contact`.
 *
 * Configure the $host, $user and $pass variables below for your environment.
 */

function getDbConnection(): PDO
{
    $host = 'localhost';       // database host
    $db   = 'contact';         // database name (as requested)
    $user = 'admin';         // TODO: replace with your DB username
    $pass = 'admin123';         // TODO: replace with your DB password
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // In production you might log the error instead of exposing it.
        // Re-throwing so the caller can handle it appropriately.
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
}
