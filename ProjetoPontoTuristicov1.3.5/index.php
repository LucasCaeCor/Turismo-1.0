<?php
session_start();
include 'db_connection.php';


// Verifica se o usuário está logado e se é admin
$usuarioLogado = isset($_SESSION['usuario']);
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;

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
                
                // Envia resposta JSON
                echo json_encode(['status' => 'success']);
                exit();
            }
        }
    }
    
    // Se não for possível excluir, retorna erro
    echo json_encode(['status' => 'error']);
    exit();
}

if (isset($_POST['editar_comentario_id']) && isset($_POST['novo_comentario_texto'])) {
    $comentarioId = $_POST['editar_comentario_id'];
    $novoComentarioTexto = $_POST['novo_comentario_texto'];

    // Atualizar o comentário no banco de dados
    $sql = "UPDATE comentarios SET comentario = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$novoComentarioTexto, $comentarioId]);

    // Retornar uma resposta para o JavaScript
    echo json_encode(['status' => 'success']);
    exit;
}

// Lógica para excluir ponto turístico (somente admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_ponto_id']) && $isAdmin) {
    $pontoId = intval($_POST['excluir_ponto_id']);
    
    // Excluir comentários associados ao ponto
    $deleteComentariosQuery = "DELETE FROM comentarios WHERE ponto_id = ?";
    $stmtDeleteComentarios = $conn->prepare($deleteComentariosQuery);
    $stmtDeleteComentarios->bind_param("i", $pontoId);
    $stmtDeleteComentarios->execute();
    
    // Excluir o ponto turístico
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
<meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Um bom Lugar para encontrar e adicionar seu Ponto preferido no mapa" />
    <meta name="author" content="Lucas Caetano" />
    <title>Ponto Turistico - Alternativo</title>
    <link rel="icon" type="image/x-icon" href="assets/iconeTurismo.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,300italic,400italic,700italic" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>


    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCOSuLd-yd2Bxa7w98Zbs1oHu9GqL0CH38"></script>

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
        map.setZoom(30);

        // Muda o tipo de mapa para satélite
        map.setMapTypeId(google.maps.MapTypeId.SATELLITE);

        // Adiciona o marcador no ponto turístico
        const marker = new google.maps.Marker({
            position: latLng,
            map: map,
            title: ponto.nome,
        });

        // Remove o marcador anterior para evitar sobrecarga
        markers.forEach(marker => marker.setMap(null));
        markers = [marker];

        const infoWindow = new google.maps.InfoWindow({
            content: `<h3>${ponto.nome}</h3><p>${ponto.descricao}</p><img src="${ponto.imagem_url}" class="img-fluid">`
        });

        infoWindow.open(map, marker);

        // Atualiza o conteúdo do modal com as informações do ponto
        document.getElementById("modal-title").textContent = ponto.nome;
        document.getElementById("modal-description").textContent = ponto.descricao;
        document.getElementById("modal-image").src = ponto.imagem_url;

        // Desloca a página para o ponto do mapa
        window.scrollTo({
            top: document.getElementById("map-container").offsetTop,
            behavior: "smooth"
        });

        // Exibe o mapa à esquerda e o modal à direita
        document.getElementById("map").style.transform = "translateX(-40%)";  // Mapa vai para a esquerda
        document.getElementById("info-modal").style.display = "block";  // Exibe o modal com informações
        document.getElementById("info-modal").style.transform = "translateX(0)";  // Exibe o modal deslizando de volta
    }
}



       



       // quando clica no card, ele leva para o mapa
       $(document).on('click', '.card-clickable', function() {
    const pontoId = $(this).data('ponto-id');  // Obter o ID do ponto do card clicado

    // Chama a função para centralizar o mapa e adicionar o marcador
    centerMapAndPlaceMarker(pontoId);

    // Rolando a página até o mapa com uma margem superior
    $('html, body').animate({
        scrollTop: $("#map").offset().top - 50  // Ajusta a rolagem para deixar uma margem de 50px do topo
    }, -500);  // A animação vai durar 1 segundo
});



       


       //Excluir ponto sem recarregar a tela
   
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
                    markers = markers.filter(marker => {
                        if (marker.pontoId === pontoId) {
                            marker.setMap(null); // Remove o marcador do mapa
                            return false; // Remove o marcador da lista
                        }
                        return true;
                    });
                } else {
                    alert("Erro ao excluir o ponto turístico.");
                }
            }
        });
    }
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
       $(document).on('click', '.excluir-comentario', function() {
    const comentarioId = $(this).data('comentario-id');
    const pontoId = $(this).data('ponto-id');


    // Atualizar a lista de comentários sem recarregar a página
    $.ajax({
        url: 'index.php',
        type: 'GET',
        data: { excluir_comentario_id: comentarioId },
        success: function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                // Atualizar a lista de comentários sem recarregar a página
                $.ajax({
                    url: 'get_comentarios.php',
                    type: 'GET',
                    data: { ponto_id: pontoId },
                    success: function(response) {
                        $('#comentariosList').html(response);
                    }
                });
                // Remover o comentário da lista de forma dinâmica
                $(`#comentario-${comentarioId}`).remove();
            } else {
                alert('Erro ao excluir o comentário.');
            }
        }
    });
});







   </script>

