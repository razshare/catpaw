<?php

use function Amp\call;

use function Amp\File\createDirectoryRecursively;

use function Amp\File\exists;
use function Amp\File\write;
use Amp\Http\Client\Request;

use Amp\Http\Client\Response;
use Amp\Promise;
use function Amp\Promise\all;

use CatPaw\Attributes\Option;

use CatPaw\Environment\Attributes\Environment;
use function CatPaw\execute;
use function CatPaw\isPhar;
use function CatPaw\Store\writable;
use Catpaw\Store\Writable;

use function CatPaw\tableFromArray;

use CatPaw\Utilities\AsciiTable;
use CatPaw\Utilities\Container;
use Condivisi\AMP\Service\HttpClientService;
use Condivisi\AMP\Service\LoggerService;
use Condivisi\AMP\Service\TokenService;
use Condivisi\ArrayTool;
use Condivisi\EspansioneStringa;
use Condivisi\Service\EMailParsingService;
use Ddeboer\Imap\MailboxInterface;
use Ddeboer\Imap\MessageInterface;
use Ddeboer\Imap\Server;

const CODICE_AGENZIA_PRINCIPALE = 'DEMO1';
const SFERANET_PROTOCOLLO       = 'https:';
const SFERANET_HOST             = 'sferanetts.partnersolution.it';
const SFERANET_ROOT             = '/CompFacileLogin';
const SFERANET_ROOT_ACCOUNTS    = '/CompFacileAccounts';

$LoggerService       = new LoggerService();
$HttpClientService   = new HttpClientService;
$TokenService        = new TokenService(Http: $HttpClientService, Logger: $LoggerService);
$EMailParsingService = new EMailParsingService();

Container::set(LoggerService::class, $LoggerService);
Container::set(HttpClientService::class, $HttpClientService);
Container::set(TokenService::class, $TokenService);
Container::set(EMailParsingService::class, $EMailParsingService);

class ConfigurazioneEmail {
    public function __construct(
        public int $port = -1,
        public string $host = '',
        public string $mailbox = '',
        public string $username = '',
        public string $password = '',
        public string $codice_agenzia = '',
        public string $agenzia_id = '',
        public string $agenzia_imap_idle_id = '',
        public bool $flag_annullato = false,
    ) {
    }
}

/**
 * @param  ConfigurazioneEmail              $configurazione
 * @return Promise<array<MailboxInterface>>
 */
function accedi(ConfigurazioneEmail $configurazione) {
    return call(function() use ($configurazione) {
        /** @var LoggerService */
        $logger = yield Container::create(LoggerService::class);

        $cartella_allegati = "./attachments/$configurazione->username/$configurazione->mailbox";
        if (!yield exists($cartella_allegati)) {
            yield createDirectoryRecursively($cartella_allegati);
        }

        /** @var array<MailboxInterface> */
        $elenco_server = [];

        foreach (preg_split('/,/', $configurazione->mailbox) as $nome_mailbox) {
            $nome_mailbox = trim($nome_mailbox);
            $server       = new Server(
                hostname: $configurazione->host,
                port: (string)$configurazione->port,
            );
            
            yield $logger->info("Accesso in corso a \"{$configurazione->username}/$nome_mailbox\"...");

            $connessione = $server->authenticate($configurazione->username, $configurazione->password);

            try {
                $mailbox = $connessione->getMailbox($nome_mailbox);
                yield $logger->info("Accesso a \"{$configurazione->username}/$nome_mailbox\" eseguito con successo");
                $elenco_server[] = $mailbox;
            } catch(Throwable) {
                yield $logger->error("Accesso a \"{$configurazione->username}/$nome_mailbox\" fallito");
                continue;
            }
        }

        return $elenco_server;
    });
}


function contenitori():Writable {
    global $messaggi;
    if ($messaggi) {
        return $messaggi;
    }
    $messaggi = writable([]);
    return $messaggi;
}

