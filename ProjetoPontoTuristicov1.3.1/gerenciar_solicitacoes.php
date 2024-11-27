<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está logado e se é admin
if (!isset($_SESSION['usuario']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php'); // Redireciona para a página de login se não estiver logado ou não for admin
    exit();
}

// Exibe mensagem de feedback se estiver presente na URL
if (isset($_GET['msg'])) {
    echo "<div class='alert alert-success'>" . htmlspecialchars($_GET['msg']) . "</div>";
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
        $stmtInsert->bind_param("ssdds", $solicitacao['nome'], $solicitacao['descricao'], $solicitacao['latitude'], $solicitacao['longitude'], $solicitacao['imagem_url']);
        
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
    <link rel="stylesheet" href="style.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCOSuLd-yd2Bxa7w98Zbs1oHu9GqL0CH38"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let map;

        function initMap() {
            const mapOptions = {
                center: { lat: -24.3245, lng: -50.6123 }, // Localização inicial
                zoom: 13
            };

            map = new google.maps.Map(document.getElementById("map"), mapOptions);
            const pontosTuristicos = <?= json_encode($pontosTuristicos) ?>;

            pontosTuristicos.forEach((ponto) => {
                const marker = new google.maps.Marker({
                    position: { lat: parseFloat(ponto.localizacao_lat), lng: parseFloat(ponto.localizacao_lng) },
                    map: map,
                    title: ponto.nome
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `<h3>${ponto.nome}</h3><p>${ponto.descricao}</p><img src="${ponto.imagem_url}" style="width:200px;height:auto;">`
                });

                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                });
            });
        }

        // Função para centralizar o mapa em um ponto específico
        function centralizarNoMapa(lat, lng) {
            const position = { lat: parseFloat(lat), lng: parseFloat(lng) };
            map.setCenter(position);
            map.setZoom(15); // Ajuste o zoom conforme necessário

            const marker = new google.maps.Marker({
                position: position,
                map: map,
                title: "Ponto turístico"
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `<h3>Ponto turístico</h3><p>Latitude: ${lat}</p><p>Longitude: ${lng}</p>`
            });

            infoWindow.open(map, marker);
        }

        window.onload = initMap;
    </script>
</head>
<body>
    <div class="container">
        <h2>Gerenciar Solicitações</h2>
        <div id="menu" class="mb-3">
            <span>Bem-vindo, <?= htmlspecialchars($_SESSION['usuario']) ?>!</span>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>

        <!-- Botão "Voltar" com redirecionamento para a página inicial -->
        <button onclick="window.location.href='index.php'" class="btn btn-secondary mb-3">Voltar</button>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h4 class="card-title"><?= htmlspecialchars($row['nome']) ?></h4>
                        <p class="card-text"><strong>Descrição:</strong> <?= htmlspecialchars($row['descricao']) ?></p>
                        <p class="card-text"><strong>Localização:</strong> Latitude: <?= htmlspecialchars($row['latitude']) ?>, Longitude: <?= htmlspecialchars($row['longitude']) ?></p>
                        <p class="card-text"><strong>Imagem:</strong> <img src="<?= htmlspecialchars($row['imagem_url']) ?>" alt="Imagem do ponto turístico" class="img-fluid" style="max-width: 200px;"></p>
    
                        <!-- Botão para ver o ponto no mapa -->
                        <button class="btn btn-info" onclick="centralizarNoMapa(<?= htmlspecialchars($row['latitude']) ?>, <?= htmlspecialchars($row['longitude']) ?>)">Ver no Mapa</button>

                        <a href="?action=aceitar&id=<?= $row['id'] ?>" class="btn btn-success" onclick="return confirm('Tem certeza que deseja aceitar esta solicitação?');">Aceitar</a>
                        <a href="rejeitar_solicitacao.php?id=<?= $row['id'] ?>" class="btn btn-danger">Rejeitar</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-warning">Nenhuma solicitação encontrada.</div>
        <?php endif; ?>

        <div id="map" style="height: 500px; width: 100%;"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
