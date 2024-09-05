(function(){
    let user;
    let drawer = '';
    let lastX;
    let lastY;
    var lines = [];
    let currentLine = [];
    let lastLb;


    setInterval(() => {
        const container = document.querySelector('.messages');

        // Função para verificar se o container está rolado
        function isScrolled(container) {
            // Verifique o scroll vertical
            const isScrolledVertical = container.scrollTop > 0;
                       
            return isScrolledVertical 
        }

        container.scrollTop = container.scrollHeight;
    }, 500);



    function sendMessage(message) {
        socket.send(message);
    }

    function parseMessage(message) {
        var msg = {type: "", sender: "", text: ""};
        try {
            msg = JSON.parse(message);
        } catch(e) {
            return false;
        }
        return msg;
    }

    function appendMessage(message) {
        var parsedMsg;
        var msgContainer = document.querySelector(".messages");

        
        if (parsedMsg = parseMessage(message)) {

            if(parsedMsg.type == 'draw' || parsedMsg.type == 'undo' || parsedMsg.type === 'refresh_lines' || parsedMsg.type === 'leaderboard') {return;}

            
            console.log('ENVIANDO:');
            console.log(parsedMsg);

            var msgElem, senderElem, textElem;
            var sender, text;

            msgElem = document.createElement("div");
            msgElem.classList.add('msg');
            msgElem.classList.add('msg-' + parsedMsg.type);

            senderElem = document.createElement("span");
            senderElem.classList.add("msg-sender");

            if(parsedMsg.type !== 'normal'){
                msgElem.classList.add('msg-join');
            }

            textElem = document.createElement("span");
            textElem.classList.add("msg-text");

            sender = document.createTextNode(parsedMsg.sender + ': ');
            text = document.createTextNode(parsedMsg.text);

            senderElem.appendChild(sender);
            textElem.appendChild(text);

            msgElem.appendChild(senderElem);
            msgElem.appendChild(textElem);

            msgContainer.appendChild(msgElem);
        }
    }
    function validarNome(nome) {
        // Verifica se o nome contém espaços
        if (nome.includes(' ')) {
            return false;
        }
    
        // Verifica se o nome tem mais de 3 letras
        if (nome.length <= 2) {
            return false;
        }
    
        // Verifica se o nome é 'Server' ou 'Browser'
        const nomesProibidos = ['server', 'browser'];
        if (nomesProibidos.includes(nome.toLowerCase())) {
            return false;
        }
    
        // Se passou por todas as validações, retorna verdadeiro
        return true;
    }

    function setup() {
        var sender = '';
        var joinForm = document.querySelector('form.join-form');
        var msgForm = document.querySelector('form.msg-form');
        var closeForm = document.querySelector('form.close-form');
        var canvas = document.getElementById('canvas');
        var leaderboard = document.querySelector('.leaderboard');
        
        function joinFormSubmit(event) {
            event.preventDefault();
            sender = document.getElementById('sender').value;


            if(!validarNome(sender)){
                alert('Nome invalido')
                return;
            }

            var joinMsg = {
                type: "join",
                sender: sender,
                text: sender + ' entrou no chat!'
            };

           
            sendMessage(JSON.stringify(joinMsg));

            var sound = new Audio('./notificacao.mp3');
            sound.play();

            if (!joinForm.classList.contains("hidden")) {
                user = sender;
            }

            canvas.classList.remove('hidden');
            joinForm.classList.add('hidden');
            msgForm.classList.remove('hidden');
            closeForm.classList.remove('hidden');
            leaderboard.classList.remove('hidden');
            leaderboard.style.display = 'grid'
            document.getElementsByClassName('chat-box')[0].style['margin-top'] = '0px'
        }
    
        joinForm.addEventListener('submit', joinFormSubmit);
    
        function msgFormSubmit(event) {
            event.preventDefault();
            var msgField, msgText, msg;
            msgField = document.getElementById('msg');
            msgText = msgField.value;

            msg = {
                type: "normal",
                sender: sender,
                text: msgText
            };
            msg = JSON.stringify(msg);

            if(msgField.value.length < 1){
                return;
            }

            sendMessage(msg);
            msgField.value = '';
        }
    
        msgForm.addEventListener('submit', msgFormSubmit);

        function closeFormSubmit(event) {
            event.preventDefault();
            clearCanvas();

            clearMsg = {
                'type': 'clear',
                'drawer': user
            };

            sendMessage(JSON.stringify(clearMsg));
            socket.close();
            window.location.reload();
        }

        closeForm.addEventListener('submit', closeFormSubmit);

        var canvasSizeMsg = {
            type: "canvas_size",
            width: canvas.width,
            height: canvas.height
        };

        sendMessage(JSON.stringify(canvasSizeMsg));
    }

    let socket = new WebSocket("ws://10.139.26.147:8920");

    var canvas = document.getElementById('canvas');
    var ctx = canvas.getContext('2d');

    var lineWidth = 5;
    var strokeColor = '#000';
    var isDrawing = false;

    function draw(e) {
        if (!isDrawing || drawer !== user) return;
    
        var x = e.clientX - canvas.offsetLeft;
        var y = e.clientY - canvas.offsetTop;
        var normalizedX = x / canvas.width;
        var normalizedY = y / canvas.height;
    
        ctx.lineWidth = lineWidth;
        ctx.strokeStyle = strokeColor;
        ctx.lineJoin = ctx.lineCap = 'round';
    
        ctx.lineTo(x, y);
        ctx.stroke();
        
        lines.push({
            normalizedX: normalizedX,
            normalizedY: normalizedY,
            strokeColor: strokeColor
        });

        var drawMsg = {
            type: "draw",
            drawer: drawer,
            normalizedX: normalizedX,
            normalizedY: normalizedY,
            strokeColor: strokeColor
        };
        
       
        sendMessage(JSON.stringify(drawMsg));

        currentLine.push({x: x / canvas.width, y: y / canvas.height, strokeColor: strokeColor});
    }

    function updateLeaderboard(leaderboard) {
        // Ordena o leaderboard por pontos em ordem decrescente
        leaderboard.sort((a, b) => b.points - a.points);
      
        // Atualiza ou cria itens da leaderboard
        const container = document.querySelector('.leaderboard');
        const currentItems = container.getElementsByClassName('leaderboard-item');
        const currentItemsMap = Array.from(currentItems).reduce((map, item) => {
          map[item.getAttribute('data-name')] = item;
          return map;
        }, {});
      
        leaderboard.forEach((player, index) => {
          let item = currentItemsMap[player.name];
          if (!item) {
            // Cria um novo item se não existir
            item = document.createElement('div');
            item.className = 'leaderboard-item';
            item.setAttribute('data-name', player.name);
            const username = document.createElement('span');
            username.className = 'username';
            username.textContent = player.name;
            const points = document.createElement('span');
            points.className = 'points';
            points.textContent = `${player.points} pts`;
            const pointsChange = document.createElement('span');
            pointsChange.className = 'points-change';
            item.appendChild(username);
            item.appendChild(points);
            item.appendChild(pointsChange);
            container.appendChild(item);
          }
      
          const oldPoints = parseInt(item.querySelector('.points').textContent);
          if (oldPoints !== player.points) {
            // Anima a mudança de pontos
            const pointsChange = item.querySelector('.points-change');
            const pointsDiff = player.points - oldPoints;
            pointsChange.textContent = `+${pointsDiff}`;
            pointsChange.classList.add('show');
            setTimeout(() => {
              pointsChange.classList.remove('show');
            }, 1000);
            item.querySelector('.points').textContent = `${player.points} pts`;
          }
      
          // Adiciona a animação de mudança de posição
          item.classList.remove('changed');
          item.classList.add('changed');
          setTimeout(() => {
            item.classList.remove('changed');
          }, 500);
      
          // Atualiza a posição do item na leaderboard
          item.style.order = index;
        });
      
        // Remove itens antigos
        Array.from(currentItems).forEach(item => {
          if (!leaderboard.some(player => player.name === item.getAttribute('data-name'))) {
            container.removeChild(item);
          }
        });
      }
    

    function changeToEraser() {
        console.log('Borracha ativada');
        strokeColor = '#FFFFFF';
    }

    function changeColor(color) {
        console.log('Cor trocada');
        strokeColor = color;
    }

    function drawLine(prevNormalizedX, prevNormalizedY, currNormalizedX, currNormalizedY, color) {
        var prevX = prevNormalizedX * canvas.width;
        var prevY = prevNormalizedY * canvas.height;
        var currX = currNormalizedX * canvas.width;
        var currY = currNormalizedY * canvas.height;
    
        ctx.lineWidth = lineWidth;
        ctx.strokeStyle = color;
        ctx.lineJoin = ctx.lineCap = 'round';
    
        ctx.beginPath();
        ctx.moveTo(prevX, prevY);
        ctx.lineTo(currX, currY);
        ctx.stroke();
        ctx.closePath();
    }
    

    function rgbToHex(rgb) {
        let rgbValues = rgb.match(/\d+/g);
        let hex = rgbValues.map(value => {
            let hexPart = parseInt(value).toString(16);
            return hexPart.length === 1 ? '0' + hexPart : hexPart;
        }).join('');
        return `#${hex.toUpperCase()}`;
    }

    canvas.addEventListener('mousedown', function (e) {
        if (drawer !== user) return;

        isDrawing = true;
        currentLine = []; // Inicia uma nova linha
        ctx.beginPath();
        var x = e.clientX - canvas.offsetLeft;
        var y = e.clientY - canvas.offsetTop;
        ctx.moveTo(x, y);

        currentLine.push({
            normalizedX: x / canvas.width,
            normalizedY: y / canvas.height,
            strokeColor: strokeColor
        });
    });

    canvas.addEventListener('mousemove', draw);

    canvas.addEventListener('mouseup', function () {
        if (drawer !== user) return;

        isDrawing = false;

        mouseUpMsg = {
            "type": 'mouseup',
            "drawer": user
        };

        lines.push(currentLine); 

        sendMessage(JSON.stringify(mouseUpMsg));
    });
    
    setInterval(() => {
        if (user !== undefined) {
            let ping = {
                "msg": 'ping',
                "sender": user
            };
            sendMessage(JSON.stringify(ping));
        }
    }, 10000);

    function novoDrawer() {
        let totalTime = 120; // Total time in seconds
        let currentTime = totalTime;

        const progressBar = document.getElementById('progressBar');
        document.getElementsByClassName("progress-container")[0].classList.remove('hidden')

        let progressInterval;

        try{
            clearInterval(progressInterval)
        } catch (e){
            console.log(e)
        }
        

        progressBar.style.width = '100%'


        let updateProgressBar = () => {
            currentTime--;
            let progressPercentage = (currentTime / totalTime) * 100;
            progressBar.style.width = progressPercentage + '%';

          

            if (currentTime <= 0) {
                clearInterval(progressInterval);
            }
        };

         progressInterval = setInterval(updateProgressBar, 1000); // Update every second

        if (drawer === user) {
            document.getElementsByClassName('dica')[0].style.display = 'none'
            document.getElementsByClassName('dica')[0].textContent = ''

            document.getElementsByClassName('dica-background')[0].style.display = 'none'

            document.getElementsByClassName('dica-title')[0].style.display = 'none'
            document.getElementsByClassName('dica-title')[0].textContent = ''

            document.getElementsByClassName('dica-letras')[0].style.display = 'none'
            document.getElementsByClassName('dica-letras')[0].textContent = ''

            document.getElementsByClassName('dica_info_main')[0].style.display = 'none'

            document.getElementById('eraser-btn').classList.remove('hidden');
            document.getElementById('clear-btn').classList.remove('hidden');
            document.getElementById('toolbar').classList.remove('hidden');
            document.getElementById('msg').disabled = true;

            document.getElementById('toolbar').style.display = 'flex';
            document.getElementById('toolbar').style['flex-direction'] = 'column';
            document.getElementById('toolbar').style.gap = '20px';

            document.getElementsByClassName('palavra')[0].style.display = 'flex';
            
            document.getElementsByClassName('dica_botao')[0].classList.remove('hidden');
            document.getElementsByClassName('dica_botao')[0].style.display = 'flex';

            document.getElementsByClassName('dica_botao')[0].addEventListener('click', function() {
                sendmsg = {
                    'type': 'dica',
                    'drawer': user
                };
    
                sendMessage(JSON.stringify(sendmsg));
            });

            document.getElementById('eraser-btn').addEventListener('click', changeToEraser);
            document.getElementById('pencil-btn').addEventListener('click', function() {
                if (strokeColor === '#FFFFFF') {
                    changeColor('#000000');
                } else {
                    changeColor(strokeColor);
                }
            });

            document.getElementById('color-btn').addEventListener('click', function() {
                const card = document.getElementById('colorPickerCard');
                card.style.display = card.style.display === 'none' ? 'block' : 'none';
            });

            document.querySelectorAll('.color-square').forEach(square => {
                square.addEventListener('click', function() {
                    const selectedColor = this.style.backgroundColor;
                    const hexColor = rgbToHex(selectedColor);
                    console.log(`You selected: ${hexColor}`);
                    changeColor(hexColor);
                    document.getElementById('colorPickerCard').style.display = 'none';
                });
            });

            document.getElementById('clear-btn').addEventListener('click', function() {
                clearCanvasOnServer()
            });
        } else {
            document.getElementsByClassName('dica')[0].style.display = 'none'
            document.getElementsByClassName('dica')[0].textContent = ''

            document.getElementsByClassName('dica-background')[0].style.display = 'none'

            document.getElementsByClassName('dica-title')[0].style.display = 'none'
            document.getElementsByClassName('dica-title')[0].textContent = ''

            document.getElementsByClassName('dica-letras')[0].style.display = 'none'
            document.getElementsByClassName('dica-letras')[0].textContent = ''

            document.getElementsByClassName('dica_info_main')[0].style.display = 'none'
           

            document.getElementById('eraser-btn').removeEventListener('click', changeToEraser);
            document.getElementById('clear-btn').removeEventListener('click', clearCanvas);

            document.getElementById('eraser-btn').classList.add('hidden');
            document.getElementById('clear-btn').classList.add('hidden');
            document.getElementById('toolbar').classList.add('hidden');

            document.getElementsByClassName('palavra')[0].style.display = 'none';
            document.getElementsByClassName('palavra')[0].textContent = 'palavra'
            document.getElementById('toolbar').style.display = 'none';
            document.getElementById('msg').disabled = false;

            document.getElementsByClassName('dica_botao')[0].style.display = 'none'
            document.getElementsByClassName('dica_botao')[0].removeEventListener('click', clearCanvas);

        }
    }

 
    function clearCanvas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
    function clearCanvasOnServer() {

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        lines = []
        currentLine = [];


        var clearMsg = {
            type: 'clear',
            drawer: user
        };

        sendMessage(JSON.stringify(clearMsg));
    }

    if (!("Notification" in window)) {
        alert("Ative as notificações para ser avisado de novas mensagens nesse chat");
    } else if (Notification.permission !== 'denied') {
        Notification.requestPermission().then(function (permission) {
            if (permission !== "granted") {
                alert("Ative as notificações para ser avisado de novas mensagens nesse chat");
            }
        });
    }

    var socketOpen = (e) => {
        console.log("connected to the socket");
        var msg = {
            type: 'join',
            sender: 'Browser',
            text: 'conectado com sucesso'
        };
        appendMessage(JSON.stringify(msg));
        setup();
    };

    var socketMessage = (e) => {
        console.log(`Message from socket: ${e.data}`);
        
        var data = JSON.parse(e.data);
        
        if (data.type === "normal") {
            var sound = new Audio('./notificacao.mp3');
            sound.play();
            
            if (data.sender !== user) {
                //var notification = new Notification("Nova mensagem no chat");
            }
        }

        if (data.type === "draw") {
            // Desenha somente para os outros clientes, exceto o desenhista
            if (drawer !== user) {
                if (!lastX || !lastY) {
                    lastX = data.normalizedX;
                    lastY = data.normalizedY;
                }
        
                drawLine(lastX, lastY, data.normalizedX, data.normalizedY, data.strokeColor);
                lastX = data.normalizedX;
                lastY = data.normalizedY;
            } else {
                //lines.push({normalizedX: data.normalizedX, normalizedY: data.normalizedY, strokeColor: data.strokeColor});
            }
        }
        
        if (data.type === "mouseup") {
            drawer = data.drawer;


            if (drawer !== user) {
                console.log('PAROU');
                lastX = null;
                lastY = null;
                
                return;
                
            }
            return;
        }

        if(data.type === "leaderboard"){

            lastLb = data.leaderboard;

            updateLeaderboard(data.leaderboard)
        }

        if (data.type === "clear") {
            drawer = data.drawer;
            if (drawer !== user) {
                clearCanvas();
                return;
            }
            return;
        }


        if (data.type === "refresh_lines") {

            lines = data.lines;
            currentLine = data.curline;

            
        }

        if (data.type === "new_drawer") {
            var sound = new Audio('./notificacao.mp3');
            sound.play();
            
            drawer = data.drawer;

            console.log(drawer);
            console.log(data);

            clearCanvas();

            clearMsg = {
                'type': 'clear',
                'drawer': drawer
            };

            sendMessage(JSON.stringify(clearMsg));
            novoDrawer();   
        }

        if (data.type === "new_drawer_draw") {
            var sound = new Audio('./notificacao.mp3');
            sound.play();
            
            drawer = user;

            console.log(drawer);
            console.log(data);

            clearCanvas();

            clearMsg = {
                'type': 'clear',
                'drawer': user
            };

            sendMessage(JSON.stringify(clearMsg));
            
            document.getElementsByClassName('palavra')[0].textContent = data.palavra
            
            novoDrawer();   
        }

        if (data.type === "retorno_dica") {
            if(data.dica_level == 0){
                document.getElementsByClassName('dica')[0].style.display = 'flex'

                let arr = []
                
                for(i in data.dica){
                    arr.push(data.dica[i])
                }
                
                document.getElementsByClassName('dica_info_main')[0].style.display = 'flex'
                document.getElementsByClassName('dica-background')[0].style.display = 'flex'
                document.getElementsByClassName('dica-title')[0].style.display = 'flex'
                document.getElementsByClassName('dica-title')[0].textContent = 'DICA'
                document.getElementsByClassName('dica')[0].textContent = arr.join(" ")
            } else {

            }
            
        }

        appendMessage(e.data);
    };

    var socketClose = (e) => {
        var msg;
        console.log(e);
        if (e.wasClean) {
            console.log("The connection closed cleanly");
            msg = {
                type: 'left',
                sender: 'Browser',
                text: 'The connection closed cleanly'
            };
        } else {
            console.log("The connection closed for some reason");
            msg = {
                type: 'left',
                sender: 'Browser',
                text: 'The connection closed for some reason'
            };
        }
        appendMessage(JSON.stringify(msg));
    };

    var socketError = (e) => {
        console.log("WebSocket Error");
        console.log(e);
    };

    socket.addEventListener("open", socketOpen);
    socket.addEventListener("message", socketMessage);
    socket.addEventListener("close", socketClose);
    socket.addEventListener("error", socketError);
})();