</head>
<body>

<!-- inicio banner -->
    <nav class="navbar navbar-light bg-light static-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">Bem Vindo, Senhor(a)</a>
            <ul class="navbar-nav ml-auto">
                <?php if ($usuarioLogado): ?>
                    <div id="menu" class="ml-auto d-flex align-items-center">
                        <span class="text-light me-3">Bem Vindo Sr. <?= htmlspecialchars($_SESSION['usuario']) ?> !</span>
                    </div>
                    <?php if (!$isAdmin): ?>
                        <a class="btn btn-primary" href="solicitacaoForm.php">Solicitar Cadastro de Ponto</a>
                    <?php endif; ?>
                    <?php if ($isAdmin): ?>
                        <a class="btn btn-primary" href="gerenciar_solicitacoes.php">Gerenciar Solicitações</a>
                        <a class="btn btn-primary" href="cadastro_ponto.php">Cadastrar Ponto</a>
                    <?php endif; ?>
                    <a class="btn btn-primary" href="logout.php">Sair</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="login.php">Entrar</a>
                    <a class="btn btn-primary" href="cadastro.php">Cadastrar-se</a>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
        
<!-- Masthead --> 
<header class="masthead">
    <div class="container mt-4">
        <h2>Pontos Turísticos de Telêmaco Borba</h2>

        <!-- Divisão para o Mapa e Modal -->
        <div id="map-container" style="display: flex; justify-content: center; align-items: flex-start; height: 80vh;">

            <!-- Mapa (inicialmente centralizado) -->
            <div id="map" style="width: 60%; height: 100%; transition: transform 0.5s ease;"></div>

            <!-- Modal com Informações (oculto inicialmente) -->
            <div id="info-modal" style="width: 35%; height: 100%; overflow-y: auto; display: none; transition: transform 0.5s ease; padding: 20px;">
                <!-- Botão Voltar -->
                <button id="back-btn" class="btn btn-primary" style="position: absolute; top: 10px; left: 10px;">Voltar</button>
                <div style="height: 50%; width: 100%; overflow: hidden;">
                    <img id="modal-image" class="img-fluid" style="object-fit: cover; height: 100%; width: 100%;" />
                </div>
                <h3 id="modal-title"></h3>
                <p id="modal-description" style="margin-bottom: 15px;"></p>
                <!-- Descrição com "Ler Mais" se for muito grande -->
                <div id="long-description-container" style="display: none;">
                    <p id="modal-long-description" style="display: none;"></p>
                    <button id="read-more-btn" style="display: none;">Ler Mais</button>
                </div>
            </div>

        </div> <!-- Fim do map-container -->

        </div> <!-- Fim da linha de cards -->

    </div> <!-- Fim do container -->
</header>

<!-- Script CSS para Divisão do Mapa e Modal -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mapElement = document.getElementById('map');
        const infoModal = document.getElementById('info-modal');
        const mapContainer = document.getElementById('map-container');
        const modalTitle = document.getElementById('modal-title');
        const modalDescription = document.getElementById('modal-description');
        const modalImage = document.getElementById('modal-image');
        const longDescriptionContainer = document.getElementById('long-description-container');
        const readMoreBtn = document.getElementById('read-more-btn');
        const modalLongDescription = document.getElementById('modal-long-description');
        const backBtn = document.getElementById('back-btn');

        // Função para exibir mapa e modal com informações
        function showMapAndModal() {
            mapElement.style.transform = "translateX(-40%)"; // Mapa se move para a esquerda
            infoModal.style.display = "block";  // Exibe o modal com as informações
            infoModal.style.transform = "translateX(0)";  // Exibe o modal deslizando de volta
        }

        // Função para preencher o modal com as informações
        function populateModal(title, description, image, longDescription) {
            modalTitle.textContent = title;
            modalDescription.textContent = description;
            modalImage.src = image;
            modalLongDescription.textContent = longDescription;

            // Verifica se a descrição é muito longa e exibe "Ler Mais"
            if (longDescription.length > 50) {
                longDescriptionContainer.style.display = 'block';
                readMoreBtn.style.display = 'inline-block';
            } else {
                longDescriptionContainer.style.display = 'none';
                readMoreBtn.style.display = 'none';
            }

            // Lógica do botão "Ler mais"
            readMoreBtn.addEventListener('click', function() {
                if (modal-description.style.display === 'none' || modalLongDescription.style.display === '') {
                    modalLongDescription.style.display = 'block';  // Exibe a descrição longa
                    readMoreBtn.textContent = 'Ler Menos';  // Troca o texto para "Ler Menos"
                } else {
                    modalLongDescription.style.display = 'none';  // Oculta a descrição longa
                    readMoreBtn.textContent = 'Ler Mais';  // Troca o texto para "Ler Mais"
                }
            });
        }

        // Função para voltar ao estado inicial
        function backToInitialState() {
            mapElement.style.transform = "translateX(0)";  // Mapa retorna ao centro
            infoModal.style.display = "none";  // Esconde o modal
        }

        // Ação do botão Voltar
        backBtn.addEventListener('click', function() {
            backToInitialState();  // Chama a função para voltar à posição inicial
        });


    });
