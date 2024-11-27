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
    <title>Solicitação de Cadastro de Ponto Turístico</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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
    <div class="container mt-5">
        <h2 class="text-center">Solicitação de Cadastro de Ponto Turístico</h2>

        <!-- Formulário para solicitação de cadastro -->
        <?php if (!$isAdmin && $usuarioLogado): ?>
            <h3 class="mt-5">Solicitação para Cadastro de Ponto Turístico</h3>
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
                <button type="submit" class="btn btn-success">Solicitar Cadastro</button>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