/** @return Promise<false|array<ConfigurazioneEmail>>  */
function configurazioni() {
    static $configurazioni = [];
    return call(function() use (&$configurazioni) {
        try {
            if ($configurazioni) {
                return $configurazioni;
            }
            /** @var TokenService */
            $stoken = yield Container::create(TokenService::class);
            /** @var false|string */
            $token = yield $stoken->findGlobalSferaNet();

            if (!$token) {
                return false;
            }

            $url = join([
                SFERANET_PROTOCOLLO,
                '//',
                SFERANET_HOST,
                SFERANET_ROOT,
                '/IMAPIdleConfig.json',
            ]);

            $richiesta = new Request($url);
            $richiesta->setHeader("x-http-method-override", "Find");
            $richiesta->setHeader("accesstoken", $token);
            $richiesta->setHeader("codiceagenzia", CODICE_AGENZIA_PRINCIPALE);
            /** @var HttpClientService */
            $http = yield Container::create(HttpClientService::class);
            /** @var Response $risposta */
            $risposta            = yield $http->request($richiesta);
            $testo               = yield $risposta->getBody()->buffer();
            $json                = json_decode($testo, true);
            $risultato           = $json['data'] ?? [];
            $dati_configurazioni = is_array($risultato) && !ArrayTool::isAssoc($risultato) ? $risultato : [$risultato];
            $configurazioni      = [];

            foreach ($dati_configurazioni as $dati_configurazione) {
                $configurazioni[] = new ConfigurazioneEmail(
                    port: $dati_configurazione['Port']                              ?? -1,
                    host: $dati_configurazione['Host']                              ?? '',
                    mailbox: $dati_configurazione['Mailbox']                        ?? '',
                    username: $dati_configurazione['Uname']                         ?? '',
                    password: $dati_configurazione['Pwd']                           ?? '',
                    codice_agenzia: $dati_configurazione['Adv']                     ?? '...',
                    agenzia_id: $dati_configurazione['AgenziaID']                   ?? '',
                    agenzia_imap_idle_id: $dati_configurazione['AgenziaIampIdleID'] ?? '',
                    flag_annullato: 1 == $dati_configurazione['FlagAnnullato'],
                );
            }
            return $configurazioni;
        } catch (Throwable) {
            return false;
        }
    });
}

class ContenitoreMessaggio {
    /**
     * @param  string                  $utente
     * @param  array<MessageInterface> $messaggi
     * @return void
     */
    public function __construct(
        public $utente,
        public $messaggi,
    ) {
    }
}

/**
 * @param  string  $codice_agenzia
 * @return Promise
 */
