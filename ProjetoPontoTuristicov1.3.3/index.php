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
        // Verifica o autor do comentário
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

// Lógica para excluir ponto turístico (somente admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_ponto_id']) && $isAdmin) {
    $pontoId = intval($_POST['excluir_ponto_id']);
    
    $deletePontoQuery = "DELETE FROM pontos_turisticos WHERE id = ?";
    $stmtDeletePonto = $conn->prepare($deletePontoQuery);
    $stmtDeletePonto->bind_param("i", $pontoId);
    $stmtDeletePonto->execute();
    $stmtDeletePonto->close();
    
    echo json_encode(['status' => 'success']);
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

    echo json_encode(['status' => 'success']);
    exit();
}

// Busca todos os pontos turísticos
$query = "SELECT * FROM pontos_turisticos";
$resultado = $conn->query($query);

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

    <script src="js/scripts.js"></script>
    <link href="css/style.css" rel="stylesheet" />
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCOSuLd-yd2Bxa7w98Zbs1oHu9GqL0CH38"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
       
    let map;
    let markers = [];  // Lista para armazenar os marcadores

    function initMap() {
        const mapOptions = {
            center: { lat: -24.3245, lng: -50.6123 },
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
                content: `<h3>${ponto.nome}</h3><p>${ponto.descricao}</p><img src="${ponto.imagem_url}" class="img-fluid">`
            });

            marker.addListener('click', () => {
                infoWindow.open(map, marker);
            });
        });
    }
    window.onload = initMap;

    // Função para centralizar o mapa e mostrar o marcador vermelho ao clicar no card
    function centerMapAndPlaceMarker(pontoId) {
        const ponto = <?= json_encode($pontosTuristicos) ?>.find(p => p.id == pontoId);
        if (ponto) {
            const latLng = new google.maps.LatLng(ponto.localizacao_lat, ponto.localizacao_lng);
            map.setCenter(latLng);
            map.setZoom(15);

            // Adiciona o marcador no ponto turístico
            const marker = new google.maps.Marker({
                position: latLng,
                map: map,
                title: ponto.nome,
                icon: {
                    url: "http://maps.google.com/mapfiles/ms/icons/red-dot.png" // Ícone do marcador (pingente vermelho)
                }
            });

            // Remove o marcador anterior para evitar sobrecarga
            markers.forEach(marker => marker.setMap(null));
            markers = [marker];

            const infoWindow = new google.maps.InfoWindow({
                content: `<h3>${ponto.nome}</h3><p>${ponto.descricao}</p><img src="${ponto.imagem_url}" class="img-fluid">`
            });

            infoWindow.open(map, marker);
        }
    }

    // Atualizar o mapa e remover marcador ao excluir ponto turístico
$(document).on('click', '.excluir-ponto', function() {
    const pontoId = $(this).data('ponto-id');

    if (confirm("Você tem certeza que deseja excluir este ponto turístico?")) {
        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: { excluir_ponto_id: pontoId },
            success: function(response) {
                const res = JSON.parse(response);
                if (res.status === 'success') {
                    // Remover o ponto da lista de pontos turísticos
                    $(`.card-clickable[data-ponto-id="${pontoId}"]`).remove();

                    // Remover marcador do mapa
                    markers.forEach(marker => {
                        if (marker.title === res.ponto_nome) {
                            marker.setMap(null);
                        }
                    });
                } else {
                    alert("Erro ao excluir o ponto turístico.");
                }
            }
        });
    }
});

    // Excluir ponto turístico sem recarregar a página
    $(document).on('click', '.excluir-ponto', function() {
        const pontoId = $(this).data('ponto-id');

        // Perguntar ao usuário se ele tem certeza que deseja excluir
        if (confirm("Você tem certeza que deseja excluir este ponto turístico?")) {
            $.ajax({
                url: 'index.php',
                type: 'POST',
                data: { excluir_ponto_id: pontoId },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        // Remove o ponto da lista de forma dinâmica
                        $(`.card-clickable[data-ponto-id="${pontoId}"]`).remove();
                    } else {
                        alert("Erro ao excluir o ponto turístico.");
                    }
                }
            });
        }
    });

    // Excluir comentário sem recarregar a página
    $(document).on('click', '.excluir-comentario', function() {
        const comentarioId = $(this).data('comentario-id');
        const pontoId = $(this).data('ponto-id'); // Obter o ponto_id do comentário

        $.ajax({
            url: 'index.php', // A URL de envio da solicitação de exclusão
            type: 'GET',
            data: { excluir_comentario_id: comentarioId }, // Envia o ID do comentário a ser excluído
            success: function(response) {
                // Atualiza a lista de comentários
                $.ajax({
                    url: 'get_comentarios.php',
                    type: 'GET',
                    data: { ponto_id: pontoId },
                    success: function(response) {
                        $('#comentariosList').html(response);
                    }
                });

                // Remove o comentário da lista de forma dinâmica
                $(`#comentario-${comentarioId}`).remove();
            }
        });
    });

    // Enviar comentário sem recarregar a página
    $(document).on('submit', '#comentarioForm', function(event) {
        event.preventDefault();
        const comentario = $('textarea[name="comentario"]').val();
        const pontoId = $('#modalPontoId').val();

        $.ajax({
            url: 'index.php',
            type: 'POST',
            data: { comentario: comentario, ponto_id: pontoId },
            success: function(response) {
                const res = JSON.parse(response);
                if (res.status === 'success') {
                    // Atualiza os comentários na página sem recarregar
                    $.ajax({
                        url: 'get_comentarios.php',
                        type: 'GET',
                        data: { ponto_id: pontoId },
                        success: function(response) {
                            $('#comentariosList').html(response);
                        }
                    });
                }
            }
        });
    });
