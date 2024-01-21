<?php
namespace CatPaw\Web;

// MIME TYPES
const __APPLICATION_JSON = 'application/json';
const __APPLICATION_XML  = 'application/xml';
const __TEXT_PLAIN       = 'text/plain';
const __TEXT_HTML        = 'text/html';

// HTTP STATUS CODE
const __CONTINUE            = 100;
const __SWITCHING_PROTOCOLS = 101;
const __PROCESSING          = 102;
const __EARLY_HINTS         = 103;

const __OK                            = 200;
const __CREATED                       = 201;
const __ACCEPTED                      = 202;
const __NON_AUTHORITATIVE_INFORMATION = 203;
const __NO_CONTENT                    = 204;
const __RESET_CONTENT                 = 205;
const __PARTIAL_CONTENT               = 206;
const __MULTI_STATUS                  = 207;
const __ALREADY_REPORTED              = 208;
const __IM_USED                       = 226;

const __MULTIPLE_CHOICES   = 300;
const __MOVED_PERMANENTLY  = 301;
const __FOUND              = 302;
const __SEE_OTHER          = 303;
const __NOT_MODIFIED       = 304;
const __USE_PROXY          = 305;
const __TEMPORARY_REDIRECT = 307;
const __PERMANENT_REDIRECT = 308;

const __BAD_REQUEST                     = 400;
const __UNAUTHORIZED                    = 401;
const __PAYMENT_REQUIRED                = 402;
const __FORBIDDEN                       = 403;
const __NOT_FOUND                       = 404;
const __METHOD_NOT_ALLOWED              = 405;
const __NOT_ACCEPTABLE                  = 406;
const __PROXY_AUTHENTICATION_REQUIRED   = 407;
const __REQUEST_TIMEOUT                 = 408;
const __CONFLICT                        = 409;
const __GONE                            = 410;
const __LENGTH_REQUIRED                 = 411;
const __PRECONDITION_FAILED             = 412;
const __PAYLOAD_TOO_LARGE               = 413;
const __URI_TOO_LONG                    = 414;
const __UNSUPPORTED_MEDIA_TYPE          = 415;
const __RANGE_NOT_SATISFIABLE           = 416;
const __EXPECTATION_FAILED              = 417;
const __MISDIRECTED_REQUEST             = 421;
const __UNPROCESSABLE_ENTITY            = 422;
const __LOCKED                          = 423;
const __FAILED_DEPENDENCY               = 424;
const __UPGRADE_REQUIRED                = 426;
const __PRECONDITION_REQUIRED           = 428;
const __TOO_MANY_REQUESTS               = 429;
const __REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
const __UNAVAILABLE_FOR_LEGAL_REASONS   = 451;

const __INTERNAL_SERVER_ERROR           = 500;
const __NOT_IMPLEMENTED                 = 501;
const __BAD_GATEWAY                     = 502;
const __SERVICE_UNAVAILABLE             = 503;
const __GATEWAY_TIMEOUT                 = 504;
const __HTTP_VERSION_NOT_SUPPORTED      = 505;
const __VARIANT_ALSO_NEGOTIATES         = 506;
const __INSUFFICIENT_STORAGE            = 507;
const __LOOP_DETECTED                   = 508;
const __NOT_EXTENDED                    = 510;
const __NETWORK_AUTHENTICATION_REQUIRED = 511;
