// resources/views/emails/otp.blade.php
<!doctype html>
<html>
  <body style="font-family: Arial; line-height: 1.6;">
    <h2>Your OTP Code</h2>
    <p>Use the code below to continue:</p>
    <div style="font-size: 28px; font-weight: bold; letter-spacing: 4px;">
      {{ $code }}
    </div>
    <p>This code expires soon. If you didn’t request this, ignore.</p>
  </body>
</html>
