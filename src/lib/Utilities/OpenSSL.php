<?php

namespace CatPaw\Utilities;

abstract class OpenSSL {
    private static string $countryNameRegex = '/^[A-z]{2}$/';
    private static string $emailRegex       = "/(?<=^)[A-z0-9!#$%&'*+-\\/=?^_`{|}~]*\\.?[A-z0-9!#$%&'*+-\\/=?^_`{|}~]*\\@[A-z0-9][A-z0-9]*[A-z0-9](\\.[A-z]+)?(?=$)/";

    public static function readLineSilent(string $prompt): ?string {
        return Strings::readLineSilent($prompt);
    }


    /**
     * Creates the contents of a PEM certificate. Save this to a file.pem and load it lader for your socket.
     * @param  array  $subject    subject an array containing fields required for the certificate.
     *                            Here's a template:
     *                            [
     *                            "countryName" => "__", //your organization country (2 letters, for example "US")
     *                            "stateOrProvinceName" => "_____", //your organization province or state
     *                            "localityName" => "_____", //your organization city or town
     *                            "organizationName" => "__________", //your organization name
     *                            "organizationalUnitName" => "_____", //name of your organization unit, for example "Web Development"
     *                            "commonName" => "__________.___", //your organization website address
     *                            "emailAddress" => "__________@__________.___" //your organization email address
     *                            ]
     * @param  string $filename   this will be the filename of the certificate.
     * @param  string $passphrase passphrase to be used to generate the certificate.
     * @param  int    $days       number of days the certificate will be valid for.
     * @return bool   true if cert was created, false otherwise.
     */
    public static function mkcert(array $subject, string $filename, string $passphrase = "", int $days = 365): bool {
        if (!preg_match(self::$countryNameRegex, $subject["countryName"])) {
            echo "[Country Name] must be a 2 letters country code. Try again.\n";
            return false;
        }
        if (trim($subject["stateOrProvinceName"]) === "") {
            echo "[State or Province name] must no be empty. Try again.\n";
            return false;
        }

        if (trim($subject["localityName"]) === "") {
            echo "[Locality Name] must no be empty. Try again.\n";
            return false;
        }

        if (trim($subject["organizationName"]) === "") {
            echo "[Organization Name] must no be empty. Try again.\n";
            return false;
        }

        if (trim($subject["commonName"]) === "") {
            echo "[Common Name] must no be empty. Try again.\n";
            return false;
        }

        if (trim($subject["emailAddress"]) !== "" && !preg_match(self::$emailRegex, $subject["emailAddress"])) {
            echo "Invalid email address in\n".print_r($subject, true);
            return false;
        }

        //create ssl cert for this script's life.

        //Create private key
        $privkey = openssl_pkey_new([
            "digest_alg"       => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);

        //Create and sign CSR
        $cert = openssl_csr_new($subject, $privkey);
        if (!$cert) {
            echo "Error during the creation of the csr file.\n";
            return false;
        }
        $cert = openssl_csr_sign($cert, null, $privkey, $days);

        //Generate PEM file
        $pem = [];
        openssl_x509_export($cert, $pem[0]);
        openssl_pkey_export($privkey, $pem[1], $passphrase);
        $pem = implode($pem);

        //Save PEM file
        file_put_contents($filename, $pem);
        chmod($filename, 0600);
        return true;
    }
}