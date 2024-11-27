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

    // Busca a solicitação para adicionar o ponto turístico
    $query = "SELECT * FROM solicitacoes WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $solicitacao = $resultado->fetch_assoc();

        // Certifique-se de que todos os dados necessários estejam disponíveis
        if (!empty($solicitacao['nome']) && !empty($solicitacao['descricao']) && 
            !empty($solicitacao['localizacao_lat']) && !empty($solicitacao['localizacao_lng']) && 
            !empty($solicitacao['imagem_url'])) {

            // Insere o ponto turístico na tabela de pontos turísticos
            $query_insert = "INSERT INTO pontos_turisticos (nome, descricao, localizacao_lat, localizacao_lng, imagem_url) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($query_insert);
            $stmt_insert->bind_param("sssss", $solicitacao['nome'], $solicitacao['descricao'], 
                                      $solicitacao['localizacao_lat'], $solicitacao['localizacao_lng'], 
                                      $solicitacao['imagem_url']);
            
            if ($stmt_insert->execute()) {
                // Atualiza o status da solicitação
                $query_update = "UPDATE solicitacoes SET status = 'aceita' WHERE id = ?";
                $stmt_update = $conn->prepare($query_update);
                $stmt_update->bind_param("i", $id);
                $stmt_update->execute();

                // Redireciona de volta para a página de gerenciamento de solicitações
                header('Location: gerenciar_solicitacoes.php?msg=Solicitação aceita e ponto turístico adicionado com sucesso!');
                exit();
            } else {
                echo "Erro ao adicionar ponto turístico: " . $conn->error; // Mensagem de erro
            }
        } else {
            echo "Dados insuficientes para adicionar o ponto turístico."; // Mensagem de dados insuficientes
        }
    } else {
        echo "Solicitação não encontrada."; // Mensagem se a solicitação não existir
    }
} else {
    echo "ID da solicitação não foi fornecido."; // Mensagem se o ID não estiver presente
}
?>
