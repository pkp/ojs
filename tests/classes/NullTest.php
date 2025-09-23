<?php

/**
 * @file tests/classes/NullTest.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Empty test to satisfy PHPUnit, which doesn't like an empty test directory.
 */

namespace APP\tests\classes;

use PKP\tests\PKPTestCase;

class NullTest extends PKPTestCase
{
    public function testTrue()
    {
        $this->assertTrue(true);
    }
}
