<?php

$address = '10.139.26.147';
$port = 8920;
$null = NULL;

include 'functions.php';

$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($sock, $address, $port);
socket_listen($sock);

$members = [];
$connections = [];
$connections[] = $sock;
$lines = [];
$current_line = [];

$current_word = ''; // Palavra atual
$current_dica = null; // Palavra atual
$current_dica_word = null;
$drawer_key = null; // Índice do jogador que está desenhando
$start_time = null; // Tempo de início da rodada
$round_duration = 120; // Duração de cada rodada em segundos
$current_round = 0; // Contador de rodadas
$max_rounds = 30; // Número máximo de rodadas antes era 10
$correct_guesses = []; // Armazena os jogadores que acertaram a palavra

echo "Listening for new connections on port $port: " . "\n";

while(true) {

    $reads = $writes = $exceptions = $connections;
    socket_select($reads, $writes, $exceptions, 1);

    if(in_array($sock, $reads)) {
        $new_connection = socket_accept($sock);
        $header = socket_read($new_connection, 1024);     
        handshake($header, $new_connection, $address, $port);
        $connections[] = $new_connection;
        $reply = [
            "type" => "join",
            "sender" => "Server",
            "text" => "Digite um nome para entrar... \n"
        ];
        $reply = pack_data(json_encode($reply));
        socket_write($new_connection, $reply, strlen($reply));
        $firstIndex = array_search($sock, $reads);
        unset($reads[$firstIndex]);
    }

    // Iniciar a primeira rodada quando houver jogadores suficientes
    if (count($members) > 2 && $current_round === 0) {
        start_round($members);
        $current_round++;
    }

    // Verificação se o tempo da rodada terminou
    if ($start_time !== null && (time() - $start_time) >= $round_duration) {
        $current_round++;
        if ($current_round < $max_rounds) {
            start_round($members);
        } else {
            end_game($members);
            break;
        }
    }

    foreach ($reads as $key => $value) {
        $data = socket_read($value, 1024);
        if(!empty($data)) {
            $message = unmask($data);
            $decoded_message = json_decode($message, true);

            if ($decoded_message) {
                if(isset($decoded_message['text'])){
                    if($decoded_message['type'] === 'join') {
                        $members[$key] = [
                            'name' => $decoded_message['sender'],
                            'connection' => $value,
                            'points' => 0
                        ];

                        $test = [
                            "type" => "refresh_lines",
                            "lines" => $lines,
                            "curline" => $current_line
                        ];
                        
                        $maskedMessage = pack_data($message);
                        foreach ($members as $mkey => $mvalue) {
                            socket_write($mvalue['connection'], $maskedMessage, strlen($maskedMessage));
                        }

                        send_leaderboard($members);

                        $maskedDrawerMessage = pack_data(json_encode($test));
                        socket_write($members[$key]['connection'], $maskedDrawerMessage, strlen($maskedDrawerMessage));

                        break;
                    }
                    if (isset($decoded_message['text']) && $decoded_message['type'] === 'normal') {
                        if ($decoded_message['sender'] !== 'Server' && $decoded_message['text'] === $current_word) {
                            $response_time = time() - $start_time;
                            $points = max(0, 1000 - $response_time * 20); // Ajuste a fórmula conforme desejado

                            $correctMessage = $decoded_message['sender'] . " acertou a palavra!";
                            $acertouMessage = [
                                "type" => "acertou",
                                "text" => $correctMessage,
                                "sender" => 'Server'
                            ];
                            
                            notify_all($members, json_encode($acertouMessage));
                            
                            // Marca o jogador como tendo acertado a palavra
                            $correct_guesses[$decoded_message['sender']] = true;
                            
                            if ($drawer_key !== null && isset($members[$drawer_key])) {
                                $members[$drawer_key]['points'] += 50; // Pontos extras para o drawer; ajuste conforme necessário
                            }

                            // Adiciona pontos ao jogador
                            if (isset($members[$key])) {
                                $members[$key]['points'] += $points;

                                send_leaderboard($members);
                            }
                            
                            // Verifica se algum jogador atingiu 2000 pontos
                            foreach ($members as $mkey => $mvalue) {
                                if ($mvalue['points'] >= 20000) {
                                    $endGameMessage = [
                                        "type" => "end_game",
                                        "text" => $mvalue['name'] . " venceu o jogo com " . $mvalue['points'] . " pontos!",
                                        "sender" => "Server"
                                    ];
                                    notify_all($members, json_encode($endGameMessage));
                                    socket_close($sock);
                                    exit;
                                }
                            }
                            
                            $total = count($correct_guesses) + 1;
                            // Verifica se todos os jogadores acertaram
                            if ($total === count($members)) {

                                if ($drawer_key !== null && isset($members[$drawer_key])) {
                                    $members[$drawer_key]['points'] += 100; // Pontos extras para o drawer; ajuste conforme necessário
                                }

                                $current_round++;
                                if ($current_round < $max_rounds) {
                                    start_round($members);
                                } else {
                                    end_game($members);
                                    break;
                                }
                            }

                            
                            break;
                        }
                    }
                    $maskedMessage = pack_data($message);
                    foreach ($members as $mkey => $mvalue) {
                        socket_write($mvalue['connection'], $maskedMessage, strlen($maskedMessage));
                    }
                }
                if($decoded_message['type'] === 'draw' || $decoded_message['type'] === 'mouseup' || $decoded_message['type'] === 'clear') {
                    $maskedMessage = pack_data($message);
                    foreach ($members as $mkey => $mvalue) {
                        socket_write($mvalue['connection'], $maskedMessage, strlen($maskedMessage));
                    }
                }
                if($decoded_message['type'] === 'draw') {
                    // Adiciona a nova linha desenhada às variáveis
                    $lines[] = [
                        'normalizedX' => $decoded_message['normalizedX'],
                        'normalizedY' => $decoded_message['normalizedY'],
                        'strokeColor' => $decoded_message['strokeColor'],
                        'stop' => false
                    ];
                }
                
                if ($decoded_message['type'] === 'mouseup') {
                    $current_line[] = $decoded_message['line'];

                    $lines[] = [
                        'normalizedX' => $decoded_message['normalizedX'],
                        'normalizedY' => $decoded_message['normalizedY'],
                        'strokeColor' => $decoded_message['strokeColor'],
                        'stop' => true
                    ];
                }
                
                if ($decoded_message['type'] === 'clear') {
                    $current_line = [];
                    $lines = [];
                }
                if ($decoded_message['type'] === 'dica') {
                    $tamanho_palavra = strlen($current_word);

                    if($current_dica == 0){
                        $current_dica_word = str_repeat('_', $tamanho_palavra);

                        echo $current_dica_word;

                        
                        $mensagemRetornoDica = [
                            "type" => "retorno_dica",
                            "dica" => $current_dica_word,
                            "dica_level" => $current_dica
                        ];
               
                        $mensagemRetornoDicaParsed = pack_data(json_encode($mensagemRetornoDica));
                        foreach ($members as $mkey => $mvalue) {
                            socket_write($mvalue['connection'], $mensagemRetornoDicaParsed, strlen($mensagemRetornoDicaParsed));
                        }
                        
                    }


                    if ($current_dica > 0) {
                        $indicesRevelados = [];
                        $letras_revelar = $current_dica == 1 ? 1 : ($current_dica == 2 ? 2 : 0);
            
                        // Revele as letras apropriadas
                        while (count($indicesRevelados) < $letras_revelar) {
                            $indice = rand(0, $tamanho_palavra - 1);
                            if (!in_array($indice, $indicesRevelados)) {
                                $indicesRevelados[] = $indice;
                            }
                        }
            
                        // Atualiza a dica com letras reveladas
                        $newDicaArray = str_split($newDica);
                        foreach ($indicesRevelados as $indice) {
                            $newDicaArray[$indice] = $palavra[$indice];
                        }
                        $current_dica_word = implode('', $newDicaArray);
            
                        $mensagemRetornoDica = [
                            "type" => "retorno_dica",
                            "dica" => $current_dica_word,
                            "dica_level" => $current_dica
                        ];
                        
                        $mensagemRetornoDicaParsed = pack_data(json_encode($mensagemRetornoDica));
                        foreach ($members as $mkey => $mvalue) {
                            socket_write($mvalue['connection'], $mensagemRetornoDicaParsed, strlen($mensagemRetornoDicaParsed));
                        }
                    }

                    $current_dica++;
                }             
            }
        }
        else if($data === '') {
            echo "disconnected " . $key . " \n";
            unset($connections[$key]);
            if(array_key_exists($key, $members)) {
                $message = [
                    "type" => "left",
                    "sender" => "Server",
                    "text" => $members[$key]['name'] . " left the chat \n"
                ];
                $maskedMessage = pack_data(json_encode($message));
                unset($members[$key]);
                send_leaderboard($members);
                foreach ($members as $mkey => $mvalue) {
                    socket_write($mvalue['connection'], $maskedMessage, strlen($maskedMessage));
                }

                $current_round++;
                if ($current_round < $max_rounds) {
                    start_round($members);
                } else {
                    end_game($members);
                    break;
                }
            }
            socket_close($value);
        }
    }
}

