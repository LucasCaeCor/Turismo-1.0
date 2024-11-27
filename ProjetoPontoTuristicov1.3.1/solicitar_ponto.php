<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Diretório de upload
$diretorioUploads = 'uploads/';

// Verifica se o diretório existe, se não, cria
if (!is_dir($diretorioUploads)) {
    mkdir($diretorioUploads, 0755, true);
}

// Inicializa variáveis de resposta
$resposta = "";

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    // Verifica se a imagem foi enviada
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        // Gera um nome único para o arquivo de imagem para evitar sobrecarga
        $nomeArquivo = uniqid() . '_' . basename($_FILES['imagem']['name']);
        $targetFile = $diretorioUploads . $nomeArquivo;

        // Verifica o tipo de arquivo
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
        
        // Verifica se o tipo de arquivo é permitido
        if (in_array($imageFileType, $allowedTypes)) {
            // Tenta mover o arquivo para o diretório de uploads
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $targetFile)) {
                // Insere a solicitação na tabela de solicitações
                $query = "INSERT INTO solicitacoes (nome, descricao, latitude, longitude, imagem) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssss", $nome, $descricao, $latitude, $longitude, $targetFile);
                
                if ($stmt->execute()) {
                    $resposta = "<p class='text-success'>Solicitação enviada com sucesso! \n Sua solicitação será analisada em breve.</p>";
                } else {
                    $resposta = "<p class='text-danger'>Erro ao enviar a solicitação: " . $conn->error . "</p>";
                }
                $stmt->close();
            } else {
                $resposta = "<p class='text-danger'>Erro ao mover o arquivo para o diretório de uploads. Verifique as permissões do diretório.</p>";
            }
        } else {
            $resposta = "<p class='text-danger'>Tipo de arquivo não permitido. Apenas JPG, JPEG, PNG e GIF são aceitos.</p>";
        }
    } else {
        $resposta = "<p class='text-danger'>Erro no envio da imagem. Tente novamente mais tarde.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Resultado da Solicitação</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .resultado-container {
            width: 400px;
            padding: 20px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="resultado-container">
        <?= $resposta; // Exibe a mensagem de resposta ?>
        <div>
            <a href="index.php" class="btn btn-primary">Voltar</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
