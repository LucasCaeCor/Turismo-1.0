<?php 
// Inicia a sessão e inclui a conexão com o banco de dados
session_start();
include 'db_connection.php';

// Verifica se o usuário está logado
$usuarioLogado = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;

$pontoId = isset($_GET['ponto_id']) ? intval($_GET['ponto_id']) : 0;
$comentarios = [];

// Consulta os comentários do banco de dados
$query = "SELECT * FROM comentarios WHERE ponto_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pontoId);
$stmt->execute();
$result = $stmt->get_result();

while ($comentario = $result->fetch_assoc()) {
    $comentarios[] = $comentario;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comentários</title>
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>



 <style>/* Seção de comentários */
    section {
        background-color: #000000 !important; /* Garante fundo preto */
        padding: 20px 0;
    }

    /* Título da seção de comentários */
    h2 {
        color: #ffffff;
        font-weight: bold;
        margin-bottom: 20px;
    }

    /* Estilo dos itens de comentário */
    .list-group-item {
        background-color: #333333;
        color: #ffffff;
        border: 1px solid #444444;
        border-radius: 8px;
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    }



    /* Menu dropdown */
    .dropdown-menu {
        background-color: #333333 !important; /* Forçar a cor de fundo */
        border: 1px solid #444444;
    }

    .dropdown-item {
        color: #ffffff;
    }

    .dropdown-item:hover {
        background-color: #555555;
    }

    /* Botão de opções */
    .btn-secondary {
        background-color: #6c757d;
        border: none;
        color: #ffffff;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
    }

    /* Modal - Forçando fundo escuro */
    .modal-content {
        background-color: #333333 !important; /* Garante fundo escuro no modal */
        color: #ffffff;
    }

    .modal-header, .modal-body, .modal-footer {
        background-color: #333333 !important; /* Garante que todas as partes do modal têm fundo escuro */
    }

    /* Forçar fundo escuro na página de comentários */
    body {
        background-color: #000000 !important;
    }
</style>

</head>
<body>

<!-- seção do card MODAL -->
<section>
    <div class="container mt-5">
        <h2 class="text-white">Comentários</h2>
        <ul class="list-group">
            <?php foreach ($comentarios as $comentario): ?>
                <li id="comentario-<?= $comentario['id'] ?>" class="list-group-item bg-dark text-white border-0 mb-3 rounded">
                    <p><strong><?= htmlspecialchars($comentario['usuario']) ?></strong> disse:</p>
                    <p><?= htmlspecialchars($comentario['comentario']) ?></p>

                    <?php if ($usuarioLogado && ($comentario['usuario'] === $_SESSION['usuario'] || $_SESSION['is_admin'])): ?>
                        <!-- Dropdown para opções de comentário -->
                        <div class="dropdown" style="position: absolute; top: 0; right: 10px;">
                            <!-- Ícone de 3 pontinhos no lugar do botão -->
                            <button class="btn btn-secondary dropdown-toggle btn-sm" type="button" id="dropdownMenuButton-<?= $comentario['id'] ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton-<?= $comentario['id'] ?>">
                                <!-- Opção de editar comentário -->
                                <li>
                                    <a class="dropdown-item editar-comentario" href="#" data-comentario-id="<?= $comentario['id'] ?>" data-comentario-texto="<?= htmlspecialchars($comentario['comentario']) ?>">Editar</a>
                                </li>
                                <!-- Opção de excluir comentário -->
                                <li>
                                    <button class="dropdown-item excluir-comentario" data-comentario-id="<?= $comentario['id'] ?>" data-ponto-id="<?= $comentario['ponto_id'] ?>">Excluir</button>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>



<!-- CSS -->
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


<section><!-- Modal Editar Comentário -->
    <div class="modal fade" id="editarModalcomentario" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarModalLabel">Editar Comentário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarComentario">
                        <input type="hidden" id="comentarioId">
                        <div class="mb-3">
                            <label for="comentarioTexto" class="form-label">Comentário</label>
                            <textarea class="form-control" id="comentarioTexto" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar alterações</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Script para o modal editar -->
<script>
    // Captura o clique no botão de editar
    document.querySelectorAll('.editar-comentario').forEach(button => {
        button.removeEventListener('click', abrirModalEditar); // Remove qualquer evento anterior
        button.addEventListener('click', abrirModalEditar);
    });

    // Função para abrir o modal de edição
    function abrirModalEditar(event) {
        const comentarioId = event.target.getAttribute('data-comentario-id');
        const comentarioTexto = event.target.getAttribute('data-comentario-texto');
        
        // Preenche o modal com os dados do comentário
        document.getElementById('comentarioId').value = comentarioId;
        document.getElementById('comentarioTexto').value = comentarioTexto;

        // Exibe o modal
        const modal = new bootstrap.Modal(document.getElementById('editarModalcomentario'));
        modal.show();
    }

    // Captura o submit do formulário de edição
    document.getElementById('formEditarComentario').addEventListener('submit', function(event) {
        event.preventDefault();
        
        const comentarioId = document.getElementById('comentarioId').value;
        const comentarioTexto = document.getElementById('comentarioTexto').value.trim();

        if (comentarioTexto === "") {
            exibirMensagemErro("O comentário não pode estar vazio.");
            return;
        }

        // Envia a edição via fetch
        fetch('editar_comentario.php', {
            method: 'POST',
            body: new URLSearchParams({
                comentario_id: comentarioId,
                comentario_texto: comentarioTexto
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualiza o comentário na página sem recarregar
                const comentarioElement = document.getElementById('comentario-' + comentarioId);
                comentarioElement.querySelector('p').textContent = comentarioTexto; // Atualiza o conteúdo do comentário

                // Exibe mensagem de sucesso
                exibirMensagemSucesso("Comentário editado com sucesso!");

                // Fecha o modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editarModalcomentario'));
                modal.hide();
            } else {
                exibirMensagemErro('Erro ao editar o comentário. Tente novamente.');
            }
        })
        .catch(error => {
            exibirMensagemErro('Erro ao editar o comentário. Tente novamente.');
            console.error('Erro ao editar o comentário:', error);
        });
    });

    // Função para exibir mensagens de erro
    function exibirMensagemErro(mensagem) {
        const mensagemErro = document.createElement('div');
        mensagemErro.classList.add('alert', 'alert-danger');
        mensagemErro.textContent = mensagem;
        document.body.appendChild(mensagemErro);
        
        // Remove a mensagem após 5 segundos
        setTimeout(() => {
            mensagemErro.remove();
        }, 5000);
    }

    // Função para exibir mensagens de sucesso
    function exibirMensagemSucesso(mensagem) {
        const mensagemSucesso = document.createElement('div');
        mensagemSucesso.classList.add('alert', 'alert-success');
        mensagemSucesso.textContent = mensagem;
        document.body.appendChild(mensagemSucesso);

        // Remove a mensagem após 5 segundos
        setTimeout(() => {
            mensagemSucesso.remove();
        }, 5000);
    }
</script>

<!-- Scripts do Bootstrap e jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>







</body>
</html>