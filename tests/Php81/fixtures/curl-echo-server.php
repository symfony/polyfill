<?php

$output = $_FILES;
foreach ($_FILES as $name => $file) {
    if (\is_string($file['tmp_name'] ?? null)) {
        unset($file['tmp_name']);
        $output[$name] = $file;
    }
}
echo json_encode($output);
