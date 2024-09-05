<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Color Picker Popup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }
        #colorPickerButton {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
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
    </style>
</head>
<body>

<button id="colorPickerButton">Select Color</button>

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

<script>
    document.getElementById('colorPickerButton').addEventListener('click', function() {
        const card = document.getElementById('colorPickerCard');
        card.style.display = card.style.display === 'none' ? 'block' : 'none';
    });

    document.querySelectorAll('.color-square').forEach(square => {
        square.addEventListener('click', function() {
            const selectedColor = this.style.backgroundColor;
            alert(`You selected: ${selectedColor}`);
            document.getElementById('colorPickerCard').style.display = 'none';
        });
    });
</script>

</body>
</html>
