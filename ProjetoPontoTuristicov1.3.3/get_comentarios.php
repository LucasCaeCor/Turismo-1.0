<?php
// Assuma que as variáveis $_SESSION['usuario'] e $_SESSION['is_admin'] foram configuradas corretamente
session_start();
include 'db_connection.php';

$usuarioLogado = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
$isAdmin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false;

// Busca os comentários com base no ponto turístico
if (isset($_GET['ponto_id'])) {
    $pontoId = intval($_GET['ponto_id']);
    $comentariosQuery = "SELECT * FROM comentarios WHERE ponto_id = ?";
    $stmt = $conn->prepare($comentariosQuery);
    $stmt->bind_param("i", $pontoId);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($comentario = $resultado->fetch_assoc()) {
        echo '<div class="d-flex justify-content-between align-items-center mb-2">';
        echo '<p><strong>' . htmlspecialchars($comentario['usuario']) . ':</strong> ' . htmlspecialchars($comentario['comentario']) . '</p>';
        
        // Exibe o botão de exclusão apenas se o usuário tiver permissão
        if ($isAdmin || ($usuarioLogado && $comentario['usuario'] === $usuarioLogado)) {
            echo '<div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="dropdownMenu' . $comentario['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        ...
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenu' . $comentario['id'] . '">
                        <a class="dropdown-item text-danger" href="?excluir_comentario_id=' . $comentario['id'] . '">Excluir</a>
                    </div>
                  </div>';
        }
        echo '</div>';
    }
    $stmt->close();
}
?>

<?php
include 'db_connection.php';

$pontoId = isset($_GET['ponto_id']) ? intval($_GET['ponto_id']) : 0;
$comentarios = [];

$query = "SELECT * FROM comentarios WHERE ponto_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pontoId);
$stmt->execute();
$result = $stmt->get_result();

while ($comentario = $result->fetch_assoc()) {
    // Exibe os comentários
    $comentarios[] = $comentario;
}

// Exibe os comentários na página
foreach ($comentarios as $comentario):
    $isAuthorOrAdmin = $comentario['usuario'] === $_SESSION['usuario'] || $_SESSION['is_admin'];
    ?>
    <li id="comentario-<?= $comentario['id'] ?>" class="list-group-item">
        <p><strong><?= htmlspecialchars($comentario['usuario']) ?></strong> disse:</p>
        <p><?= htmlspecialchars($comentario['comentario']) ?></p>

        <?php if ($isAuthorOrAdmin): ?>
            <button class="btn btn-danger excluir-comentario" data-comentario-id="<?= $comentario['id'] ?>" data-ponto-id="<?= $comentario['ponto_id'] ?>">Excluir</button>
        <?php endif; ?>
    </li>
<?php endforeach; ?>

