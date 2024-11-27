<?php
session_start();
include 'db_connection.php'; // Conexão com o banco de dados

// Inicializa variáveis de erro e resposta
$erro = "";
$resposta = "";

// Verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $senha = $_POST['senha'];

    // Valida campos vazios
    if (empty($usuario) || empty($senha)) {
        $erro = "Todos os campos são obrigatórios.";
    } else {
        // Verifica se o nome de usuário já existe
        $queryCheck = "SELECT COUNT(*) FROM usuarios WHERE usuario = ?";
        $stmtCheck = $conn->prepare($queryCheck);
        $stmtCheck->bind_param("s", $usuario);
        $stmtCheck->execute();
        $stmtCheck->bind_result($count);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($count > 0) {
            $erro = "O nome de usuário já está em uso. Escolha outro.";
        } else {
            // Criptografa a senha
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            // Insere o usuário no banco de dados
            $query = "INSERT INTO usuarios (usuario, senha, data_cadastro) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $usuario, $senhaHash);

            if ($stmt->execute()) {
                $_SESSION['mensagem'] = "Cadastro realizado com sucesso!";
                header('Location: login.php');
                exit();
            } else {
                error_log("Erro ao cadastrar usuário: " . $stmt->error, 3, "logs/erros.log");
                $erro = "Erro ao cadastrar. Tente novamente mais tarde.";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .cadastro-container {
            width: 300px;
            padding: 20px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .error {
            color: red;
            font-size: 0.9em;
            margin-top: 10px;
        }
        .success {
            color: green;
            font-size: 0.9em;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="cadastro-container">
        <h2 class="text-center">Cadastrar</h2>
        <?php if ($erro): ?>
            <p class="error"><?= htmlspecialchars($erro) ?></p>
        <?php elseif (isset($_SESSION['mensagem'])): ?>
            <p class="success"><?= htmlspecialchars($_SESSION['mensagem']) ?></p>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="usuario">Usuário:</label>
                <input type="text" name="usuario" id="usuario" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" name="senha" id="senha" class="form-control" required>
                <small class="form-text text-muted">
                    A senha deve conter no mínimo 8 caracteres, com letras e números.
                </small>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Cadastrar</button>
        </form>
        <p class="text-center mt-3">Já tem uma conta? <a href="login.php">Faça login aqui</a>.</p>
    </div>

    <script>
        // Validação no Frontend
        document.querySelector('form').addEventListener('submit', function (e) {
            const senha = document.getElementById('senha').value;
            if (senha.length < 8 || !/\d/.test(senha) || !/[a-zA-Z]/.test(senha)) {
                e.preventDefault();
                alert('A senha deve ter no mínimo 8 caracteres e incluir letras e números.');
            }
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
