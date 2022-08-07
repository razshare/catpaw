<?php
chdir(dirname(__FILE__));

function copyAll(string $srcDir, string $destDir) {
    $resourceDir = opendir($srcDir);
    if (!is_dir(dirname($destDir))) {
        @mkdir(dirname($destDir));
    }

    if (is_dir($srcDir) && !is_dir($destDir)) {
        @mkdir($destDir);
    }

    while (false !== ($file = readdir($resourceDir))) {
        if ('.' != $file && '..' != $file) {
            if (is_dir($srcDir."/".$file)) {
                copyAll($srcDir."/".$file, $destDir."/".$file);
            } else {
                copy($srcDir."/".$file, $destDir."/".$file);
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

function export(string $project, array $directories) {
    foreach ($directories as $directory) {
        deleteAll(realpath("../catpaw-$project/$directory"));
        mkdir("../catpaw-$project/$directory");
        copyAll(realpath("./$directory"), realpath("../catpaw-$project/$directory"));
    }
}

export("cli", ['bin','.vscode','.github']);
export("environment", ['bin','.vscode','.github']);
export("examples", ['bin','.vscode','.github']);
export("mysql", ['bin','.vscode','.github']);
export("mysql-dbms", ['bin','.vscode','.github']);
export("openapi", ['bin','.vscode','.github']);
export("optional", ['bin','.vscode','.github']);
export("queue", ['bin','.vscode','.github']);
export("raspberrypi", ['bin','.vscode','.github']);
export("starter", ['bin','.vscode','.github']);
export("store", ['bin','.vscode','.github']);
export("svelte-starter", ['bin','.github']);