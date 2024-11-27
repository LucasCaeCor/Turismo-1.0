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
        $nome = htmlspecialchars($_POST['nome']);
        $descricao = htmlspecialchars($_POST['descricao']);
        
        // Valida a latitude e longitude
        $localizacao_lat = filter_var($_POST['latitude'], FILTER_VALIDATE_FLOAT);
        $localizacao_lng = filter_var($_POST['longitude'], FILTER_VALIDATE_FLOAT);

        // Verifica se latitude e longitude são numéricas e estão no intervalo válido
        if ($localizacao_lat === false || $localizacao_lng === false) {
            $resposta = "<p class='text-danger'>Latitude e longitude devem ser valores numéricos válidos.</p>";
        } elseif ($localizacao_lat < -90 || $localizacao_lat > 90) {
            $resposta = "<p class='text-danger'>Latitude deve estar entre -90 e 90.</p>";
        } elseif ($localizacao_lng < -180 || $localizacao_lng > 180) {
            $resposta = "<p class='text-danger'>Longitude deve estar entre -180 e 180.</p>";
        } else {
            // Configuração para upload
            $diretorio_upload = 'uploads/';
            if (!is_dir($diretorio_upload)) {
                mkdir($diretorio_upload, 0755, true);
            }

            $imagem_nome = uniqid() . '-' . basename($_FILES['imagem']['name']);
            $imagem_url = $diretorio_upload . $imagem_nome;

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
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCOSuLd-yd2Bxa7w98Zbs1oHu9GqL0CH38"></script>
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
                <label for="local">Local:</label>
                <input type="text" id="local" name="local" class="form-control" placeholder="Digite o nome do local (ex: cidade ou ponto turístico)" required>
            </div>

            <div class="form-group">
                <label for="latitude">Latitude:</label>
                <input type="text" id="latitude" name="latitude" class="form-control" readonly required>
            </div>

            <div class="form-group">
                <label for="longitude">Longitude:</label>
                <input type="text" id="longitude" name="longitude" class="form-control" readonly required>
            </div>

            <div class="form-group">
                <label for="imagem">Imagem:</label>
                <input type="file" id="imagem" name="imagem" accept="image/*" class="form-control" required>
            </div>

            <input type="submit" class="btn btn-primary" value="Cadastrar">
        </form>
        
        <a href="index.php" class="btn btn-secondary mt-3">Voltar para a página inicial</a>
    </div>

    <script>
        // Função para obter as coordenadas do local
        function geocodeAddress() {
            var geocoder = new google.maps.Geocoder();
            var address = document.getElementById('local').value;
            
            geocoder.geocode({ 'address': address }, function(results, status) {
                if (status === 'OK') {
                    var lat = results[0].geometry.location.lat();
                    var lng = results[0].geometry.location.lng();

                    // Preenche os campos de latitude e longitude
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                } else {
                    alert("Não foi possível encontrar o local: " + status);
                }
            });
        }

        // Adiciona o evento para o campo de local
        document.getElementById('local').addEventListener('blur', geocodeAddress);
    </script>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
