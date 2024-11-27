<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está logado e se é admin
$usuarioLogado = isset($_SESSION['usuario']);
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;

// Lógica para excluir comentário
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['excluir_comentario_id'])) {
    $comentarioId = intval($_GET['excluir_comentario_id']);
    
    // Verifica se é o autor do comentário ou um administrador
    if ($usuarioLogado) {
        $usuarioComentarioQuery = "SELECT usuario FROM comentarios WHERE id = ?";
        $stmt = $conn->prepare($usuarioComentarioQuery);
        $stmt->bind_param("i", $comentarioId);
        $stmt->execute();
        $resultadoComentario = $stmt->get_result();
        
        if ($resultadoComentario->num_rows > 0) {
            $comentario = $resultadoComentario->fetch_assoc();
            if ($comentario['usuario'] === $_SESSION['usuario'] || $isAdmin) {
                // Excluir comentário
                $deleteQuery = "DELETE FROM comentarios WHERE id = ?";
                $stmtDelete = $conn->prepare($deleteQuery);
                $stmtDelete->bind_param("i", $comentarioId);
                $stmtDelete->execute();
                $stmtDelete->close();
            }
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Adiciona um comentário, se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'], $_POST['ponto_id']) && $usuarioLogado) {
    $comentario = htmlspecialchars($_POST['comentario']);
    $pontoId = intval($_POST['ponto_id']);
    $usuario = htmlspecialchars($_SESSION['usuario']);

    $stmt = $conn->prepare("INSERT INTO comentarios (ponto_id, usuario, comentario, data_comentario) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $pontoId, $usuario, $comentario);
    $stmt->execute();
    $stmt->close();
}

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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $(".btn-toggle").click(function () {
                const targetId = $(this).attr("data-target");
                $("#" + targetId).toggle(); // Alterna a exibição do elemento

                // Atualiza o texto do botão
                const isVisible = $("#" + targetId).is(":visible");
                $(this).text(isVisible ? "Esconder Informações" : "Mostrar Informações");
            });
        });

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
    </script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Pontos Turísticos de Telêmaco Borba</h2>

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

            <?php if ($usuarioLogado && !$isAdmin): ?>
                <a href="solicitacaoForm.php" class="btn btn-warning">Novo Ponto</a>
            <?php endif; ?>
        </div>

        <div id="map" class="mb-4" style="width: 100%; height: 70vh;"></div>

        <?php if (count($pontosTuristicos) > 0): ?>
            <div class="row mt-5">
                <?php foreach ($pontosTuristicos as $ponto): ?>
                    <div class="col-sm-4 mb-3">
                        <div class="card" style="height:100%; width:100%;">
                            <img src="<?= htmlspecialchars($ponto['imagem_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($ponto['nome']) ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($ponto['nome']) ?></h5>
                                <div id="info-<?= $ponto['id'] ?>" style="display:none;">
                                    <p><?= htmlspecialchars($ponto['descricao']) ?></p>

                                    <!-- Comentários -->
                                    <div class="mt-3">
                                        <h6>Comentários:</h6>
                                        <?php
                                        $queryComentarios = "SELECT * FROM comentarios WHERE ponto_id = ? ORDER BY data_comentario DESC";
                                        $stmtComentarios = $conn->prepare($queryComentarios);
                                        $stmtComentarios->bind_param("i", $ponto['id']);
                                        $stmtComentarios->execute();
                                        $resultComentarios = $stmtComentarios->get_result();

                                        if ($resultComentarios->num_rows > 0):
                                            while ($comentario = $resultComentarios->fetch_assoc()):
                                        ?>
                                            <div class="alert alert-secondary">
                                                <strong><?= htmlspecialchars($comentario['usuario']) ?>:</strong> 
                                                <?= htmlspecialchars($comentario['comentario']) ?>
                                                <br><small><?= htmlspecialchars($comentario['data_comentario']) ?></small>

                                                <!-- Botão de excluir comentário, visível apenas para o autor ou administradores -->
                                                <?php if ($usuarioLogado && ($comentario['usuario'] === $_SESSION['usuario'] || $isAdmin)): ?>
                                                    <a href="?excluir_comentario_id=<?= $comentario['id'] ?>" class="btn btn-danger btn-sm ml-2" onclick="return confirm('Tem certeza que deseja excluir este comentário?');">Excluir</a>
                                                <?php endif; ?>
                                            </div>
                                        <?php
                                            endwhile;
                                        else:
                                            echo "<p>Sem comentários ainda.</p>";
                                        endif;

                                        $stmtComentarios->close();
                                        ?>
                                    </div>

                                    <!-- Formulário de comentário -->
                                    <?php if ($usuarioLogado): ?>
                                        <form method="POST" class="mt-3">
                                            <input type="hidden" name="ponto_id" value="<?= $ponto['id'] ?>">
                                            <div class="form-group">
                                                <textarea name="comentario" class="form-control" placeholder="Deixe seu comentário..." required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Comentar</button>
                                        </form>
                                    <?php else: ?>
                                        <p><a href="login.php">Faça login</a> para comentar.</p>
                                    <?php endif; ?>

                                    <!-- Botão excluir para admin -->
                                    <?php if ($isAdmin): ?>
                                        <a href="excluir_ponto.php?id=<?= $ponto['id'] ?>" class="btn btn-danger mt-3" onclick="return confirm('Tem certeza que deseja excluir este ponto turístico?');">Excluir Ponto</a>
                                    <?php endif; ?>
                                </div>

                                <button class="btn btn-info btn-toggle" data-target="info-<?= $ponto['id'] ?>">Mostrar Informações</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="alert alert-warning">Não há pontos turísticos cadastrados.</p>
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
