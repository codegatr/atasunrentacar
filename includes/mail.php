<?php
/**
 * ATA SU - Mail Modulu
 * Native mail() veya SMTP ile e-posta gonderir.
 * Ayarlardan mail_smtp_host bos ise mail() kullanir, dolu ise SMTP.
 */
defined('ATASU') or exit('403');

class Mail
{
    /**
     * E-posta gonder. SMTP veya mail() otomatik secilir.
     * @return array ['basari' => bool, 'mesaj' => string]
     */
    public static function gonder(string $alici, string $konu, string $govdeHtml, ?string $govdeMetin = null, array $extra = []): array
    {
        if (!filter_var($alici, FILTER_VALIDATE_EMAIL)) {
            return ['basari' => false, 'mesaj' => 'Gecersiz alici e-posta'];
        }

        $smtpHost = (string)ayar('mail_smtp_host', '');
        $gonderen = (string)(ayar('mail_from', '') ?: ayar('email', ''));
        $gonderenAd = (string)ayar('site_baslik', 'ATA SU Rent A Car');

        if (!$gonderen) {
            return ['basari' => false, 'mesaj' => 'Gonderen e-posta tanimlanmamis'];
        }

        if ($govdeMetin === null) {
            $govdeMetin = trim(html_entity_decode(strip_tags($govdeHtml), ENT_QUOTES, 'UTF-8'));
        }

        if ($smtpHost) {
            return self::smtpGonder($alici, $konu, $govdeHtml, $govdeMetin, $gonderen, $gonderenAd, $extra);
        }
        return self::mailGonder($alici, $konu, $govdeHtml, $govdeMetin, $gonderen, $gonderenAd, $extra);
    }

    private static function mailGonder(string $alici, string $konu, string $html, string $metin, string $from, string $fromAd, array $extra): array
    {
        $sinir = '=_atasu_' . md5(uniqid('', true));
        $headers = [
            'From: ' . self::header($fromAd) . ' <' . $from . '>',
            'Reply-To: ' . ($extra['reply_to'] ?? $from),
            'Return-Path: ' . $from,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $sinir . '"',
            'X-Mailer: ATASU/1.0',
            'Date: ' . date('r'),
        ];

        $body = "--$sinir\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $metin . "\r\n\r\n";
        $body .= "--$sinir\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $html . "\r\n\r\n";
        $body .= "--$sinir--";

        $sonuc = @mail($alici, self::header($konu), $body, implode("\r\n", $headers));
        if ($sonuc) {
            return ['basari' => true, 'mesaj' => 'Gonderildi'];
        }
        return ['basari' => false, 'mesaj' => 'mail() basarisiz'];
    }

    private static function smtpGonder(string $alici, string $konu, string $html, string $metin, string $from, string $fromAd, array $extra): array
    {
        $host = (string)ayar('mail_smtp_host', '');
        $port = (int)(ayar('mail_smtp_port', 587));
        $user = (string)ayar('mail_smtp_user', '');
        $pass = (string)ayar('mail_smtp_pass', '');
        $secure = (string)ayar('mail_smtp_secure', 'tls');

        try {
            $smtp = new SMTPGonderici($host, $port, $secure, $user, $pass);
            $smtp->setFrom($from, $fromAd);
            $smtp->addTo($alici);
            if (!empty($extra['reply_to'])) $smtp->setReplyTo($extra['reply_to']);
            $sonuc = $smtp->send($konu, $html, $metin);
            return $sonuc;
        } catch (Throwable $e) {
            return ['basari' => false, 'mesaj' => 'SMTP: ' . $e->getMessage()];
        }
    }

    private static function header(string $deger): string
    {
        if (preg_match('/[\x80-\xff]/', $deger)) {
            return '=?UTF-8?B?' . base64_encode($deger) . '?=';
        }
        return $deger;
    }

    /**
     * HTML sablon - basit cerceve
     */
    public static function sablon(string $baslik, string $icerik, string $cta_url = '', string $cta_metin = ''): string
    {
        $siteAdi = (string)ayar('site_baslik', 'ATA SU Rent A Car');
        $siteUrl = SITE_URL;
        $logoUrl = ayar('logo') ? upload_url(ayar('logo')) : '';
        $renk1 = '#1e3a5f';
        $renk2 = '#3b82f6';
        $cta = '';
        if ($cta_url && $cta_metin) {
            $cta = '<p style="text-align:center;margin:30px 0 20px;"><a href="' . htmlspecialchars($cta_url) . '" style="background:' . $renk2 . ';color:#fff;padding:12px 30px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">' . htmlspecialchars($cta_metin) . '</a></p>';
        }
        return '<!DOCTYPE html>
<html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,Segoe UI,sans-serif;color:#1e293b;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;">
<tr><td align="center" style="padding:30px 12px;">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
<tr><td style="background:' . $renk1 . ';padding:24px;text-align:center;">
' . ($logoUrl ? '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($siteAdi) . '" style="max-height:50px;">' : '<h1 style="color:#fff;margin:0;font-size:24px;">' . htmlspecialchars($siteAdi) . '</h1>') . '
</td></tr>
<tr><td style="padding:36px 30px;">
<h2 style="color:' . $renk1 . ';margin:0 0 20px;font-size:22px;">' . htmlspecialchars($baslik) . '</h2>
<div style="line-height:1.7;color:#334155;font-size:15px;">' . $icerik . '</div>
' . $cta . '
</td></tr>
<tr><td style="background:#f8fafc;padding:20px 30px;border-top:1px solid #e2e8f0;text-align:center;font-size:13px;color:#64748b;">
<p style="margin:0 0 6px;"><strong>' . htmlspecialchars($siteAdi) . '</strong></p>
<p style="margin:0;">' . htmlspecialchars((string)ayar('adres', 'Konya')) . ' · ' . htmlspecialchars((string)ayar('telefon', '')) . '</p>
<p style="margin:6px 0 0;"><a href="' . $siteUrl . '" style="color:' . $renk2 . ';text-decoration:none;">' . preg_replace('|^https?://|', '', $siteUrl) . '</a></p>
</td></tr>
</table>
</td></tr></table>
</body></html>';
    }
}

