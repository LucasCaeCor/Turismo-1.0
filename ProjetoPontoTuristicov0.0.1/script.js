const pontosTuristicos = [
    {
        nome: "Parque Ecológico Samuel Klabin",
        descricao: "Um parque que oferece contato com a natureza, trilhas e áreas para piqueniques.",
        localizacao: { lat: -24.286042, lng: -50.589052 },
        categoria: "natureza",
        imagem: "https://dynamic-media-cdn.tripadvisor.com/media/photo-o/13/0a/05/e2/entrada-parque-ecologico.jpg?w=1200&h=-1&s=1"
    },
    {
        nome: "Museu Histórico de Telêmaco Borba",
        descricao: "Museu que preserva a história da cidade, com exposições de artefatos antigos e documentos.",
        localizacao: { lat: -24.32264336150586, lng: -50.61995122687681},
        categoria: "historia",
        imagem: "path/to/museu.jpg"
    },
    {
        nome: "Praça Dr. Horácio Klabin",
        descricao: "Praça central com áreas para eventos, monumentos e locais de descanso.",
        localizacao: { lat: -24.330119, lng: -50.622094 },
        categoria: "natureza",
        imagem: "path/to/praca.jpg"
    },
    {
        nome: "Catedral de São José Operário",
        descricao: "Uma das igrejas mais importantes da cidade, com uma arquitetura notável.",
        localizacao: { lat: -24.334095, lng: -50.629659 },
        categoria: "historia",
        imagem: "path/to/catedral.jpg"
    },
    {
        nome: "Capela Nossa Senhora de Fátima",
        descricao: "Uma capela histórica que fica em uma área verde, popular para momentos de reflexão.",
        localizacao: { lat: -24.329861766262106, lng: -50.62044088870253 },
        categoria: "historia",
        imagem: "path/to/capela.jpg"
    },
    {
        nome: "Bonde Aéreo de Telêmaco Borba",
        descricao: "Um passeio panorâmico que oferece vistas aéreas da cidade e seus arredores.",
        localizacao: { lat: -24.318750001292216, lng: -50.61724068368572},
        categoria: "natureza",
        imagem: "https://dynamic-media-cdn.tripadvisor.com/media/photo-o/12/17/90/3a/vista-maravilhosa.jpg?w=1200&h=-1&s=1"
    }
];

function initMap() {
    const mapOptions = {
        center: { lat: -24.3245, lng: -50.6123 },
        zoom: 13
    };

    const map = new google.maps.Map(document.getElementById("map"), mapOptions);

    const markers = pontosTuristicos.map((ponto, index) => {
        const marker = new google.maps.Marker({
            position: ponto.localizacao,
            map: map,
            title: ponto.nome
        });

        const infoWindow = new google.maps.InfoWindow({
            content: `<h3>${ponto.nome}</h3><p>${ponto.descricao}</p><img src="${ponto.imagem}" class="info-window-img" style="width:25%;">`
        });

        marker.addListener('click', () => {
            infoWindow.open(map, marker);
        });

        document.querySelectorAll('#lista-pontos .nav-item')[index].addEventListener('click', () => {
            map.setCenter(ponto.localizacao);
            map.setZoom(15);
            marker.setAnimation(google.maps.Animation.BOUNCE);
            setTimeout(() => {
                marker.setAnimation(null);
            }, 1500);
        });

        return marker;
    });

    // Função de filtro e pesquisa
    function filtrarPontos() {
        const searchTerm = document.getElementById('pesquisa').value.toLowerCase();
        const selectedCategory = document.getElementById('filtro').value;

        pontosTuristicos.forEach((ponto, index) => {
            const matchesSearch = ponto.nome.toLowerCase().includes(searchTerm);
            const matchesCategory = selectedCategory === 'todos' || ponto.categoria === selectedCategory;

            if (matchesSearch && matchesCategory) {
                markers[index].setMap(map);
                document.querySelectorAll('#lista-pontos .nav-item')[index].style.display = 'block';
            } else {
                markers[index].setMap(null);
                document.querySelectorAll('#lista-pontos .nav-item')[index].style.display = 'none';
            }
        });
    }

    document.getElementById('pesquisa').addEventListener('input', filtrarPontos);
    document.getElementById('filtro').addEventListener('change', filtrarPontos);
}

document.getElementById('darkModeToggle').addEventListener('click', function() {
    document.body.classList.toggle('dark-mode');
});

window.onload = initMap;
