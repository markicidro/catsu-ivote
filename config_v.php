<?php
// Encryption key - KEEP THIS SECRET!
define('VOTE_ENCRYPTION_KEY', 'mark_rivera');

// Use in functions:
function encryptVote($data) {
    $key = VOTE_ENCRYPTION_KEY;
    // ... rest of code
}