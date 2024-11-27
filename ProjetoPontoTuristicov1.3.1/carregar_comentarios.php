<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ponto_id'], $_POST['offset'])) {
    $pontoId = intval($_POST['ponto_id']);
    $offset = intval($_POST['offset']);
    
    $query = "SELECT * FROM comentarios WHERE ponto_id = ? ORDER BY data_comentario DESC LIMIT 3 OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $pontoId, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($comentario = $result->fetch_assoc()):
?>
        <div class="alert alert-secondary">
            <strong><?= htmlspecialchars($comentario['usuario']) ?>:</strong> 
            <?= htmlspecialchars($comentario['comentario']) ?>
        </div>
<?php
    endwhile;

    $stmt->close();
}
?>
v