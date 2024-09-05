<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat</title>
    <link rel="stylesheet" href="estilo.css">

    <style>
        @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
#colorPickerCard {
    display: none;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 200px;
    padding: 15px;
    background-color: white;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
    border-radius: 10px;
}
.color-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    grid-gap: 5px;
}
.color-square {
    width: 30px;
    height: 30px;
    cursor: pointer;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.palavra{
    display: none;
    margin-top: 2px;
    margin-bottom: 1px;
}
.dica{
    display: none;
    margin-bottom: 0;
    margin-top: 0;
}
.container_info{
    display: flex;
    align-items: center;
    justify-content: center;
   flex-direction: row;
   gap: 100px;
}

body {
    font-family: Arial, sans-serif;
}
.leaderboard {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            width: 90%;
            position: relative;
            /*display: grid;*/
        }

        .leaderboard-item {
            display: flex;
            justify-content: space-between;
            padding: 5px;
            border-bottom: 1px solid #eee;
            position: relative;
            transition: transform 0.5s;
           
        }

        .leaderboard-item.changed {
            background-color: #e0f7fa;
            transform: translateX(10px);
        }

        .username {
            font-weight: bold;
        }

        .points {
            color: #333;
        }

        .points-change {
            position: absolute;
            right: 0;
            top: 0;
            color: #4caf50;
            font-size: 14px;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.5s, transform 0.5s;
        }

        .points-change.show {
            opacity: 1;
            transform: translateY(0);
        }
        .progress-container {
            width: 800px;
            height: 10px;
            background-color: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 20px;
        }

        .progress-bar {
            height: 100%;
            width: 100%; /* Start full */
            background-color: #76c7c0;
            transition: width 1s linear;
        }

        .timer {
            margin-top: 10px;
            font-family: Arial, sans-serif;
            font-size: 16px;
            align-self: center;
        }

        .dica{
          color: white;
         }

        .dica-background{
            display: none;
            padding: 20px;
            border-radius: 20px;
            border: 1px solid black;

            flex-direction: column;

            background-color: #132d85;
        }
        .dica-title{
            display: none;
            align-self: center;

            color: yellow;
            margin-top: 0;
            margin-bottom: 0;
            font-size: x-small
        }
        .dica-letras{
            display: none;
            margin-top: 0;
        }

        .dica_info_main{
            display: none;
            flex-direction: column;
            gap: 5px;
            
        }
        .dica-letras{
            color: white;
        }
    </style>
</head>
<body>
<div id="colorPickerCard">
        <div class="color-grid">
            <!-- Colors -->
            <div class="color-square" style="background-color: #FF6633;"></div>
            <div class="color-square" style="background-color: #FFB399;"></div>
            <div class="color-square" style="background-color: #FF33FF;"></div>
            <div class="color-square" style="background-color: #FFFF99;"></div>
            <div class="color-square" style="background-color: #00B3E6;"></div>
            <div class="color-square" style="background-color: #E6B333;"></div>
            <div class="color-square" style="background-color: #3366E6;"></div>
            <div class="color-square" style="background-color: #999966;"></div>
            <div class="color-square" style="background-color: #99FF99;"></div>
            <div class="color-square" style="background-color: #B34D4D;"></div>
            <div class="color-square" style="background-color: #80B300;"></div>
            <div class="color-square" style="background-color: #809900;"></div>
            <div class="color-square" style="background-color: #E6B3B3;"></div>
            <div class="color-square" style="background-color: #6680B3;"></div>
            <div class="color-square" style="background-color: #66991A;"></div>
            <div class="color-square" style="background-color: #FF99E6;"></div>
            <div class="color-square" style="background-color: #CCFF1A;"></div>
            <div class="color-square" style="background-color: #FF1A66;"></div>
            <div class="color-square" style="background-color: #E6331A;"></div>
            <div class="color-square" style="background-color: #33FFCC;"></div>
            <div class="color-square" style="background-color: #66994D;"></div>
            <div class="color-square" style="background-color: #B366CC;"></div>
            <div class="color-square" style="background-color: #4D8000;"></div>
            <div class="color-square" style="background-color: #B33300;"></div>
            <div class="color-square" style="background-color: #CC80CC;"></div>
        </div>
    </div>

    <div class='container_info'>
        <h1 class="palavra">palavra</h1>
        <div class="dica-background">
            <h1 class="dica-title"></h1>

            <div class="dica_info_main">
                <p class="dica-letras"></p>
                <h1 class="dica"></h1>
            </div>
        </div>
        <button class="dica_botao hidden">dica</button>
    </div>
    
    
    <div class="container" style="gap: 40px;">
        <div class="chat-box">
            <div class="messages"></div>
            <form action="" class="join-form">
                <input type="text" name="sender" id="sender" placeholder="Digite seu nome">
                <button class="entrar_submit" type="submit">Entrar no Chat</button>
            </form>
            <form action="" method="post" class="msg-form hidden">
                <input type="text" name="msg" id="msg" placeholder="Digite aqui">
                <button class='msg_submit' type="submit">Enviar mensagem</button>
            </form>
            <form action="" class="close-form hidden"> 
                <button class='sair_submit' type="submit">Sair do Chat</button>
            </form>

            <div class='leaderboard hidden'>
                <div class="leaderboard-title ">
                    <i class="fas fa-crown"></i>
                    Placar
                </div>
            </div>
            
        </div>
        
        <div class="canvas_container" style="display:flex; flex-direction: column">
           <canvas id="canvas" width="800" height="600" style="border:1px solid black" class="hidden canvas"></canvas>
           <div class="progress-container hidden">
                  <div class="progress-bar" id="progressBar"></div>
           </div>
        </div>
    
        <div id="toolbar" class="hidden">
            <img id="pencil-btn" src="https://cdn-icons-png.flaticon.com/512/103/103414.png" alt="" >
            <img id="color-btn" src="https://i.pinimg.com/originals/88/30/ec/8830ec3b593efdf38ae08195b37afa5c.png" alt="" >
            <img id="clear-btn" src="https://cdn-icons-png.flaticon.com/512/419/419609.png" alt="" >
            <img id="eraser-btn" src="https://cdn-icons-png.flaticon.com/512/1778/1778507.png" alt="">
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>