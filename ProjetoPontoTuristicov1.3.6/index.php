<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está logado e se é admin
$usuarioLogado = isset($_SESSION['usuario']);
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;

// logica para excluir comentario (FUNCIONANDO)
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

// Lógica para excluir ponto turístico (somente admin) (FUNCIONANDO)
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

// Adiciona um comentário, se enviado  (FUNCIONANDO)
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
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCOSuLd-yd2Bxa7w98Zbs1oHu9GqL0CH38"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>





<style>/* Seção de comentários */
    .btn-secondary:hover {
        background-color: #5a6268;
    }
</style>

</head>
<body>
<section><!-- inicio banner -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark static-top">
        <div class="container">
            <a class="navbar-brand hover-gold" href="index.php">HomePage</a>
            <ul class="navbar-nav ml-auto">
                <?php if ($usuarioLogado): ?>
                    <div id="menu" class="ml-auto d-flex align-items-center justify-content-center w-100">
                        <span class="text-light me-3">Bem Vindo Sr. <?= htmlspecialchars($_SESSION['usuario']) ?> !</span>
                    </div>
                    <!-- Menu Dropdown com a classe personalizada 'dropzim' -->
                    <li class="nav-item dropzim dropdown">
                        <a class="nav-link dropdown-toggle btn btn-dark text-white hover-gold" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Menu
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <?php if (!$isAdmin): ?>
                                <li><a class="dropdown-item" href="solicitacaoForm.php">Solicitar Cadastro de Ponto</a></li>
                            <?php endif; ?>
                            <?php if ($isAdmin): ?>
                                <li><a class="dropdown-item" href="gerenciar_solicitacoes.php">Gerenciar Solicitações</a></li>
                                <li><a class="dropdown-item" href="cadastro_ponto.php">Cadastrar Ponto</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="logout.php">Sair</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <a class="btn btn-dark text-white me-3 hover-gold" href="login.php">Entrar</a>
                    <a class="btn btn-dark text-white hover-gold" href="cadastro.php">Cadastrar-se</a>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</section>

<!-- Inclua o Bootstrap JS se não estiver no seu arquivo -->



<!-- Estilo adicional BANNER -->
<style>
    .hover-gold:hover {
        color: gold !important;
        background-color: black !important;
        border-color: gold !important;
    }

    #menu {
        text-align: center;
        width: 100%;
    }

    .navbar-brand {
        color: white !important;
    }

    .navbar-nav {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
    }
</style>



