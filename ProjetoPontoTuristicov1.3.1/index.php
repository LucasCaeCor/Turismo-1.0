<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está logado e se é admin
$usuarioLogado = isset($_SESSION['usuario']);
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;

// Lógica para excluir comentário
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['excluir_comentario_id'])) {
    $comentarioId = intval($_GET['excluir_comentario_id']);
    if ($usuarioLogado) {
        $usuarioComentarioQuery = "SELECT usuario FROM comentarios WHERE id = ?";
        $stmt = $conn->prepare($usuarioComentarioQuery);
        $stmt->bind_param("i", $comentarioId);
        $stmt->execute();
        $resultadoComentario = $stmt->get_result();

        if ($resultadoComentario->num_rows > 0) {
            $comentario = $resultadoComentario->fetch_assoc();
            if ($comentario['usuario'] === $_SESSION['usuario'] || $isAdmin) {
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

// Adiciona um comentário
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pontos Turísticos de Telêmaco Borba</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCOSuLd-yd2Bxa7w98Zbs1oHu9GqL0CH38"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="style.css" rel="stylesheet" />

    <style>
        .descricao-comentarios {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 10px;
        }
    </style>

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
                    content: `<h3>${ponto.nome}</h3><p>${ponto.descricao}</p><img src="${ponto.imagem_url}" class="img-fluid">`
                });

                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                });
            });
        }
        window.onload = initMap;

        $(document).on('click', '.mostrar-mais', function () {
            const target = $($(this).data('target'));
            const button = $(this);

            if (target.hasClass('d-none')) {
                target.removeClass('d-none');
                button.text('Mostrar menos');
            } else {
                target.addClass('d-none');
                button.text('Mostrar mais');
            }
        });
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="#">Telêmaco Turismo</a>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav ml-auto">
            <?php if ($usuarioLogado): ?>
                <li class="nav-item"><a class="nav-link" href="#">Bem-vindo, <?= htmlspecialchars($_SESSION['usuario']) ?></a></li>
                
                <?php if (!$isAdmin): ?>
                    <li class="nav-item"><a class="nav-link" href="solicitacaoForm.php">Solicitar Cadastro de Ponto</a></li>
                <?php endif; ?>

                <?php if ($isAdmin): ?>
                    <li class="nav-item"><a class="nav-link" href="cadastro_ponto.php">Cadastrar Ponto</a></li>
                    <li class="nav-item"><a class="nav-link" href="gerenciar_solicitacoes.php">Gerenciar Solicitações</a></li>
                <?php endif; ?>

                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                <li class="nav-item"><a class="nav-link" href="cadastro.php">Cadastrar</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<div class="container mt-4">
    <h1 class="text-center">Pontos Turísticos de Telêmaco Borba</h1>

    <div id="map" class="mb-4" style="width: 100%; height: 400px;"></div>

    <div class="row">
        <?php foreach ($pontosTuristicos as $ponto): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <img src="<?= htmlspecialchars($ponto['imagem_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($ponto['nome']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($ponto['nome']) ?></h5>

                        <div class="conteudo-oculto d-none" id="conteudo-<?= $ponto['id'] ?>">
                            <div class="descricao-comentarios">
                                <p class="card-text"><?= htmlspecialchars($ponto['descricao']) ?></p>

                                <div class="mt-3">
                                    <h6>Comentários:</h6>
                                    <div class="comentarios-lista" id="comentarios-<?= $ponto['id'] ?>">
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
                                                <?php if ($usuarioLogado && ($comentario['usuario'] === $_SESSION['usuario'] || $isAdmin)): ?>
                                                    <a href="?excluir_comentario_id=<?= $comentario['id'] ?>" class="btn btn-danger btn-sm ml-2">Excluir</a>
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

                                    <?php if ($usuarioLogado): ?>
                                        <form method="POST" class="mt-3">
                                            <input type="hidden" name="ponto_id" value="<?= $ponto['id'] ?>">
                                            <textarea name="comentario" class="form-control mb-2" placeholder="Deixe seu comentário..." required></textarea>
                                            <button type="submit" class="btn btn-primary">Comentar</button>
                                        </form>
                                    <?php else: ?>
                                        <p><small>Faça login para comentar.</small></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-info mostrar-mais" data-target="#conteudo-<?= $ponto['id'] ?>">Mostrar mais</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>

