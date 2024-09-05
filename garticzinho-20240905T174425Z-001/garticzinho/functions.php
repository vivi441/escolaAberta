<?php

$substantivos = [
    'abacate', 'abacaxi', 'abelha', 'abóbora', 'acordeão', 'adaga', 'agulha', 'alface', 'amendoim',
    'amor', 'anjo', 'arara', 'arroz', 'avião', 'bacalhau', 'bala', 'bambu', 'banana', 'barco', 'bola',
    'bordado', 'botão', 'cachorro', 'cadeira', 'café', 'cama', 'caneta', 'carro', 'caixa', 'cabelo', 
    'cachoeira', 'calça', 'cesta', 'chave', 'coração', 'criança', 'cubo', 'dado', 'desenho', 'escada',
    'espada', 'estrela', 'faca', 'festa', 'fogo', 'foto', 'futebol', 'galo', 'galinha', 'girafa', 
    'guitarra', 'hambúrguer', 'iguana', 'imã', 'jarra', 'joia', 'lâmpada', 'leão', 'livro', 'maçã', 
    'mesa', 'moeda', 'morcego', 'navio', 'nuvem', 'óculos', 'pato', 'peixe', 'pneu', 'prato', 'presente', 
    'quadrado', 'quadro', 'rádio', 'rato', 'relógio', 'sanduíche', 'sapato', 'sol', 'tigre', 'toalha', 
    'urso', 'vaso', 'vela', 'violino', 'volante', 'zebra', 'açúcar', 'alvo', 'andorinha', 'aniversário',
    'arte', 'banco', 'bico', 'bioma', 'bloco', 'bolo', 'cabana', 'caçamba', 'canal', 'capítulo', 'carne',
    'chapéu', 'cidade', 'colar', 'computador', 'cozinha', 'cúpula', 'célula', 'detetive', 'elefante', 
    'escola', 'escova', 'faixa', 'ferramenta', 'filme', 'foguete', 'força', 'fumaça', 'gelo', 'herói',
    'imagem', 'ilha', 'janela', 'joelho', 'jornal', 'lago', 'lona', 'mangueira', 'mapa', 'médico', 
    'música', 'nicho', 'papel', 'pente', 'ponto', 'prédio', 'raquete', 'rede', 'relâmpago', 'represa', 
    'sal', 'sombra', 'sobremesa', 'tatuagem', 'túnel', 'turista', 'vaca', 'vagalume', 'vassoura', 
    'violão', 'volta', 'vulcão', 'zangão', 'zumbi', 'água', 'adega', 'adereço', 'aeroporto', 'alarme', 
    'alcachofra', 'almeirão', 'alpinista', 'âncora', 'animal', 'anta', 'aparelho', 'apito', 'arco', 
    'armazém', 'artista', 'atleta', 'aula', 'automóvel', 'barraca', 'baú', 'bico', 'biscoito', 
    'bolacha', 'bota', 'brisa', 'broto', 'buquê', 'cadeado', 'cafeteira', 'caneca', 'canhão', 'capa', 
    'carneiro', 'carroça', 'cartaz', 'chiclete', 'cinto', 'citação', 'colina', 'couro', 'decoração', 
    'degradação', 'detergente', 'dicionário', 'eleição', 'enigma', 'equipamento', 'esmalte', 'espuma', 
    'estação', 'farol', 'ferradura', 'fileira', 'galeto', 'garagem', 'geladeira', 'grama', 'grão',
    'jarro', 'jovem', 'maca', 'manga', 'mochila', 'nascente', 'navalha', 'oculista', 'oficina', 
    'operação', 'pá', 'papagaio', 'penteadeira', 'placa', 'rampa', 'recreio', 'roda', 'rua', 
    'sino', 'tábua', 'tenda', 'travesseiro', 'trompete', 'vidro', 'vila'
];



function unmask($text) {
    $length = @ord($text[1]) & 127;
    if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8); 
	}
    elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14); 
	}
    else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6); 
	}
    $text = "";
    for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i % 4];    
	}
    return $text;
}

function pack_data($text) {
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);

    if($length <= 125) {
		$header = pack('CC', $b1, $length);
	}
        
    elseif($length > 125 && $length < 65536) {
		$header = pack('CCn', $b1, 126, $length);
	}
        
    elseif($length >= 65536) {
		$header = pack('CCNN', $b1, 127, $length);
	}
        
    return $header.$text;
}

function handshake($request_header,$sock, $host_name, $port) {
	$headers = array();
	$lines = preg_split("/\r\n/", $request_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)){
			$headers[$matches[1]] = $matches[2];
		}
	}

	$sec_key = $headers['Sec-WebSocket-Key'];
	$sec_accept = base64_encode(pack('H*', sha1($sec_key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	$response_header  = "HTTP/1.1 101 Switching Protocols\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"Sec-WebSocket-Accept:$sec_accept\r\n\r\n";
	socket_write($sock,$response_header,strlen($response_header));
}

function get_random_word() {

	global $substantivos; // Declara a variável global
	
	$indiceAleatorio = array_rand($substantivos);

	// Obtendo o valor correspondente ao índice aleatório
	$substantivoAleatorio = $substantivos[$indiceAleatorio];

    return $substantivoAleatorio;// Retorna apenas a palavra
}
