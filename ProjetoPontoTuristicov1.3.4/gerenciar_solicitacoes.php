<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está logado e se é admin
if (!isset($_SESSION['usuario']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php'); // Redireciona para a página de login se não estiver logado ou não for admin
    exit();
}


// Aqui você pode buscar e exibir suas solicitações
$query = "SELECT * FROM solicitacoes WHERE status != 'rejeitada'"; // Modifique a consulta para excluir as rejeitadas
$result = $conn->query($query);

// Lógica para aceitar solicitação
if (isset($_GET['action']) && $_GET['action'] == 'aceitar' && isset($_GET['id'])) {
    $idSolicitacao = $_GET['id'];
    
    // Buscar os detalhes da solicitação
    $querySolicitacao = "SELECT * FROM solicitacoes WHERE id = ?";
    $stmt = $conn->prepare($querySolicitacao);
    $stmt->bind_param("s", $idSolicitacao);
    $stmt->execute();
    $solicitacao = $stmt->get_result()->fetch_assoc();
    
    if ($solicitacao) {
        // Insira os dados na tabela de pontos turísticos
        $queryInsert = "INSERT INTO pontos_turisticos (nome, descricao, localizacao_lat, localizacao_lng, imagem_url) VALUES (?, ?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($queryInsert);
        $stmtInsert->bind_param("ssdds", $solicitacao['nome'], $solicitacao['descricao'], $solicitacao['latitude'], $solicitacao['longitude'], $solicitacao['imagem']);
        
        if ($stmtInsert->execute()) {
            // Após aceitar a solicitação, exclua-a da tabela de solicitações
            $queryDelete = "DELETE FROM solicitacoes WHERE id = ?";
            $stmtDelete = $conn->prepare($queryDelete);
            $stmtDelete->bind_param("s", $idSolicitacao);
            $stmtDelete->execute();
            $stmtDelete->close();
            
            // Redirecionar com mensagem de sucesso
            header("Location: gerenciar_solicitacoes.php?msg=Solicitação aceita e ponto turístico cadastrado com sucesso!");
            exit();
        } else {
            echo "Erro ao cadastrar ponto turístico: " . $stmtInsert->error;
        }
        $stmtInsert->close();
    } else {
        echo "Solicitação não encontrada.";
    }
    $stmt->close();
}

// Buscando pontos turísticos cadastrados
$queryPontosTuristicos = "SELECT * FROM solicitacoes";
$resultPontos = $conn->query($queryPontosTuristicos);
$pontosTuristicos = [];
while ($row = $resultPontos->fetch_assoc()) {
    $pontosTuristicos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Solicitações</title>
    <script src="js/scripts.js"></script>
    <link href="css/style.css" rel="stylesheet" />
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCOSuLd-yd2Bxa7w98Zbs1oHu9GqL0CH38"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let map;
        let marker; // Variável global para o marcador

        function initMap() {
            const mapOptions = {
                center: { lat: -24.3245, lng: -50.6123 }, // Localização inicial
                zoom: 13
            };

            map = new google.maps.Map(document.getElementById("map"), mapOptions);
        }

        // Função para centralizar o mapa e adicionar a pingela vermelha
        function centralizarNoMapa(lat, lng) {
            const position = { lat: parseFloat(lat), lng: parseFloat(lng) };

            // Centraliza o mapa no local clicado
            map.setCenter(position);
            map.setZoom(15); // Ajuste o zoom conforme necessário

            // Se já houver um marcador, remova-o
            if (marker) {
                marker.setMap(null);
            }

            marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: "Ponto turístico",
                    icon: {
                        url: "https://maps.google.com/mapfiles/ms/icons/red-dot.png", // URL da pingela vermelha
                        scaledSize: new google.maps.Size(30, 30), // Tamanho do marcador
                    }
                });

                // Adiciona uma janela de informações ao marcador com o nome da solicitação
                const infoWindow = new google.maps.InfoWindow({
                    content: `<h3>${nomeSolicitacao}</h3><p>Latitude: ${lat}</p><p>Longitude: ${lng}</p>`
                });

            infoWindow.open(map, marker);
        }

        window.onload = initMap;

        // Função para ocultar a mensagem após 5 segundos
        function hideMessage() {
            const messageDiv = document.getElementById('message');
            if (messageDiv) {
                setTimeout(function() {
                    messageDiv.style.display = 'none';
                }, 5000); // Esconde após 5 segundos
            }
        }

        window.onload = function() {
            hideMessage(); // Chama a função ao carregar a página
            initMap();
        }
    </script>