function scarica_messaggi(string $codice_agenzia) {
    return call(function() use ($codice_agenzia) {
        /** @var LoggerService */
        $logger = yield Container::create(LoggerService::class);
        /** @var array<MailboxInterface> */
        $elenco_mailbox = [];
        /** @var false|ConfigurazioneEmail */
        $configurazione = false;
        /** @var ConfigurazioneEmail */
        foreach ($configurazioni = yield configurazioni() as $configurazione_locale) {
            if (
                strtolower($configurazione_locale->codice_agenzia) 
                === strtolower($codice_agenzia)
            ) {
                $elenco_mailbox = yield accedi($configurazione_locale);
                $configurazione = $configurazione_locale;
                break;
            }
        }

        if (!$elenco_mailbox || !$configurazione) {
            /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
            if ($_ENV['dev']) {
                if (isset($_ENV['configurazione-dev'])) {
                    $configurazione_dev = $_ENV['configurazione-dev'];
                    /** @psalm-suppress InvalidArrayOffset */
                    $port = $configurazione_dev['Port'] ?? -1;
                    /** @psalm-suppress InvalidArrayOffset */
                    $host = $configurazione_dev['Host'] ?? '';
                    /** @psalm-suppress InvalidArrayOffset */
                    $mailbox = $configurazione_dev['Mailbox'] ?? '';
                    /** @psalm-suppress InvalidArrayOffset */
                    $username = $configurazione_dev['Uname'] ?? '';
                    /** @psalm-suppress InvalidArrayOffset */
                    $password = $configurazione_dev['Pwd'] ?? '';
                    /** @psalm-suppress InvalidArrayOffset */
                    $codice_agenzia = $configurazione_dev['Adv'] ?? '';
                    /** @psalm-suppress InvalidArrayOffset */
                    $agenzia_id = $configurazione_dev['AgenziaID'] ?? '';
                    /** @psalm-suppress InvalidArrayOffset */
                    $agenzia_imap_idle_id = $configurazione_dev['AgenziaIampIdleID'] ?? '';
                    /** @psalm-suppress InvalidArrayOffset */
                    $flag_annullato = (bool)($configurazione_dev['FlagAnnullato'] ?? false);

                    $configurazione = new ConfigurazioneEmail(
                        port: $port,
                        host: $host,
                        mailbox: $mailbox,
                        username: $username,
                        password: $password,
                        codice_agenzia: $codice_agenzia,
                        agenzia_id: $agenzia_id,
                        agenzia_imap_idle_id: $agenzia_imap_idle_id,
                        flag_annullato: $flag_annullato,
                    );

                    $elenco_mailbox = yield accedi($configurazione);
                }
            }
        }
    
        if (!$elenco_mailbox) {
            return false;
        }
    
        /** @var array<ContenitoreMessaggio> */
        $contenitori = [];

        foreach ($elenco_mailbox as $mailbox) {
            $contenitore = new ContenitoreMessaggio(
                utente: $configurazione->username,
                messaggi: []
            );
            $messaggi_trovati = $mailbox->getMessages(new \Ddeboer\Imap\Search\Flag\Unseen());
            for ($messaggi_trovati->rewind();$messaggi_trovati->valid();$messaggi_trovati->next()) {
                /** @var MessageInterface */
                $messaggio               = $messaggi_trovati->current();
                $contenitore->messaggi[] = $messaggio;
            }
            if ($contenitore->messaggi) {
                $contenitori[] = $contenitore;
            }
        }

        /** @var ConfigurazioneEmail $configurazione */
            
        if (!$contenitori) {
            yield $logger->info("Nessun messaggio trovato in $configurazione->username.");
        } else {
            $numero_contenitori = count($contenitori);
            yield $logger->info("$numero_contenitori messaggi trovati in $configurazione->username.");
        }

        $old = contenitori()->get();
        contenitori()->set([ ...$old, ...$contenitori]);
    });
}

function mostra_aiuto() {
    $tabella = AsciiTable::create();
    $tabella->add("opzione", "obbligatorietà", "valore predefinito", "descrizione");
    $tabella->add("--codice-agenzia", "obbligatoria", "", <<<TEXT
        Il codice dell'agenzia da interrogate.
        E' possibile indicare un elenco di codici separati da ",".
        Esempio: "demo1,demo2"
        TEXT);
    
    $tabella->add("--carica-in-file", "", "", <<<TEXT
        Se impostata il programma scriverà l'output nel file indicato in formato json.
        E' possibile espandere la stringa con la variabile "{{codice_agenzia}}".
        Esempio: "output-{{codice_agenzia}}.json
        TEXT);
    
    $tabella->add("--carica-in-http", "", "", <<<TEXT
        Se impostata il programma scriverà l'output nel url indicato (POST) in formato json.
        E' possibile espandere la stringa con la variabile "{{codice_agenzia}}".
        Esempio: "https://facilews3.partnersolution.it/Api/Rest/{{codice_agenzia}}/Cattura/Email
        TEXT);
    
    
    $tabella->add("--configurazioni", "", "", <<<TEXT
        Elenca le configurazioni disponibili.
        Le password sono mascherate con "***".
        TEXT);
    
    $tabella->add("--configurazioni-mostra-password", "", "", "Visualizza le password in --configurazioni");
    
    $tabella->add("--php", "", "php", "Indica il path dell'eseguibile php da usare per l'esecuzione parallela.");

    echo $tabella.PHP_EOL;
}

