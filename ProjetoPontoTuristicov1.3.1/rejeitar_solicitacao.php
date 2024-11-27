<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está logado e se é admin
if (!isset($_SESSION['usuario']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php'); // Redireciona para a página de login se não estiver logado ou não for admin
    exit();
}

// Verifica se o ID da solicitação foi passado
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Exclui a solicitação do banco de dados
    $query = "DELETE FROM solicitacoes WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);

    // Tente executar a consulta
    if ($stmt->execute()) {
        // Redireciona de volta para a página de gerenciamento de solicitações
        header('Location: gerenciar_solicitacoes.php?msg=Solicitação rejeitada e removida com sucesso!');
        exit();
    } else {
        // Exibe mensagem de erro
        echo "Erro ao rejeitar a solicitação: " . $conn->error; // Adiciona uma mensagem de erro
    }
} else {
    echo "ID da solicitação não foi fornecido."; // Mensagem se o ID não estiver presente
}
?>
