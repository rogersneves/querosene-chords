<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Código de verificação</title>
<style>
  body { margin:0; padding:0; background:#0D0D0D; font-family:'Segoe UI',Arial,sans-serif; color:#F5F5F5; }
  .wrap { max-width:480px; margin:40px auto; background:#1A1A1A; border-radius:16px; overflow:hidden; }
  .header { background:#FF6D00; padding:28px 32px; }
  .header h1 { margin:0; font-size:20px; color:#fff; font-weight:900; letter-spacing:-0.3px; }
  .body { padding:32px; }
  .greeting { font-size:15px; color:#ccc; margin-bottom:20px; }
  .code-box { background:#0D0D0D; border:2px solid #FF6D00; border-radius:12px;
              text-align:center; padding:24px 16px; margin:24px 0; }
  .code { font-size:40px; font-weight:900; letter-spacing:12px; color:#FF6D00;
          font-family:'Courier New',monospace; }
  .expiry { font-size:13px; color:#888; margin-top:8px; }
  .info { font-size:13px; color:#888; line-height:1.6; margin-top:20px; }
  .footer { padding:20px 32px; border-top:1px solid rgba(255,255,255,0.06);
            font-size:12px; color:#555; text-align:center; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>🔥 Querosene Chords</h1>
  </div>
  <div class="body">
    <p class="greeting">Olá, <strong>{{ $userName }}</strong>!</p>
    <p style="font-size:15px;color:#ccc;">Use o código abaixo para confirmar o acesso à sua conta:</p>

    <div class="code-box">
      <div class="code">{{ $code }}</div>
      <div class="expiry">Válido por 10 minutos</div>
    </div>

    <p class="info">
      Se você não tentou acessar sua conta, ignore este e-mail.
      Nenhuma ação é necessária.
    </p>
  </div>
  <div class="footer">
    Querosene Chords · Dê um gás na sua música
  </div>
</div>
</body>
</html>
