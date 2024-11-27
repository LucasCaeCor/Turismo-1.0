<?php
session_start();
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];

    // Verifica o usuário no banco de dados
    $query = "SELECT * FROM usuarios WHERE usuario = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $row = $resultado->fetch_assoc();
        if (password_verify($senha, $row['senha'])) {
            $_SESSION['usuario'] = $usuario; // Armazena o usuário na sessão
            $_SESSION['is_admin'] = $row['is_admin']; // Armazena se o usuário é admin
            header('Location: index.php'); // Redireciona para a página principal
            exit();
        } else {
            $erro = "Senha incorreta.";
        }
    } else {
        $erro = "Usuário não encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet" />
    <style>
        body {
            height: 100vh;
            background: linear-gradient(to bottom right, #1e1e2f, #2a2a42);
            display: flex;
            justify-content: center;
            align-items: center;
            color: #f8f9fa;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            background-color: rgba(0, 0, 0, 0.8);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
        }
        .login-container h2 {
            color: #ffc107;
            text-align: center;
        }
        .login-container .form-control {
            background-color: transparent;
            color: #f8f9fa;
            border: 1px solid #ced4da;
        }
        .login-container .form-control:focus {
            border-color: #ffc107;
            box-shadow: 0 0 5px rgba(255, 193, 7, 0.8);
        }
        .login-container .btn-primary {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }
        .login-container .btn-primary:hover {
            background-color: #e0a800;
            border-color: #d39e00;
        }
        .error {
            color: #dc3545;
            text-align: center;
            font-size: 0.9rem;
        }
        .login-container a {
            color: #ffc107;
        }
        .login-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <h2>Login</h2>

        <form method="POST">
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuário:</label>
                <input type="text" name="usuario" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">Senha:</label>
                <input type="password" name="senha" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
            
        </form>
        <?php if (isset($erro)): ?>
            <p class="error mt-3"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>
        <p class="text-center mt-3">Ainda não tem uma conta? <a href="cadastro.php">Cadastre-se aqui</a>.</p>
        <a class="navbar-brand" href="index.php"><h2>voltar</h2></a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
