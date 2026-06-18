function yesnoCheck1() {
    if (document.getElementById('diet1').checked) {
        document.getElementById('dietarea').style.display = 'block';
        document.getElementById('diet_question').style.display = 'block';
    }
    else {
        document.getElementById('dietarea').style.display = 'none';
        document.getElementById('diet_question').style.display = 'none';
    }

}

function yesnoCheck2() {
    if (document.getElementById('access1').checked) {
        document.getElementById('accessarea').style.display = 'block';
        document.getElementById('access_question').style.display = 'block';
    }else {
        document.getElementById('accessarea').style.display = 'none';
        document.getElementById('access_question').style.display = 'none';
        
    }

}