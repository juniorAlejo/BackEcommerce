<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
    .container { max-width: 480px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .header { background: #1e40af; padding: 32px; text-align: center; }
    .header h1 { color: #fff; margin: 0; font-size: 24px; }
    .body { padding: 32px; text-align: center; }
    .code { font-size: 42px; font-weight: bold; letter-spacing: 10px; color: #1e40af; background: #eff6ff; border-radius: 8px; padding: 16px 24px; display: inline-block; margin: 24px 0; }
    .note { color: #6b7280; font-size: 13px; margin-top: 16px; }
    .footer { background: #f9fafb; padding: 16px; text-align: center; color: #9ca3af; font-size: 12px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>HypexTech</h1>
    </div>
    <div class="body">
      @if($userName)
        <p style="color:#374151;font-size:16px;">Hola, <strong>{{ $userName }}</strong></p>
      @endif
      <p style="color:#374151;font-size:15px;">Tu código de verificación es:</p>
      <div class="code">{{ $code }}</div>
      <p class="note">Este código expira en <strong>10 minutos</strong>.<br>Si no solicitaste esto, ignora este mensaje.</p>
    </div>
    <div class="footer">© {{ date('Y') }} HypexTech — Todos los derechos reservados</div>
  </div>
</body>
</html>