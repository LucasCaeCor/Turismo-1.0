<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Verifica se o usuário é administrador
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: index.php");
    exit();
}

// Inicializa a variável de resposta
$resposta = "";

// Processa o cadastro do ponto turístico
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Valida se os campos estão preenchidos
    if (!empty($_POST['nome']) && !empty($_POST['descricao']) && !empty($_POST['latitude']) && !empty($_POST['longitude']) && !empty($_FILES['imagem']['name'])) {
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $localizacao_lat = $_POST['latitude'];
        $localizacao_lng = $_POST['longitude'];

        // Upload da imagem
        $imagem_url = 'uploads/' . basename($_FILES['imagem']['name']);
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $imagem_url)) {
            // Prepara e executa a query de inserção
            $query = "INSERT INTO pontos_turisticos (nome, descricao, localizacao_lat, localizacao_lng, imagem_url) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssdds", $nome, $descricao, $localizacao_lat, $localizacao_lng, $imagem_url);
            
            if ($stmt->execute()) {
                $resposta = "<p class='text-success'>Ponto turístico cadastrado com sucesso!</p>";
            } else {
                $resposta = "<p class='text-danger'>Erro ao cadastrar: " . $stmt->error . "</p>";
            }
        } else {
            $resposta = "<p class='text-danger'>Erro ao fazer upload da imagem.</p>";
        }
    } else {
        $resposta = "<p class='text-danger'>Todos os campos devem ser preenchidos.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Ponto Turístico</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Cadastrar Ponto Turístico</h2>
        
        <?= $resposta; // Exibe a mensagem de resposta ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" class="form-control" required></textarea>
            </div>

            <div class="form-group">
                <label for="latitude">Latitude:</label>
                <input type="text" id="latitude" name="latitude" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="longitude">Longitude:</label>
                <input type="text" id="longitude" name="longitude" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="imagem">Imagem:</label>
                <input type="file" id="imagem" name="imagem" accept="image/*" class="form-control" required>
            </div>

            <input type="submit" class="btn btn-primary" value="Cadastrar">
        </form>
        
        <a href="index.php" class="btn btn-secondary mt-3">Voltar para a página inicial</a>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