<!-- Masthead --> 
<header class="masthead">
    <!-- INICIO MAPA -->
    <section>
        <div class="container text-center">
            <h2>Pontos Turísticos de Telêmaco Borba</h2>
            <!-- Divisão para o Mapa e Modal -->
            <div id="map-container" style="display: flex; justify-content: center; align-items: flex-start; height: 80vh;">
                <!-- Mapa (inicialmente centralizado) -->
                <div id="map" style="width: 60%; height: 100%; transition: transform 0.5s ease;"></div>
                <div id="info-modal" class="modal-content" style="display: none; padding: 20px; transition: transform 0.5s ease; width: 60%; height: 100%; position: relative; background-color: rgba(0, 0, 0, 0.7);">
                    <!-- Botão Voltar -->
                    <button id="back-btn" class="btn btn-back" style="position: absolute; top: 5px; left: 5px; padding-top: 5px; padding-left: 5px;">Voltar</button>

                    <div class="image-container" style="height: 250px; width: 100%; overflow: hidden;">
                        <img id="modal-image" class="img-fluid" style="object-fit: cover; height: 100%; width: 100%;" />
                    </div>

                    <h3 id="modal-title" class="text-center" style="color: white; margin-top: 20px;"></h3>

                    <!-- Campo de descrição com rolagem -->
                    <p id="modal-description" class="text-center" style="color: white; margin-bottom: 15px; padding-bottom: 10px; max-height: calc(100% - 250px); overflow-y: auto; text-align: justify;"></p>
                </div>
            </div> <!-- Fim do map-container -->
        </div>
    </section>

    </section>
    <!--Estilos para o modal (aqui-->
    <style>
        .modal-content {
            background-color: rgba(0, 0, 0, 0.7); /* Fundo transparente */
            border-radius: 10px; /* Bordas arredondadas */
            overflow: hidden; /* Remove barras de rolagem inicialmente */
            color: white; /* Cor do texto branca */
        


        }

        /* Tamanho da imagem */
        .modal img {
            max-width: 100%; /* Imagem ocupa o espaço corretamente */
            max-height: 100%; /* Evita que a imagem ultrapasse os limites */
            object-fit: cover; /* Faz a imagem cobrir o espaço disponível sem distorcer */
            margin-bottom: 15px;
        }

        /* Estilo do botão Voltar */
        #back-btn {
            background-color: #808080; /* Cor de fundo cinza */
            color: white; /* Cor do texto branca */
            border: 2px solid #cccccc; /* Borda suave cinza clara */
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            position: absolute;
            top: 5px;
            left: 5px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); /* Sombra suave para destaque */
            transition: all 0.3s ease; /* Transição suave */
        }

        /* Efeito hover no botão */
        #back-btn:hover {
            color: gold; /* Cor dourada ao passar o mouse */
            border-color: gold; /* Borda dourada ao passar o mouse */
            box-shadow: 0px 6px 12px rgba(255, 223, 0, 0.5); /* Sombra dourada no hover */
        }

        /* Ajustando o campo de texto */
        #modal-description {
            max-height: calc(100% - 250px); /* Tamanho máximo de texto, respeitando a altura da imagem */
            overflow-y: auto; /* Barra de rolagem automática se necessário */
            text-align: justify;
            padding-bottom: 10px; /* Distância do texto para o final do modal */
            height: 100%; /* Mantém a altura total */
        }

        /* Responsividade */
        @media (max-width: 768px) {
            #info-modal {
                width: 90%; /* Modal ocupa mais espaço em telas pequenas */
            }

            #back-btn {
                padding: 8px 15px; /* Ajuste do botão para telas menores */
                font-size: 14px; /* Menor tamanho de texto */
            }
        }
    </style>
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

</header>



<!-- Coisas a Se fazer no site -->
<section class="features-icons bg-dark text-white text-center">
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
                    <?php if (!$isAdmin && $usuarioLogado): ?>    
                        <a href="solicitacaoForm.php" class="btn btn-primary" aria-label="Cadastrar um ponto turístico">Cadastrar Ponto</a>
                    <?php endif; ?>
            </div>
            <div class="features-icons-item mx-auto mb-0 mb-lg-3">
                <div class="features-icons-icon d-flex"><i class="bi-terminal m-auto text-primary"></i></div>
                    <h3>Comente</h3>
                    <p class="lead mb-0">Comente no seu ponto favorito ou no que você mais odiou. Deixe seu feedback!</p>              
            </div>
        </div> 
    </div>
</section>
     
<!-- Aqui são os cards com as imagens-->
<section class="showcase">
    <div class="container-fluid py-4">
        <div class="row g-3">
            <?php foreach ($pontosTuristicos as $ponto): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card card-clickable h-100 shadow-sm border-0" data-ponto-id="<?= $ponto['id'] ?>" onclick="centerMapAndPlaceMarker(<?= $ponto['id'] ?>)">
                        <div class="card-img-container">
                            <img class="card-img-top img-fluid" src="<?= $ponto['imagem_url'] ?>" alt="Imagem de <?= htmlspecialchars($ponto['nome']) ?>" style="height: 150px; object-fit: cover;">
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-truncate"><?= htmlspecialchars($ponto['nome']) ?></h5>
                            <p class="card-text text-truncate" style="max-height: 60px; overflow: hidden;"><?= htmlspecialchars($ponto['descricao']) ?></p>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <?php if ($isAdmin): ?>
                                <button class="btn btn-sm btn-danger excluir-ponto" data-ponto-id="<?= $ponto['id'] ?>">Excluir</button>
                                <button class="btn btn-sm btn-warning editar-ponto" data-ponto-id="<?= $ponto['id'] ?>" data-bs-toggle="modal" data-bs-target="#editarModal">Editar</button>
                            <?php endif; ?>
                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#comentariosModal" data-ponto-id="<?= $ponto['id'] ?>">Comentários</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Modal para Editar -->
