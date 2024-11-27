<?php
session_start();
include 'db_connection.php';

// Verifica se o usuário está logado e se é admin
if (!isset($_SESSION['usuario']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php'); // Redireciona para a página de login se não estiver logado ou não for admin
    exit();
}

// Exibe mensagem de feedback se estiver presente na URL
if (isset($_GET['msg'])) {
    echo "<div class='alert alert-success'>" . htmlspecialchars($_GET['msg']) . "</div>";
}

// Aqui você pode buscar e exibir suas solicitações
$query = "SELECT * FROM solicitacoes WHERE status != 'rejeitada'"; // Modifique a consulta para excluir as rejeitadas
$result = $conn->query($query);

// Lógica para aceitar solicitação
if (isset($_GET['action']) && $_GET['action'] == 'aceitar' && isset($_GET['id'])) {
    $idSolicitacao = $_GET['id'];
    
    // Buscar os detalhes da solicitação
    $querySolicitacao = "SELECT * FROM solicitacoes WHERE id = ?";
    $stmt = $conn->prepare($querySolicitacao);
    $stmt->bind_param("s", $idSolicitacao);
    $stmt->execute();
    $solicitacao = $stmt->get_result()->fetch_assoc();

    if ($solicitacao) {
        // Insira os dados na tabela de pontos turísticos
        $queryInsert = "INSERT INTO pontos_turisticos (nome, descricao, localizacao_lat, localizacao_lng, imagem_url) VALUES (?, ?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($queryInsert);
        $stmtInsert->bind_param("ssdds", $solicitacao['nome'], $solicitacao['descricao'], $solicitacao['latitude'], $solicitacao['longitude'], $solicitacao['imagem_url']);
        
        if ($stmtInsert->execute()) {
            // Após aceitar a solicitação, exclua-a da tabela de solicitações
            $queryDelete = "DELETE FROM solicitacoes WHERE id = ?";
            $stmtDelete = $conn->prepare($queryDelete);
            $stmtDelete->bind_param("s", $idSolicitacao);
            $stmtDelete->execute();
            $stmtDelete->close();
            
            // Redirecionar com mensagem de sucesso
            header("Location: gerenciar_solicitacoes.php?msg=Solicitação aceita e ponto turístico cadastrado com sucesso!");
            exit();
        } else {
            echo "Erro ao cadastrar ponto turístico: " . $stmtInsert->error;
        }
        $stmtInsert->close();
    } else {
        echo "Solicitação não encontrada.";
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Solicitações</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Gerenciar Solicitações</h2>
        <div id="menu" class="mb-3">
            <span>Bem-vindo, <?= htmlspecialchars($_SESSION['usuario']) ?>!</span>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>

        <button onclick="window.history.back()" class="btn btn-secondary mb-3">Voltar</button>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h4 class="card-title"><?= htmlspecialchars($row['nome']) ?></h4>
                        <p class="card-text"><?= htmlspecialchars($row['descricao']) ?></p>
                        <a href="?action=aceitar&id=<?= $row['id'] ?>" class="btn btn-success" onclick="return confirm('Tem certeza que deseja aceitar esta solicitação?');">Aceitar</a>
                        <a href="rejeitar_solicitacao.php?id=<?= $row['id'] ?>" class="btn btn-danger">Rejeitar</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-warning">Nenhuma solicitação encontrada.</div>
        <?php endif; ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
