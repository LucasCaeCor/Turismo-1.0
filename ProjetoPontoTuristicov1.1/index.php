<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está logado e se é admin
$usuarioLogado = isset($_SESSION['usuario']);
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;

// Busca todos os pontos turísticos
$query = "SELECT * FROM pontos_turisticos";
$resultado = $conn->query($query);

// Armazena os pontos turísticos em um array
$pontosTuristicos = [];
while ($ponto = $resultado->fetch_assoc()) {
    $pontosTuristicos[] = $ponto;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pontos Turísticos de Telêmaco Borba</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCOSuLd-yd2Bxa7w98Zbs1oHu9GqL0CH38"></script>

    <script>
        function initMap() {
            const mapOptions = {
                center: { lat: -24.3245, lng: -50.6123 },
                zoom: 13
            };

            const map = new google.maps.Map(document.getElementById("map"), mapOptions);
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
        window.onload = initMap;

        function obterLocalizacao() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById("latitude").value = position.coords.latitude;
                    document.getElementById("longitude").value = position.coords.longitude;
                }, function() {
                    alert("Não foi possível obter a localização.");
                });
            } else {
                alert("Geolocalização não é suportada neste navegador.");
            }
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Pontos Turísticos de Telêmaco Borba</h2>

        <!-- Menu de navegação -->
        <div class="mb-4">
            <?php if ($usuarioLogado): ?>
                <span class="mr-3">Bem-vindo, <?= htmlspecialchars($_SESSION['usuario']) ?>!</span>
                <?php if ($isAdmin): ?>
                    <a href="cadastro_ponto.php" class="btn btn-primary">Cadastrar Ponto Turístico</a>
                    <a href="gerenciar_solicitacoes.php" class="btn btn-secondary">Gerenciar Solicitações</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-success">Login</a>
                <a href="cadastro.php" class="btn btn-info">Cadastrar</a>
            <?php endif; ?>
        </div>

        <div id="map" class="mb-4" style="width: 100%; height: 80vh;"></div>

        <?php if (count($pontosTuristicos) > 0): ?>
            <div class="row">
                <?php foreach ($pontosTuristicos as $ponto): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <img src="<?= htmlspecialchars($ponto['imagem_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($ponto['nome']) ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($ponto['nome']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($ponto['descricao']) ?></p>
                                <?php if ($isAdmin): ?>
                                    <a href="excluir_ponto.php?id=<?= $ponto['id'] ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir este ponto turístico?');">Excluir</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Nenhum ponto turístico encontrado.</p>
        <?php endif; ?>

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
                    <input type="text" class="form-control" name="latitude" id="latitude" required>
                </div>
                <div class="form-group">
                    <label for="longitude">Longitude:</label>
                    <input type="text" class="form-control" name="longitude" id="longitude" required>
                </div>
                <div class="form-group">
                    <label for="imagem">Imagem:</label>
                    <input type="file" class="form-control-file" name="imagem" accept="image/*" required>
                </div>
                <button type="button" class="btn btn-info" onclick="obterLocalizacao()">Obter Localização Atual</button>
                <button type="submit" class="btn btn-success">Solicitar Cadastro</button>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