<div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarModalLabel">Editar Ponto Turístico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarPonto" enctype="multipart/form-data">
                    <input type="hidden" id="pontoId" name="pontoId">
                    <div class="mb-3">
                        <label for="nomePonto" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="nomePonto" name="nomePonto" required>
                    </div>
                    <div class="mb-3">
                        <label for="descricaoPonto" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricaoPonto" name="descricaoPonto" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="imagemArquivo" class="form-label">Imagem</label>
                        <!-- Campo para upload de arquivo -->
                        <input type="file" class="form-control" id="imagemArquivo" name="imagemArquivo" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Script para o botão editar -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Preencher o modal de edição ao clicar no botão editar
        document.querySelectorAll('.editar-ponto').forEach(button => {
            button.addEventListener('click', function () {
                const pontoId = this.getAttribute('data-ponto-id');

                // Buscar informações do ponto por ID
                const ponto = <?= json_encode($pontosTuristicos) ?>.find(p => p.id == pontoId);

                if (ponto) {
                    document.getElementById('pontoId').value = ponto.id;
                    document.getElementById('nomePonto').value = ponto.nome;
                    document.getElementById('descricaoPonto').value = ponto.descricao;
                }
            });
        });

        // Enviar as alterações ao backend
        document.getElementById('formEditarPonto').addEventListener('submit', function (e) {
            e.preventDefault();

            const pontoId = document.getElementById('pontoId').value;
            const nome = document.getElementById('nomePonto').value;
            const descricao = document.getElementById('descricaoPonto').value;
            const imagemArquivo = document.getElementById('imagemArquivo').files[0];

            const formData = new FormData();
            formData.append('pontoId', pontoId);
            formData.append('nomePonto', nome);
            formData.append('descricaoPonto', descricao);

            if (imagemArquivo) {
                formData.append('imagemArquivo', imagemArquivo);
            }

            fetch('processar_edicao.php', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Atualizar os dados do card correspondente
                        const card = document.querySelector(`.card[data-ponto-id="${pontoId}"]`);
                        if (card) {
                            card.querySelector('.card-title').textContent = nome;
                            card.querySelector('.card-text').textContent = descricao;

                            if (imagemArquivo) {
                                // Atualizar a imagem do card (se foi alterada)
                                const reader = new FileReader();
                                reader.onload = function (e) {
                                    card.querySelector('.card-img-top').src = e.target.result;
                                };
                                reader.readAsDataURL(imagemArquivo);
                            }
                        }

                        // Fechar o modal
                        const editarModal = bootstrap.Modal.getInstance(document.getElementById('editarModal'));
                        editarModal.hide();

                        // Exibir mensagem de sucesso
                        alert('Alterações salvas com sucesso!');
                    } else {
                        alert('Erro: ' + data.message);
                    }
                })
                .catch(error => console.error('Erro ao salvar as alterações:', error));
        });
    });
</script>




