<?php
session_start();
include 'db_connection.php';

if ($_SESSION['usuario_tipo'] != 'admin') {
    header("Location: index.php");
    exit;
}

$query = "SELECT * FROM pontos_turisticos";
$resultado = $conn->query($query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $categoria = $_POST['categoria'];
    $imagem_url = $_POST['imagem_url'];

    $stmt = $conn->prepare("INSERT INTO pontos_turisticos (nome, descricao, latitude, longitude, categoria, imagem_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddss", $nome, $descricao, $latitude, $longitude, $categoria, $imagem_url);
    $stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Administração</title>
</head>
<body>
    <h2>Gerenciar Pontos Turísticos</h2>
    <form method="POST" action="admin.php">
        <label>Nome:</label><input type="text" name="nome" required><br>
        <label>Descrição:</label><textarea name="descricao" required></textarea><br>
        <label>Latitude:</label><input type="text" name="latitude" required><br>
        <label>Longitude:</label><input type="text" name="longitude" required><br>
        <label>Categoria:</label><input type="text" name="categoria" required><br>
        <label>Imagem URL:</label><input type="text" name="imagem_url"><br>
        <button type="submit">Adicionar Ponto Turístico</button>
    </form>
</body>
</html>
