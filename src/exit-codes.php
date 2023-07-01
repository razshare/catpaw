<?php
namespace CatPaw\ExistCodes;

/** Success */
const SUCCESS = 0;
/** Operation not permitted */
const OPERATION_NOT_PERMITTED = 1;
/** No such file or directory */
const NO_SUCH_FILE_OR_DIRECTORY = 2;
/** No such process */
const NO_SUCH_PROCESS = 3;
/** Interrupted system call */
const INTERRUPTED_SYSTEM_CALL = 4;
/** Input/output error */
const IO_ERROR = 5;
/** No such device or address */
const NO_SUCH_DEVICE_OR_ADDRESS = 6;
/** Argument list too long */
const ARGUMENT_LIST_TOO_LONG = 7;
/** Exec format error */
const EXEC_FORMAT_ERROR = 8;
/** Bad file descriptor */
const BAD_FILE_DESCRIPTOR = 9;
/** No child processes */
const NO_CHILD_PROCESSES = 10;
/** Resource temporarily unavailable */
const RESOURCE_TEMPORARILY_UNAVAILABLE = 11;
/** Cannot allocate memory */
const CANNOT_ALLOCATE_MEMORY = 12;
/** Permission denied */
const PERMISSION_DENIED = 13;
/** Bad address */
const BAD_ADDRESS = 14;
/** Block device required */
const BLOCK_DEVICE_REQUIRED = 15;
/** Device or resource busy */
const DEVICE_OR_RESOURCE_BUSY = 16;
/** File exists */
const FILE_EXISTS = 17;
/** Invalid cross-device link */
const INVALID_CROSS_DEVICE_LINK = 18;
/** No such device */
const NO_SUCH_DEVICE = 19;
/** Not a directory */
const NOT_A_DIRECTORY = 20;
/** Is a directory */
const IS_A_DIRECTORY = 21;
/** Invalid argument */
const INVALID_ARGUMENT = 22;
/** Too many open files in system */
const TOO_MANY_OPEN_FILES_IN_SYSTEM = 23;
/** Too many open files */
const TOO_MANY_OPEN_FILES = 24;
/** Inappropriate ioctl for device */
const INAPPROPRIATE_IOCTL_FOR_DEVICE = 25;
/** Text file busy */
const TEXT_FILE_BUSY = 26;
/** File too large */
const FILE_TOO_LARGE = 27;
/** No space left on device */
const NO_SPACE_LEFT_ON_DEVICE = 28;
/** Illegal seek */
const ILLEGAL_SEEK = 29;
/** Read-only file system */
const READ_ONLY_FILE_SYSTEM = 30;
/** Too many links */
const TOO_MANY_LINKS = 31;
/** Broken pipe */
const BROKEN_PIPE = 32;
/** Numerical argument out of domain */
const NUMERICAL_ARGUMENT_OUT_OF_DOMAIN = 33;
/** Numerical result out of range */
const NUMERICAL_RESULT_OUT_OF_RANGE = 34;
/** Resource deadlock avoided */
const RESOURCE_DEADLOCK_AVOIDED = 35;
/** File name too long */
const FILE_NAME_TOO_LONG = 36;
/** No locks available */
const NO_LOCKS_AVAILABLE = 37;
/** Function not implemented */
const FUNCTION_NOT_IMPLEMENTED = 38;
/** Directory not empty */
const DIRECTORY_NOT_EMPTY = 39;
/** Too many levels of symbolic links */
const TOO_MANY_LEVELS_OF_SYMBOLIC_LINKS = 40;
/** No message of desired type */
const NO_MESSAGE_OF_DESIRED_TYPE = 42;
/** Identifier removed */
const IDENTIFIER_REMOVED = 43;
/** Channel number out of range */
const CHANNEL_NUMBER_OUT_OF_RANGE = 44;
/** Level 2 not synchronized */
const LEVEL_2_NOT_SYNCHRONIZED = 45;
/** Level 3 halted */
const LEVEL_3_HALTED = 46;
/** Level 3 reset */
const LEVEL_3_RESET = 47;
/** Link number out of range */
const LINK_NUMBER_OUT_OF_RANGE = 48;
/** Protocol driver not attached */
const PROTOCOL_DRIVER_NOT_ATTACHED = 49;
/** No CSI structure available */
const NO_CSI_STRUCTURE_AVAILABLE = 50;
/** Level 2 halted */
const LEVEL_2_HALTED = 51;
/** Invalid exchange */
const INVALID_EXCHANGE = 52;
/** Invalid request descriptor */
const INVALID_REQUEST_DESCRIPTOR = 53;
/** Exchange full */
const EXCHANGE_FULL = 54;
/** No anode */
const NO_ANODE = 55;
/** Invalid request code */
const INVALID_REQUEST_CODE = 56;
/** Invalid slot */
const INVALID_SLOT = 57;
/** Bad font file format */
const BAD_FONT_FILE_FORMAT = 59;
/** Device not a stream */
const DEVICE_NOT_A_STREAM = 60;
/** No data available */
const NO_DATA_AVAILABLE = 61;
/** Timer expired */
const TIMER_EXPIRED = 62;
/** Out of streams resources */
const OUT_OF_STREAMS_RESOURCES = 63;
/** Machine is not on the network */
const MACHINE_IS_NOT_ON_THE_NETWORK = 64;
/** Package not installed */
const PACKAGE_NOT_INSTALLED = 65;
/** Object is remote */
const OBJECT_IS_REMOTE = 66;
/** Link has been severed */
const LINK_HAS_BEEN_SEVERED = 67;
/** Advertise error */
const ADVERTISE_ERROR = 68;
/** Srmount error */
const SRMOUNT_ERROR = 69;
/** Communication error on send */
const COMMUNICATION_ERROR_ON_SEND = 70;
/** Protocol error */
const PROTOCOL_ERROR = 71;
/** Multihop attempted */
const MULTIHOP_ATTEMPTED = 72;
/** RFS specific error */
const RFS_SPECIFIC_ERROR = 73;
/** Bad message */
const BAD_MESSAGE = 74;
/** Value too large for defined data type */
const VALUE_TOO_LARGE_FOR_DEFINED_DATA_TYPE = 75;
/** Name not unique on network */
const NAME_NOT_UNIQUE_ON_NETWORK = 76;
/** File descriptor in bad state */
const FILE_DESCRIPTOR_IN_BAD_STATE = 77;
/** Remote address changed */
const REMOTE_ADDRESS_CHANGED = 78;
/** Can not access a needed shared library */
const CAN_NOT_ACCESS_A_NEEDED_SHARED_LIBRARY = 79;
/** Accessing a corrupted shared library */
const ACCESSING_A_CORRUPTED_SHARED_LIBRARY = 80;
/** .lib section in a.out corrupted */
const LIB_SECTION_CORRUPTED = 81;
/** Attempting to link in too many shared libraries */
const ATTEMPTING_TO_LINK_IN_TOO_MANY_SHARED_LIBRARIES = 82;
/** Cannot exec a shared library directly */
const CANNOT_EXEC_A_SHARED_LIBRARY_DIRECTLY = 83;
/** Invalid or incomplete multibyte or wide character */
const INVALID_OR_INCOMPLETE_MULTIBYTE_OR_WIDE_CHARACTER = 84;
/** Interrupted system call should be restarted */
const INTERRUPTED_SYSTEM_CALL_SHOULD_BE_RESTARTED = 85;
/** Streams pipe error */
const STREAMS_PIPE_ERROR = 86;
/** Too many users */
const TOO_MANY_USERS = 87;
/** Socket operation on non-socket */
const SOCKET_OPERATION_ON_NON_SOCKET = 88;
/** Destination address required */
const DESTINATION_ADDRESS_REQUIRED = 89;
/** Message too long */
const MESSAGE_TOO_LONG = 90;
/** Protocol wrong type for socket */
const PROTOCOL_WRONG_TYPE_FOR_SOCKET = 91;
/** Protocol not available */
const PROTOCOL_NOT_AVAILABLE = 92;
/** Protocol not supported */
const PROTOCOL_NOT_SUPPORTED = 93;
/** Socket type not supported */
const SOCKET_TYPE_NOT_SUPPORTED = 94;
/** Operation not supported */
const OPERATION_NOT_SUPPORTED = 95;
/** Protocol family not supported */
const PROTOCOL_FAMILY_NOT_SUPPORTED = 96;
/** Address family not supported by protocol */
const ADDRESS_FAMILY_NOT_SUPPORTED_BY_PROTOCOL = 97;
/** Address already in use */
const ADDRESS_ALREADY_IN_USE = 98;
/** Cannot assign requested address */
const CANNOT_ASSIGN_REQUESTED_ADDRESS = 99;
/** Network is down */
const NETWORK_IS_DOWN = 100;
/** Network is unreachable */
const NETWORK_IS_UNREACHABLE = 101;
/** Network dropped connection on reset */
const NETWORK_DROPPED_CONNECTION_ON_RESET = 102;
/** Software caused connection abort */
const SOFTWARE_CAUSED_CONNECTION_ABORT = 103;
/** Connection reset by peer */
const CONNECTION_RESET_BY_PEER = 104;
/** No buffer space available */
const NO_BUFFER_SPACE_AVAILABLE = 105;
/** Transport endpoint is already connected */
const TRANSPORT_ENDPOINT_IS_ALREADY_CONNECTED = 106;
/** Transport endpoint is not connected */
const TRANSPORT_ENDPOINT_IS_NOT_CONNECTED = 107;
/** Cannot send after transport endpoint shutdown */
const CANNOT_SEND_AFTER_TRANSPORT_ENDPOINT_SHUTDOWN = 108;
/** Too many references */
const TOO_MANY_REFERENCES = 109;
/** Connection timed out */
const CONNECTION_TIMED_OUT = 110;
/** Connection refused */
const CONNECTION_REFUSED = 111;
/** Host is down */
const HOST_IS_DOWN = 112;
/** No route to host */
const NO_ROUTE_TO_HOST = 113;
/** Operation already in progress */
const OPERATION_ALREADY_IN_PROGRESS = 114;
/** Operation now in progress */
const OPERATION_NOW_IN_PROGRESS = 115;
/** Stale file handle */
const STALE_FILE_HANDLE = 116;
/** Structure needs cleaning */
const STRUCTURE_NEEDS_CLEANING = 117;
/** Not a XENIX named type file */
const NOT_A_XENIX_NAMED_TYPE_FILE = 118;
/** No XENIX semaphores available */
const NO_XENIX_SEMAPHORES_AVAILABLE = 119;
/** Is a named type file */
const IS_A_NAMED_TYPE_FILE = 120;
/** Remote I/O error */
const REMOTE_IO_ERROR = 121;
/** Disk quota exceeded */
const DISK_QUOTA_EXCEEDED = 122;
/** No medium found */
const NO_MEDIUM_FOUND = 123;
/** Operation canceled */
const OPERATION_CANCELED = 125;
/** Required key not available */
const REQUIRED_KEY_NOT_AVAILABLE = 126;
/** Key has expired */
const KEY_HAS_EXPIRED = 127;
/** Key has been revoked */
const KEY_HAS_BEEN_REVOKED = 128;
/** Key was rejected by service */
const KEY_WAS_REJECTED_BY_SERVICE = 129;
/** Owner died */
const OWNER_DIED = 130;
/** State not recoverable */
const STATE_NOT_RECOVERABLE = 131;
/** Operation not possible due to RF-kill */
const OPERATION_NOT_POSSIBLE_DUE_TO_RF_KILL = 132;
/** Memory page has hardware error */
const MEMORY_PAGE_HAS_HARDWARE_ERROR = 133;
