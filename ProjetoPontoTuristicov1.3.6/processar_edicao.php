<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Configuração do banco de dados
$host = 'localhost';
$dbname = 'telemaco_turismo';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar ao banco de dados: ' . $e->getMessage()]);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pontoId = $_POST['pontoId'] ?? null;
    $nome = $_POST['nomePonto'] ?? null;
    $descricao = $_POST['descricaoPonto'] ?? null;

    if (!$pontoId || !$nome || !$descricao) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos fornecidos.']);
        exit;
    }

    // Processar o upload da imagem
    $destinoImagem = null;
    if (isset($_FILES['imagemArquivo']) && $_FILES['imagemArquivo']['error'] === UPLOAD_ERR_OK) {
        // Verificar se o arquivo é uma imagem
        $tipoImagem = mime_content_type($_FILES['imagemArquivo']['tmp_name']);
        if (strpos($tipoImagem, 'image') === false) {
            echo json_encode(['success' => false, 'message' => 'O arquivo não é uma imagem válida.']);
            exit;
        }

        $imagemTmp = $_FILES['imagemArquivo']['tmp_name'];
        $imagemNome = uniqid() . '_' . $_FILES['imagemArquivo']['name'];
        $destinoImagem = 'uploads/' . $imagemNome;

        // Garantir que o diretório de upload exista
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        // Mover o arquivo para o diretório de uploads
        if (!move_uploaded_file($imagemTmp, $destinoImagem)) {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar a imagem.']);
            exit;
        }
    }

    try {
        // Atualizar o banco de dados
        $sql = "UPDATE pontos_turisticos SET nome = :nome, descricao = :descricao";
        if ($destinoImagem) {
            $sql .= ", imagem_url = :imagem_url";
        }
        $sql .= " WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':id', $pontoId);

        if ($destinoImagem) {
            $stmt->bindParam(':imagem_url', $destinoImagem);
        }

        $stmt->execute();

        // Resposta de sucesso
        echo json_encode(['success' => true, 'message' => 'Ponto atualizado com sucesso!']);
        exit;

    } catch (PDOException $e) {
        // Detalhamento do erro
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o ponto turístico: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}


?>
