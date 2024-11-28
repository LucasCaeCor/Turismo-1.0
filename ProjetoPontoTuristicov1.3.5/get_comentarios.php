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
</head>
<body>

<!-- seção do card MODAL -->
<section> 
    <div class="container mt-5">
        <h2>Comentários</h2>
        <ul class="list-group">
            <?php foreach ($comentarios as $comentario): ?>
                <li id="comentario-<?= $comentario['id'] ?>" class="list-group-item">
                    <p><strong><?= htmlspecialchars($comentario['usuario']) ?></strong> disse:</p>
                    <p><?= htmlspecialchars($comentario['comentario']) ?></p>

                    <?php if ($usuarioLogado && ($comentario['usuario'] === $_SESSION['usuario'] || $_SESSION['is_admin'])): ?>
                        <!-- Dropdown para opções de comentário -->
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                Opções
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
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


<section><!-- Modal Editar Comentário -->
    <div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
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
                            <textarea class="form-control" id="comentarioTexto" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar alterações</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Scripts do Bootstrap e jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>



<!-- Aqui TEM as funções para os botões do modal, editar e excluir -->
<script>
    // Captura o clique no botão de editar (FUNCIONANDO)
    document.querySelectorAll('.editar-comentario').forEach(button => {
        button.removeEventListener('click', abrirModalEditar); // Remove qualquer evento anterior
        button.addEventListener('click', abrirModalEditar);
    });

    // Função para abrir o modal de edição (FUNCIONANDO)
    function abrirModalEditar(event) {
        const comentarioId = event.target.getAttribute('data-comentario-id');
        const comentarioTexto = event.target.getAttribute('data-comentario-texto');
        
        // Preenche o modal com os dados do comentário (FUNCIONANDO)
        document.getElementById('comentarioId').value = comentarioId;
        document.getElementById('comentarioTexto').value = comentarioTexto;

        // Exibe o modal (FUNCIONANDO)
        const modal = new bootstrap.Modal(document.getElementById('editarModal'));
        modal.show();
    }

    // Captura o submit do formulário de edição (FUNCIONANDO)
    document.getElementById('formEditarComentario').addEventListener('submit', function(event) {
        event.preventDefault();
        
        const comentarioId = document.getElementById('comentarioId').value;
        const comentarioTexto = document.getElementById('comentarioTexto').value;
        
        if (comentarioTexto.trim() === "") {
            alert("O comentário não pode estar vazio.");
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'editar_comentario.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Atualiza o comentário na página
                const comentarioElement = document.getElementById('comentario-' + comentarioId);
                comentarioElement.querySelector('p:last-child').textContent = comentarioTexto;
                const modal = bootstrap.Modal.getInstance(document.getElementById('editarModal'));
                modal.hide();
            } else {
                alert('Erro ao editar o comentário. Tente novamente.');
            }
        };
        xhr.send('comentario_id=' + comentarioId + '&comentario_texto=' + encodeURIComponent(comentarioTexto));
    });


</script>


</body>
</html>