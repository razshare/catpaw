<?php
chdir(dirname(__FILE__));

function copyAll(string $srcDir, string $destDir) {
    $resourceDir = opendir($srcDir);
    @mkdir(dirname($destDir));

    while (false !== ($File = readdir($resourceDir))) {
        if ('.' != $File && '..' != $File) {
            if (is_dir($srcDir."/".$File)) {
                copyAll($srcDir."/".$File, $destDir."/".$File);
            } else {
                copy($srcDir."/".$File, $destDir."/".$File);
            }
        }
    }
    closedir($resourceDir);
}


function deleteAll(string $dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ("." != $object && ".." != $object) {
                if (is_dir($dir.DIRECTORY_SEPARATOR.$object) && !is_link($dir."/".$object)) {
                    deleteAll($dir.DIRECTORY_SEPARATOR.$object);
                } else {
                    unlink($dir.DIRECTORY_SEPARATOR.$object);
                }
            }
        }
        rmdir($dir);
    }
}

function export(string $project) {
    deleteAll(realpath("../catpaw-$project/bin"));
    mkdir("../catpaw-$project/bin");
    copyAll(realpath("./bin"), realpath("../catpaw-$project/bin"));
}

export("cli");
export("environment");
export("examples");
export("mysql");
export("mysql-dbms");
export("openapi");
export("optional");
export("queue");
export("raspberrypi");
export("starter");
export("store");
export("svelte-starter");
