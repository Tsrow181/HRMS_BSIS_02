<?php
class EmailSender {
    public function sendEmail($to, $subject, $body) {
        // Test mode - simulate email sending without actual SMTP
        error_log("[TEST MODE] Email would be sent to: $to");
        error_log("[TEST MODE] Subject: $subject");
        error_log("[TEST MODE] Body: " . substr($body, 0, 100) . "...");
        
        // Always return true in test mode
        return true;
        
        // SMTP configuration (disabled for testing)
        /*
        $smtp_host = SMTP_HOST;
        $smtp_port = SMTP_PORT;
        $smtp_username = SMTP_USERNAME;
        $smtp_password = SMTP_PASSWORD;
        $smtp_from = SMTP_FROM_EMAIL;
        $smtp_from_name = SMTP_FROM_NAME;
        
        // Create socket connection
        $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 30);
        if (!$socket) {
            error_log("SMTP Connection failed: $errstr ($errno)");
            return false;
        }
        
        // Read initial response
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '220') {
            error_log("SMTP Initial response failed: $response");
            fclose($socket);
            return false;
        }
        
        // EHLO command
        fputs($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 512);
        
        // Start TLS
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '220') {
            error_log("STARTTLS failed: $response");
            fclose($socket);
            return false;
        }
        
        // Enable crypto
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log("TLS encryption failed");
            fclose($socket);
            return false;
        }
        
        // EHLO again after TLS
        fputs($socket, "EHLO localhost\r\n");
        $response = fgets($socket, 512);
        
        // AUTH LOGIN
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '334') {
            error_log("AUTH LOGIN failed: $response");
            fclose($socket);
            return false;
        }
        
        // Send username
        fputs($socket, base64_encode($smtp_username) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '334') {
            error_log("Username authentication failed: $response");
            fclose($socket);
            return false;
        }
        
        // Send password
        fputs($socket, base64_encode($smtp_password) . "\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '235') {
            error_log("Password authentication failed: $response");
            fclose($socket);
            return false;
        }
        
        // MAIL FROM
        fputs($socket, "MAIL FROM: <$smtp_from>\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            error_log("MAIL FROM failed: $response");
            fclose($socket);
            return false;
        }
        
        // RCPT TO
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            error_log("RCPT TO failed: $response");
            fclose($socket);
            return false;
        }
        
        // DATA
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '354') {
            error_log("DATA command failed: $response");
            fclose($socket);
            return false;
        }
        
        // Email headers and body
        $email_data = "From: $smtp_from_name <$smtp_from>\r\n";
        $email_data .= "To: $to\r\n";
        $email_data .= "Subject: $subject\r\n";
        $email_data .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $email_data .= "\r\n";
        $email_data .= $body;
        $email_data .= "\r\n.\r\n";
        
        fputs($socket, $email_data);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            error_log("Email sending failed: $response");
            fclose($socket);
            return false;
        }
        
        // QUIT
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        return true;
        */
    }
}
?>