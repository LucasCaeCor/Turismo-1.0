function carregarComentarios(pontoId) {
    $.ajax({
        url: 'get_comentarios.php',
        type: 'GET',
        data: { ponto_id: pontoId },
        success: function(response) {
            const comentariosList = $('#comentariosList');
            comentariosList.empty(); // Limpa a lista de comentários existente

            response.forEach((comentario) => {
                // Substitui os placeholders pelo conteúdo do comentário
                const template = $('#comentario-template').html()
                    .replace(/%USUARIO%/g, comentario.usuario)
                    .replace(/%COMENTARIO%/g, comentario.comentario)
                    .replace(/%DATA%/g, comentario.data)
                    .replace(/%ID%/g, comentario.id)
                    .replace(/%OPCOES%/g, comentario.opcoes);

                comentariosList.append(template); // Adiciona o comentário ao DOM
            });
        },
        error: function() {
            alert('Erro ao carregar comentários.'); // Exibe mensagem de erro em caso de falha
        }
    });
}

// Evento para carregar comentários ao clicar no card
$(document).on('click', '.card-clickable', function () {
    const pontoId = $(this).data('ponto-id');
    const pontoNome = $(this).data('ponto-nome');
    const pontoDescricao = $(this).data('ponto-descricao');
    const pontoImagem = $(this).data('ponto-imagem');

    // Define os detalhes do ponto turístico no modal
    $('#modalNome').text(pontoNome);
    $('#modalDescricao').text(pontoDescricao);
    $('#modalImagem').attr('src', pontoImagem);
    $('#modalPontoId').val(pontoId);

    // Carrega os comentários para o ponto selecionado
    carregarComentarios(pontoId);

    // Exibe o modal
    $('#modalPontoTuristico').modal('show');
});

// Evento para excluir comentário
$(document).on('click', '.btn-excluir-comentario', function () {
    const comentarioId = $(this).data('comentario-id');
    const pontoId = $('#modalPontoId').val(); // Recupera o ID do ponto associado

    if (confirm('Tem certeza que deseja excluir este comentário?')) {
        $.ajax({
            url: 'excluir_comentario.php',
            type: 'POST',
            data: { comentario_id: comentarioId },
            success: function(response) {
                if (response.success) {
                    alert('Comentário excluído com sucesso.');
                    carregarComentarios(pontoId); // Recarrega os comentários
                } else {
                    alert('Erro ao excluir comentário.');
                }
            },
            error: function() {
                alert('Erro ao processar a solicitação.');
            }
        });
    }
});

$(document).on('click', '.dropdown-item', function (e) {
    e.preventDefault();
    const url = $(this).attr('href');

    $.ajax({
        url: url,
        type: 'GET',
        success: function () {
            const pontoId = $('#modalPontoId').val();
            // Recarregar comentários após exclusão
            $.ajax({
                url: 'get_comentarios.php',
                type: 'GET',
                data: { ponto_id: pontoId },
                success: function (response) {
                    $('#comentariosList').html(response);
                }
            });
        }
    });
});

$(document).on('click', '.excluir-ponto', function (e) {
    e.preventDefault(); // Evita o comportamento padrão do link
    
    const pontoId = $(this).data('id');
    
    if (confirm('Tem certeza de que deseja excluir este ponto turístico?')) {
        $.ajax({
            url: '<?= $_SERVER["PHP_SELF"] ?>',
            method: 'GET',
            data: { excluir_ponto_id: pontoId },
            success: function () {
                alert('Ponto turístico excluído com sucesso!');
                location.reload(); // Recarrega a página para atualizar a lista
            },
            error: function () {
                alert('Ocorreu um erro ao tentar excluir o ponto turístico.');
            }
        });
    }
});

$(document).on('click', '.excluir-comentario', function() {
    const comentarioId = $(this).data('comentario-id');
    
    $.ajax({
        url: 'index.php',
        type: 'GET',
        data: { excluir_comentario_id: comentarioId },
        success: function(response) {
            const res = JSON.parse(response);
            if (res.status === 'success') {
                // Remove o comentário da lista de forma dinâmica
                $(`#comentario-${comentarioId}`).remove();
            } else {
                alert('Erro ao excluir o comentário.');
            }
        }
    });
});

$(document).on('submit', 'form', function(event) {
    event.preventDefault(); // Impede o envio tradicional do formulário
    const form = $(this);
    const comentario = form.find('textarea[name="comentario"]').val();
    const pontoId = form.find('input[name="ponto_id"]').val();

    $.ajax({
        url: "", // URL do script onde o comentário será enviado
        type: "POST",
        data: {
            comentario: comentario,
            ponto_id: pontoId
        },
        success: function(response) {
            // Após a inserção, atualiza a lista de comentários na mesma página
            $.ajax({
                url: 'get_comentarios.php',
                type: 'GET',
                data: { ponto_id: pontoId },
                success: function(comentarios) {
                    $('#comentariosList').html(comentarios); // Atualiza a lista de comentários
                }
            });
        }
    });
});