<script>
    function centralizarNoMapa(lat, lng, nomeSolicitacao) {
        const position = { lat: parseFloat(lat), lng: parseFloat(lng) };

        // Centraliza o mapa no local clicado
        map.setCenter(position);
        map.setZoom(15); // Ajuste o zoom conforme necessário

        // Se já houver um marcador, remova-o
        if (marker) {
            marker.setMap(null);
        }

        // Adiciona um novo marcador (pingela vermelha)
        marker = new google.maps.Marker({
            position: position,
            map: map,
            title: "Ponto turístico",
            icon: {
                url: "https://maps.google.com/mapfiles/ms/icons/red-dot.png", // URL da pingela vermelha
                scaledSize: new google.maps.Size(30, 30), // Tamanho do marcador
            }
        });

        // Adiciona uma janela de informações ao marcador com o nome da solicitação
        const infoWindow = new google.maps.InfoWindow({
            content: `<h3>${nomeSolicitacao}</h3><p>Latitude: ${lat}</p><p>Longitude: ${lng}</p>`
        });

        // Exibe a janela de informações no marcador
        infoWindow.open(map, marker);
    }
</script>

</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="gerenciar_solicitacoes.php"><h2>Gerenciar Solicitações</h2></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <div id="menu" class="ms-auto d-flex align-items-center flex-wrap gap-2">
                    <span class="text-light me-3">Vai fazer Alterações <?= htmlspecialchars($_SESSION['usuario']) ?>?</span>
                    <button onclick="window.location.href='index.php'" class="btn btn-secondary">Voltar</button>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Exibe a mensagem de sucesso ou erro abaixo da navbar -->
    <?php if (isset($_GET['msg'])): ?>
        <div id="message" class="alert alert-success" role="alert">
            <?= htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card mb-3 shadow-sm" style="background-color: rgba(0, 0, 0, 0.8); border-radius: 12px; overflow: hidden;">
                <div class="row g-0">
                    <div class="col-md-4">
                        <img 
                            src="<?= htmlspecialchars($row['imagem']) ?>" 
                            alt="Imagem do ponto turístico" 
                            class="img-fluid rounded-start" 
                            style="max-height: 200px; object-fit: cover;"
                        >
                    </div>
                    <div class="col-md-8">
                        <div class="card-body text-white">
                            <h4 class="card-title text-warning"><?= htmlspecialchars($row['nome']) ?></h4>
                            <p class="card-text"><strong>Descrição:</strong> <?= htmlspecialchars($row['descricao']) ?></p>
                            <p class="card-text"><strong>Localização:</strong> Latitude: <?= htmlspecialchars($row['latitude']) ?>, Longitude: <?= htmlspecialchars($row['longitude']) ?></p>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                            <button class="btn btn-outline-info flex-fill" onclick="centralizarNoMapa(<?= htmlspecialchars($row['latitude']) ?>, <?= htmlspecialchars($row['longitude']) ?>, '<?= htmlspecialchars($row['nome']) ?>')">Ver no Mapa</button>
                            <a href="?action=aceitar&id=<?= $row['id'] ?>" class="btn btn-outline-success flex-fill" onclick="return confirm('Tem certeza que deseja aceitar esta solicitação?');">Aceitar</a>
                            <a href="rejeitar_solicitacao.php?id=<?= $row['id'] ?>" class="btn btn-outline-danger flex-fill" onclick="return confirm('Tem certeza que deseja rejeitar esta solicitação?');">Rejeitar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center text-white">Não há solicitações pendentes.</p>
        <?php endif; ?>
    </div>

    <div id="map" style="height: 500px;"></div>
    
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
