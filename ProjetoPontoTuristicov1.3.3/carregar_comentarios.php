<?php
// Verifique se a requisição possui um ponto_id
if (isset($_GET['ponto_id'])) {
    $pontoId = (int)$_GET['ponto_id'];

    // Conecte-se ao banco de dados
    include('conexao.php');  // Substitua pelo seu arquivo de conexão

    // Consulta SQL para obter os comentários relacionados ao ponto turístico
    $queryComentarios = "SELECT * FROM comentarios WHERE ponto_id = ? ORDER BY data_comentario DESC LIMIT 3";
    $stmtComentarios = $conn->prepare($queryComentarios);
    $stmtComentarios->bind_param("i", $pontoId); // Vincula o ID do ponto
    $stmtComentarios->execute();
    $resultComentarios = $stmtComentarios->get_result();

    // Verifique se há comentários
    if ($resultComentarios->num_rows > 0) {
        // Itera sobre os comentários
        while ($comentario = $resultComentarios->fetch_assoc()) {
            ?>
            <li class="comentario-item mb-4 p-3" style="background-color: #f8f9fa; border-radius: 8px; position: relative;">
                <!-- Ícone de três pontinhos -->
                <?php if (isset($usuarioLogado) && ($usuarioLogado || $isAdmin)): ?>
                    <button class="btn btn-link btn-sm" style="position: absolute; top: 10px; right: 10px;" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="?excluir_comentario_id=<?= $comentario['id'] ?>">Excluir</a>
                    </div>
                <?php endif; ?>

                <!-- Informações do comentário -->
                <strong><?= htmlspecialchars($comentario['usuario']) ?>:</strong>
                <p class="mt-2"><?= nl2br(htmlspecialchars($comentario['comentario'])) ?></p>
            </li>
            <?php
        }
    } else {
        echo "<p>Sem comentários ainda.</p>";
    }

    $stmtComentarios->close();
}
?>
