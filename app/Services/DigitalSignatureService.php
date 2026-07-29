<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DigitalSignatureService
{
    private const KEY_DIRECTORY = 'certs';

    private const PRIVATE_KEY_FILE = 'private.pem';

    private const PUBLIC_KEY_FILE = 'public.pem';

    public function ensureKeyPairExists(): void
    {
        $keyDir = storage_path('app/'.self::KEY_DIRECTORY);

        if (! is_dir($keyDir)) {
            mkdir($keyDir, 0755, true);
        }

        $privateKeyPath = $keyDir.'/'.self::PRIVATE_KEY_FILE;
        $publicKeyPath = $keyDir.'/'.self::PUBLIC_KEY_FILE;

        if (file_exists($privateKeyPath) && file_exists($publicKeyPath)) {
            $this->validateExistingKeys($privateKeyPath, $publicKeyPath);

            return;
        }

        $config = $this->opensslConfig();

        $keyPair = openssl_pkey_new($config);

        if ($keyPair === false) {
            // Windows often fails without OPENSSL_CONF — retry with bundled config path.
            $opensslCnf = $this->resolveOpenSslConfigPath();
            if ($opensslCnf) {
                $config['config'] = $opensslCnf;
                $keyPair = openssl_pkey_new($config);
            }
        }

        if ($keyPair === false) {
            $errors = [];
            while ($msg = openssl_error_string()) {
                $errors[] = $msg;
            }
            Log::error('RSA key generation failed', ['openssl_errors' => $errors]);
            throw new \RuntimeException(
                'Failed to generate RSA key pair. '.(implode(' | ', $errors) ?: 'Check OpenSSL on server.')
            );
        }

        $exportOk = openssl_pkey_export($keyPair, $privateKeyPem, null, $config);
        if (! $exportOk) {
            throw new \RuntimeException('Failed to export private key.');
        }

        file_put_contents($privateKeyPath, $privateKeyPem);
        @chmod($privateKeyPath, 0600);

        $keyDetails = openssl_pkey_get_details($keyPair);
        if (! is_array($keyDetails) || empty($keyDetails['key'])) {
            throw new \RuntimeException('Failed to read public key details.');
        }

        file_put_contents($publicKeyPath, $keyDetails['key']);
    }

    public function sign(string $data): string
    {
        $this->ensureKeyPairExists();

        $privateKeyPath = storage_path('app/'.self::KEY_DIRECTORY.'/'.self::PRIVATE_KEY_FILE);
        $privateKey = file_get_contents($privateKeyPath);

        if ($privateKey === false) {
            throw new \RuntimeException('Unable to read private key.');
        }

        $signature = '';
        $success = openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (! $success) {
            throw new \RuntimeException('Failed to sign data.');
        }

        return base64_encode($signature);
    }

    public function verify(string $data, string $base64Signature): bool
    {
        $publicKeyPath = storage_path('app/'.self::KEY_DIRECTORY.'/'.self::PUBLIC_KEY_FILE);

        if (! file_exists($publicKeyPath)) {
            return false;
        }

        $publicKey = file_get_contents($publicKeyPath);
        $signature = base64_decode($base64Signature, true);

        if ($signature === false) {
            return false;
        }

        return openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    public function getPublicKey(): string
    {
        $this->ensureKeyPairExists();

        $publicKeyPath = storage_path('app/'.self::KEY_DIRECTORY.'/'.self::PUBLIC_KEY_FILE);
        $publicKey = file_get_contents($publicKeyPath);

        if ($publicKey === false) {
            throw new \RuntimeException('Unable to read public key.');
        }

        return $publicKey;
    }

    public function getPublicKeyFingerprint(): string
    {
        return hash('sha256', $this->getPublicKey());
    }

    private function opensslConfig(): array
    {
        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'digest_alg' => 'sha256',
        ];

        $cnf = $this->resolveOpenSslConfigPath();
        if ($cnf) {
            $config['config'] = $cnf;
        }

        return $config;
    }

    private function resolveOpenSslConfigPath(): ?string
    {
        $candidates = array_filter([
            env('OPENSSL_CONF'),
            'C:\\Program Files\\Common Files\\SSL\\openssl.cnf',
            'C:\\Program Files\\OpenSSL-Win64\\bin\\openssl.cfg',
            PHP_BINDIR.'\\extras\\ssl\\openssl.cnf',
            dirname(PHP_BINARY).'\\extras\\ssl\\openssl.cnf',
        ]);

        foreach ($candidates as $path) {
            if (is_string($path) && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function validateExistingKeys(string $privateKeyPath, string $publicKeyPath): void
    {
        $private = openssl_pkey_get_private(file_get_contents($privateKeyPath));
        $public = openssl_pkey_get_public(file_get_contents($publicKeyPath));

        if ($private === false || $public === false) {
            @unlink($privateKeyPath);
            @unlink($publicKeyPath);
            $this->ensureKeyPairExists();
        }
    }
}
