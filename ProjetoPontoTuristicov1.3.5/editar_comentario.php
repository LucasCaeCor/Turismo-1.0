<?php
session_start();
include 'db_connection.php';

if (isset($_POST['comentario_id']) && isset($_POST['comentario_texto'])) {
    $comentarioId = $_POST['comentario_id'];
    $comentarioTexto = $_POST['comentario_texto'];

    // Sanitize the input to prevent XSS
    $comentarioTexto = htmlspecialchars($comentarioTexto);

    // Atualiza o comentário no banco de dados
    $query = "UPDATE comentarios SET comentario = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $comentarioTexto, $comentarioId);
    if ($stmt->execute()) {
        echo "Comentário atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar o comentário!";
    }
} else {
    echo "Dados não recebidos corretamente!";
}
?>