/**
 * Minimal SMTP gonderici - PHPMailer bagimliligini kaldirir.
 */
class SMTPGonderici
{
    private $sock;
    private string $host;
    private int $port;
    private string $secure;
    private string $user;
    private string $pass;
    private string $fromAddr = '';
    private string $fromName = '';
    private array $to = [];
    private string $replyTo = '';

    public function __construct(string $host, int $port, string $secure, string $user, string $pass)
    {
        $this->host = $host;
        $this->port = $port;
        $this->secure = strtolower($secure);
        $this->user = $user;
        $this->pass = $pass;
    }

    public function setFrom(string $addr, string $name): void { $this->fromAddr = $addr; $this->fromName = $name; }
    public function addTo(string $addr): void { $this->to[] = $addr; }
    public function setReplyTo(string $addr): void { $this->replyTo = $addr; }

    public function send(string $konu, string $html, string $metin): array
    {
        $hostStr = ($this->secure === 'ssl' ? 'ssl://' : '') . $this->host;
        $this->sock = @stream_socket_client($hostStr . ':' . $this->port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
        if (!$this->sock) {
            return ['basari' => false, 'mesaj' => "SMTP baglanti: $errstr ($errno)"];
        }
        stream_set_timeout($this->sock, 15);

        $banner = $this->oku();
        if (substr($banner, 0, 3) !== '220') return ['basari' => false, 'mesaj' => 'SMTP banner: ' . $banner];

        $this->yaz('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $this->okuCokSatir();

        if ($this->secure === 'tls') {
            $this->yaz('STARTTLS');
            $r = $this->oku();
            if (substr($r, 0, 3) !== '220') return ['basari' => false, 'mesaj' => 'STARTTLS: ' . $r];
            if (!@stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                return ['basari' => false, 'mesaj' => 'TLS yukseltme basarisiz'];
            }
            $this->yaz('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $this->okuCokSatir();
        }

        if ($this->user) {
            $this->yaz('AUTH LOGIN');
            $r = $this->oku();
            if (substr($r, 0, 3) !== '334') return ['basari' => false, 'mesaj' => 'AUTH: ' . $r];
            $this->yaz(base64_encode($this->user));
            $r = $this->oku();
            if (substr($r, 0, 3) !== '334') return ['basari' => false, 'mesaj' => 'AUTH user: ' . $r];
            $this->yaz(base64_encode($this->pass));
            $r = $this->oku();
            if (substr($r, 0, 3) !== '235') return ['basari' => false, 'mesaj' => 'AUTH pass: ' . $r];
        }

        $this->yaz('MAIL FROM:<' . $this->fromAddr . '>');
        $r = $this->oku();
        if (substr($r, 0, 3) !== '250') return ['basari' => false, 'mesaj' => 'MAIL FROM: ' . $r];

        foreach ($this->to as $rcpt) {
            $this->yaz('RCPT TO:<' . $rcpt . '>');
            $r = $this->oku();
            if (substr($r, 0, 3) !== '250' && substr($r, 0, 3) !== '251') {
                return ['basari' => false, 'mesaj' => 'RCPT: ' . $r];
            }
        }

        $this->yaz('DATA');
        $r = $this->oku();
        if (substr($r, 0, 3) !== '354') return ['basari' => false, 'mesaj' => 'DATA: ' . $r];

        $sinir = '=_atasu_' . md5(uniqid('', true));
        $fromHeader = '=?UTF-8?B?' . base64_encode($this->fromName) . '?= <' . $this->fromAddr . '>';
        $konuHeader = '=?UTF-8?B?' . base64_encode($konu) . '?=';

        $msg = "From: $fromHeader\r\n";
        $msg .= "To: " . implode(', ', $this->to) . "\r\n";
        if ($this->replyTo) $msg .= "Reply-To: " . $this->replyTo . "\r\n";
        $msg .= "Subject: $konuHeader\r\n";
        $msg .= "Date: " . date('r') . "\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: multipart/alternative; boundary=\"$sinir\"\r\n";
        $msg .= "X-Mailer: ATASU/1.0\r\n\r\n";
        $msg .= "--$sinir\r\n";
        $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $msg .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $msg .= $metin . "\r\n\r\n";
        $msg .= "--$sinir\r\n";
        $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
        $msg .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $msg .= $html . "\r\n";
        $msg .= "--$sinir--\r\n.\r\n";

        fwrite($this->sock, $msg);
        $r = $this->oku();
        if (substr($r, 0, 3) !== '250') return ['basari' => false, 'mesaj' => 'Body: ' . $r];

        $this->yaz('QUIT');
        @fclose($this->sock);
        return ['basari' => true, 'mesaj' => 'Gonderildi (SMTP)'];
    }

    private function yaz(string $cmd): void
    {
        fwrite($this->sock, $cmd . "\r\n");
    }

    private function oku(): string
    {
        $t = '';
        while (($s = fgets($this->sock, 1024)) !== false) {
            $t .= $s;
            if (strlen($s) >= 4 && $s[3] === ' ') break;
        }
        return $t;
    }

    private function okuCokSatir(): string
    {
        return $this->oku();
    }
}