function mostra_configurazioni(bool $mostra_password) {
    return call(function() use ($mostra_password) {
        /** @var ConfigurazioneEmail */
        foreach (yield configurazioni() as $configurazione) {
            if (!$mostra_password) {
                $configurazione->password = '***';
            }
            
            $table = AsciiTable::create();
            $table->add("$configurazione->codice_agenzia ($configurazione->username)");
            $table->add(tableFromArray([
                "port"                 => $configurazione->port,
                "host"                 => $configurazione->host,
                "mailbox"              => $configurazione->mailbox,
                "username"             => $configurazione->username,
                "password"             => $mostra_password?$configurazione->password:'***',
                "codice_agenzia"       => $configurazione->codice_agenzia,
                "agenzia_id"           => $configurazione->agenzia_id,
                "agenzia_imap_idle_id" => $configurazione->agenzia_imap_idle_id,
                "flag_annullato"       => $configurazione->flag_annullato?'1':'0',
            ]));

            echo $table.PHP_EOL.PHP_EOL;
        }
    });
}

/**
 * @param  string                               $carica_in_http
 * @param  string                               $codice_agenzia
 * @return Generator<int, Promise, mixed, void>
 */
#[Environment]
function main(
    HttpClientService $http,
    TokenService $stoken,
    LoggerService $logger,
    EMailParsingService $parser,
    #[Option("--php")] string $php = 'php',
    #[Option("--carica-in-file")] string $carica_in_file,
    #[Option("--carica-in-http")] string $carica_in_http,
    #[Option("--codice-agenzia")] string $codice_agenzia,
    #[Option("--configurazioni")] bool $configurazioni,
    #[Option("--configurazioni-mostra-password")] bool $configurazioni_mostra_password,
    #[Option("-h")] bool $aiuto,
    #[Option("-l")] string $lib,
    #[Option("-e")] string $entry,
    #[Option("--segna-come-letto")] bool $segnaComeLetto,
) {
    global $argv;

    print_r([
        "--codice-agenzia" => $codice_agenzia,
        "--carica-in-http" => $carica_in_http,
        "--carica-in-file" => $carica_in_file,
    ]);

    if ($configurazioni) {
        yield mostra_configurazioni($configurazioni_mostra_password);
        die();
    }

    if ($aiuto) {
        mostra_aiuto();
        die();
    }

    if (!$codice_agenzia) {
        die("Specificare un codice agenzia con l'opzione --codice-agenzia".PHP_EOL);
    }

    if ('*' === $codice_agenzia) {
        /** @var false|array<ConfigurazioneEmail> */
        static $configurazioni = [];
        static $codici         = [];

        $configurazioni = yield configurazioni();

        if (!$configurazioni) {
            $configurazioni = [];
        }
        foreach ($configurazioni as $condigurazione) {
            $codici[] = $condigurazione->codice_agenzia;
        }
        $codice_agenzia = join(',', $codici);
    }

    $elenco_codici_agenzie = explode(',', $codice_agenzia);
    if (count($elenco_codici_agenzie) > 1) {
        /** @var array<Promise> */
        $processi = [];
        foreach ($elenco_codici_agenzie as $codice_agenzia) {
            $codice_agenzia = trim($codice_agenzia);
            
            if (isPhar()) {
                $file     = preg_replace('/^phar:\/\//', '', Phar::running());
                $launcher = "$php $file";
            } else {
                $file     = $argv[0];
                $launcher = "$php $file -l$lib -e$entry";
            }

            $codice_agenzia = join(' ', [
                "--codice-agenzia=\"$codice_agenzia\"",
                "--carica-in-file=\"$carica_in_file\"",
                "--carica-in-http=\"$carica_in_http\"",
                "--php=\"$php\"",
            ]);

            $istruzione = "$launcher $codice_agenzia";
            echo "x::$istruzione".PHP_EOL;
            $processi[] = call(function() use ($istruzione) {
                echo yield execute($istruzione, realpath('.'));
            });
        }
        yield all($processi);
        die();
    }

    die();

    $codice_agenzia = trim($codice_agenzia);
    $carica_in_file = EspansioneStringa::parametrizzata($carica_in_file, [
        "codice_agenzia" => $codice_agenzia
    ]);
    $carica_in_http = EspansioneStringa::parametrizzata($carica_in_http, [
        "codice_agenzia" => $codice_agenzia
    ]);

    if (!$carica_in_file) {
        $carica_in_file = $_ENV['carica-in-file'] ?? '';
    }

    if (!$carica_in_http) {
        $carica_in_http = $_ENV['carica-in-http'] ?? '';
    }

    if (!$token = yield $stoken->findFacileWS()) {
        $logger->error("Non è stato possibile ottenere il token facilews.");
        return;
    }
    contenitori()->subscribe(function($contenitori) use (
        $http,
        $carica_in_http,
        $carica_in_file,
        $token,
        $segnaComeLetto,
        $parser,
    ) {
        /** @var array<ContenitoreMessaggio> $contenitori */


        $fspayloads = [];

        foreach ($contenitori as $contenitore) {
            /** @var MessageInterface */
            foreach ($contenitore->messaggi as $messaggio) {
                $tos           = [];
                $email_diretta = false;
                foreach ($messaggio->getTo() as $to) {
                    $tos[] = [
                        "personal" => $to->getName()        ?? '',
                        "mailbox"  => $to->getMailbox()     ?? '',
                        "host"     => $to->getHostname()    ?? '',
                        "mail"     => $to->getAddress()     ?? '',
                        "full"     => $to->getFullAddress() ?? '',
                    ];

                    if ($to->getAddress() === $contenitore->utente) {
                        $email_diretta = true;
                    }
                }
                
                $from = [
                    "personal" => $messaggio->getFrom()?->getName()        ?? '',
                    "mailbox"  => $messaggio->getFrom()?->getMailbox()     ?? '',
                    "host"     => $messaggio->getFrom()?->getHostname()    ?? '',
                    "mail"     => $messaggio->getFrom()?->getAddress()     ?? '',
                    "full"     => $messaggio->getFrom()?->getFullAddress() ?? '',
                ];

                if (!$email_diretta) {
                    try {
                        $corpo_testuale = $messaggio->getBodyText()
                                        ?? \Soundasleep\Html2Text::convert($messaggio->getBodyHtml() ?? '');
                    } catch(Throwable) {
                        $corpo_testuale = $messaggio->getBodyHtml() ?? '';
                    }

                    if ($fromDaCorpo = $parser->findAddressFromString($corpo_testuale)) {
                        $from = [
                            "personal" => $fromDaCorpo->personal,
                            "mailbox"  => $fromDaCorpo->mailbox,
                            "host"     => $fromDaCorpo->host,
                            "mail"     => $fromDaCorpo->mail,
                            "full"     => $fromDaCorpo->full,
                        ];
                    }
                }

                $payload = [
                    "bcc"       => $messaggio->getBcc(),
                    "html"      => $messaggio->getBodyHtml()              ?? '',
                    "text"      => $messaggio->getBodyText()              ?? '',
                    "timestamp" => $messaggio->getDate()?->getTimestamp() ?? 0,
                    "from"      => $from,
                    "to"        => $tos,
                    "subject"   => $messaggio->getSubject() ?? '',
                ];

                if ($carica_in_file) {
                    $fspayloads[] = $payload;
                }

                if ($carica_in_http) {
                    try {
                        $richiesta = new Request($carica_in_http);
                        $richiesta->setMethod("POST");
                        $richiesta->setBody(json_encode($payload));
                        $richiesta->setHeader("Authorization", $token);
                        /** @var Response */
                        $risposta = yield $http->request($richiesta);
                        $stato    = $risposta->getStatus();
                        if ($stato >= 300) {
                            echo "Errore richiesta (stato $stato)".PHP_EOL;
                        // $messaggio->setFlag("\\Flagged");
                        } else {
                            if ($segnaComeLetto) {
                                // $messaggio->markAsSeen();
                            }
                            $messaggio->clearFlag("\\Flagged");
                        }
                    } catch(\Throwable $e) {
                        echo($e->getMessage()).PHP_EOL;
                        // $messaggio->setFlag("\\Flagged");
                    }
                } else if ($carica_in_file) {
                    if ($segnaComeLetto) {
                        // $messaggio->markAsSeen();
                    }
                }
            }
        }


        if ($fspayloads) {
            $cartella = dirname($carica_in_file);
            if (yield exists($cartella)) {
                yield createDirectoryRecursively($cartella);
            }
            yield write($carica_in_file, json_encode($fspayloads));
        }
    });

    yield scarica_messaggi($codice_agenzia);
}