<?php
// Simple math CAPTCHA implementation

// Generate CAPTCHA question
function generateCaptcha() {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $operation = rand(0, 1) ? '+' : '-';
    
    if ($operation === '+') {
        $answer = $num1 + $num2;
        $question = "$num1 + $num2";
    } else {
        // Ensure positive result
        if ($num1 < $num2) {
            $temp = $num1;
            $num1 = $num2;
            $num2 = $temp;
        }
        $answer = $num1 - $num2;
        $question = "$num1 - $num2";
    }
    
    $_SESSION['captcha_answer'] = $answer;
    return $question;
}

// Verify CAPTCHA answer
function verifyCaptcha($userAnswer) {
    return isset($_SESSION['captcha_answer']) && 
           intval($userAnswer) === $_SESSION['captcha_answer'];
}

// Generate CAPTCHA HTML
function captchaHTML() {
    $question = generateCaptcha();
    return '
    <div class="form-group">
        <label for="captcha">Security Question: What is ' . $question . '?</label>
        <input type="number" class="form-control" id="captcha" name="captcha" required>
    </div>';
}
?>