<?php
// app/Mail/OtpMail.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable {
  use Queueable, SerializesModels;

  public function __construct(
    public string $code,
    public string $purpose
  ) {}

  public function build() {
    $title = $this->purpose === 'email_verify' ? 'Email Verification OTP' : 'Login OTP';
    return $this->subject($title)
      ->view('emails.otp');
  }
}
