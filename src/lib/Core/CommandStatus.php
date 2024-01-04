<?php
namespace CatPaw;

readonly class CommandStatus {
    /** Success */
    public const SUCCESS = 0;
    /** Operation not permitted */
    public const OPERATION_NOT_PERMITTED = 1;
    /** No such file or directory */
    public const NO_SUCH_FILE_OR_DIRECTORY = 2;
    /** No such process */
    public const NO_SUCH_PROCESS = 3;
    /** Interrupted system call */
    public const INTERRUPTED_SYSTEM_CALL = 4;
    /** Input/output error */
    public const IO_ERROR = 5;
    /** No such device or address */
    public const NO_SUCH_DEVICE_OR_ADDRESS = 6;
    /** Argument list too long */
    public const ARGUMENT_LIST_TOO_LONG = 7;
    /** Exec format error */
    public const EXEC_FORMAT_ERROR = 8;
    /** Bad file descriptor */
    public const BAD_FILE_DESCRIPTOR = 9;
    /** No child processes */
    public const NO_CHILD_PROCESSES = 10;
    /** Resource temporarily unavailable */
    public const RESOURCE_TEMPORARILY_UNAVAILABLE = 11;
    /** Cannot allocate memory */
    public const CANNOT_ALLOCATE_MEMORY = 12;
    /** Permission denied */
    public const PERMISSION_DENIED = 13;
    /** Bad address */
    public const BAD_ADDRESS = 14;
    /** Block device required */
    public const BLOCK_DEVICE_REQUIRED = 15;
    /** Device or resource busy */
    public const DEVICE_OR_RESOURCE_BUSY = 16;
    /** File exists */
    public const FILE_EXISTS = 17;
    /** Invalid cross-device link */
    public const INVALID_CROSS_DEVICE_LINK = 18;
    /** No such device */
    public const NO_SUCH_DEVICE = 19;
    /** Not a directory */
    public const NOT_A_DIRECTORY = 20;
    /** Is a directory */
    public const IS_A_DIRECTORY = 21;
    /** Invalid argument */
    public const INVALID_ARGUMENT = 22;
    /** Too many open files in system */
    public const TOO_MANY_OPEN_FILES_IN_SYSTEM = 23;
    /** Too many open files */
    public const TOO_MANY_OPEN_FILES = 24;
    /** Inappropriate ioctl for device */
    public const INAPPROPRIATE_IOCTL_FOR_DEVICE = 25;
    /** Text file busy */
    public const TEXT_FILE_BUSY = 26;
    /** File too large */
    public const FILE_TOO_LARGE = 27;
    /** No space left on device */
    public const NO_SPACE_LEFT_ON_DEVICE = 28;
    /** Illegal seek */
    public const ILLEGAL_SEEK = 29;
    /** Read-only file system */
    public const READ_ONLY_FILE_SYSTEM = 30;
    /** Too many links */
    public const TOO_MANY_LINKS = 31;
    /** Broken pipe */
    public const BROKEN_PIPE = 32;
    /** Numerical argument out of domain */
    public const NUMERICAL_ARGUMENT_OUT_OF_DOMAIN = 33;
    /** Numerical result out of range */
    public const NUMERICAL_RESULT_OUT_OF_RANGE = 34;
    /** Resource deadlock avoided */
    public const RESOURCE_DEADLOCK_AVOIDED = 35;
    /** File name too long */
    public const FILE_NAME_TOO_LONG = 36;
    /** No locks available */
    public const NO_LOCKS_AVAILABLE = 37;
    /** Function not implemented */
    public const FUNCTION_NOT_IMPLEMENTED = 38;
    /** Directory not empty */
    public const DIRECTORY_NOT_EMPTY = 39;
    /** Too many levels of symbolic links */
    public const TOO_MANY_LEVELS_OF_SYMBOLIC_LINKS = 40;
    /** No message of desired type */
    public const NO_MESSAGE_OF_DESIRED_TYPE = 42;
    /** Identifier removed */
    public const IDENTIFIER_REMOVED = 43;
    /** Channel number out of range */
    public const CHANNEL_NUMBER_OUT_OF_RANGE = 44;
    /** Level 2 not synchronized */
    public const LEVEL_2_NOT_SYNCHRONIZED = 45;
    /** Level 3 halted */
    public const LEVEL_3_HALTED = 46;
    /** Level 3 reset */
    public const LEVEL_3_RESET = 47;
    /** Link number out of range */
    public const LINK_NUMBER_OUT_OF_RANGE = 48;
    /** Protocol driver not attached */
    public const PROTOCOL_DRIVER_NOT_ATTACHED = 49;
    /** No CSI structure available */
    public const NO_CSI_STRUCTURE_AVAILABLE = 50;
    /** Level 2 halted */
    public const LEVEL_2_HALTED = 51;
    /** Invalid exchange */
    public const INVALID_EXCHANGE = 52;
    /** Invalid request descriptor */
    public const INVALID_REQUEST_DESCRIPTOR = 53;
    /** Exchange full */
    public const EXCHANGE_FULL = 54;
    /** No anode */
    public const NO_ANODE = 55;
    /** Invalid request code */
    public const INVALID_REQUEST_CODE = 56;
    /** Invalid slot */
    public const INVALID_SLOT = 57;
    /** Bad font file format */
    public const BAD_FONT_FILE_FORMAT = 59;
    /** Device not a stream */
    public const DEVICE_NOT_A_STREAM = 60;
    /** No data available */
    public const NO_DATA_AVAILABLE = 61;
    /** Timer expired */
    public const TIMER_EXPIRED = 62;
    /** Out of streams resources */
    public const OUT_OF_STREAMS_RESOURCES = 63;
    /** Machine is not on the network */
    public const MACHINE_IS_NOT_ON_THE_NETWORK = 64;
    /** Package not installed */
    public const PACKAGE_NOT_INSTALLED = 65;
    /** Object is remote */
    public const OBJECT_IS_REMOTE = 66;
    /** Link has been severed */
    public const LINK_HAS_BEEN_SEVERED = 67;
    /** Advertise error */
    public const ADVERTISE_ERROR = 68;
    /** Srmount error */
    public const SRMOUNT_ERROR = 69;
    /** Communication error on send */
    public const COMMUNICATION_ERROR_ON_SEND = 70;
    /** Protocol error */
    public const PROTOCOL_ERROR = 71;
    /** Multihop attempted */
    public const MULTIHOP_ATTEMPTED = 72;
    /** RFS specific error */
    public const RFS_SPECIFIC_ERROR = 73;
    /** Bad message */
    public const BAD_MESSAGE = 74;
    /** Value too large for defined data type */
    public const VALUE_TOO_LARGE_FOR_DEFINED_DATA_TYPE = 75;
    /** Name not unique on network */
    public const NAME_NOT_UNIQUE_ON_NETWORK = 76;
    /** File descriptor in bad state */
    public const FILE_DESCRIPTOR_IN_BAD_STATE = 77;
    /** Remote address changed */
    public const REMOTE_ADDRESS_CHANGED = 78;
    /** Can not access a needed shared library */
    public const CAN_NOT_ACCESS_A_NEEDED_SHARED_LIBRARY = 79;
    /** Accessing a corrupted shared library */
    public const ACCESSING_A_CORRUPTED_SHARED_LIBRARY = 80;
    /** .lib section in a.out corrupted */
    public const LIB_SECTION_CORRUPTED = 81;
    /** Attempting to link in too many shared libraries */
    public const ATTEMPTING_TO_LINK_IN_TOO_MANY_SHARED_LIBRARIES = 82;
    /** Cannot exec a shared library directly */
    public const CANNOT_EXEC_A_SHARED_LIBRARY_DIRECTLY = 83;
    /** Invalid or incomplete multibyte or wide character */
    public const INVALID_OR_INCOMPLETE_MULTIBYTE_OR_WIDE_CHARACTER = 84;
    /** Interrupted system call should be restarted */
    public const INTERRUPTED_SYSTEM_CALL_SHOULD_BE_RESTARTED = 85;
    /** Streams pipe error */
    public const STREAMS_PIPE_ERROR = 86;
    /** Too many users */
    public const TOO_MANY_USERS = 87;
    /** Socket operation on non-socket */
    public const SOCKET_OPERATION_ON_NON_SOCKET = 88;
    /** Destination address required */
    public const DESTINATION_ADDRESS_REQUIRED = 89;
    /** Message too long */
    public const MESSAGE_TOO_LONG = 90;
    /** Protocol wrong type for socket */
    public const PROTOCOL_WRONG_TYPE_FOR_SOCKET = 91;
    /** Protocol not available */
    public const PROTOCOL_NOT_AVAILABLE = 92;
    /** Protocol not supported */
    public const PROTOCOL_NOT_SUPPORTED = 93;
    /** Socket type not supported */
    public const SOCKET_TYPE_NOT_SUPPORTED = 94;
    /** Operation not supported */
    public const OPERATION_NOT_SUPPORTED = 95;
    /** Protocol family not supported */
    public const PROTOCOL_FAMILY_NOT_SUPPORTED = 96;
    /** Address family not supported by protocol */
    public const ADDRESS_FAMILY_NOT_SUPPORTED_BY_PROTOCOL = 97;
    /** Address already in use */
    public const ADDRESS_ALREADY_IN_USE = 98;
    /** Cannot assign requested address */
    public const CANNOT_ASSIGN_REQUESTED_ADDRESS = 99;
    /** Network is down */
    public const NETWORK_IS_DOWN = 100;
    /** Network is unreachable */
    public const NETWORK_IS_UNREACHABLE = 101;
    /** Network dropped connection on reset */
    public const NETWORK_DROPPED_CONNECTION_ON_RESET = 102;
    /** Software caused connection abort */
    public const SOFTWARE_CAUSED_CONNECTION_ABORT = 103;
    /** Connection reset by peer */
    public const CONNECTION_RESET_BY_PEER = 104;
    /** No buffer space available */
    public const NO_BUFFER_SPACE_AVAILABLE = 105;
    /** Transport endpoint is already connected */
    public const TRANSPORT_ENDPOINT_IS_ALREADY_CONNECTED = 106;
    /** Transport endpoint is not connected */
    public const TRANSPORT_ENDPOINT_IS_NOT_CONNECTED = 107;
    /** Cannot send after transport endpoint shutdown */
    public const CANNOT_SEND_AFTER_TRANSPORT_ENDPOINT_SHUTDOWN = 108;
    /** Too many references */
    public const TOO_MANY_REFERENCES = 109;
    /** Connection timed out */
    public const CONNECTION_TIMED_OUT = 110;
    /** Connection refused */
    public const CONNECTION_REFUSED = 111;
    /** Host is down */
    public const HOST_IS_DOWN = 112;
    /** No route to host */
    public const NO_ROUTE_TO_HOST = 113;
    /** Operation already in progress */
    public const OPERATION_ALREADY_IN_PROGRESS = 114;
    /** Operation now in progress */
    public const OPERATION_NOW_IN_PROGRESS = 115;
    /** Stale file handle */
    public const STALE_FILE_HANDLE = 116;
    /** Structure needs cleaning */
    public const STRUCTURE_NEEDS_CLEANING = 117;
    /** Not a XENIX named type file */
    public const NOT_A_XENIX_NAMED_TYPE_FILE = 118;
    /** No XENIX semaphores available */
    public const NO_XENIX_SEMAPHORES_AVAILABLE = 119;
    /** Is a named type file */
    public const IS_A_NAMED_TYPE_FILE = 120;
    /** Remote I/O error */
    public const REMOTE_IO_ERROR = 121;
    /** Disk quota exceeded */
    public const DISK_QUOTA_EXCEEDED = 122;
    /** No medium found */
    public const NO_MEDIUM_FOUND = 123;
    /** Operation canceled */
    public const OPERATION_CANCELED = 125;
    /** Required key not available */
    public const REQUIRED_KEY_NOT_AVAILABLE = 126;
    /** Key has expired */
    public const KEY_HAS_EXPIRED = 127;
    /** Key has been revoked */
    public const KEY_HAS_BEEN_REVOKED = 128;
    /** Key was rejected by service */
    public const KEY_WAS_REJECTED_BY_SERVICE = 129;
    /** Owner died */
    public const OWNER_DIED = 130;
    /** State not recoverable */
    public const STATE_NOT_RECOVERABLE = 131;
    /** Operation not possible due to RF-kill */
    public const OPERATION_NOT_POSSIBLE_DUE_TO_RF_KILL = 132;
    /** Memory page has hardware error */
    public const MEMORY_PAGE_HAS_HARDWARE_ERROR = 133;

}