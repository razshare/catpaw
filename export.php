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

function export(string $project, array $items) {
    foreach ($items as $item) {
        if (is_file(realpath("./$item"))) {
            if (!is_file("../catpaw-$project/$item")) {
                touch("../catpaw-$project/$item");
            }
            copy(realpath("./$item"), realpath("../catpaw-$project/$item"));
            continue;
        }
        deleteAll(realpath("../catpaw-$project/$item"));
        mkdir("../catpaw-$project/$item");
        copyAll(realpath("./$item"), realpath("../catpaw-$project/$item"));
    }
}

export("cli", ['bin','.vscode','.github','start']);
export("environment", ['bin','.vscode','.github','start']);
export("examples", ['bin','.vscode','.github','start']);
export("mysql", ['bin','.vscode','.github','start']);
export("mysql-dbms", ['bin','.vscode','.github','start']);
export("openapi", ['bin','.vscode','.github','start']);
export("optional", ['bin','.vscode','.github','start']);
export("queue", ['bin','.vscode','.github','start']);
export("raspberrypi", ['bin','.vscode','.github','start']);
export("starter", ['bin','.vscode','.github','start']);
export("store", ['bin','.vscode','.github','start']);
export("web", ['bin','.vscode','.github','start']);
export("cui", ['bin','.vscode','.github','start']);

export("svelte-starter", ['bin','.github','start']);