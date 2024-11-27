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

        $(document).on('click', '.card-clickable', function () {
            const pontoId = $(this).data('ponto-id');
            const pontoNome = $(this).data('ponto-nome');
            const pontoDescricao = $(this).data('ponto-descricao');
            const pontoImagem = $(this).data('ponto-imagem');

            $('#modalNome').text(pontoNome);
            $('#modalDescricao').text(pontoDescricao);
            $('#modalImagem').attr('src', pontoImagem);
            $('#modalPontoId').val(pontoId);

            $.ajax({
                url: 'get_comentarios.php',
                type: 'GET',
                data: { ponto_id: pontoId },
                success: function(response) {
                    $('#comentariosList').html(response);
                }
            });

            $('#modalPontoTuristico').modal('show');
        });

   
        // Excluir ponto turístico sem recarregar a página
        $(document).on('click', '.excluir-ponto', function() {
            const pontoId = $(this).data('ponto-id');

            $.ajax({
                url: 'index.php',
                type: 'POST',
                data: { excluir_ponto_id: pontoId },
                success: function(response) {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        // Remove o ponto da lista de forma dinâmica
                        $(`.card-clickable[data-ponto-id="${pontoId}"]`).remove();
                    }
                }
            });
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
            // Verifique a resposta se a exclusão foi bem-sucedida
            const res = JSON.parse(response);
            if (res.status === 'success') {
                // Atualizar a lista de comentários
                $.ajax({
                    url: 'get_comentarios.php',
                    type: 'GET',
                    data: { ponto_id: pontoId },
                    success: function(response) {
                        $('#comentariosList').html(response); // Atualiza os comentários
                    }
                });

                // Remove o comentário da lista de forma dinâmica
                $(`#comentario-${comentarioId}`).remove();
            } else {
                alert('Erro ao excluir o comentário.');
            }
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
                        <li class="nav-item"><a class="nav-link" href="gerenciar_solicitacoes.php">Gerenciar Solicitações</a></li>
                        <li class="nav-item"><a class="nav-link" href="cadastro_ponto.php">Cadastrar Ponto</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Cadastro</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <div id="map" style="height: 500px;"></div>

        <div class="row mt-4">
            <?php foreach ($pontosTuristicos as $ponto): ?>
                <div class="col-md-4 mb-4">
                    <div class="card card-clickable" data-ponto-id="<?= $ponto['id'] ?>" data-ponto-nome="<?= htmlspecialchars($ponto['nome']) ?>" data-ponto-descricao="<?= htmlspecialchars($ponto['descricao']) ?>" data-ponto-imagem="<?= htmlspecialchars($ponto['imagem_url']) ?>">
                        <img class="card-img-top" src="<?= htmlspecialchars($ponto['imagem_url']) ?>" alt="Imagem do ponto turístico">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($ponto['nome']) ?></h5>

                            <!-- Seta para mostrar/ocultar detalhes -->
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="button" class="btn btn-link btn-sm toggle-details">
                                    <i class="fas fa-chevron-down"></i> Mostrar Detalhes
                                </button>
                            </div>
                            
                           
                        </div>
                        <?php if ($isAdmin): ?>
                            <button class="btn btn-danger excluir-ponto" data-ponto-id="<?= $ponto['id'] ?>">Excluir</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal para mostrar detalhes e comentar -->
    <!-- Modal para mostrar detalhes e comentar -->
<div class="modal fade" id="modalPontoTuristico" tabindex="-1" role="dialog" aria-labelledby="modalPontoTuristicoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNome"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="modalDescricao"></p>
                <img id="modalImagem" class="img-fluid" alt="Imagem do ponto turístico">
                
                <?php if ($usuarioLogado): ?>
                    <div class="mt-3">
                        <form id="comentarioForm" action="" method="POST">
                            <div class="form-group">
                                <label for="comentario">Deixe seu comentário:</label>
                                <textarea class="form-control" name="comentario" required></textarea>
                            </div>
                            <input type="hidden" name="ponto_id" id="modalPontoId" value="">
                            <button type="submit" class="btn btn-primary">Enviar Comentário</button>
                        </form>
                    </div>
                <?php endif; ?>

                <h4>Comentários:</h4>
                
                <ul id="comentariosList" class="list-group">
                    <!-- Comentários serão carregados via AJAX -->
                     
                </ul>
                
            </div>
        </div>
    </div>
</div>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>