<!-- CSS(n) -->
<style>
    /* Fundo da seção */
    .showcase {
        background-color: #000000; /* Preto */
        padding: 20px 0;
    }

    /* Estilo dos cards */
    .card {
        background-color: #333333; /* Cinza escuro */
        color: #ffffff;
        border-radius: 10px;
        overflow: hidden;
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    }

    .card:hover {
        transform: scale(1.05);
        box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.5);
    }

    .card-title {
        font-weight: bold;
        color: #ffffff;
    }

    .card-text {
        color: #cccccc;
    }

    /* Rodapé */
    .card-footer {
        background-color: rgba(51, 51, 51, 0.9); /* Fundo cinza escuro */
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Botões */
    .btn-primary {
        background-color: #007bff; /* Azul */
        border: none;
        color: #ffffff;
    }

    .btn-primary:hover {
        background-color: #0056b3;
    }

    .btn-secondary {
        background-color: #6c757d; /* Cinza claro */
        border: none;
        color: #ffffff;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
    }

    .btn-danger:hover {
        background-color: #ff4444;
    }


    
</style>
 <!-- Modal para exibir e adicionar comentários -->
<section>

    <div class="modal fade" id="comentariosModal" tabindex="-1" role="dialog" aria-labelledby="comentariosModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
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



 <!-- Footer -->
<section>
    <footer class="footer" style="background-color: rgba(128, 128, 128, 0.8); padding: 20px 0;">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 h-100 text-center text-lg-start my-auto">
                    <ul class="list-inline mb-2">
                        <li class="list-inline-item"><a href="#!" class="text-white">Sobre</a></li>
                        <li class="list-inline-item">⋅</li>
                        <li class="list-inline-item"><a href="#!" class="text-white">Contato</a></li>
                        <li class="list-inline-item">⋅</li>
                        <li class="list-inline-item"><a href="#!" class="text-white">Termos de Uso</a></li>
                        <li class="list-inline-item">⋅</li>
                        <li class="list-inline-item"><a href="#!" class="text-white">Política de Privacidade</a></li>
                    </ul>
                    <p class="text-white small mb-4 mb-lg-0">&copy; Projeto Turismo. Todos os Direitos Reservados.</p>
                </div>
                <div class="col-lg-6 h-100 text-center text-lg-end my-auto">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item me-4">
                            <a href="#!" class="text-white"><i class="bi-facebook fs-3"></i></a>
                        </li>
                        <li class="list-inline-item me-4">
                            <a href="#!" class="text-white"><i class="bi-twitter fs-3"></i></a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#!" class="text-white"><i class="bi-instagram fs-3"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
</section>
<!-- modelo de estilo para o footer -->
<style>
    /* Garantir que o fundo do footer é transparente com cinza e texto branco */
    footer {
        background-color: rgba(128, 128, 128, 0.8); /* Fundo cinza com transparência */
        color: white; /* Texto branco */
    }

    footer .list-inline-item a {
        color: white; /* Cor dos links */
        text-decoration: none; /* Remove underline */
    }

    footer .list-inline-item a:hover {
        color: gold; /* Cor dourada no hover */
    }

    /* Garantir que o footer tenha boa visibilidade e contraste */
    footer .text-white {
        color: white !important; /* Garantir que o texto seja sempre branco */
    }

    footer .list-inline-item {
        margin-right: 15px; /* Ajuste de espaçamento entre os itens */
    }

    footer .list-inline-item i {
        color: white; /* Cor dos ícones */
    }

    footer .list-inline-item i:hover {
        color: gold; /* Cor dourada nos ícones ao passar o mouse */
    }
</style>

        
<!-- INICIAR MAPA SCRIPT -->
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
            content: `<h3>${ponto.nome}</h3>`

            // content: `<h3>${ponto.nome}</h3><p>${ponto.descricao}</p><img src="${ponto.imagem_url}" class="img-fluid">`
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
</script>

<!-- zoom dinamico ai clicar no card, ir ate o ponto no mapa -->
<script>
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
</script>   
      

<script>//Excluir ponto sem recarregar a tela
       
   
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
</script>

<!-- ações do comentario sem recarregar  a pagina -->
<script>
   
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

<!-- // Preencher o modal com comentários -->
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



<!-- bootstrap -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    </body>
</html>

