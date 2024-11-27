<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está logado
$usuarioLogado = isset($_SESSION['usuario']);
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitação de Cadastro de Ponto Turístico</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(145deg, #ffbb33, #ffdd66); /* Gradiente suave */
            font-family: 'Arial', sans-serif;
            color: #333;
        }

        .navbar {
            margin-bottom: 20px;
        }

        .form-container {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            max-width: 600px;
            margin: 50px auto;
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #ff6600;
        }

        .form-container label {
            color: #333;
        }

        .form-container input, .form-container textarea {
            border-radius: 8px;
            border: 1px solid #ddd;
            margin-bottom: 15px;
            padding: 12px;
            width: 100%;
        }

        .form-container textarea {
            resize: vertical;
        }

        .form-container .btn-primary {
            background-color: #28a745;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 8px;
            width: 100%;
            margin-top: 15px;
        }

        .form-container .btn-primary:hover {
            background-color: #218838;
        }

        .form-container .alert {
            text-align: center;
        }

        .back-btn {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-btn a {
            color: #007bff;
            font-size: 18px;
            text-decoration: none;
        }

        .back-btn a:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        // Variáveis globais para armazenar latitude e longitude
        let latitudeAtual = null;
        let longitudeAtual = null;

        function iniciarMonitoramento() {
            if (navigator.geolocation) {
                // Monitorando a posição atual em tempo real
                navigator.geolocation.watchPosition(function(position) {
                    latitudeAtual = position.coords.latitude;
                    longitudeAtual = position.coords.longitude;

                    // Atualiza os campos de latitude e longitude com os valores atuais
                    document.getElementById('localizacao_lat').value = latitudeAtual;
                    document.getElementById('localizacao_lng').value = longitudeAtual;
                }, function(error) {
                    let mensagemErro;
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            mensagemErro = 'Você negou o acesso à sua localização. Por favor, permita o acesso ou insira as coordenadas manualmente.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            mensagemErro = 'A localização não está disponível. Tente novamente mais tarde.';
                            break;
                        case error.TIMEOUT:
                            mensagemErro = 'A requisição de localização demorou muito para ser concluída. Tente novamente.';
                            break;
                        default:
                            mensagemErro = `Erro desconhecido (Código: ${error.code}). Mensagem: ${error.message}. Tente novamente.`;
                    }
                    alert(mensagemErro);
                    // Fornece uma alternativa para inserção manual
                    document.getElementById('localizacao_lat').value = '';
                    document.getElementById('localizacao_lng').value = '';
                }, {
                    enableHighAccuracy: true, // Tenta obter a localização com maior precisão
                    timeout: 5000,             // Define um tempo limite para a requisição
                    maximumAge: 0             // Não utiliza informações de localização antigas
                });
            } else {
                alert('A geolocalização não é suportada pelo seu navegador.');
            }
        }
    </script>
</head>
<body onload="iniciarMonitoramento()">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="cadastro_ponto.php"><h2>Cadastro De Ponto Turístico</h2></a>
            <div class="ml-auto d-flex align-items-center">
                <button onclick="window.location.href='index.php'" class="btn btn-secondary">Voltar</button>
                <a href="logout.php" class="btn btn-danger ml-2">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container mt-5">
        <h2 class="text-center">Solicitação de Cadastro de Ponto Turístico</h2>

        <!-- Formulário para solicitação de cadastro -->
        <?php if (!$isAdmin && $usuarioLogado): ?>
            <div class="form-container">
                <h3 class="card-title text-center">Formulário de Cadastro</h3>
                <form action="solicitar_ponto.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nome">Nome do Ponto Turístico:</label>
                        <input type="text" class="form-control" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="descricao">Descrição:</label>
                        <textarea class="form-control" name="descricao" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="latitude">Latitude:</label>
                        <input type="text" class="form-control" name="latitude" id="localizacao_lat" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="longitude">Longitude:</label>
                        <input type="text" class="form-control" name="longitude" id="localizacao_lng" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="imagem">Imagem:</label>
                        <input type="file" class="form-control-file" name="imagem" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">Solicitar Cadastro</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mt-4 text-center">
                <strong>Apenas usuários não administradores podem solicitar o cadastro de pontos turísticos.</strong>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
