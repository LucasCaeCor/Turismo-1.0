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
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $latitude = filter_var($_POST['latitude'], FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($_POST['longitude'], FILTER_VALIDATE_FLOAT);
    $categoria = trim($_POST['categoria']);
    $imagem_url = filter_var($_POST['imagem_url'], FILTER_VALIDATE_URL);

    if ($nome && $descricao && $latitude && $longitude && $categoria && $imagem_url) {
        $stmt = $conn->prepare("INSERT INTO pontos_turisticos (nome, descricao, latitude, longitude, categoria, imagem_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddss", $nome, $descricao, $latitude, $longitude, $categoria, $imagem_url);

        if ($stmt->execute()) {
            $mensagem = "Ponto turístico adicionado com sucesso!";
        } else {
            $mensagem = "Erro ao adicionar ponto turístico: " . $conn->error;
        }
    } else {
        $mensagem = "Por favor, preencha todos os campos corretamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Administração</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container">
    <h2 class="my-4">Gerenciar Pontos Turísticos</h2>
    <?php if (!empty($mensagem)) : ?>
        <div class="alert alert-info"><?= $mensagem ?></div>
    <?php endif; ?>

    <form method="POST" action="admin.php" class="mb-4">
        <div class="mb-3">
            <label>Nome:</label>
            <input type="text" name="nome" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Descrição:</label>
            <textarea name="descricao" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
            <label>Latitude:</label>
            <input type="text" name="latitude" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Longitude:</label>
            <input type="text" name="longitude" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Categoria:</label>
            <select name="categoria" class="form-control" required>
                <option value="Parque">Parque</option>
                <option value="Museu">Museu</option>
                <option value="Praia">Praia</option>
                <option value="Outro">Outro</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Imagem URL:</label>
            <input type="url" name="imagem_url" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Adicionar Ponto Turístico</button>
    </form>

    <h3>Pontos Turísticos Existentes</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Imagem</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($ponto = $resultado->fetch_assoc()) : ?>
                <tr>
                    <td><?= htmlspecialchars($ponto['nome']) ?></td>
                    <td><?= htmlspecialchars($ponto['descricao']) ?></td>
                    <td><?= htmlspecialchars($ponto['categoria']) ?></td>
                    <td><?= htmlspecialchars($ponto['latitude']) ?></td>
                    <td><?= htmlspecialchars($ponto['longitude']) ?></td>
                    <td><img src="<?= htmlspecialchars($ponto['imagem_url']) ?>" alt="<?= htmlspecialchars($ponto['nome']) ?>" style="width:100px;"></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
