<?php if ($isAdmin): ?>
    <button class="btn btn-danger excluir-ponto" data-ponto-id="<?= $ponto['id'] ?>">Excluir</button>
<?php endif; ?>

<script>
// Aguardar o carregamento completo da página
document.addEventListener('DOMContentLoaded', function() {
    // Aguardar o clique no botão de exclusão
    document.querySelectorAll('.excluir-ponto').forEach(function(button) {
        button.addEventListener('click', function() {
            // Obter o ID do ponto turístico a partir do atributo data
            var pontoId = button.getAttribute('data-ponto-id');
            
            // Confirmar a exclusão com o usuário
            if (confirm('Tem certeza que deseja excluir este ponto turístico?')) {
                // Se confirmado, redirecionar para o script PHP de exclusão
                window.location.href = 'excluir_ponto.php?id=' + pontoId;
            }
        });
    });
});
</script>
