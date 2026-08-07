<?php

namespace App\Shared\Infrastructure\Mail;

require_once __DIR__ . '/../../../../vendor/phpmailer/Exception.php';
require_once __DIR__ . '/../../../../vendor/phpmailer/SMTP.php';
require_once __DIR__ . '/../../../../vendor/phpmailer/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private string $fromAddress;
    private string $fromName;

    public function __construct()
    {
        $this->fromAddress = $this->getEnvValue('MAIL_FROM_ADDRESS', 'noreply@bloodconnect.com');
        $this->fromName = $this->getEnvValue('MAIL_FROM_NAME', 'BloodConnect');
    }

    public function sendOtp(string $toEmail, string|int $otp, ?string $name = null): bool
    {
        $greeting = 'Hello' . ($name !== null && $name !== '' ? ' ' . htmlspecialchars($name) : '');

        $body = "
            <p>{$greeting},</p>
            <p>Welcome to BloodConnect! Your account has been created successfully.</p>
            <p>Please use the verification code below to verify your email address and activate your account:</p>
            <h1 style='color:#e63946; text-align:center; font-size:32px; letter-spacing:6px;'>{$otp}</h1>
            <p>Enter this code on the verification page to complete your registration.</p>
            <p style='color:#71717a; font-size:13px;'>This code will expire in 10 minutes.</p>
        ";

        return $this->send(
            $toEmail,
            'Welcome to BloodConnect',
            $this->layout('Verify Your Email Address', $body),
            "Welcome to BloodConnect. Your verification code is {$otp}. This code will expire in 10 minutes."
        );
    }

    public function sendPasswordResetOtp(string $toEmail, string|int $otp): bool
    {
        $body = "
            <p>Hello,</p>
            <p>You requested a password reset for your BloodConnect account.</p>
            <p>Your reset code is:</p>
            <h1 style='color:#e63946; text-align:center; font-size:32px; letter-spacing:6px;'>{$otp}</h1>
            <p>Please enter this code to continue resetting your password.</p>
            <p style='color:#71717a; font-size:13px;'>This code will expire in 10 minutes.</p>
        ";

        return $this->send(
            $toEmail,
            'BloodConnect Password Reset Code',
            $this->layout('Password Reset', $body),
            "Your BloodConnect password reset code is {$otp}. This code will expire in 10 minutes."
        );
    }

    public function sendBloodRequestAlert(string $toEmail, array $request): bool
    {
        $patientName = $this->text($request['patient_name'] ?? 'A patient');
        $bloodGroup = $this->text($request['blood_group_needed'] ?? '');
        $hospital = $this->text($request['hospital_name'] ?? 'the hospital');
        $urgency = $this->text(strtoupper((string)($request['urgency'] ?? 'ROUTINE')));
        $requestCode = $this->text($request['request_code'] ?? '');

        $body = "
            <p>Hello,</p>
            <p>A blood request has been registered that matches your blood group.</p>
            {$this->dataTable([
                'Request Code' => $requestCode,
                'Patient' => $patientName,
                'Blood Group Needed' => $bloodGroup,
                'Urgency' => $urgency,
                'Hospital' => $hospital,
            ])}
            <p>Please log in to your BloodConnect donor account to review and respond to the request.</p>
            <p>Thank you for being a lifesaver.</p>
        ";

        return $this->send(
            $toEmail,
            "BloodConnect: Urgent {$bloodGroup} Blood Request ({$requestCode})",
            $this->layout('New Blood Request Alert', $body),
            "A {$bloodGroup} blood request ({$requestCode}) from {$patientName} at {$hospital} needs your help. Log in to BloodConnect to respond."
        );
    }

    public function sendNewBloodRequestSubmitted(string $toEmail, array $data): bool
    {
        $body = "
            <p>Hello Administrator,</p>
            <p>A new blood request has been submitted and needs your attention.</p>
            {$this->dataTable([
                'Patient' => $this->text($data['patient_name'] ?? 'A patient'),
                'Blood Group' => $this->text($data['blood_group'] ?? ''),
                'Hospital' => $this->text($data['hospital_name'] ?? 'the hospital'),
                'Urgency' => $this->text(strtoupper((string)($data['urgency'] ?? 'ROUTINE'))),
                'Request Code' => $this->text($data['request_code'] ?? ''),
            ])}
            <p>Please log in to BloodConnect to assign a donor.</p>
        ";

        return $this->send(
            $toEmail,
            'New Blood Request Submitted',
            $this->layout('New Blood Request Submitted', $body),
            "A new blood request has been submitted. Patient: {$this->plain($data['patient_name'] ?? 'A patient')}, Blood Group: {$this->plain($data['blood_group'] ?? '')}, Hospital: {$this->plain($data['hospital_name'] ?? 'the hospital')}. Please log in to assign a donor."
        );
    }

    public function sendDonorAssignedToDonor(string $toEmail, array $data): bool
    {
        $body = "
            <p>Hello {$this->text($data['donor_name'] ?? 'Donor')},</p>
            <p>You have been assigned to a new blood request.</p>
            {$this->dataTable([
                'Patient' => $this->text($data['patient_name'] ?? 'A patient'),
                'Blood Group' => $this->text($data['blood_group'] ?? ''),
                'Hospital' => $this->text($data['hospital_name'] ?? 'the hospital'),
                'Request Code' => $this->text($data['request_code'] ?? ''),
            ])}
            <p>Please log in to BloodConnect to accept or decline this request.</p>
        ";

        return $this->send(
            $toEmail,
            'You Have Been Assigned to a Blood Request',
            $this->layout('Blood Request Assigned', $body),
            "Hello {$this->plain($data['donor_name'] ?? 'Donor')}, you have been assigned to a new blood request for patient {$this->plain($data['patient_name'] ?? 'A patient')}. Please log in to accept or decline."
        );
    }

    public function sendDonorAssignedToPatient(string $toEmail, array $data): bool
    {
        $body = "
            <p>Hello {$this->text($data['patient_name'] ?? 'Patient')},</p>
            <p>A donor has been assigned to your blood request ({$this->text($data['request_code'] ?? '')}).</p>
            <p>Please wait for the donor's response.</p>
        ";

        return $this->send(
            $toEmail,
            'A Donor Has Been Assigned to Your Request',
            $this->layout('Donor Assigned', $body),
            "A donor has been assigned to your blood request ({$this->plain($data['request_code'] ?? '')}). Please wait for the donor's response."
        );
    }

    public function sendDonorAcceptedToPatient(string $toEmail, array $data): bool
    {
        $body = "
            <p>Hello {$this->text($data['patient_name'] ?? 'Patient')},</p>
            <p><strong>Good news!</strong> Your blood request ({$this->text($data['request_code'] ?? '')}) has been accepted.</p>
            {$this->dataTable([
                'Donor' => $this->text($data['donor_name'] ?? 'A donor'),
                'Hospital' => $this->text($data['hospital_name'] ?? 'the hospital'),
            ])}
            <p>Please contact the hospital for further arrangements.</p>
        ";

        return $this->send(
            $toEmail,
            'Good News! Your Blood Request Has Been Accepted',
            $this->layout('Blood Request Accepted', $body),
            "Good news! Your blood request ({$this->plain($data['request_code'] ?? '')}) has been accepted. Donor: {$this->plain($data['donor_name'] ?? 'A donor')}, Hospital: {$this->plain($data['hospital_name'] ?? 'the hospital')}. Please contact the hospital for further arrangements."
        );
    }

    public function sendDonorAcceptedToAdmin(string $toEmail, array $data): bool
    {
        $body = "
            <p>Hello Administrator,</p>
            <p>Blood request {$this->text($data['request_code'] ?? '')} has been accepted by a donor.</p>
            {$this->dataTable([
                'Donor' => $this->text($data['donor_name'] ?? 'A donor'),
                'Patient' => $this->text($data['patient_name'] ?? 'A patient'),
                'Hospital' => $this->text($data['hospital_name'] ?? 'the hospital'),
            ])}
            <p>Please log in to BloodConnect to review the request.</p>
        ";

        return $this->send(
            $toEmail,
            'Blood Request Accepted',
            $this->layout('Blood Request Accepted', $body),
            "Blood request {$this->plain($data['request_code'] ?? '')} has been accepted by donor {$this->plain($data['donor_name'] ?? 'A donor')} for patient {$this->plain($data['patient_name'] ?? 'A patient')}."
        );
    }

    public function sendDonorDeclinedToPatient(string $toEmail, array $data): bool
    {
        $body = "
            <p>Hello {$this->text($data['patient_name'] ?? 'Patient')},</p>
            <p>The assigned donor has declined your blood request ({$this->text($data['request_code'] ?? '')}).</p>
            <p>The administrator will assign another suitable donor shortly.</p>
        ";

        return $this->send(
            $toEmail,
            'Donor Declined Your Blood Request',
            $this->layout('Blood Request Declined', $body),
            "The assigned donor has declined your blood request ({$this->plain($data['request_code'] ?? '')}). The administrator will assign another suitable donor shortly."
        );
    }

    public function sendDonorDeclinedToAdmin(string $toEmail, array $data): bool
    {
        $body = "
            <p>Hello Administrator,</p>
            <p>The assigned donor has declined a blood request.</p>
            {$this->dataTable([
                'Donor' => $this->text($data['donor_name'] ?? 'A donor'),
                'Patient' => $this->text($data['patient_name'] ?? 'A patient'),
                'Blood Group' => $this->text($data['blood_group'] ?? ''),
                'Request Code' => $this->text($data['request_code'] ?? ''),
            ])}
            <p>The administrator will assign another suitable donor shortly.</p>
        ";

        return $this->send(
            $toEmail,
            'Blood Request Declined',
            $this->layout('Blood Request Declined', $body),
            "Donor {$this->plain($data['donor_name'] ?? 'A donor')} declined blood request {$this->plain($data['request_code'] ?? '')} for patient {$this->plain($data['patient_name'] ?? 'A patient')}."
        );
    }

    public function sendDonationCompletedToPatient(string $toEmail, array $data): bool
    {
        $body = "
            <p>Hello {$this->text($data['patient_name'] ?? 'Patient')},</p>
            <p><strong>Congratulations!</strong> The blood donation for your request ({$this->text($data['request_code'] ?? '')}) has been completed successfully.</p>
            <p>Thank you for using BloodConnect.</p>
        ";

        return $this->send(
            $toEmail,
            'Donation Completed',
            $this->layout('Donation Completed', $body),
            "Congratulations! Your blood donation process has been completed successfully. Thank you for using BloodConnect."
        );
    }

    public function sendDonationCompletedToDonor(string $toEmail, array $data): bool
    {
        $body = "
            <p>Hello {$this->text($data['donor_name'] ?? 'Donor')},</p>
            <p><strong>Congratulations!</strong> Your blood donation process has been completed successfully.</p>
            <p>Thank you for being a lifesaver and for using BloodConnect.</p>
        ";

        return $this->send(
            $toEmail,
            'Donation Completed',
            $this->layout('Donation Completed', $body),
            "Congratulations! Your blood donation process has been completed successfully. Thank you for using BloodConnect."
        );
    }

    public function sendRequestExpiredToPatient(string $toEmail, array $data): bool
    {
        $body = "
            <p>Hello {$this->text($data['patient_name'] ?? 'Patient')},</p>
            <p>Your blood request ({$this->text($data['request_code'] ?? '')}) has expired.</p>
            <p>Please submit a new request if blood is still required.</p>
        ";

        return $this->send(
            $toEmail,
            'Your Blood Request Has Expired',
            $this->layout('Blood Request Expired', $body),
            "Your blood request ({$this->plain($data['request_code'] ?? '')}) has expired. Please submit a new request if blood is still required."
        );
    }

    public function sendRequestExpiredToAdmin(string $toEmail, array $data): bool
    {
        $body = "
            <p>Hello Administrator,</p>
            <p>Blood request {$this->text($data['request_code'] ?? '')} has expired without being completed.</p>
            {$this->dataTable([
                'Patient' => $this->text($data['patient_name'] ?? 'A patient'),
                'Blood Group' => $this->text($data['blood_group'] ?? ''),
                'Hospital' => $this->text($data['hospital_name'] ?? 'the hospital'),
            ])}
        ";

        return $this->send(
            $toEmail,
            'Blood Request Expired',
            $this->layout('Blood Request Expired', $body),
            "Blood request {$this->plain($data['request_code'] ?? '')} for patient {$this->plain($data['patient_name'] ?? 'A patient')} has expired without being completed."
        );
    }

    private function send(string $toEmail, string $subject, string $htmlBody, string $altBody): bool
    {
        $mail = new PHPMailer(true);

        try {
            $host = $this->getEnvValue('MAIL_HOST');
            $username = $this->getEnvValue('MAIL_USERNAME');
            $password = $this->getEnvValue('MAIL_PASSWORD');

            if ($host && $username && $password) {
                $mail->isSMTP();
                $mail->Host = $host;
                $mail->SMTPAuth = true;
                $mail->Username = $username;
                $mail->Password = $password;
                $mail->SMTPSecure = $this->getEnvValue('MAIL_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS);
                $mail->Port = (int) $this->getEnvValue('MAIL_PORT', 587);
                $mail->SMTPDebug = 0;
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
            } else {
                $mail->isMail();
            }

            $mail->setFrom($this->fromAddress, $this->fromName);
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $altBody;

            $sent = $mail->send();

            if (!$sent) {
                error_log('Mail send failed: ' . $mail->ErrorInfo);
            }

            return $sent;
        } catch (Exception $e) {
            error_log('Mail Error: ' . $e->getMessage());
            return false;
        }
    }

    private function layout(string $title, string $bodyHtml): string
    {
        return "
            <div style='font-family:Arial, Helvetica, sans-serif; background:#f4f4f5; padding:24px;'>
                <div style='max-width:600px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e4e4e7;'>
                    <div style='background:#e63946; color:#ffffff; padding:20px 28px;'>
                        <h1 style='margin:0; font-size:20px;'>BloodConnect</h1>
                        <p style='margin:4px 0 0; font-size:12px; opacity:.85;'>Blood Donation Management System</p>
                    </div>
                    <div style='padding:28px; line-height:1.6; color:#27272a;'>
                        <h2 style='color:#18181b; font-size:18px; margin:0 0 16px;'>{$title}</h2>
                        {$bodyHtml}
                        <p style='color:#71717a; font-size:12px; border-top:1px solid #e4e4e7; padding-top:16px; margin-top:24px;'>
                            This is an automated message from BloodConnect. Please do not reply to this email.
                        </p>
                    </div>
                </div>
            </div>
        ";
    }

    private function dataTable(array $rows): string
    {
        $out = "<table style='width:100%; border-collapse:collapse; margin:16px 0; font-size:14px;'>";
        foreach ($rows as $label => $value) {
            $out .= "<tr>
                        <td style='padding:8px 12px; background:#fafafa; border:1px solid #e4e4e7; font-weight:bold; width:40%; vertical-align:top;'>{$label}</td>
                        <td style='padding:8px 12px; border:1px solid #e4e4e7; vertical-align:top;'>{$value}</td>
                    </tr>";
        }
        $out .= "</table>";
        return $out;
    }

    private function text(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function plain(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function getEnvValue(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value !== false && $value !== '') {
            return $value;
        }

        $envFile = __DIR__ . '/../../../../.env';
        if (!file_exists($envFile)) {
            return $default;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            [$name, $val] = array_pad(explode('=', $line, 2), 2, '');
            if (trim($name) === $key) {
                return trim($val);
            }
        }

        return $default;
    }
}
