// ========================================
// 3. resources/views/debug-csrf.blade.php
// 新しく作成してCSRFテストページを作る
// ========================================
?>
<!DOCTYPE html>
<html>
<head>
    <title>CSRF Debug</title>
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
</head>
<body>
    <h1>CSRF Debug Page</h1>
    
    <div id="info">
        <p>Session ID: <span id="session-id"></span></p>
        <p>CSRF Token: <span id="csrf-token"><?php echo e(csrf_token()); ?></span></p>
        <p>Token Length: <span id="token-length"><?php echo e(strlen(csrf_token())); ?></span></p>
    </div>

    <h2>Test Form 1: Normal POST</h2>
    <form action="/debug-csrf-post" method="POST">
        <?php echo csrf_field(); ?>
        <button type="submit">Submit with CSRF</button>
    </form>

    <h2>Test Form 2: Ajax POST</h2>
    <button onclick="testAjax()">Test Ajax POST</button>
    <div id="ajax-result"></div>

    <h2>Cookie Information</h2>
    <pre id="cookies"></pre>

    <script>
        // Cookie情報を表示
        document.getElementById('cookies').textContent = document.cookie;

        // セッション情報を取得
        fetch('/debug-csrf')
            .then(r => r.json())
            .then(data => {
                console.log('Session Info:', data);
                document.getElementById('session-id').textContent = data.session_id;
                document.getElementById('info').innerHTML += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            });

        // Ajax POSTテスト
        function testAjax() {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch('/debug-csrf-post', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    _token: token,
                    test: 'data'
                })
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('ajax-result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                console.log('Ajax Result:', data);
            })
            .catch(err => {
                document.getElementById('ajax-result').innerHTML = '<pre style="color: red;">' + err + '</pre>';
                console.error('Ajax Error:', err);
            });
        }
    </script>
</body>
</html>
<?php ?><?php /**PATH /var/www/html/resources/views/debug-csrf.blade.php ENDPATH**/ ?>