<?php
$json = file_get_contents('php://input');
$obj = json_decode($json);

if (!isset($obj->verification_token)) {
    exit(status_header(400));
}

if ($obj->verification_token === get_option('poetica_verification_token')) {
    status_header(200);
} else {
    status_header(400);
}
?>
