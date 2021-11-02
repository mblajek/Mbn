<?php
$githubZip = 'github.zip';

function updateMbn($githubZip, $query) {
    function getAllFilesZip($githubZip) {
        $zip = FileHelper::getZipFile($githubZip, true);
        if (!$zip) {
            return 'Failed to open zip';
        }

        $allFilesZip = [];
        $allContentsZip = [];
        $baseDirLength = strlen($zip->getNameIndex(0));
        for ($i = 1; $i < $zip->numFiles; $i++) {
            $fileName = substr($zip->getNameIndex($i), $baseDirLength);
            if ($fileName[strlen($fileName) - 1] === '/' || strpos($fileName, 'release/') === 0) {
                continue;
            }
            $fileContentsZip = $zip->getFromIndex($i);
            $allFilesZip [$fileName] = strlen($fileContentsZip) . '-' . hash('sha256', $fileContentsZip);
            $allContentsZip[$fileName] = $fileContentsZip;
        }
        return ['files' => $allFilesZip, 'contents' => $allContentsZip];
    }

    $githubZipContents = file_get_contents(env::githubZip);

    FileHelper::putFile($githubZip, $githubZipContents, true, true);
    $allFilesAndContentsZip = getAllFilesZip($githubZip);
    if (is_string($allFilesAndContentsZip)) {
        return $allFilesAndContentsZip;
    }

    $allFiles = FileHelper::getAllFiles();
    $allFilesZip = $allFilesAndContentsZip['files'];
    $allContentsZip = $allFilesAndContentsZip['contents'];

    $commonFiles = [];
    $changedFiles = [];

    foreach (array_unique(array_merge(array_keys($allFiles), array_keys($allFilesZip))) as $file) {
        $dirFile = isset($allFiles[$file]) ? $allFiles[$file] : null;
        $zipFile = isset($allFilesZip[$file]) ? $allFilesZip[$file] : null;
        if ($dirFile === $zipFile) {
            $commonFiles[] = $file;
        } else {
            $changedFiles[$file] = ['dir' => $dirFile, 'zip' => $zipFile];
        }
    }
    $updateInfo = implode(', ', $commonFiles) . PHP_EOL . print_r($changedFiles, true);
    $updateHash = hash('sha256', $updateInfo);

    if ($query !== $updateHash) {
        return $updateInfo . $updateHash;
    }
    foreach ($changedFiles as $fileName => $changedFile) {
        if ($changedFile['zip'] !== null) {
            FileHelper::putFile($fileName, $allContentsZip[$fileName]);
        } else {
            FileHelper::deleteFile($fileName);
        }
    }

    return $updateInfo . 'updated';
}

echo updateMbn($githubZip, isset($query) ? $query : '');