</script>

    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="index.php"><h2>Telêmaco Turismo</h2></a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ml-auto">
                <?php if ($usuarioLogado): ?>
                    <div id="menu" class="ml-auto d-flex align-items-center">
                        <span class="text-light me-3">Bem Vindo Sr.  <?= htmlspecialchars($_SESSION['usuario']) ?> !</span>
                    </div>
                    <?php if (!$isAdmin): ?>
                        <li class="nav-item"><a class="nav-link" href="solicitacaoForm.php">Solicitar Cadastro de Ponto</a></li>
                    <?php endif; ?>
                    <?php if ($isAdmin): ?>
                        <li class="nav-item"><a class="nav-link" href="gerenciar_solicitacoes.php">Gerenciar Solicitações</a></li>
                        <li class="nav-item"><a class="nav-link" href="cadastro_ponto.php">Cadastrar Ponto</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Entrar</a></li>
                    <li class="nav-item"><a class="nav-link" href="cadastro.php">Cadastrar-se</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Pontos Turísticos de Telêmaco Borba</h2>
        <div id="map" style="height: 400px; width: 100%;"></div>

         <div class="row mt-4">
            <?php foreach ($pontosTuristicos as $ponto): ?>
                <div class="col-md-4">
                    <div class="card card-clickable" data-ponto-id="<?= $ponto['id'] ?>" onclick="centerMapAndPlaceMarker(<?= $ponto['id'] ?>)">
                        <img class="card-img-top" src="<?= $ponto['imagem_url'] ?>" alt="Imagem de <?= htmlspecialchars($ponto['nome']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($ponto['nome']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($ponto['descricao']) ?></p>
                            <?php if ($isAdmin): ?>
                                <button class="btn btn-danger excluir-ponto" data-ponto-id="<?= $ponto['id'] ?>">Excluir</button>
                            <?php endif; ?>
                            <button class="btn btn-info" data-toggle="modal" data-target="#comentariosModal" data-ponto-id="<?= $ponto['id'] ?>">Comentários</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<!-- Modal para exibir e adicionar comentários -->
<div class="modal fade" id="comentariosModal" tabindex="-1" role="dialog" aria-labelledby="comentariosModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="comentariosModalLabel">Comentários</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul id="comentariosList"></ul>
                <form id="comentarioForm">
                    <div class="form-group">
                        <textarea name="comentario" class="form-control" rows="3" placeholder="Escreva seu comentário..." required></textarea>
                    </div>
                    <input type="hidden" id="modalPontoId" name="ponto_id">
                    <button type="submit" class="btn btn-primary mt-2">Enviar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Preencher o modal com comentários
    $('#comentariosModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget); 
        const pontoId = button.data('ponto-id'); 
        const modal = $(this);
        modal.find('#modalPontoId').val(pontoId);

        // Carrega os comentários via AJAX
        $.ajax({
            url: 'get_comentarios.php',
            type: 'GET',
            data: { ponto_id: pontoId },
            success: function(response) {
                $('#comentariosList').html(response);
            }
        });
    });

    // Excluir comentário sem recarregar a página
    $(document).on('click', '.excluir-comentario', function() {
        const comentarioId = $(this).data('comentario-id');
        const pontoId = $(this).data('ponto-id'); // Obter o ponto_id do comentário

        // Confirmação antes de excluir
        if (confirm("Você tem certeza que deseja excluir este comentário?")) {
            // Envia a requisição de exclusão via AJAX
            $.ajax({
                url: 'index.php', // A URL de envio da solicitação de exclusão
                type: 'GET',
                data: { excluir_comentario_id: comentarioId, ponto_id: pontoId }, // Envia o ID do comentário a ser excluído
                success: function(response) {
                    // Atualiza a lista de comentários
                    $.ajax({
                        url: 'get_comentarios.php',
                        type: 'GET',
                        data: { ponto_id: pontoId },
                        success: function(response) {
                            $('#comentariosList').html(response);
                        }
                    });

                    // Remove o comentário da lista de forma dinâmica
                    $(`#comentario-${comentarioId}`).remove();
                }
            });
        }
    });


</script>


    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