function start_round(&$members) {
    global $current_word, $drawer_key, $start_time, $correct_guesses, $current_dica, $current_dica_word;

    if (count($members) > 2) {
        $drawer_key = array_rand($members);
        $drawer = $members[$drawer_key]['name'];
        $current_word = get_random_word();
        $current_dica = 0;
        $current_dica_word = null;
        $start_time = time();
        $correct_guesses = []; // Limpa as adivinhações corretas

        // Envia a palavra para o jogador escolhido
        $drawerMessage = [
            "type" => "new_drawer_draw",
            "text" => "Você foi escolhido para desenhar: " . $current_word,
            "sender" => "Server",
            "palavra" => $current_word
        ];
        $maskedDrawerMessage = pack_data(json_encode($drawerMessage));
        socket_write($members[$drawer_key]['connection'], $maskedDrawerMessage, strlen($maskedDrawerMessage));

        // Notifica os outros jogadores sobre quem é o desenhista
        $gameMessage = [
            "type" => "new_drawer",
            "text" => $drawer . " foi escolhido para desenhar!",
            "sender" => "Server",
            "drawer" => $drawer
        ];
        notify_all($members, json_encode($gameMessage), $drawer_key);
    }
}

function end_game(&$members) {
    $endGameMessage = [
        "type" => "end_game",
        "text" => "O jogo terminou! Obrigado por jogar."
    ];
    notify_all($members, json_encode($endGameMessage));
}

function notify_all(&$members, $message, $exclude_key = null) {
    $maskedMessage = pack_data($message);
    foreach ($members as $mkey => $mvalue) {
        if ($exclude_key === null || $mkey !== $exclude_key) {
            socket_write($mvalue['connection'], $maskedMessage, strlen($maskedMessage));
        }
    }
}
function send_leaderboard(&$members) {
  // Ordena os membros pelo número de pontos, do maior para o menor
  uasort($members, function($a, $b) {
    return $b['points'] - $a['points'];
    });

    // Cria um array com a lista de jogadores e seus pontos
    $leaderboard = [];
    foreach ($members as $name => $member) {
        $leaderboard[] = [
            'name' => $member['name'],
            'points' => $member['points']
        ];
    }

    // Cria a mensagem que será enviada para todos os clientes
    $messageLeaderboard = [
        'type' => 'leaderboard',
        'leaderboard' => $leaderboard
    ];

    echo $messageLeaderboard;
    // Envia a mensagem para todos os clientes
    notify_all($members, json_encode($messageLeaderboard));
}

socket_close($sock);

?>
