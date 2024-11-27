<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está logado e é administrador
if (!isset($_SESSION['usuario']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Certificando-se de que o ID é um número inteiro

    // Valida se o ID está dentro de um intervalo aceitável
    if ($id > 0) {
        // Prepara a consulta para excluir o ponto turístico
        $query = "DELETE FROM pontos_turisticos WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            // Redireciona de volta para a página principal com uma mensagem de sucesso
            header('Location: index.php?msg=Ponto turístico excluído com sucesso!');
        } else {
            // Redireciona de volta com uma mensagem de erro
            header('Location: index.php?msg=Erro ao excluir o ponto turístico.');
        }

        $stmt->close();
    } else {
        header('Location: index.php?msg=ID inválido fornecido.');
    }
} else {
    header('Location: index.php?msg=ID do ponto turístico não fornecido.');
}

$conn->close();
?>