</script>








        <!-- Coisas a Se fazer no site-->
        <section class="features-icons bg-light text-center">
            <div class="container">
                <div class="row">
                <div class="features-icons-item mx-auto mb-5 mb-lg-0 mb-lg-3">
                    <div class="features-icons-icon d-flex"><i class="bi-window m-auto text-primary"></i></div>
                        <h3>Procure</h3>
                        <p class="lead mb-0">Procure o Ponto Turístico Desejado</p>
                        <input type="text" id="search-input" class="form-control mt-3" placeholder="Digite o nome do ponto turístico">
                    </div>
                    <div class="features-icons-item mx-auto mb-5 mb-lg-0 mb-lg-3">
                        <div class="features-icons-icon d-flex"><i class="bi-layers m-auto text-primary"></i></div>
                            <h3>Cadastre um Ponto</h3>
                            <p class="lead mb-0">Cadastre o Lugar que julga ser importante!</p>
                            <?php if (!$isAdmin): ?>    
                                <a href="solicitacaoForm.php" class="btn btn-primary" aria-label="Cadastrar um ponto turístico">Cadastrar Ponto</a>
                            <?php endif; ?>

                    </div>

                    <div class="features-icons-item mx-auto mb-0 mb-lg-3">
                        <div class="features-icons-icon d-flex"><i class="bi-terminal m-auto text-primary"></i></div>
                            <h3>Comente</h3>
                            <p class="lead mb-0">Comente no seu ponto favorito ou no que você mais odiou. Deixe seu feedback!</p>
                            
                    </div>


            </div>
        </section>
        
        <!-- Aqui são os cards com as imagens-->
        <section class="showcase">
        <div class="container-fluid p-0">
    <div class="row">
        <?php foreach ($pontosTuristicos as $ponto): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-clickable h-100" data-ponto-id="<?= $ponto['id'] ?>" onclick="centerMapAndPlaceMarker(<?= $ponto['id'] ?>)">
                    <div class="card-img-container">
                        <img class="card-img-top" src="<?= $ponto['imagem_url'] ?>" alt="Imagem de <?= htmlspecialchars($ponto['nome']) ?>">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($ponto['nome']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($ponto['descricao']) ?></p>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
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

<style>
    .card-img-container {
        width: 100%;
        height: 100%; /* Altura uniforme para todas as imagens */
        
    }

    .card-img-container img {
        
        width: 50%;
        height: 100%;
        object-fit: cover; /* Garante que a imagem preencha o espaço sem distorções */
    }
</style>


           <!-- Modal para exibir e adicionar comentários -->
            <div class="modal fade" id="comentariosModal" tabindex="-1" role="dialog" aria-labelledby="comentariosModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <ul id="comentariosList"></ul>
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
                        </div>
                    </div>
                </div>
            </div>

              

        </section>
        
       
        <!-- Footer-->
        <footer class="footer bg-light ">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 h-100 text-center text-lg-start my-auto">
                        <ul class="list-inline mb-2">
                            <li class="list-inline-item"><a href="#!">Sobre</a></li>
                            <li class="list-inline-item">⋅</li>
                            <li class="list-inline-item"><a href="#!">Contato</a></li>
                            <li class="list-inline-item">⋅</li>
                            <li class="list-inline-item"><a href="#!">Terms of Use</a></li>
                            <li class="list-inline-item">⋅</li>
                            <li class="list-inline-item"><a href="#!">Privacy Policy</a></li>
                        </ul>
                        <p class="text-muted small mb-4 mb-lg-0">&copy; Your Website 2023. All Rights Reserved.</p>
                    </div>
                    <div class="col-lg-6 h-100 text-center text-lg-end my-auto">
                        <ul class="list-inline mb-0">
                            <li class="list-inline-item me-4">
                                <a href="#!"><i class="bi-facebook fs-3"></i></a>
                            </li>
                            <li class="list-inline-item me-4">
                                <a href="#!"><i class="bi-twitter fs-3"></i></a>
                            </li>
                            <li class="list-inline-item">
                                <a href="#!"><i class="bi-instagram fs-3"></i></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>

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
        </script>

          <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
          

    </body>
</html>

