<?php

switch ($op) {
    case 'verify':
    case 'authorizeOrcid':
    case 'about':
        return new APP\pages\orcid\OrcidHandler();
}
