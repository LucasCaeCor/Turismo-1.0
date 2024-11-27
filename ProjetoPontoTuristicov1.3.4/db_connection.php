<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "telemaco_turismo";

// Configura o MySQLi para lançar exceções em caso de erro
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);

} catch (mysqli_sql_exception $e) {
    // Exibe uma mensagem amigável e detalha o erro
    die("Erro na conexão: " . $e->getMessage());
}
?>
