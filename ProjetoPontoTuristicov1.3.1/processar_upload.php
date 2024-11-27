<?php
session_start();
include 'db_connection.php'; // Inclua a conexão com o banco de dados

// Verifique se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php"); // Redireciona para a página de login se não estiver logado
    exit();
}

// Verifique se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Validação do campo de imagem
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        // Defina o diretório para salvar as imagens
        $diretorioUploads = 'uploads/';
        
        // Verifique se o diretório existe, caso contrário crie-o
        if (!is_dir($diretorioUploads)) {
            mkdir($diretorioUploads, 0777, true);
        }

        // Obtenha informações do arquivo
        $nomeArquivo = $_FILES['imagem']['name'];
        $tipoArquivo = $_FILES['imagem']['type'];
        $tamanhoArquivo = $_FILES['imagem']['size'];
        $tempNomeArquivo = $_FILES['imagem']['tmp_name'];

        // Verifique a extensão da imagem
        $extensoesPermitidas = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($tipoArquivo, $extensoesPermitidas)) {
            die("Erro: Apenas arquivos JPG, PNG e GIF são permitidos.");
        }

        // Defina um nome único para a imagem (evitar sobrescrição de arquivos com o mesmo nome)
        $nomeNovoArquivo = uniqid() . '-' . basename($nomeArquivo);
        $caminhoDestino = $diretorioUploads . $nomeNovoArquivo;

        // Mova o arquivo para o diretório de uploads
        if (move_uploaded_file($tempNomeArquivo, $caminhoDestino)) {
            // O upload foi bem-sucedido

            // Obtendo os dados do ponto turístico para adicionar ao banco
            $nome = $_POST['nome']; // Nome do ponto turístico
            $descricao = $_POST['descricao']; // Descrição do ponto turístico
            $latitude = $_POST['latitude']; // Latitude do ponto turístico
            $longitude = $_POST['longitude']; // Longitude do ponto turístico

            // Prepare a consulta para inserir no banco de dados
            $query = "INSERT INTO pontos_turisticos (nome, descricao, localizacao_lat, localizacao_lng, imagem_url) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssdds", $nome, $descricao, $latitude, $longitude, $caminhoDestino);

            // Execute a consulta
            if ($stmt->execute()) {
                // Redireciona com mensagem de sucesso
                header("Location: index.php?msg=Ponto turístico cadastrado com sucesso!");
                exit();
            } else {
                echo "Erro ao cadastrar ponto turístico: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "Erro ao fazer upload do arquivo. Tente novamente.";
        }
    } else {
        echo "Erro: Nenhum arquivo enviado ou houve um erro no upload.";
    }
} else {
    echo "Erro: Método de requisição inválido.";
}
?>
